<?php

namespace App\Services\Auth;

/**
 * Identitas Google yang SUDAH diverifikasi oleh backend.
 */
final readonly class GoogleIdentity
{
    public function __construct(
        public string $googleId,
        public string $email,
        public bool $emailVerified,
        public ?string $name = null,
        public ?string $avatar = null,
        public ?string $nonce = null,
    ) {}
}
