<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Rules\NotReservedAlias;
use App\Rules\SafeUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLinkRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'original_url' => ['required', 'string', new SafeUrl],
            'alias' => ['nullable', 'string', 'min:3', 'max:32', 'alpha_dash:ascii', new NotReservedAlias, Rule::unique('links', 'code')],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
