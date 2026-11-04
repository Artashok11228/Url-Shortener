<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NotReservedAlias implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && in_array(strtolower($value), config('shortener.reserved_aliases'), true)) {
            $fail('The :attribute is reserved.');
        }
    }
}
