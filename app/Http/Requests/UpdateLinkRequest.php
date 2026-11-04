<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Rules\NotReservedAlias;
use App\Rules\SafeUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLinkRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'original_url' => ['sometimes', 'required', 'string', new SafeUrl],
            'alias' => [
                'sometimes', 'required', 'string', 'min:3', 'max:32', 'alpha_dash:ascii', new NotReservedAlias,
                Rule::unique('links', 'code')->ignore($this->route('link')),
            ],
            'expires_at' => ['sometimes', 'nullable', 'date', 'after:now'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * The API talks about an "alias" but the column is called "code".
     *
     * @return array<string, mixed>
     */
    public function changes(): array
    {
        $validated = $this->validated();

        if (array_key_exists('alias', $validated)) {
            $validated['code'] = $validated['alias'];
            unset($validated['alias']);
        }

        return $validated;
    }
}
