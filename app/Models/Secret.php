<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\SecretFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @property string $id
 * @property string $encrypted_secret
 * @property string $encryption_iv
 * @property Carbon $expires_at
 * @method static SecretFactory factory(...$parameters)
 * @method static Builder|Secret newModelQuery()
 * @method static Builder|Secret newQuery()
 * @method static Builder|Secret query()
 * @method static Builder|Secret whereEncryptedSecret($value)
 * @method static Builder|Secret whereEncryptionIv($value)
 * @method static Builder|Secret whereExpiresAt($value)
 * @method static Builder|Secret whereId($value)
 */
class Secret extends Model
{
    /** @use HasFactory<SecretFactory> */
    use HasFactory;

    public const MIN_TTL = 5; // 5 minutes
    public const MAX_TTL = 60 * 24; // 1 day
    public const DEFAULT_TTL = 60; // 1 hour
    public const MAX_SECRET_LENGTH = 255;
    public const MAX_PASSPHRASE_LENGTH = 255;

    protected $table = 'secrets';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['id', 'encrypted_secret', 'encryption_iv', 'expires_at'];
    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->id = (string) Str::uuid();
        $this->expires_at = Carbon::now()->addMinutes(self::DEFAULT_TTL);
    }

    /**
     * Check if the secret has expired.
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Encrypt the secret using the provided key.
     * @param string $secret
     * @param string $key
     */
    public function encryptSecret(string $secret, string $key): void
    {
        $iv = random_bytes(16);
        $encryptedSecret = openssl_encrypt($secret, 'aes-256-cbc', $key, 0, $iv);

        $this->encrypted_secret = base64_encode($encryptedSecret);
        $this->encryption_iv = base64_encode($iv);
    }

    /**
     * Decrypt the secret using the provided key.
     * @param string $key
     * @return false|string The decrypted secret on success or false on failure
     */
    public function decryptSecret(string $key): false|string
    {
        return openssl_decrypt(base64_decode($this->encrypted_secret), 'aes-256-cbc', $key, 0, base64_decode($this->encryption_iv));
    }
}
