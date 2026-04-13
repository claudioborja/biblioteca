<?php
declare(strict_types=1);

namespace Tests\Builders;

/**
 * UserBuilder — fluent factory for user test arrays.
 *
 * Usage:
 *   $user = UserBuilder::make()->withRole('admin')->build();
 *   $users = UserBuilder::make()->build(3); // array of 3 users
 */
final class UserBuilder
{
    private static int $sequence = 0;

    private array $overrides = [];

    private function __construct() {}

    public static function make(): self
    {
        return new self();
    }

    public function withId(int $id): self
    {
        $clone           = clone $this;
        $clone->overrides['id'] = $id;
        return $clone;
    }

    public function withName(string $name): self
    {
        $clone           = clone $this;
        $clone->overrides['name'] = $name;
        return $clone;
    }

    public function withEmail(string $email): self
    {
        $clone              = clone $this;
        $clone->overrides['email'] = $email;
        return $clone;
    }

    public function withRole(string $role): self
    {
        $clone             = clone $this;
        $clone->overrides['role'] = $role;
        return $clone;
    }

    public function withStatus(string $status): self
    {
        $clone               = clone $this;
        $clone->overrides['status'] = $status;
        return $clone;
    }

    public function active(): self
    {
        return $this->withStatus('active');
    }

    public function inactive(): self
    {
        return $this->withStatus('inactive');
    }

    public function asAdmin(): self
    {
        return $this->withRole('admin');
    }

    public function asLibrarian(): self
    {
        return $this->withRole('librarian');
    }

    public function asMember(): self
    {
        return $this->withRole('user');
    }

    public function asTeacher(): self
    {
        return $this->withRole('teacher');
    }

    /** Build one user array. */
    public function build(): array
    {
        $seq = ++self::$sequence;

        return array_merge([
            'id'              => $seq,
            'user_number'     => date('Y') . '-' . str_pad((string) $seq, 5, '0', STR_PAD_LEFT),
            'document_number' => '10000000' . str_pad((string) $seq, 2, '0', STR_PAD_LEFT),
            'name'            => "Usuario Prueba {$seq}",
            'email'           => "usuario.prueba{$seq}@test.local",
            'password_hash'   => password_hash('secret1234', PASSWORD_BCRYPT),
            'role'            => 'user',
            'user_type'       => 'student',
            'status'          => 'active',
            'phone'           => null,
            'address'         => null,
            'birthdate'       => null,
            'photo'           => null,
            'last_login_at'   => null,
            'last_login_ip'   => null,
            'remember_token'  => null,
            'remember_expires'=> null,
            'force_password_change' => 0,
            'created_at'      => date('Y-m-d H:i:s'),
            'updated_at'      => date('Y-m-d H:i:s'),
        ], $this->overrides);
    }

    /**
     * Build multiple user arrays.
     *
     * @return array<int, array>
     */
    public function buildMany(int $count): array
    {
        return array_map(fn() => $this->build(), range(1, $count));
    }
}
