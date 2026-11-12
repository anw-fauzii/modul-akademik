<?php

namespace App\Http\Controllers\Admin\DataMaster;

use App\Http\Controllers\Controller;
use App\Models\Admin\DataMaster\Karyawan;
use Illuminate\Http\Client\Response as HttpClientResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use Throwable;

class KaryawanController extends Controller
{
    private const MAX_PHOTO_REDIRECTS = 2;

    private const ALLOWED_IMAGE_MIME_TYPES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/avif',
    ];

    /**
     * Menampilkan daftar data guru / karyawan.
     */
    public function index(): View
    {
        $karyawan = Karyawan::orderBy('nama_lengkap', 'asc')->get();

        return view('admin.dataMaster.karyawan.index', compact('karyawan'));
    }

    /**
     * Menampilkan detail informasi guru / karyawan.
     */
    public function show(Karyawan $karyawan): View
    {
        $karyawan->load(['user', 'jenjangPendidikan']);

        return view('admin.dataMaster.karyawan.show', compact('karyawan'));
    }

    /**
     * Menyajikan foto karyawan melalui proxy same-origin agar browser tidak
     * melakukan request lintas-port yang menimpa cookie/sesi SSO pada host sama.
     */
    public function foto(Request $request, Karyawan $karyawan): Response
    {
        $fotoUrl = $karyawan->foto_url;

        if (! $fotoUrl || $this->pointsToCurrentApp($fotoUrl, $request)) {
            abort(404);
        }

        $remoteResponse = $this->fetchRemotePhoto($fotoUrl, $request);
        if ($remoteResponse === null) {
            abort(404);
        }

        $contentType = $this->resolveSafeImageContentType($remoteResponse);
        if ($contentType === null) {
            abort(404);
        }

        return response($remoteResponse->body(), 200, [
            'Content-Type' => $contentType,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function fetchRemotePhoto(string $initialUrl, Request $request): ?HttpClientResponse
    {
        $baseOrigin = $this->normalizeOrigin($initialUrl);
        if ($baseOrigin === null) {
            return null;
        }

        $currentUrl = $initialUrl;

        for ($attempt = 0; $attempt <= self::MAX_PHOTO_REDIRECTS; $attempt++) {
            $headers = [
                'Accept' => 'image/avif,image/webp,image/png,image/jpeg,*/*;q=0.8',
            ];

            if ($attempt === 0) {
                $apiKey = trim((string) config('services.kepegawaian.api_key', ''));
                if ($apiKey !== '') {
                    $headers['X-API-Key'] = $apiKey;
                }

                $accessToken = $request->hasSession() ? $request->session()->get('access_token') : null;
                if (is_string($accessToken) && $accessToken !== '') {
                    $headers['Authorization'] = 'Bearer '.$accessToken;
                }
            }

            try {
                $response = Http::withoutRedirecting()
                    ->connectTimeout(2)
                    ->timeout(5)
                    ->withHeaders($headers)
                    ->get($currentUrl);
            } catch (Throwable) {
                return null;
            }

            if ($response->redirect()) {
                $nextUrl = $this->resolveSafeSameOriginRedirect(
                    (string) $response->header('Location'),
                    $baseOrigin
                );

                if ($nextUrl === null) {
                    return null;
                }

                $currentUrl = $nextUrl;

                continue;
            }

            if (! $response->successful() || $response->body() === '') {
                return null;
            }

            return $response;
        }

        return null;
    }

    private function resolveSafeSameOriginRedirect(string $location, string $baseOrigin): ?string
    {
        $location = trim($location);
        if ($location === '') {
            return null;
        }

        $candidate = str_starts_with($location, '/')
            ? $baseOrigin.$location
            : $location;

        if ($this->normalizeOrigin($candidate) !== $baseOrigin) {
            return null;
        }

        $path = strtolower((string) parse_url($candidate, PHP_URL_PATH));
        if (str_contains($path, '/login') || str_contains($path, '/oauth/')) {
            return null;
        }

        return $candidate;
    }

    private function resolveSafeImageContentType(HttpClientResponse $response): ?string
    {
        $rawContentType = strtolower(trim(explode(';', (string) $response->header('Content-Type'))[0]));

        return in_array($rawContentType, self::ALLOWED_IMAGE_MIME_TYPES, true)
            ? $rawContentType
            : null;
    }

    private function pointsToCurrentApp(string $targetUrl, Request $request): bool
    {
        $targetOrigin = $this->normalizeOrigin($targetUrl);
        if ($targetOrigin === null) {
            return true;
        }

        $requestPort = (int) $request->getPort();
        $requestOrigin = strtolower($request->getScheme()).'://'.strtolower($request->getHost()).':'.($requestPort > 0 ? $requestPort : ($request->isSecure() ? 443 : 80));
        $appOrigin = $this->normalizeOrigin((string) config('app.url', ''));

        return $targetOrigin === $requestOrigin || ($appOrigin !== null && $targetOrigin === $appOrigin);
    }

    private function normalizeOrigin(string $url): ?string
    {
        $parts = parse_url($url);
        if (! is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }

        $scheme = strtolower($parts['scheme']);
        $host = strtolower($parts['host']);
        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);

        return "{$scheme}://{$host}:{$port}";
    }
}
