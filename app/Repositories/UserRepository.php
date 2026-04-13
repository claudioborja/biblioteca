<?php
// app/Repositories/UserRepository.php
declare(strict_types=1);

namespace Repositories;

use Core\Database;

final class UserRepository
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    // ── Finders ──────────────────────────────────────────────────────────────

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([mb_strtolower(trim($email))]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([mb_strtolower(trim($email))]);
        return (bool) $stmt->fetchColumn();
    }

    public function documentExists(string $documentNumber): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM users WHERE document_number = ? LIMIT 1');
        $stmt->execute([trim($documentNumber)]);
        return (bool) $stmt->fetchColumn();
    }

    public function documentExistsExcept(string $documentNumber, int $excludeUserId): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM users WHERE document_number = ? AND id <> ? LIMIT 1');
        $stmt->execute([trim($documentNumber), $excludeUserId]);
        return (bool) $stmt->fetchColumn();
    }

    // ── Write ─────────────────────────────────────────────────────────────────

    public function create(array $data): int
    {
        $sql = 'INSERT INTO users
                    (user_number, document_number, name, email, password_hash, role, user_type, status, created_at, updated_at)
                VALUES
                    (:user_number, :document_number, :name, :email, :password_hash, :role, :user_type, :status, NOW(), NOW())';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_number' => $data['user_number'] ?? null,
            ':document_number'   => $data['document_number']   ?? null,
            ':name'              => $data['name'],
            ':email'             => mb_strtolower(trim($data['email'])),
            ':password_hash'     => $data['password_hash'],
            ':role'              => $data['role']        ?? 'user',
            ':user_type'       => $data['user_type'] ?? 'student',
            ':status'            => $data['status']      ?? 'active',
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $allowed = [
            'name', 'email', 'password_hash', 'role', 'user_type', 'status',
            'phone', 'address', 'birthdate', 'photo',
            'last_login_at', 'last_login_ip', 'force_password_change',
            'remember_token', 'remember_expires', 'email_verified_at',
        ];

        $sets   = [];
        $values = [];
        foreach ($data as $col => $val) {
            if (!in_array($col, $allowed, true)) continue;
            $sets[]   = "`{$col}` = ?";
            $values[] = $val;
        }
        if (empty($sets)) return false;

        $values[] = $id;
        $sql = 'UPDATE users SET ' . implode(', ', $sets) . ', updated_at = NOW() WHERE id = ?';
        return (bool) $this->db->prepare($sql)->execute($values);
    }

    // ── Remember-me token ─────────────────────────────────────────────────────

    public function saveRememberToken(int $userId, string $tokenHash, string $expires): void
    {
        $this->update($userId, [
            'remember_token'   => $tokenHash,
            'remember_expires' => $expires,
        ]);
    }

    public function findByRememberToken(string $tokenHash): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM users
              WHERE remember_token = ?
                AND remember_expires > NOW()
              LIMIT 1'
        );
        $stmt->execute([$tokenHash]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function clearRememberToken(int $userId): void
    {
        $this->update($userId, ['remember_token' => null, 'remember_expires' => null]);
    }

    // ── Password reset ────────────────────────────────────────────────────────

    public function savePasswordResetToken(int $userId, string $tokenHash, string $expires): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO password_resets (user_id, token_hash, expires_at, created_at)
             VALUES (?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE token_hash = VALUES(token_hash),
                                      expires_at = VALUES(expires_at),
                                      used_at    = NULL,
                                      created_at = NOW()'
        );
        $stmt->execute([$userId, $tokenHash, $expires]);
    }

    public function findPasswordResetToken(int $userId, string $tokenHash): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM password_resets
              WHERE user_id = ? AND token_hash = ? AND used_at IS NULL
              LIMIT 1'
        );
        $stmt->execute([$userId, $tokenHash]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function markPasswordResetUsed(int $userId): void
    {
        $this->db->prepare(
            'UPDATE password_resets SET used_at = NOW() WHERE user_id = ?'
        )->execute([$userId]);
    }

    // ── Email verification ──────────────────────────────────────────────────

    public function saveEmailVerificationToken(int $userId, string $tokenHash, string $expires): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO email_verifications (user_id, token_hash, expires_at, created_at)
             VALUES (?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE token_hash = VALUES(token_hash),
                                      expires_at = VALUES(expires_at),
                                      used_at    = NULL,
                                      created_at = NOW()'
        );
        $stmt->execute([$userId, $tokenHash, $expires]);
    }

    public function findEmailVerificationToken(int $userId, string $tokenHash): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM email_verifications
              WHERE user_id = ? AND token_hash = ? AND used_at IS NULL
              LIMIT 1'
        );
        $stmt->execute([$userId, $tokenHash]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function findPendingEmailVerificationByToken(string $tokenHash): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT ev.*, u.email, u.role, u.status, u.email_verified_at
             FROM email_verifications ev
             INNER JOIN users u ON u.id = ev.user_id
             WHERE ev.token_hash = ?
               AND ev.used_at IS NULL
             LIMIT 1'
        );
        $stmt->execute([$tokenHash]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function markEmailVerificationUsed(int $userId): void
    {
        $this->db->prepare(
            'UPDATE email_verifications SET used_at = NOW() WHERE user_id = ?'
        )->execute([$userId]);
    }

    // ── User number generator ───────────────────────────────────────────

    public function generateUserNumber(): string
    {
        $year = date('Y');
        $stmt = $this->db->prepare(
            "SELECT user_number FROM users
              WHERE user_number LIKE ?
              ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute([$year . '-%']);
        $last = $stmt->fetchColumn();
        // formato: 2026-00001 → extraer solo los dígitos tras el guion (posición 5)
        $seq  = $last ? ((int) substr((string) $last, 5)) + 1 : 1;
        return $year . '-' . str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }
}

