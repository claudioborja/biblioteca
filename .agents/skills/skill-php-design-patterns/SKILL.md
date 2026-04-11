---
name: skill-php-design-patterns
description: "**WORKFLOW SKILL** — Professional PHP design patterns implementation at expert level. USE FOR: applying Gang of Four (GoF) patterns in PHP; architectural patterns (MVC, ADR, CQRS, Event Sourcing, Hexagonal Architecture, DDD); SOLID principles enforcement; dependency injection and IoC containers; repository and specification patterns; service layer design; domain modeling in PHP; refactoring legacy PHP to clean architecture; framework-agnostic pattern implementation (Laravel, Symfony, Slim, custom); interface and abstract class design; PHP 8.x features in pattern implementation (enums, fibers, readonly, intersection types, named arguments). INVOKES: file system tools, codebase exploration subagents. DO NOT USE FOR: frontend/UI concerns; SQL query optimization unrelated to repository pattern; DevOps or deployment."
---

# PHP Design Patterns — Professional Implementation

## Core Philosophy

- **SOLID first**: Every pattern is a tool to enforce SOLID principles — apply it only when it solves a real problem.
- **PHP 8.x idiomatic**: Use constructor promotion, enums, readonly properties, named arguments, union/intersection types, fibers where they add clarity.
- **Framework-aware but framework-agnostic**: Implement patterns that work standalone; show framework integration (Laravel / Symfony) as a secondary layer.
- **Testability by design**: Every pattern implementation must be trivially unit-testable — no hidden dependencies, no static calls in business logic.
- **No over-engineering**: Apply the simplest pattern that solves the problem. A well-named function beats a misapplied pattern.

---

## Pattern Decision Matrix

| Problem | Pattern(s) to Apply |
|---------|---------------------|
| Object creation complexity | Factory Method, Abstract Factory, Builder, Prototype |
| Single instance requirement | Singleton (use sparingly — prefer DI container) |
| Algorithm interchangeability | Strategy |
| State-dependent behavior | State |
| Event-driven decoupling | Observer / Event Dispatcher |
| Conditional logic explosion | Chain of Responsibility, Command |
| Cross-cutting concerns | Decorator, Proxy, Middleware |
| Incompatible interfaces | Adapter, Facade |
| Tree structures / composites | Composite |
| Expensive object creation | Flyweight, Prototype, Object Pool |
| Complex subsystem simplification | Facade |
| Lazy initialization | Proxy, Virtual Proxy |
| Query encapsulation | Repository + Specification |
| Read/Write separation | CQRS |
| Undo/redo operations | Command + Memento |
| Domain event tracking | Event Sourcing |
| Port/Adapter isolation | Hexagonal Architecture |

---

## Creational Patterns

### Factory Method

```php
<?php

declare(strict_types=1);

interface Notifier
{
    public function send(string $message, string $recipient): void;
}

final class EmailNotifier implements Notifier
{
    public function send(string $message, string $recipient): void
    {
        // Send email
    }
}

final class SmsNotifier implements Notifier
{
    public function send(string $message, string $recipient): void
    {
        // Send SMS
    }
}

interface NotifierFactory
{
    public function create(): Notifier;
}

final class EmailNotifierFactory implements NotifierFactory
{
    public function create(): Notifier
    {
        return new EmailNotifier();
    }
}

final class SmsNotifierFactory implements NotifierFactory
{
    public function create(): Notifier
    {
        return new SmsNotifier();
    }
}
```

### Abstract Factory

```php
<?php

declare(strict_types=1);

interface Button
{
    public function render(): string;
}

interface Checkbox
{
    public function render(): string;
}

interface UIFactory
{
    public function createButton(): Button;
    public function createCheckbox(): Checkbox;
}

final class DarkButton implements Button
{
    public function render(): string { return '<button class="dark">'; }
}

final class DarkCheckbox implements Checkbox
{
    public function render(): string { return '<input type="checkbox" class="dark">'; }
}

final class DarkThemeFactory implements UIFactory
{
    public function createButton(): Button { return new DarkButton(); }
    public function createCheckbox(): Checkbox { return new DarkCheckbox(); }
}
```

### Builder

```php
<?php

declare(strict_types=1);

final class QueryBuilder
{
    private string $table = '';
    private array $conditions = [];
    private array $columns = ['*'];
    private ?int $limit = null;
    private ?string $orderBy = null;

    public function from(string $table): static
    {
        $clone = clone $this;
        $clone->table = $table;
        return $clone;
    }

    public function select(string ...$columns): static
    {
        $clone = clone $this;
        $clone->columns = $columns;
        return $clone;
    }

    public function where(string $condition): static
    {
        $clone = clone $this;
        $clone->conditions[] = $condition;
        return $clone;
    }

    public function limit(int $limit): static
    {
        $clone = clone $this;
        $clone->limit = $limit;
        return $clone;
    }

    public function orderBy(string $column): static
    {
        $clone = clone $this;
        $clone->orderBy = $column;
        return $clone;
    }

    public function build(): string
    {
        $sql = sprintf('SELECT %s FROM %s', implode(', ', $this->columns), $this->table);

        if ($this->conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $this->conditions);
        }
        if ($this->orderBy !== null) {
            $sql .= " ORDER BY {$this->orderBy}";
        }
        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }

        return $sql;
    }
}

// Usage — immutable builder (each method returns a new instance)
$query = (new QueryBuilder())
    ->from('users')
    ->select('id', 'name', 'email')
    ->where('active = 1')
    ->orderBy('name')
    ->limit(20)
    ->build();
```

---

## Structural Patterns

### Repository + Specification

```php
<?php

declare(strict_types=1);

// Domain entity
final class User
{
    public function __construct(
        public readonly int $id,
        public readonly string $email,
        public readonly bool $active,
        public readonly \DateTimeImmutable $createdAt,
    ) {}
}

// Specification interface
interface Specification
{
    public function isSatisfiedBy(mixed $candidate): bool;
    public function toExpression(): string;
}

final class ActiveUserSpecification implements Specification
{
    public function isSatisfiedBy(mixed $candidate): bool
    {
        return $candidate instanceof User && $candidate->active;
    }

    public function toExpression(): string
    {
        return 'active = 1';
    }
}

final class AndSpecification implements Specification
{
    public function __construct(
        private readonly Specification $left,
        private readonly Specification $right,
    ) {}

    public function isSatisfiedBy(mixed $candidate): bool
    {
        return $this->left->isSatisfiedBy($candidate)
            && $this->right->isSatisfiedBy($candidate);
    }

    public function toExpression(): string
    {
        return "({$this->left->toExpression()}) AND ({$this->right->toExpression()})";
    }
}

// Repository contract (Port)
interface UserRepository
{
    public function findById(int $id): ?User;
    public function findBySpecification(Specification $spec): array;
    public function save(User $user): void;
    public function delete(int $id): void;
}

// Concrete implementation (Adapter)
final class PdoUserRepository implements UserRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    public function findById(int $id): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ? $this->hydrate($row) : null;
    }

    public function findBySpecification(Specification $spec): array
    {
        $stmt = $this->pdo->query(
            "SELECT * FROM users WHERE {$spec->toExpression()}"
        );

        return array_map($this->hydrate(...), $stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    public function save(User $user): void { /* ... */ }
    public function delete(int $id): void { /* ... */ }

    private function hydrate(array $row): User
    {
        return new User(
            id: (int) $row['id'],
            email: $row['email'],
            active: (bool) $row['active'],
            createdAt: new \DateTimeImmutable($row['created_at']),
        );
    }
}
```

### Decorator

```php
<?php

declare(strict_types=1);

interface Logger
{
    public function log(string $level, string $message, array $context = []): void;
}

final class FileLogger implements Logger
{
    public function __construct(private readonly string $path) {}

    public function log(string $level, string $message, array $context = []): void
    {
        file_put_contents($this->path, "[{$level}] {$message}\n", FILE_APPEND);
    }
}

final class TimestampLoggerDecorator implements Logger
{
    public function __construct(private readonly Logger $inner) {}

    public function log(string $level, string $message, array $context = []): void
    {
        $ts = (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM);
        $this->inner->log($level, "[{$ts}] {$message}", $context);
    }
}

final class ContextLoggerDecorator implements Logger
{
    public function __construct(private readonly Logger $inner) {}

    public function log(string $level, string $message, array $context = []): void
    {
        $ctx = $context !== [] ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $this->inner->log($level, $message . $ctx, $context);
    }
}

// Composition
$logger = new TimestampLoggerDecorator(
    new ContextLoggerDecorator(
        new FileLogger('/var/log/app.log')
    )
);
```

### Proxy (Lazy + Caching)

```php
<?php

declare(strict_types=1);

interface ProductRepository
{
    public function findById(int $id): ?array;
}

final class DatabaseProductRepository implements ProductRepository
{
    public function findById(int $id): ?array
    {
        // Expensive DB query
        return ['id' => $id, 'name' => 'Product'];
    }
}

final class CachingProductRepositoryProxy implements ProductRepository
{
    private array $cache = [];

    public function __construct(private readonly ProductRepository $inner) {}

    public function findById(int $id): ?array
    {
        if (!isset($this->cache[$id])) {
            $this->cache[$id] = $this->inner->findById($id);
        }

        return $this->cache[$id];
    }
}
```

---

## Behavioral Patterns

### Strategy

```php
<?php

declare(strict_types=1);

interface PaymentStrategy
{
    public function charge(float $amount, array $paymentData): bool;
}

final class StripePayment implements PaymentStrategy
{
    public function charge(float $amount, array $paymentData): bool
    {
        // Stripe API call
        return true;
    }
}

final class PayPalPayment implements PaymentStrategy
{
    public function charge(float $amount, array $paymentData): bool
    {
        // PayPal API call
        return true;
    }
}

final class PaymentProcessor
{
    public function __construct(private PaymentStrategy $strategy) {}

    public function setStrategy(PaymentStrategy $strategy): void
    {
        $this->strategy = $strategy;
    }

    public function process(float $amount, array $data): bool
    {
        return $this->strategy->charge($amount, $data);
    }
}
```

### Observer / Event Dispatcher

```php
<?php

declare(strict_types=1);

// Domain Event
final class UserRegistered
{
    public function __construct(
        public readonly int $userId,
        public readonly string $email,
        public readonly \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {}
}

// Listener contract
interface EventListener
{
    public function handle(object $event): void;
}

final class SendWelcomeEmail implements EventListener
{
    public function handle(object $event): void
    {
        if (!$event instanceof UserRegistered) return;
        // Send email to $event->email
    }
}

final class CreateUserAuditLog implements EventListener
{
    public function handle(object $event): void
    {
        if (!$event instanceof UserRegistered) return;
        // Write audit log
    }
}

// Dispatcher
final class EventDispatcher
{
    /** @var array<class-string, EventListener[]> */
    private array $listeners = [];

    public function listen(string $eventClass, EventListener $listener): void
    {
        $this->listeners[$eventClass][] = $listener;
    }

    public function dispatch(object $event): void
    {
        foreach ($this->listeners[$event::class] ?? [] as $listener) {
            $listener->handle($event);
        }
    }
}

// Wiring
$dispatcher = new EventDispatcher();
$dispatcher->listen(UserRegistered::class, new SendWelcomeEmail());
$dispatcher->listen(UserRegistered::class, new CreateUserAuditLog());

$dispatcher->dispatch(new UserRegistered(userId: 1, email: 'user@example.com'));
```

### Command + Command Bus (CQRS-ready)

```php
<?php

declare(strict_types=1);

// Command (immutable intent)
final class CreateUserCommand
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
    ) {}
}

// Handler
interface CommandHandler
{
    public function handle(object $command): mixed;
}

final class CreateUserHandler implements CommandHandler
{
    public function __construct(private readonly UserRepository $users) {}

    public function handle(object $command): mixed
    {
        assert($command instanceof CreateUserCommand);

        // Domain logic here
        $user = new User(
            id: random_int(1, PHP_INT_MAX),
            email: $command->email,
            active: true,
            createdAt: new \DateTimeImmutable(),
        );

        $this->users->save($user);

        return $user->id;
    }
}

// Command Bus
final class CommandBus
{
    /** @var array<class-string, CommandHandler> */
    private array $handlers = [];

    public function register(string $commandClass, CommandHandler $handler): void
    {
        $this->handlers[$commandClass] = $handler;
    }

    public function dispatch(object $command): mixed
    {
        $handler = $this->handlers[$command::class]
            ?? throw new \RuntimeException("No handler for " . $command::class);

        return $handler->handle($command);
    }
}
```

### Chain of Responsibility (Middleware Pipeline)

```php
<?php

declare(strict_types=1);

interface Middleware
{
    public function process(array $request, callable $next): array;
}

final class AuthMiddleware implements Middleware
{
    public function process(array $request, callable $next): array
    {
        if (empty($request['token'])) {
            return ['status' => 401, 'body' => 'Unauthorized'];
        }
        return $next($request);
    }
}

final class RateLimitMiddleware implements Middleware
{
    public function process(array $request, callable $next): array
    {
        // Check rate limit
        return $next($request);
    }
}

final class Pipeline
{
    /** @param Middleware[] $stages */
    public function __construct(private readonly array $stages) {}

    public function run(array $request, callable $destination): array
    {
        $pipeline = array_reduce(
            array_reverse($this->stages),
            fn (callable $carry, Middleware $middleware): callable =>
                fn (array $req): array => $middleware->process($req, $carry),
            $destination,
        );

        return $pipeline($request);
    }
}
```

---

## Architectural Patterns

### Hexagonal Architecture (Ports & Adapters)

```
src/
├── Domain/                    # Pure PHP — no framework, no infrastructure
│   ├── Model/                 # Entities, Value Objects, Aggregates
│   ├── Repository/            # Interfaces (Ports)
│   ├── Service/               # Domain services
│   └── Event/                 # Domain events
├── Application/               # Use cases / Application services
│   ├── Command/               # Commands + Handlers
│   ├── Query/                 # Queries + Handlers
│   └── DTO/                   # Data Transfer Objects
├── Infrastructure/            # Adapters (framework, DB, external APIs)
│   ├── Persistence/           # Repository implementations
│   ├── Http/                  # Controllers, Middleware
│   ├── Messaging/             # Queue adapters
│   └── ServiceProvider/       # DI container wiring
└── Presentation/              # UI layer (CLI, REST, GraphQL)
```

**Rules:**
- Domain layer has **zero** dependencies on Infrastructure.
- Application layer depends only on Domain interfaces.
- Infrastructure depends on Application and Domain.
- Dependency arrows always point inward.

### CQRS

```php
<?php

declare(strict_types=1);

// Query (read side — no side effects)
final class GetActiveUsersQuery
{
    public function __construct(public readonly int $limit = 50) {}
}

final class GetActiveUsersHandler
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return array<int, array{id: int, name: string, email: string}> */
    public function handle(GetActiveUsersQuery $query): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, email FROM users WHERE active = 1 LIMIT :limit'
        );
        $stmt->execute(['limit' => $query->limit]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
```

### Value Object

```php
<?php

declare(strict_types=1);

final class Money
{
    public function __construct(
        private readonly int $amount,    // in cents
        private readonly string $currency,
    ) {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Amount cannot be negative.');
        }
        if (!in_array($currency, ['USD', 'EUR', 'GBP'], true)) {
            throw new \InvalidArgumentException("Unsupported currency: {$currency}");
        }
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);
        return new self($this->amount + $other->amount, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);
        return new self($this->amount - $other->amount, $this->currency);
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }

    public function format(): string
    {
        return number_format($this->amount / 100, 2) . ' ' . $this->currency;
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new \LogicException('Cannot operate on different currencies.');
        }
    }
}
```

### Aggregate Root (DDD)

```php
<?php

declare(strict_types=1);

abstract class AggregateRoot
{
    private array $domainEvents = [];

    protected function recordEvent(object $event): void
    {
        $this->domainEvents[] = $event;
    }

    public function releaseEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];
        return $events;
    }
}

final class Order extends AggregateRoot
{
    private array $items = [];
    private string $status = 'pending';

    public function __construct(private readonly string $id) {}

    public function addItem(string $productId, int $quantity, Money $price): void
    {
        if ($this->status !== 'pending') {
            throw new \DomainException('Cannot modify a non-pending order.');
        }

        $this->items[] = ['productId' => $productId, 'quantity' => $quantity, 'price' => $price];
        $this->recordEvent(new OrderItemAdded($this->id, $productId, $quantity));
    }

    public function confirm(): void
    {
        if ($this->items === []) {
            throw new \DomainException('Cannot confirm an empty order.');
        }

        $this->status = 'confirmed';
        $this->recordEvent(new OrderConfirmed($this->id));
    }
}
```

---

## PHP 8.x Pattern Enhancements

### Enums as Type-Safe State

```php
<?php

enum OrderStatus: string
{
    case Pending   = 'pending';
    case Confirmed = 'confirmed';
    case Shipped   = 'shipped';
    case Cancelled = 'cancelled';

    public function canTransitionTo(self $next): bool
    {
        return match($this) {
            self::Pending   => in_array($next, [self::Confirmed, self::Cancelled], true),
            self::Confirmed => $next === self::Shipped,
            default         => false,
        };
    }

    public function label(): string
    {
        return match($this) {
            self::Pending   => 'Pending Payment',
            self::Confirmed => 'Order Confirmed',
            self::Shipped   => 'Shipped',
            self::Cancelled => 'Cancelled',
        };
    }
}
```

### Readonly + Constructor Promotion

```php
<?php

final class ProductCreated
{
    public function __construct(
        public readonly string $productId,
        public readonly string $name,
        public readonly Money $price,
        public readonly \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {}
}
```

### First-Class Callable Syntax

```php
<?php

$users = [new User(1, 'a@b.com', true, new \DateTimeImmutable())];

// PHP 8.1+ first-class callable
$emails = array_map($this->extractEmail(...), $users);

private function extractEmail(User $user): string
{
    return $user->email;
}
```

---

## Dependency Injection Container Wiring

### Symfony (services.yaml)

```yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true

    App\Infrastructure\Persistence\PdoUserRepository:
        arguments:
            $pdo: '@PDO'

    App\Domain\Repository\UserRepository:
        alias: App\Infrastructure\Persistence\PdoUserRepository
```

### Laravel (AppServiceProvider)

```php
<?php

public function register(): void
{
    $this->app->bind(
        UserRepository::class,
        PdoUserRepository::class,
    );

    $this->app->singleton(EventDispatcher::class, function () {
        $dispatcher = new EventDispatcher();
        $dispatcher->listen(UserRegistered::class, new SendWelcomeEmail());
        return $dispatcher;
    });
}
```

---

## SOLID Enforcement Rules

| Principle | PHP Enforcement |
|-----------|----------------|
| **S**RP | One class = one reason to change. Max ~200 lines per class. |
| **O**CP | Extend via interfaces/abstract classes; never modify closed code. |
| **L**SP | Subtypes must honor the full contract of the parent interface. No weakened preconditions, no strengthened postconditions. |
| **I**SP | Interfaces ≤ 5 methods. Split large interfaces into role-specific ones. |
| **D**IP | Depend on abstractions (interfaces). Inject via constructor. No `new` in business logic. |

---

## Anti-Patterns to Avoid

| Anti-Pattern | Problem | Fix |
|--------------|---------|-----|
| God Class | Hundreds of unrelated methods in one class | Split by responsibility (SRP) |
| Static calls in business logic | Hidden dependency, untestable | Inject via interface |
| Anemic Domain Model | Entities are pure data bags, logic in services | Move behavior into entities |
| Service Locator | Pulls dependencies from container at runtime | Constructor injection |
| Singleton abuse | Global mutable state, hard to test | Register as singleton in DI container |
| Repository that returns query builders | Leaks infrastructure into domain | Return domain objects only |
| Fat Controller | Business logic in HTTP layer | Move to Application Service / Use Case |

---

## Workflow

1. **Explore** — Read existing code before proposing any pattern. Identify the architectural style already in use.
2. **Diagnose** — Name the actual problem (tangled logic? untestable code? coupling?). Choose the simplest pattern that solves it.
3. **Define contracts first** — Write interfaces before implementations.
4. **Domain stays pure** — No framework classes in Domain layer.
5. **One pattern at a time** — Introduce patterns incrementally; avoid big-bang rewrites.
6. **Test at boundaries** — Unit test domain logic; integration test repositories and adapters.
7. **Document the why** — Add a one-line comment on non-obvious pattern choices.
8. **Validate SOLID** — Before finishing, verify each SOLID principle is not violated by the new code.

---

## Testing Patterns

```php
<?php

// Repository test with in-memory implementation
final class InMemoryUserRepository implements UserRepository
{
    /** @var array<int, User> */
    private array $storage = [];

    public function findById(int $id): ?User
    {
        return $this->storage[$id] ?? null;
    }

    public function findBySpecification(Specification $spec): array
    {
        return array_values(array_filter($this->storage, $spec->isSatisfiedBy(...)));
    }

    public function save(User $user): void
    {
        $this->storage[$user->id] = $user;
    }

    public function delete(int $id): void
    {
        unset($this->storage[$id]);
    }
}

// Usage in PHPUnit
final class CreateUserHandlerTest extends TestCase
{
    public function test_creates_user_and_persists(): void
    {
        $repository = new InMemoryUserRepository();
        $handler    = new CreateUserHandler($repository);

        $handler->handle(new CreateUserCommand('John', 'john@example.com', 'secret'));

        $this->assertCount(1, $repository->findBySpecification(new ActiveUserSpecification()));
    }
}
```
