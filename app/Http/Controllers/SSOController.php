<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Illuminate\Support\Facades\Auth;

class SSOController extends Controller
{
    public function getLogin(Request $request)
    {
        $state = Str::random(40);

        $request->session()->put('state', $state);

        $query = http_build_query([
            'client_id' => config('auth.client_id'),
            'redirect_uri' => config('auth.callback'),
            'response_type' => 'code',
            'scope' => config('auth.scope'),
            'state' => $state,
        ]);

        return redirect(
            config('auth.host') . '/oauth/authorize?' . $query
        );
    }

    public function getCallback(Request $request)
    {
        $state = $request->session()->pull("state");
        $codeVerifier = $request->session()->pull('code_verifier');
    
        throw_unless(strlen($state) > 0 && $state === $request->state, InvalidArgumentException::class);
        $response = Http::asForm()->post(config('auth.host') . '/oauth/token', [
            'grant_type' => "authorization_code",
            'client_id' => config('auth.client_id'),
            'client_secret' => config('auth.client_secret'),
            'redirect_uri' => config('auth.callback'),
            'code_verifier' => $codeVerifier,
            'code' => $request->query('code'),
        ]);
        $request->session()->put($response->json());
        return redirect()->route('sso.authuser');
    }

    public function connectUser(Request $request)
    {
        $access_token = $request->session()->get('access_token');
        
        $response = Http::withHeaders(
            [
                "Accept" => "application/json",
                'Authorization' => 'Bearer '. $access_token
            ]
        )->get(config('auth.host') . '/api/user');
        $userArray =  $response->json();
        try{
            $email = $userArray['email'];
        } catch (\Throwable $th) {
            return redirect()->route('login')->withErrors(['email' => 'Email not found in SSO response.']);
        }
        $user = \App\Models\User::where('email', $email)->first();
        if (!$user) {
            $user = \App\Models\User::create([
                'name' => $userArray['name'],
                'email' => $userArray['email'],
                'email_verified_at' => now(),
            ]);
        }
        Auth::login($user);
        return redirect()->route('home');
    }
}
