<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Link;

class UpdateLink
{
    /**
     * @param  array{original_url?: string, code?: string, expires_at?: ?string, is_active?: bool}  $attributes
     */
    public function handle(Link $link, array $attributes): Link
    {
        $link->fill($attributes)->save();

        return $link;
    }
}
