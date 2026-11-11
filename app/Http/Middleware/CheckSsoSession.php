<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class CheckSsoSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->session()->get('access_token');
        if (!$token) {
            return $this->logoutLocal($request);
        }
        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(3)
                ->get(config('auth.host') . '/api/user');

            if ($response->status() === 401) {
                return $this->logoutLocal($request);
            }
            if ($response->failed()) {
                abort(503, 'Server SSO tidak dapat memverifikasi login.');
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            abort(503, 'Server SSO tidak dapat dihubungi.');
        }

        return $next($request);
    }

    private function logoutLocal(Request $request): Response
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->withErrors([
                'email' => 'Sesi login berakhir. Silakan login kembali.'
            ]);
    }
}