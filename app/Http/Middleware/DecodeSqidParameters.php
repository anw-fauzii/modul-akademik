<?php

namespace App\Http\Middleware;

use App\Models\Admin\DataMaster\Karyawan;
use App\Support\Sqid;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Men-decode parameter query & konteks redirect yang memakai Sqids kembali ke ID internal,
 * dan langsung menolak token Sqid tidak valid / ID mentah pada parameter URL dengan HTTP 404.
 */
class DecodeSqidParameters
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set('sqids.raw_query', $request->query->all());

        $decodedQuery = $this->decodeParameterBag($request->query->all(), true);
        if ($decodedQuery !== []) {
            $request->query->add($decodedQuery);
            if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
                $request->merge($decodedQuery);
            }
        }

        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            $decodedBody = $this->decodeParameterBag($request->request->all(), false);
            if ($decodedBody !== []) {
                $request->request->add($decodedBody);
                $request->merge($decodedBody);
            }
        }

        return $next($request);
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function decodeParameterBag(array $values, bool $isUrlQuery): array
    {
        $decoded = [];

        foreach (Sqid::QUERY_PARAM_MAP as $param => $modelClass) {
            if (! array_key_exists($param, $values)) {
                continue;
            }

            $rawValue = $values[$param];
            if (Sqid::isSentinelValue($rawValue)) {
                continue;
            }

            if (! is_string($rawValue) && ! is_int($rawValue)) {
                abort(404);
            }

            $token = trim((string) $rawValue);

            // karyawan_nipy dapat berupa NIPY mentah (form/filter) atau Sqid; Sqid valid di-decode, selainnya dibiarkan.
            if ($param === 'karyawan_nipy') {
                $decodedNipy = Sqid::decodeFor(Karyawan::class, $token);
                if ($decodedNipy !== null) {
                    $decoded[$param] = (string) $decodedNipy;
                }

                continue;
            }

            $resolved = Sqid::decodeFor($modelClass, $token);
            if ($resolved === null) {
                abort(404);
            }

            $decoded[$param] = (string) $resolved;
        }

        return $decoded;
    }
}
