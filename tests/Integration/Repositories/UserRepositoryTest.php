<?php
declare(strict_types=1);

namespace Tests\Integration\Repositories;

use Core\Database;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Repositories\UserRepository;

/**
 * Integration tests for UserRepository.
 * Each test runs inside a transaction that is rolled back on tearDown
 * — the database is always left in its original state.
 */
final class UserRepositoryTest extends TestCase
{
    private \PDO $pdo;
    private UserRepository $repo;

    protected function setUp(): void
    {
        $this->pdo  = Database::connect();
        $this->repo = new UserRepository();
        $this->pdo->exec('START TRANSACTION');
    }

    protected function tearDown(): void
    {
        $this->pdo->exec('ROLLBACK');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function insertUser(array $overrides = []): int
    {
        $uid = uniqid();
        $defaults = [
            'user_number' => 'T-' . $uid,
            'name'              => 'Usuario Test',
            'email'             => 'test_' . $uid . '@biblioteca.mx',
            'password_hash'     => password_hash('secret123', PASSWORD_BCRYPT),
            'role'              => 'user',
            'user_type'       => 'student',
            'status'            => 'active',
            'document_number'   => 'DOC-' . $uid,
        ];

        return $this->repo->create(array_merge($defaults, $overrides));
    }

    // ── create / findById ────────────────────────────────────────────────────

    #[Test]
    public function it_creates_a_user_and_returns_an_id(): void
    {
        $id = $this->insertUser();

        $this->assertGreaterThan(0, $id);
    }

    #[Test]
    public function it_finds_a_user_by_id(): void
    {
        $id   = $this->insertUser(['name' => 'Ana García']);
        $user = $this->repo->findById($id);

        $this->assertNotNull($user);
        $this->assertSame('Ana García', $user['name']);
    }

    #[Test]
    public function it_returns_null_for_nonexistent_id(): void
    {
        $this->assertNull($this->repo->findById(999999));
    }

    // ── findByEmail ──────────────────────────────────────────────────────────

    #[Test]
    public function it_finds_a_user_by_email(): void
    {
        $email = 'unique_' . uniqid() . '@test.mx';
        $this->insertUser(['email' => $email]);

        $user = $this->repo->findByEmail($email);

        $this->assertNotNull($user);
        $this->assertSame(strtolower($email), $user['email']);
    }

    #[Test]
    public function it_finds_user_case_insensitively_by_email(): void
    {
        $email = 'CaseTest_' . uniqid() . '@test.mx';
        $this->insertUser(['email' => $email]);

        $user = $this->repo->findByEmail(strtoupper($email));

        $this->assertNotNull($user);
    }

    #[Test]
    public function it_returns_null_for_nonexistent_email(): void
    {
        $this->assertNull($this->repo->findByEmail('nobody_' . uniqid() . '@void.mx'));
    }

    // ── emailExists ──────────────────────────────────────────────────────────

    #[Test]
    public function it_detects_existing_email(): void
    {
        $email = 'exists_' . uniqid() . '@test.mx';
        $this->insertUser(['email' => $email]);

        $this->assertTrue($this->repo->emailExists($email));
    }

    #[Test]
    public function it_returns_false_for_non_existing_email(): void
    {
        $this->assertFalse($this->repo->emailExists('ghost_' . uniqid() . '@void.mx'));
    }

    // ── update ───────────────────────────────────────────────────────────────

    #[Test]
    public function it_updates_allowed_fields(): void
    {
        $id = $this->insertUser(['name' => 'Original Name']);

        $this->repo->update($id, ['name' => 'Updated Name']);

        $user = $this->repo->findById($id);
        $this->assertSame('Updated Name', $user['name']);
    }

    #[Test]
    public function it_ignores_disallowed_fields_on_update(): void
    {
        $id = $this->insertUser();

        // 'id' and 'created_at' are not in the allowed list
        $result = $this->repo->update($id, ['id' => 9999, 'created_at' => '1990-01-01']);

        // Should return false (no valid fields to update)
        $this->assertFalse($result);

        $user = $this->repo->findById($id);
        $this->assertNotSame(9999, (int) $user['id']);
    }

    // ── remember token ───────────────────────────────────────────────────────

    #[Test]
    public function it_saves_and_retrieves_remember_token(): void
    {
        $id    = $this->insertUser();
        $hash  = hash('sha256', 'random_token');
        $exp   = date('Y-m-d H:i:s', strtotime('+30 days'));

        $this->repo->saveRememberToken($id, $hash, $exp);

        $user = $this->repo->findByRememberToken($hash);
        $this->assertNotNull($user);
        $this->assertSame($id, (int) $user['id']);
    }

    #[Test]
    public function it_clears_remember_token(): void
    {
        $id   = $this->insertUser();
        $hash = hash('sha256', 'some_token');
        $exp  = date('Y-m-d H:i:s', strtotime('+1 day'));

        $this->repo->saveRememberToken($id, $hash, $exp);
        $this->repo->clearRememberToken($id);

        $this->assertNull($this->repo->findByRememberToken($hash));
    }

    #[Test]
    public function it_returns_null_for_expired_token(): void
    {
        $id   = $this->insertUser();
        $hash = hash('sha256', 'expired_token');
        $past = date('Y-m-d H:i:s', strtotime('-1 second'));

        $this->repo->saveRememberToken($id, $hash, $past);

        $this->assertNull($this->repo->findByRememberToken($hash));
    }
}
