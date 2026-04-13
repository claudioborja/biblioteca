<?php
// app/Core/Validator.php — Validación de datos de entrada
declare(strict_types=1);

namespace Core;

final class Validator
{
    private array $errors = [];
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function make(array $data, array $rules): self
    {
        $validator = new self($data);
        $validator->validate($rules);
        return $validator;
    }

    public function validate(array $rules): void
    {
        foreach ($rules as $field => $fieldRules) {
            $ruleList = is_string($fieldRules) ? explode('|', $fieldRules) : $fieldRules;
            $value = $this->data[$field] ?? null;

            foreach ($ruleList as $rule) {
                $params = [];
                if (str_contains($rule, ':')) {
                    [$rule, $paramStr] = explode(':', $rule, 2);
                    $params = explode(',', $paramStr);
                }

                $method = 'rule' . ucfirst($rule);
                if (method_exists($this, $method)) {
                    $this->$method($field, $value, $params);
                }
            }
        }
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    // ── Reglas ──────────────────────────────────────────────────────────────

    private function ruleRequired(string $field, mixed $value): void
    {
        if ($value === null || $value === '' || $value === []) {
            $this->addError($field, "El campo {$field} es obligatorio.");
        }
    }

    private function ruleEmail(string $field, mixed $value): void
    {
        if ($value !== null && $value !== '' && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            $this->addError($field, "El campo {$field} debe ser un correo válido.");
        }
    }

    private function ruleMin(string $field, mixed $value, array $params): void
    {
        $min = (int) ($params[0] ?? 0);
        if (is_string($value) && mb_strlen($value) < $min) {
            $this->addError($field, "El campo {$field} debe tener al menos {$min} caracteres.");
        }
    }

    private function ruleMax(string $field, mixed $value, array $params): void
    {
        $max = (int) ($params[0] ?? 0);
        if (is_string($value) && mb_strlen($value) > $max) {
            $this->addError($field, "El campo {$field} no puede exceder {$max} caracteres.");
        }
    }

    private function ruleNumeric(string $field, mixed $value): void
    {
        if ($value !== null && $value !== '' && !is_numeric($value)) {
            $this->addError($field, "El campo {$field} debe ser numérico.");
        }
    }

    private function ruleInteger(string $field, mixed $value): void
    {
        if ($value !== null && $value !== '' && filter_var($value, FILTER_VALIDATE_INT) === false) {
            $this->addError($field, "El campo {$field} debe ser un número entero.");
        }
    }

    private function ruleIn(string $field, mixed $value, array $params): void
    {
        if ($value !== null && $value !== '' && !in_array($value, $params, true)) {
            $allowed = implode(', ', $params);
            $this->addError($field, "El campo {$field} debe ser uno de: {$allowed}.");
        }
    }
}
