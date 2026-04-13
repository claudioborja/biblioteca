<?php
declare(strict_types=1);

namespace Tests\Unit\Security;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * A02 — Cryptographic Failures.
 * Verifies secure random generation, password hashing, and timing-safe comparisons.
 */
final class CryptographyTest extends TestCase
{
    // ── Secure random token generation ───────────────────────────────────────

    #[Test]
    public function random_bytes_produces_unique_tokens(): void
    {
        $tokens = [];
        for ($i = 0; $i < 50; $i++) {
            $tokens[] = bin2hex(random_bytes(32));
        }
        $this->assertSame(50, count(array_unique($tokens)),
            'random_bytes() must never produce duplicate tokens');
    }

    #[Test]
    public function random_token_has_correct_entropy_length(): void
    {
        $token = bin2hex(random_bytes(32));
        $this->assertSame(64, strlen($token), '32 bytes = 64 hex chars');
    }

    // ── Password hashing with ARGON2ID ───────────────────────────────────────

    #[Test]
    public function argon2id_produces_valid_hash(): void
    {
        $hash = password_hash('secret123', PASSWORD_ARGON2ID);
        $this->assertStringStartsWith('$argon2id$', $hash,
            'Must use ARGON2ID algorithm');
    }

    #[Test]
    public function password_verify_correctly_validates_argon2id_hash(): void
    {
        $hash = password_hash('correct_password', PASSWORD_ARGON2ID);
        $this->assertTrue(password_verify('correct_password', $hash));
        $this->assertFalse(password_verify('wrong_password', $hash));
    }

    #[Test]
    public function same_password_produces_different_hashes_each_time(): void
    {
        $hash1 = password_hash('same_password', PASSWORD_ARGON2ID);
        $hash2 = password_hash('same_password', PASSWORD_ARGON2ID);

        $this->assertNotSame($hash1, $hash2,
            'Each hash call must produce a different salt → different output');

        // Both must still verify correctly
        $this->assertTrue(password_verify('same_password', $hash1));
        $this->assertTrue(password_verify('same_password', $hash2));
    }

    #[Test]
    public function password_hash_is_not_reversible(): void
    {
        $password = 'mysecret123!';
        $hash     = password_hash($password, PASSWORD_ARGON2ID);

        // The raw password must not appear in the hash output
        $this->assertStringNotContainsString($password, $hash);
    }

    // ── Timing-safe comparison (hash_equals) ─────────────────────────────────

    #[Test]
    public function hash_equals_rejects_different_strings(): void
    {
        $this->assertFalse(hash_equals('correct_token', 'wrong_token'));
    }

    #[Test]
    public function hash_equals_accepts_identical_strings(): void
    {
        $token = bin2hex(random_bytes(32));
        $this->assertTrue(hash_equals($token, $token));
    }

    #[Test]
    public function hash_equals_rejects_string_differing_by_one_char(): void
    {
        $token = 'abcdef1234567890abcdef1234567890abcdef1234567890abcdef1234567890';
        $tampered = substr($token, 0, -1) . 'x'; // change last char

        $this->assertFalse(hash_equals($token, $tampered));
    }

    #[Test]
    public function hash_equals_rejects_empty_vs_non_empty(): void
    {
        $this->assertFalse(hash_equals('real_token', ''));
        $this->assertFalse(hash_equals('', 'fake_token'));
    }

    // ── Token storage: hash before persisting ────────────────────────────────

    #[Test]
    public function sha256_hash_of_token_is_not_the_token(): void
    {
        $rawToken    = bin2hex(random_bytes(32));
        $storedHash  = hash('sha256', $rawToken);

        $this->assertNotSame($rawToken, $storedHash,
            'The stored hash must never equal the raw token');

        $this->assertSame(64, strlen($storedHash),
            'SHA-256 output is always 64 hex chars');
    }

    #[Test]
    public function password_reset_token_comparison_uses_hashed_values(): void
    {
        // Simulate the auth flow: generate token, hash for storage, compare on reset
        $rawToken   = bin2hex(random_bytes(32));
        $storedHash = hash('sha256', $rawToken);

        // Attacker submits a slightly different token
        $fakeToken  = substr($rawToken, 0, -1) . 'z';
        $fakeHash   = hash('sha256', $fakeToken);

        // Even hashed values differ — constant-time comparison still works
        $this->assertFalse(hash_equals($storedHash, $fakeHash));
        $this->assertTrue(hash_equals($storedHash, hash('sha256', $rawToken)));
    }

    // ── Forbidden algorithms ─────────────────────────────────────────────────

    #[Test]
    public function md5_output_is_easily_distinguished_from_argon2id(): void
    {
        // Document that MD5 is not used for passwords — it produces 32 hex chars,
        // a completely different format from a proper password hash
        $md5Hash = md5('password');
        $this->assertSame(32, strlen($md5Hash));
        $this->assertStringStartsNotWith('$argon2id$', $md5Hash,
            'MD5 is not a password-hashing algorithm — must not be used as one');
    }

    #[Test]
    public function sha1_output_is_easily_distinguished_from_argon2id(): void
    {
        $sha1Hash = sha1('password');
        $this->assertSame(40, strlen($sha1Hash));
        $this->assertStringStartsNotWith('$argon2id$', $sha1Hash,
            'SHA1 is not a password-hashing algorithm — must not be used as one');
    }
}
