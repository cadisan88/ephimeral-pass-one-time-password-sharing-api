<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('secrets', function (Blueprint $table) {
            $table->char('id', 36)->primary(); // UUID
            $table->text('encrypted_secret'); // AES-256 Encrypted Password
            $table->string('encryption_iv', 32); // IV for AES Encryption
            $table->dateTime('expires_at')->index(); // Expiration Time
            $table->timestamps(); // created_at for record keeping
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('secrets');
    }
};
