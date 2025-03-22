<?php

namespace App\Http\Controllers;

use App\Models\Secret;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SecretsController extends Controller
{
    /**
     * Create a new secret.
     * @param Request $request
     * @return JsonResponse
     */
    public function createSecret(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required|string|min:1|max:255',
            'passphrase' => 'nullable|string|min:1|max:255',
            'expires_in' => 'nullable|integer|min:5',
        ]);

        $key = $request->input('passphrase', Str::random(32));

        $secret = new Secret();
        $secret->encryptSecret($request->input('password'), $key);
        $request->filled('expires_in') ?? $secret->expires_at = now()->addMinutes($request->input('expires_in', Secret::DEFAULT_TTL));
        $secret->save();

        if ($request->filled('passphrase')) {
            return response()->json([
                'url' => url("/api/secrets/{$secret->id}?passphrase="),
                'note' => 'This secret requires the passphrase at the end of the URL to decrypt.',
                'expires_at' => $secret->expires_at,
            ]);
        }

        return response()->json([
            'url' => url("/api/secrets/{$secret->id}?token={$key}"),
            'expires_at' => $secret->expires_at,
        ]);
    }
}
