---
name: skill-php-testing
description: "**WORKFLOW SKILL** — Professional testing in pure PHP without frameworks at expert level. USE FOR: PHPUnit setup and configuration; unit tests for services, repositories, validators, value objects; in-memory repository implementations for isolated tests; test doubles (stubs, mocks, spies) with PHPUnit MockObject; integration tests with real MariaDB (test database); feature/acceptance tests for HTTP layer; test data builders and object mothers; fixtures and database seeding for tests; test coverage reports; testing auth flows, file uploads, email queue, cache; TDD workflow; testing library-specific features (loans, reservations, fines, inventory); running tests on VPS and shared hosting CI-less environments; test organization and naming conventions. DO NOT USE FOR: browser/E2E testing (Selenium/Playwright); frontend JS testing; load testing."
---

# PHP Testing — Professional Test Suite for Pure PHP

## Core Philosophy

- **Tests are executable documentation**: A test suite describes what the system does — not just that it works.
- **Fast by default**: Unit tests must run in milliseconds. No DB, no filesystem, no network in unit tests.
- **In-memory repositories**: The fastest, most reliable way to test services without a database.
- **Test one thing**: One assertion per logical concept — not one `assert` per method.
- **TDD when possible**: Write the test first, then the implementation. Red → Green → Refactor.

---

## Project Structure

```
tests/
├── Unit/
│   ├── Services/
│   │   ├── BookServiceTest.php
│   │   ├── LoanServiceTest.php
│   │   └── FineServiceTest.php
│   ├── Repositories/
│   │   └── InMemory/
│   │       ├── InMemoryBookRepository.php
│   │       └── InMemoryUserRepository.php
│   ├── Auth/
│   │   ├── AccessControlTest.php
│   │   └── CsrfTest.php
│   ├── Core/
│   │   ├── ValidatorTest.php
│   │   └── RouterTest.php
│   └── Helpers/
│       └── SanitizeTest.php
├── Integration/
│   ├── Repositories/
│   │   ├── PdoBookRepositoryTest.php
│   │   └── PdoLoanRepositoryTest.php
│   └── Mail/
│       └── MailQueueTest.php
├── Feature/
│   ├── Auth/
│   │   ├── LoginTest.php
│   │   └── PasswordResetTest.php
│   └── Books/
│       └── BookCatalogTest.php
├── Fixtures/
│   ├── books.php
│   ├── users.php
│   └── loans.php
├── Builders/
│   ├── BookBuilder.php
│   ├── UserBuilder.php
│   └── LoanBuilder.php
├── bootstrap.php
└── phpunit.xml
```

---

## PHPUnit Configuration

```xml
<!-- phpunit.xml -->
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="tests/bootstrap.php"
         cacheDirectory=".phpunit.cache"
         colors="true"
         displayDetailsOnTestsThatTriggerWarnings="true"
         displayDetailsOnTestsThatTriggerDeprecations="true">

    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
    </testsuites>

    <coverage>
        <include>
            <directory>src</directory>
        </include>
        <report>
            <html outputDirectory="coverage"/>
            <text outputFile="coverage/summary.txt"/>
        </report>
    </coverage>

    <php>
        <env name="APP_ENV"     value="testing"/>
        <env name="APP_DEBUG"   value="true"/>
        <env name="DB_DATABASE" value="biblioteca_test"/>
        <env name="DB_USERNAME" value="test_user"/>
        <env name="DB_PASSWORD" value="test_pass"/>
        <env name="MAIL_DRIVER" value="log"/>
        <env name="CACHE_DRIVER" value="array"/>
    </php>
</phpunit>
```

```php
<?php
// tests/bootstrap.php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('TESTING', true);

require BASE_PATH . '/bootstrap.php';
```

---

## In-Memory Repositories

```php
<?php
// tests/Unit/Repositories/InMemory/InMemoryBookRepository.php
declare(strict_types=1);

namespace Tests\Repositories\InMemory;

use Repositories\BookRepositoryInterface;

final class InMemoryBookRepository implements BookRepositoryInterface
{
    /** @var array<int, array> */
    private array $storage = [];
    private int   $nextId  = 1;

    public function findById(int $id): ?array
    {
        return $this->storage[$id] ?? null;
    }

    public function findAll(int $limit = 50, int $offset = 0): array
    {
        $active = array_filter($this->storage, fn($b) => $b['deleted_at'] === null);
        return array_slice(array_values($active), $offset, $limit);
    }

    public function findByIsbn(string $isbn): ?array
    {
        foreach ($this->storage as $book) {
            if ($book['isbn'] === $isbn && $book['deleted_at'] === null) return $book;
        }
        return null;
    }

    public function findFeatured(int $limit = 10): array
    {
        $featured = array_filter($this->storage, fn($b) => $b['featured'] ?? false);
        return array_slice(array_values($featured), 0, $limit);
    }

    public function findByCategory(int $categoryId, int $perPage = 20, int $page = 1): array
    {
        $filtered = array_filter($this->storage, fn($b) => $b['category_id'] === $categoryId);
        $offset   = ($page - 1) * $perPage;
        $data     = array_slice(array_values($filtered), $offset, $perPage);
        return [
            'data'         => $data,
            'total'        => count($filtered),
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => (int) ceil(count($filtered) / $perPage),
        ];
    }

    public function search(string $query, array $filters = []): array
    {
        $q       = mb_strtolower($query);
        $results = array_filter($this->storage, function (array $book) use ($q, $filters) {
            $match = str_contains(mb_strtolower($book['title']), $q)
                  || str_contains(mb_strtolower($book['author']), $q)
                  || str_contains(mb_strtolower($book['isbn'] ?? ''), $q);

            if (isset($filters['category_id'])) {
                $match = $match && $book['category_id'] === $filters['category_id'];
            }
            return $match;
        });
        return array_values($results);
    }

    public function insert(array $data): int
    {
        $id = $this->nextId++;
        $this->storage[$id] = array_merge($data, [
            'id'         => $id,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'deleted_at' => null,
        ]);
        return $id;
    }

    public function update(int $id, array $data): bool
    {
        if (!isset($this->storage[$id])) return false;
        $this->storage[$id] = array_merge($this->storage[$id], $data, ['updated_at' => date('Y-m-d H:i:s')]);
        return true;
    }

    public function softDelete(int $id): bool
    {
        if (!isset($this->storage[$id])) return false;
        $this->storage[$id]['deleted_at'] = date('Y-m-d H:i:s');
        return true;
    }

    public function count(string $where = '', array $params = []): int
    {
        return count(array_filter($this->storage, fn($b) => $b['deleted_at'] === null));
    }

    public function getAllCoverPaths(): array
    {
        return array_column(array_filter($this->storage, fn($b) => isset($b['cover_path'])), 'cover_path');
    }

    // Test helpers
    public function all(): array    { return $this->storage; }
    public function reset(): void   { $this->storage = []; $this->nextId = 1; }
    public function count_all(): int { return count($this->storage); }
}
```

---

## Object Mother / Test Builders

```php
<?php
// tests/Builders/BookBuilder.php
declare(strict_types=1);

namespace Tests\Builders;

final class BookBuilder
{
    private array $data = [
        'title'       => 'Cien años de soledad',
        'author'      => 'Gabriel García Márquez',
        'isbn'        => '978-0-06-088328-7',
        'category_id' => 1,
        'copies'      => 3,
        'available'   => 3,
        'year'        => 1967,
        'publisher'   => 'Editorial Sudamericana',
        'language'    => 'es',
        'pages'       => 471,
        'featured'    => false,
        'cover_path'  => null,
        'deleted_at'  => null,
    ];

    public static function aBook(): self { return new self(); }

    public function withTitle(string $title): self
    {
        $clone = clone $this;
        $clone->data['title'] = $title;
        return $clone;
    }

    public function withAuthor(string $author): self
    {
        $clone = clone $this;
        $clone->data['author'] = $author;
        return $clone;
    }

    public function withIsbn(string $isbn): self
    {
        $clone = clone $this;
        $clone->data['isbn'] = $isbn;
        return $clone;
    }

    public function withCopies(int $copies, int $available = -1): self
    {
        $clone = clone $this;
        $clone->data['copies']    = $copies;
        $clone->data['available'] = $available === -1 ? $copies : $available;
        return $clone;
    }

    public function featured(): self
    {
        $clone = clone $this;
        $clone->data['featured'] = true;
        return $clone;
    }

    public function unavailable(): self
    {
        $clone = clone $this;
        $clone->data['available'] = 0;
        return $clone;
    }

    public function inCategory(int $categoryId): self
    {
        $clone = clone $this;
        $clone->data['category_id'] = $categoryId;
        return $clone;
    }

    public function build(): array { return $this->data; }
}
```

```php
<?php
// tests/Builders/UserBuilder.php
declare(strict_types=1);

namespace Tests\Builders;

final class UserBuilder
{
    private array $data = [
        'name'          => 'Ana García',
        'email'         => 'ana@example.com',
        'password_hash' => '',
        'role'          => 'member',
        'member_number' => 'M-0001',
        'is_active'     => 1,
        'deleted_at'    => null,
    ];

    public function __construct()
    {
        $this->data['password_hash'] = password_hash('password123', PASSWORD_BCRYPT);
    }

    public static function aUser(): self    { return new self(); }
    public static function anAdmin(): self  { return (new self())->asAdmin(); }
    public static function aLibrarian(): self { return (new self())->asLibrarian(); }

    public function withEmail(string $email): self
    {
        $clone = clone $this;
        $clone->data['email'] = $email;
        return $clone;
    }

    public function withName(string $name): self
    {
        $clone = clone $this;
        $clone->data['name'] = $name;
        return $clone;
    }

    public function asAdmin(): self
    {
        $clone = clone $this;
        $clone->data['role'] = 'admin';
        return $clone;
    }

    public function asLibrarian(): self
    {
        $clone = clone $this;
        $clone->data['role'] = 'librarian';
        return $clone;
    }

    public function inactive(): self
    {
        $clone = clone $this;
        $clone->data['is_active'] = 0;
        return $clone;
    }

    public function build(): array { return $this->data; }
}
```

```php
<?php
// tests/Builders/LoanBuilder.php
declare(strict_types=1);

namespace Tests\Builders;

final class LoanBuilder
{
    private array $data = [
        'user_id'     => 1,
        'book_id'     => 1,
        'status'      => 'active',
        'loan_date'   => '',
        'due_date'    => '',
        'return_date' => null,
        'fine_amount' => '0.00',
        'deleted_at'  => null,
    ];

    public function __construct()
    {
        $this->data['loan_date'] = date('Y-m-d');
        $this->data['due_date']  = date('Y-m-d', strtotime('+14 days'));
    }

    public static function aLoan(): self { return new self(); }

    public function forUser(int $userId): self
    {
        $clone = clone $this;
        $clone->data['user_id'] = $userId;
        return $clone;
    }

    public function forBook(int $bookId): self
    {
        $clone = clone $this;
        $clone->data['book_id'] = $bookId;
        return $clone;
    }

    public function overdue(int $daysAgo = 5): self
    {
        $clone = clone $this;
        $clone->data['loan_date'] = date('Y-m-d', strtotime("-{$daysAgo} days"));
        $clone->data['due_date']  = date('Y-m-d', strtotime('-1 day'));
        return $clone;
    }

    public function returned(): self
    {
        $clone = clone $this;
        $clone->data['status']      = 'returned';
        $clone->data['return_date'] = date('Y-m-d');
        return $clone;
    }

    public function withFine(string $amount): self
    {
        $clone = clone $this;
        $clone->data['fine_amount'] = $amount;
        return $clone;
    }

    public function build(): array { return $this->data; }
}
```

---

## Unit Tests — Services

```php
<?php
// tests/Unit/Services/LoanServiceTest.php
declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Services\LoanService;
use Tests\Builders\BookBuilder;
use Tests\Builders\UserBuilder;
use Tests\Builders\LoanBuilder;
use Tests\Repositories\InMemory\InMemoryBookRepository;
use Tests\Repositories\InMemory\InMemoryLoanRepository;
use Tests\Repositories\InMemory\InMemoryUserRepository;

final class LoanServiceTest extends TestCase
{
    private LoanService            $service;
    private InMemoryBookRepository $books;
    private InMemoryLoanRepository $loans;
    private InMemoryUserRepository $users;

    protected function setUp(): void
    {
        $this->books   = new InMemoryBookRepository();
        $this->loans   = new InMemoryLoanRepository();
        $this->users   = new InMemoryUserRepository();
        $this->service = new LoanService($this->books, $this->loans, $this->users);
    }

    #[Test]
    public function it_creates_a_loan_when_book_is_available(): void
    {
        $bookId = $this->books->insert(BookBuilder::aBook()->withCopies(2)->build());
        $userId = $this->users->insert(UserBuilder::aUser()->build());

        $loanId = $this->service->create($userId, $bookId);

        $this->assertNotNull($loanId);
        $loan = $this->loans->findById($loanId);
        $this->assertSame('active', $loan['status']);
        $this->assertSame($userId, $loan['user_id']);
        $this->assertSame($bookId, $loan['book_id']);
    }

    #[Test]
    public function it_decrements_available_copies_on_loan(): void
    {
        $bookId = $this->books->insert(BookBuilder::aBook()->withCopies(3)->build());
        $userId = $this->users->insert(UserBuilder::aUser()->build());

        $this->service->create($userId, $bookId);

        $book = $this->books->findById($bookId);
        $this->assertSame(2, (int) $book['available']);
    }

    #[Test]
    public function it_throws_when_no_copies_available(): void
    {
        $bookId = $this->books->insert(BookBuilder::aBook()->unavailable()->build());
        $userId = $this->users->insert(UserBuilder::aUser()->build());

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('No hay ejemplares disponibles');

        $this->service->create($userId, $bookId);
    }

    #[Test]
    public function it_throws_when_user_has_overdue_loans(): void
    {
        $bookId      = $this->books->insert(BookBuilder::aBook()->build());
        $userId      = $this->users->insert(UserBuilder::aUser()->build());
        $anotherBook = $this->books->insert(BookBuilder::aBook()->withIsbn('999')->build());

        $this->loans->insert(LoanBuilder::aLoan()->forUser($userId)->forBook($anotherBook)->overdue()->build());

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('préstamos vencidos');

        $this->service->create($userId, $bookId);
    }

    #[Test]
    public function it_returns_a_loan_and_restores_availability(): void
    {
        $bookId = $this->books->insert(BookBuilder::aBook()->withCopies(1, 0)->build());
        $userId = $this->users->insert(UserBuilder::aUser()->build());
        $loanId = $this->loans->insert(LoanBuilder::aLoan()->forUser($userId)->forBook($bookId)->build());

        $this->service->return($loanId);

        $loan = $this->loans->findById($loanId);
        $book = $this->books->findById($bookId);

        $this->assertSame('returned', $loan['status']);
        $this->assertNotNull($loan['return_date']);
        $this->assertSame(1, (int) $book['available']);
    }

    #[Test]
    #[DataProvider('overdueDaysAndFines')]
    public function it_calculates_fine_correctly(int $daysOverdue, string $expectedFine): void
    {
        $fine = $this->service->calculateFine($daysOverdue, finePerDay: 0.50);
        $this->assertSame($expectedFine, number_format($fine, 2));
    }

    public static function overdueDaysAndFines(): array
    {
        return [
            'one day overdue'    => [1,  '0.50'],
            'one week overdue'   => [7,  '3.50'],
            'one month overdue'  => [30, '15.00'],
            'not overdue'        => [0,  '0.00'],
        ];
    }

    #[Test]
    public function it_prevents_duplicate_active_loan_for_same_book(): void
    {
        $bookId = $this->books->insert(BookBuilder::aBook()->withCopies(5)->build());
        $userId = $this->users->insert(UserBuilder::aUser()->build());

        $this->service->create($userId, $bookId);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('ya tiene este libro en préstamo');

        $this->service->create($userId, $bookId);
    }
}
```

---

## Unit Tests — Core Components

```php
<?php
// tests/Unit/Core/ValidatorTest.php
declare(strict_types=1);

namespace Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Core\Validator;

final class ValidatorTest extends TestCase
{
    #[Test]
    public function it_passes_when_all_rules_are_satisfied(): void
    {
        $v = new Validator();
        $result = $v->validate(
            ['email' => 'user@example.com', 'name' => 'Ana'],
            ['email' => 'required|email', 'name' => 'required|min:2'],
        );
        $this->assertTrue($result);
        $this->assertEmpty($v->errors());
    }

    #[Test]
    public function it_fails_when_required_field_is_missing(): void
    {
        $v = new Validator();
        $v->validate(['name' => ''], ['name' => 'required']);
        $this->assertTrue($v->fails());
        $this->assertNotEmpty($v->errors()['name']);
    }

    #[Test]
    #[DataProvider('invalidEmails')]
    public function it_rejects_invalid_emails(string $email): void
    {
        $v = new Validator();
        $v->validate(['email' => $email], ['email' => 'email']);
        $this->assertTrue($v->fails());
    }

    public static function invalidEmails(): array
    {
        return [
            ['not-an-email'],
            ['missing@'],
            ['@nodomain.com'],
            ['spaces in@email.com'],
        ];
    }

    #[Test]
    public function it_validates_min_and_max_length(): void
    {
        $v = new Validator();
        $v->validate(['pass' => 'abc'], ['pass' => 'min:8|max:20']);
        $this->assertTrue($v->fails());
        $this->assertNotEmpty($v->first('pass'));
    }
}
```

```php
<?php
// tests/Unit/Auth/AccessControlTest.php
declare(strict_types=1);

namespace Tests\Unit\Auth;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Auth\AccessControl;
use Auth\Permission;
use Auth\Role;

final class AccessControlTest extends TestCase
{
    #[Test]
    #[DataProvider('adminPermissions')]
    public function admin_can_do_everything(Permission $permission): void
    {
        $this->assertTrue(AccessControl::can(Role::Admin->value, $permission));
    }

    public static function adminPermissions(): array
    {
        return array_map(fn($p) => [$p], Permission::cases());
    }

    #[Test]
    public function member_cannot_create_books(): void
    {
        $this->assertFalse(AccessControl::can(Role::Member->value, Permission::BooksCreate));
    }

    #[Test]
    public function librarian_cannot_delete_users(): void
    {
        $this->assertFalse(AccessControl::can(Role::Librarian->value, Permission::UsersDelete));
    }

    #[Test]
    public function guest_can_only_view_books(): void
    {
        $this->assertTrue(AccessControl::can(Role::Guest->value, Permission::BooksView));
        $this->assertFalse(AccessControl::can(Role::Guest->value, Permission::LoansCreate));
        $this->assertFalse(AccessControl::can(Role::Guest->value, Permission::UsersView));
    }
}
```

---

## Integration Tests — PDO Repositories

```php
<?php
// tests/Integration/Repositories/PdoBookRepositoryTest.php
declare(strict_types=1);

namespace Tests\Integration\Repositories;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Repositories\PdoBookRepository;
use Tests\Builders\BookBuilder;

final class PdoBookRepositoryTest extends TestCase
{
    private \PDO              $db;
    private PdoBookRepository $repo;

    protected function setUp(): void
    {
        $this->db   = \Core\Database::connect();
        $this->repo = new PdoBookRepository($this->db);
        $this->db->exec('START TRANSACTION');
    }

    protected function tearDown(): void
    {
        // Roll back after each test — DB is always clean
        $this->db->exec('ROLLBACK');
    }

    #[Test]
    public function it_persists_and_retrieves_a_book(): void
    {
        $data = BookBuilder::aBook()->withTitle('Don Quijote')->build();
        $id   = $this->repo->insert($data);

        $book = $this->repo->findById($id);

        $this->assertSame('Don Quijote', $book['title']);
        $this->assertSame($data['isbn'], $book['isbn']);
    }

    #[Test]
    public function it_soft_deletes_a_book(): void
    {
        $id = $this->repo->insert(BookBuilder::aBook()->build());

        $this->repo->softDelete($id);

        $this->assertNull($this->repo->findById($id));
    }

    #[Test]
    public function it_updates_a_book(): void
    {
        $id = $this->repo->insert(BookBuilder::aBook()->build());

        $this->repo->update($id, ['title' => 'Nuevo título']);

        $book = $this->repo->findById($id);
        $this->assertSame('Nuevo título', $book['title']);
    }
}
```

---

## Feature Tests — HTTP Layer

```php
<?php
// tests/Feature/Auth/LoginTest.php
declare(strict_types=1);

namespace Tests\Feature\Auth;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Feature tests simulate HTTP requests through the full stack.
 * Use for critical user flows: login, loan creation, search.
 */
final class LoginTest extends TestCase
{
    private \PDO $db;

    protected function setUp(): void
    {
        $this->db = \Core\Database::connect();
        $this->db->exec('START TRANSACTION');
        $this->seedUser();
    }

    protected function tearDown(): void
    {
        $this->db->exec('ROLLBACK');
    }

    #[Test]
    public function valid_credentials_authenticate_user(): void
    {
        $auth = new \Auth\AuthService(new \Repositories\PdoUserRepository($this->db));

        $result = $auth->attempt('test@biblioteca.com', 'password123');

        $this->assertTrue($result);
        $this->assertTrue($auth->check());
        $this->assertSame('member', $auth->role());
    }

    #[Test]
    public function invalid_password_is_rejected(): void
    {
        $auth = new \Auth\AuthService(new \Repositories\PdoUserRepository($this->db));

        $result = $auth->attempt('test@biblioteca.com', 'wrong_password');

        $this->assertFalse($result);
        $this->assertFalse($auth->check());
    }

    private function seedUser(): void
    {
        $this->db->prepare("
            INSERT INTO users (name, email, password_hash, role, member_number, is_active)
            VALUES ('Test User', 'test@biblioteca.com', ?, 'member', 'T-0001', 1)
        ")->execute([password_hash('password123', PASSWORD_BCRYPT)]);
    }
}
```

---

## Test Helpers

```php
<?php
// tests/TestCase.php — base class with common helpers
declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function assertArrayHasKeys(array $keys, array $array): void
    {
        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $array, "Expected key [{$key}] not found in array.");
        }
    }

    protected function assertSameDate(string $expected, string $actual): void
    {
        $this->assertSame(
            date('Y-m-d', strtotime($expected)),
            date('Y-m-d', strtotime($actual)),
        );
    }

    protected function assertIsOverdue(array $loan): void
    {
        $this->assertGreaterThan(0, strtotime($loan['due_date']) - time() < 0 ? 1 : 0,
            'Expected loan to be overdue but due_date is in the future.'
        );
    }

    /** Create a mock that verifies it was called exactly once */
    protected function expectCalledOnce(string $class, string $method): object
    {
        $mock = $this->createMock($class);
        $mock->expects($this->once())->method($method);
        return $mock;
    }
}
```

---

## Running Tests

```bash
# Install PHPUnit (VPS with Composer)
composer require --dev phpunit/phpunit

# Run all tests
./vendor/bin/phpunit

# Run only unit tests (fast — no DB)
./vendor/bin/phpunit --testsuite=Unit

# Run with coverage (requires Xdebug or PCOV)
XDEBUG_MODE=coverage ./vendor/bin/phpunit --coverage-html coverage/

# Run a specific test file
./vendor/bin/phpunit tests/Unit/Services/LoanServiceTest.php

# Run tests matching a filter
./vendor/bin/phpunit --filter "it_calculates_fine"

# Run on shared hosting (no Composer — use phpunit.phar)
wget https://phar.phpunit.de/phpunit-11.phar
php phpunit-11.phar --bootstrap tests/bootstrap.php tests/Unit/

# Stop on first failure (useful in TDD)
./vendor/bin/phpunit --stop-on-failure
```

---

## TDD Workflow

```
1. Write a failing test that describes the desired behavior
   → php phpunit LoanServiceTest.php --filter "it_throws_when_no_copies"

2. Run it — see it RED (test fails, class/method doesn't exist yet)

3. Write the minimum code to make it pass

4. Run again — see it GREEN

5. Refactor the implementation without breaking the test

6. Repeat for the next behavior
```

---

## Naming Conventions

```php
// Test class: <ClassUnderTest>Test
final class LoanServiceTest extends TestCase {}

// Method: it_<describes_behavior_in_plain_english>
public function it_creates_a_loan_when_book_is_available(): void {}
public function it_throws_when_no_copies_available(): void {}
public function it_calculates_fine_correctly(): void {}

// Arrange-Act-Assert structure
public function it_returns_book_and_restores_availability(): void
{
    // Arrange
    $bookId = $this->books->insert(BookBuilder::aBook()->build());

    // Act
    $this->service->return($loanId);

    // Assert
    $this->assertSame('returned', $this->loans->findById($loanId)['status']);
}
```

---

## Workflow

1. **Unit tests sin DB** — Services y lógica de dominio usan InMemory repositories; se ejecutan en < 1 segundo.
2. **Integration tests con transacción** — `START TRANSACTION` en `setUp()`, `ROLLBACK` en `tearDown()` — DB siempre limpia.
3. **Builders, no arrays manuales** — `BookBuilder::aBook()->withCopies(0)->build()` es legible y mantenible.
4. **Una aserción por concepto** — No agrupar 10 `assert` en un test; dividir en métodos separados.
5. **Nombrar por comportamiento** — `it_throws_when_no_copies_available` describe el dominio, no la implementación.
6. **Correr Unit suite en cada guardado** — Los tests unitarios deben ser tan rápidos que se ejecuten constantemente.
7. **Coverage como guía, no objetivo** — 80% de cobertura en servicios y repositorios; no obsesionarse con el 100%.
8. **Testear casos límite** — Libro sin copias, usuario inactivo, préstamo ya devuelto, multa de 0 días.
