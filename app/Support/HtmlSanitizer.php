<?php

namespace App\Support;

use DOMAttr;
use DOMDocument;
use DOMElement;
use DOMNode;

class HtmlSanitizer
{
    /**
     * Daftar tag HTML aman yang diizinkan untuk konten pengumuman rich text.
     *
     * @var list<string>
     */
    private const array ALLOWED_TAGS = [
        'p',
        'br',
        'strong',
        'b',
        'em',
        'i',
        'u',
        's',
        'del',
        'ins',
        'sub',
        'sup',
        'h1',
        'h2',
        'h3',
        'h4',
        'h5',
        'h6',
        'ul',
        'ol',
        'li',
        'blockquote',
        'pre',
        'code',
        'hr',
        'a',
        'span',
        'div',
        'table',
        'thead',
        'tbody',
        'tfoot',
        'tr',
        'th',
        'td',
        'caption',
        'colgroup',
        'col',
    ];

    /**
     * Tag berbahaya yang harus dihapus beserta seluruh isinya.
     *
     * @var list<string>
     */
    private const array FORBIDDEN_SUBTREE_TAGS = [
        'script',
        'style',
        'iframe',
        'object',
        'embed',
        'applet',
        'svg',
        'math',
        'form',
        'input',
        'button',
        'textarea',
        'select',
        'meta',
        'link',
        'base',
    ];

    /**
     * Properti CSS inline aman yang diizinkan pada atribut style.
     *
     * @var list<string>
     */
    private const array ALLOWED_STYLE_PROPERTIES = [
        'text-align',
        'color',
        'background-color',
        'font-size',
        'font-weight',
        'font-style',
        'text-decoration',
        'text-decoration-line',
        'padding',
        'padding-left',
        'margin-left',
        'list-style-type',
        'width',
        'border',
        'border-width',
        'border-style',
        'border-color',
        'border-collapse',
        'border-spacing',
    ];

    /**
     * Skema URL aman untuk tautan pengumuman.
     *
     * @var list<string>
     */
    private const array ALLOWED_URL_SCHEMES = [
        'http',
        'https',
        'mailto',
        'tel',
    ];

    /**
     * Membersihkan HTML rich text agar hanya menyimpan tag dan atribut aman (anti-XSS).
     */
    public static function sanitize(?string $content): string
    {
        $trimmed = trim((string) ($content ?? ''));

        if ($trimmed === '') {
            return '';
        }

        if (! preg_match('/<[^>]+>/', $trimmed)) {
            return $trimmed;
        }

        $previousUseInternalErrors = libxml_use_internal_errors(true);

        try {
            $dom = new DOMDocument('1.0', 'UTF-8');
            $dom->loadHTML(
                '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body><div>'.$trimmed.'</div></body></html>',
                LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
            );

            $body = $dom->getElementsByTagName('body')->item(0);
            $root = $body?->firstChild;

            if (! $root instanceof DOMElement) {
                return trim(strip_tags($trimmed));
            }

            self::sanitizeChildren($root);

            $result = '';

            foreach ($root->childNodes as $child) {
                $result .= $dom->saveHTML($child);
            }

            return trim($result);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousUseInternalErrors);
        }
    }

    /**
     * Mengekstrak teks polos yang bersih dari tag maupun entitas HTML seperti &nbsp;.
     */
    public static function extractPlainText(?string $content): string
    {
        $sanitized = self::sanitize($content);

        if ($sanitized === '') {
            return '';
        }

        $withBlockSpacing = (string) preg_replace(
            '/<\/?(?:p|div|li|ul|ol|h[1-6]|blockquote|tr|td|th|table|br|hr)\b[^>]*>/i',
            ' ',
            $sanitized
        );
        $stripped = strip_tags($withBlockSpacing);
        $decoded = html_entity_decode($stripped, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $normalizedSpaces = str_replace("\u{00A0}", ' ', $decoded);

        return trim((string) preg_replace('/\s+/u', ' ', $normalizedSpaces));
    }

    /**
     * Mengubah konten pengumuman (teks polos maupun HTML editor) menjadi HTML aman siap tampil.
     */
    public static function toSafeDisplayHtml(?string $content): string
    {
        $trimmed = trim((string) ($content ?? ''));

        if ($trimmed === '') {
            return '-';
        }

        if (! preg_match('/<[^>]+>/', $trimmed)) {
            return nl2br(e($trimmed));
        }

        $sanitized = self::sanitize($trimmed);

        return self::extractPlainText($sanitized) === '' ? '-' : $sanitized;
    }

    /**
     * Menyiapkan konten awal untuk rich text editor (mengonversi teks polos menjadi paragraf HTML).
     */
    public static function toEditorHtml(?string $content): string
    {
        $trimmed = trim((string) ($content ?? ''));

        if ($trimmed === '') {
            return '';
        }

        if (preg_match('/<[^>]+>/', $trimmed)) {
            return self::sanitize($trimmed);
        }

        $normalized = str_replace(["\r\n", "\r"], "\n", $trimmed);
        $paragraphs = preg_split('/\n{2,}/', $normalized) ?: [$normalized];

        return implode('', array_map(
            static fn (string $paragraph): string => '<p>'.nl2br(e(trim($paragraph)), false).'</p>',
            array_filter($paragraphs, static fn (string $paragraph): bool => trim($paragraph) !== '')
        ));
    }

    private static function sanitizeChildren(DOMNode $parent): void
    {
        $children = [];

        foreach ($parent->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $child) {
            if (! $child instanceof DOMElement) {
                if ($child->nodeType === XML_COMMENT_NODE) {
                    $parent->removeChild($child);
                }

                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, self::FORBIDDEN_SUBTREE_TAGS, true)) {
                $parent->removeChild($child);

                continue;
            }

            self::sanitizeChildren($child);

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                while ($child->firstChild !== null) {
                    $parent->insertBefore($child->firstChild, $child);
                }

                $parent->removeChild($child);

                continue;
            }

            self::sanitizeAttributes($child, $tag);
        }
    }

    private static function sanitizeAttributes(DOMElement $element, string $tag): void
    {
        $attributes = [];

        foreach ($element->attributes as $attribute) {
            if ($attribute instanceof DOMAttr) {
                $attributes[] = $attribute->name;
            }
        }

        foreach ($attributes as $attrName) {
            $lowerAttr = strtolower($attrName);
            $rawValue = $element->getAttribute($attrName);

            if (str_starts_with($lowerAttr, 'on')) {
                $element->removeAttribute($attrName);

                continue;
            }

            if ($lowerAttr === 'style') {
                $cleanStyle = self::sanitizeStyle($rawValue);

                if ($cleanStyle === '') {
                    $element->removeAttribute($attrName);
                } else {
                    $element->setAttribute('style', $cleanStyle);
                }

                continue;
            }

            if ($tag === 'a' && self::handleAnchorAttribute($element, $lowerAttr, $attrName, $rawValue)) {
                continue;
            }

            if (in_array($tag, ['td', 'th'], true) && in_array($lowerAttr, ['colspan', 'rowspan', 'align', 'valign'], true)) {
                continue;
            }

            if ($tag === 'table' && in_array($lowerAttr, ['border', 'cellpadding', 'cellspacing'], true)) {
                continue;
            }

            $element->removeAttribute($attrName);
        }

        if ($tag === 'a' && strtolower($element->getAttribute('target')) === '_blank') {
            $element->setAttribute('rel', 'noopener noreferrer');
        }
    }

    private static function handleAnchorAttribute(DOMElement $element, string $lowerAttr, string $attrName, string $rawValue): bool
    {
        if ($lowerAttr === 'href') {
            if (! self::isSafeUrl($rawValue)) {
                $element->removeAttribute($attrName);
            }

            return true;
        }

        if ($lowerAttr === 'target') {
            if (! in_array($rawValue, ['_blank', '_self'], true)) {
                $element->removeAttribute($attrName);
            }

            return true;
        }

        return in_array($lowerAttr, ['title', 'rel'], true);
    }

    private static function isSafeUrl(string $url): bool
    {
        $trimmed = trim($url);

        if ($trimmed === '') {
            return false;
        }

        $normalized = preg_replace('/[\x00-\x20\s]+/', '', $trimmed) ?? '';

        if ($normalized === '') {
            return false;
        }

        if (str_starts_with($normalized, '/') || str_starts_with($normalized, '#')) {
            return ! str_starts_with($normalized, '//');
        }

        $scheme = parse_url($normalized, PHP_URL_SCHEME);

        if ($scheme === false) {
            return false;
        }

        if ($scheme === null) {
            return ! str_contains($normalized, ':');
        }

        return in_array(strtolower($scheme), self::ALLOWED_URL_SCHEMES, true);
    }

    private static function sanitizeStyle(string $style): string
    {
        $declarations = explode(';', $style);
        $safeDeclarations = [];

        foreach ($declarations as $declaration) {
            $parts = explode(':', $declaration, 2);

            if (count($parts) !== 2) {
                continue;
            }

            $property = strtolower(trim($parts[0]));
            $value = trim($parts[1]);

            if ($value === '' || ! in_array($property, self::ALLOWED_STYLE_PROPERTIES, true)) {
                continue;
            }

            if (preg_match('/url\s*\(|expression\s*\(|javascript:|vbscript:|@import|-moz-binding/i', $value)) {
                continue;
            }

            if (! preg_match('/^[a-zA-Z0-9\s#%(),.\-]+$/', $value)) {
                continue;
            }

            $safeDeclarations[] = $property.': '.$value;
        }

        return implode('; ', $safeDeclarations);
    }
}
