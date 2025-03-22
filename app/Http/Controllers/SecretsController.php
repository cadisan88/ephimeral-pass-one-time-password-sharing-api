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
            'password' => 'required|string|min:1|max:' . Secret::MAX_SECRET_LENGTH,
            'passphrase' => 'nullable|string|min:1|max:' . Secret::MAX_PASSPHRASE_LENGTH,
            'expires_in' => 'nullable|integer|min:' . Secret::MIN_TTL . '|max:' . Secret::MAX_TTL,
        ]);

        $key = $request->input('passphrase', Str::random(32));

        $secret = new Secret();
        $secret->encryptSecret($request->input('password'), $key);
        $request->filled('expires_in') ?? $secret->expires_at = now()->addMinutes($request->input('expires_in', Secret::DEFAULT_TTL));
        $secret->save();

        if ($request->filled('passphrase')) {
            return response()->json([
                'url' => url("/api/secrets/{$secret->id}?passphrase="),
                'note' => 'This secret requires the passphrase at the end of the URL to decrypt and must be provided by the recipient.',
                'expires_at' => $secret->expires_at,
            ]);
        }

        return response()->json([
            'url' => url("/api/secrets/{$secret->id}?token={$key}"),
            'expires_at' => $secret->expires_at,
        ]);
    }
}
