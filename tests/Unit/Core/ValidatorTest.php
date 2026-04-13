<?php
declare(strict_types=1);

namespace Tests\Unit\Core;

use Core\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    // ── required ────────────────────────────────────────────────────────────

    #[Test]
    public function it_passes_when_required_field_is_present(): void
    {
        $v = Validator::make(['name' => 'Ana'], ['name' => 'required']);
        $this->assertFalse($v->fails());
        $this->assertEmpty($v->errors());
    }

    #[Test]
    public function it_fails_when_required_field_is_empty_string(): void
    {
        $v = Validator::make(['name' => ''], ['name' => 'required']);
        $this->assertTrue($v->fails());
        $this->assertNotEmpty($v->firstError('name'));
    }

    #[Test]
    public function it_fails_when_required_field_is_null(): void
    {
        $v = Validator::make([], ['name' => 'required']);
        $this->assertTrue($v->fails());
    }

    // ── email ────────────────────────────────────────────────────────────────

    #[Test]
    public function it_passes_valid_email(): void
    {
        $v = Validator::make(['email' => 'user@example.com'], ['email' => 'email']);
        $this->assertFalse($v->fails());
    }

    #[Test]
    #[DataProvider('invalidEmails')]
    public function it_rejects_invalid_emails(string $email): void
    {
        $v = Validator::make(['email' => $email], ['email' => 'email']);
        $this->assertTrue($v->fails(), "Expected '{$email}' to be invalid");
    }

    public static function invalidEmails(): array
    {
        return [
            'missing domain'    => ['user@'],
            'missing local'     => ['@domain.com'],
            'no at sign'        => ['notanemail'],
            'spaces'            => ['user name@domain.com'],
            'double at'         => ['user@@domain.com'],
        ];
    }

    #[Test]
    public function it_skips_email_validation_when_value_is_empty(): void
    {
        // empty optional field should not trigger email error
        $v = Validator::make(['email' => ''], ['email' => 'email']);
        $this->assertFalse($v->fails());
    }

    // ── min / max ────────────────────────────────────────────────────────────

    #[Test]
    public function it_fails_when_string_is_below_min_length(): void
    {
        $v = Validator::make(['pass' => 'abc'], ['pass' => 'min:8']);
        $this->assertTrue($v->fails());
        $this->assertNotNull($v->firstError('pass'));
    }

    #[Test]
    public function it_passes_when_string_meets_min_length(): void
    {
        $v = Validator::make(['pass' => 'abcdefgh'], ['pass' => 'min:8']);
        $this->assertFalse($v->fails());
    }

    #[Test]
    public function it_fails_when_string_exceeds_max_length(): void
    {
        $v = Validator::make(['code' => str_repeat('x', 21)], ['code' => 'max:20']);
        $this->assertTrue($v->fails());
    }

    #[Test]
    public function it_passes_when_string_is_at_max_length(): void
    {
        $v = Validator::make(['code' => str_repeat('x', 20)], ['code' => 'max:20']);
        $this->assertFalse($v->fails());
    }

    // ── numeric / integer ────────────────────────────────────────────────────

    #[Test]
    public function it_passes_numeric_value(): void
    {
        $v = Validator::make(['qty' => '42'], ['qty' => 'numeric']);
        $this->assertFalse($v->fails());
    }

    #[Test]
    public function it_fails_non_numeric_value(): void
    {
        $v = Validator::make(['qty' => 'abc'], ['qty' => 'numeric']);
        $this->assertTrue($v->fails());
    }

    #[Test]
    public function it_passes_integer_value(): void
    {
        $v = Validator::make(['age' => '30'], ['age' => 'integer']);
        $this->assertFalse($v->fails());
    }

    #[Test]
    public function it_fails_float_for_integer_rule(): void
    {
        $v = Validator::make(['age' => '3.5'], ['age' => 'integer']);
        $this->assertTrue($v->fails());
    }

    // ── in ───────────────────────────────────────────────────────────────────

    #[Test]
    public function it_passes_when_value_is_in_allowed_list(): void
    {
        $v = Validator::make(['status' => 'active'], ['status' => 'in:active,inactive']);
        $this->assertFalse($v->fails());
    }

    #[Test]
    public function it_fails_when_value_is_not_in_allowed_list(): void
    {
        $v = Validator::make(['status' => 'deleted'], ['status' => 'in:active,inactive']);
        $this->assertTrue($v->fails());
    }

    // ── chained rules ────────────────────────────────────────────────────────

    #[Test]
    public function it_applies_multiple_rules_and_collects_all_errors(): void
    {
        $v = Validator::make(
            ['email' => '', 'name' => ''],
            ['email' => 'required|email', 'name' => 'required|min:2']
        );

        $this->assertTrue($v->fails());
        $this->assertNotEmpty($v->errors()['email']);
        $this->assertNotEmpty($v->errors()['name']);
    }

    #[Test]
    public function it_reports_no_errors_for_valid_full_form(): void
    {
        $v = Validator::make(
            ['email' => 'ana@lib.mx', 'name' => 'Ana García', 'role' => 'user'],
            ['email' => 'required|email', 'name' => 'required|min:2|max:100', 'role' => 'required|in:admin,librarian,user']
        );

        $this->assertFalse($v->fails());
    }
}
