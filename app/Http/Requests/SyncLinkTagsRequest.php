<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Link;
use Illuminate\Foundation\Http\FormRequest;

class SyncLinkTagsRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Link $link */
        $link = $this->route('link');

        return $this->user()?->can('update', $link) ?? false;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            // Comma-separated names in one field; empty string clears all tags.
            'tags' => ['nullable', 'string', 'max:500'],
        ];
    }
}
