<?php

namespace Tests\Unit;

use App\Services\Auth\GoogleIdTokenVerifier;
use App\Services\Auth\InvalidGoogleTokenException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GoogleIdTokenVerifierTest extends TestCase
{
    private const AUD = 'web-client.apps.googleusercontent.com';

    private \OpenSSLAsymmetricKey $privateKey;

    protected function setUp(): void
    {
        parent::setUp();

        $this->privateKey = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $csr = openssl_csr_new(['commonName' => 'test'], $this->privateKey);
        $cert = openssl_csr_sign($csr, null, $this->privateKey, 1);
        openssl_x509_export($cert, $pem);

        Cache::flush();
        Http::fake(['www.googleapis.com/*' => Http::response(['kid-1' => $pem], 200, ['Cache-Control' => 'public, max-age=3600'])]);
    }

    public function test_valid_token_is_accepted(): void
    {
        $identity = (new GoogleIdTokenVerifier)->verify($this->jwt($this->claims()), [self::AUD]);

        $this->assertSame('budi@gmail.com', $identity->email);
        $this->assertSame('1234567890', $identity->googleId);
        $this->assertTrue($identity->emailVerified);
    }

    public function test_unverified_email_flag_is_reported(): void
    {
        $identity = (new GoogleIdTokenVerifier)->verify($this->jwt($this->claims(['email_verified' => false])), [self::AUD]);

        $this->assertFalse($identity->emailVerified);
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1?: array<string, mixed>}>
     */
    public static function invalidTokens(): array
    {
        return [
            'audience lain (client ID tidak cocok)' => [['aud' => 'attacker-app.apps.googleusercontent.com']],
            'issuer palsu' => [['iss' => 'https://evil.example.com']],
            'kedaluwarsa' => [['exp' => time() - 3600]],
            'tanpa email' => [['email' => null]],
            'iat di masa depan' => [['iat' => time() + 3600]],
            'alg none' => [[], ['alg' => 'none', 'kid' => 'kid-1']],
            'kid tidak dikenal' => [[], ['alg' => 'RS256', 'kid' => 'unknown']],
        ];
    }

    /**
     * @param  array<string, mixed>  $claims
     * @param  array<string, mixed>  $header
     */
    #[DataProvider('invalidTokens')]
    public function test_invalid_tokens_are_rejected(array $claims, array $header = []): void
    {
        $this->expectException(InvalidGoogleTokenException::class);

        (new GoogleIdTokenVerifier)->verify($this->jwt(array_filter($this->claims($claims), fn ($v) => $v !== null), $header), [self::AUD]);
    }

    public function test_tampered_payload_fails_signature_check(): void
    {
        [$h, , $s] = explode('.', $this->jwt($this->claims()));
        $forged = $this->b64(json_encode($this->claims(['email' => 'admin@gmail.com'])));

        $this->expectException(InvalidGoogleTokenException::class);
        $this->expectExceptionMessage('Tanda tangan');

        (new GoogleIdTokenVerifier)->verify("$h.$forged.$s", [self::AUD]);
    }

    public function test_token_signed_by_other_key_is_rejected(): void
    {
        $this->privateKey = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);

        $this->expectException(InvalidGoogleTokenException::class);

        (new GoogleIdTokenVerifier)->verify($this->jwt($this->claims()), [self::AUD]);
    }

    public function test_missing_server_configuration_is_rejected(): void
    {
        $this->expectException(InvalidGoogleTokenException::class);

        (new GoogleIdTokenVerifier)->verify($this->jwt($this->claims()), []);
    }

    /**
     * @param  array<string, mixed>  $override
     * @return array<string, mixed>
     */
    private function claims(array $override = []): array
    {
        return array_merge([
            'iss' => 'https://accounts.google.com',
            'aud' => self::AUD,
            'sub' => '1234567890',
            'email' => 'budi@gmail.com',
            'email_verified' => true,
            'iat' => time(),
            'exp' => time() + 3600,
        ], $override);
    }

    /**
     * @param  array<string, mixed>  $claims
     * @param  array<string, mixed>  $header
     */
    private function jwt(array $claims, array $header = []): string
    {
        $header = $this->b64(json_encode($header ?: ['alg' => 'RS256', 'kid' => 'kid-1', 'typ' => 'JWT']));
        $payload = $this->b64(json_encode($claims));
        openssl_sign("$header.$payload", $signature, $this->privateKey, OPENSSL_ALGO_SHA256);

        return "$header.$payload.".$this->b64($signature);
    }

    private function b64(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
