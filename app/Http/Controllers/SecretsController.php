<?php

namespace App\Http\Controllers;

use App\Models\Secret;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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

    public function getSecret(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'token' => ['sometimes', 'string', 'min:1', 'max:' . Secret::MAX_PASSPHRASE_LENGTH],
            'passphrase' => ['sometimes', 'string', 'min:1', 'max:' . Secret::MAX_PASSPHRASE_LENGTH],
        ]);

        if ($request->filled('token') && $request->filled('passphrase')) {
            return response()->json([
                'message' => 'Only one parameter (token or passphrase) should be provided, not both',
                'errors' => [
                    'token' => 'Only one parameter (token or passphrase) should be provided, not both',
                    'passphrase' => 'Only one parameter (token or passphrase) should be provided, not both',
                ],
            ], Response::HTTP_BAD_REQUEST);
        }

        $key = $request->filled('token') ? $request->input('token') : $request->input('passphrase');

        /** @var Secret $secret */
        $secret = Secret::where('id', $id)->first();

        if (!$secret) {
            return response()->json(['error' => 'Secret not found'], Response::HTTP_NOT_FOUND);
        }

        if ($secret->isExpired()) {
            return response()->json(['error' => 'Secret has expired'], Response::HTTP_GONE);
        }

        $decryptedSecret = $secret->decryptSecret($key);

        if ($decryptedSecret === false) {
            // Invalid key
            return response()->json(['error' => 'Invalid token or passphrase'], Response::HTTP_UNAUTHORIZED);
        }

        $secret->delete();

        return response()->json([
            'secret' => $decryptedSecret,
            'note' => 'This secret has been deleted and can no longer be retrieved.',
        ]);
    }
}
