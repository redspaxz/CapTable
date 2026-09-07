<?php

declare(strict_types=1);

namespace App\Core;

class Validator
{
    private array $errors = [];

    public function __construct(private array $data)
    {
    }

    public function required(string ...$fields): self
    {
        foreach ($fields as $field) {
            $value = $this->data[$field] ?? '';
            if ($value === '' || $value === null) {
                $this->errors[$field] = 'This field is required.';
            }
        }
        return $this;
    }

    public function positive(string ...$fields): self
    {
        foreach ($fields as $field) {
            $value = $this->data[$field] ?? null;
            if ($value !== null && $value !== '' && (!is_numeric($value) || (int) $value <= 0)) {
                $this->errors[$field] = 'Must be a positive whole number.';
            }
        }
        return $this;
    }

    public function date(string ...$fields): self
    {
        foreach ($fields as $field) {
            $value = $this->data[$field] ?? '';
            if ($value !== '' && !preg_match('#^\d{4}-\d{2}-\d{2}$#', (string) $value)) {
                $this->errors[$field] = 'Invalid date (YYYY-MM-DD).';
            }
        }
        return $this;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
