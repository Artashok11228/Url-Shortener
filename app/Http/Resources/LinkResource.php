<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Link;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Link
 */
class LinkResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'short_url' => $this->shortUrl(),
            'original_url' => $this->original_url,
            'is_active' => $this->is_active,
            'is_expired' => $this->isExpired(),
            'expires_at' => $this->expires_at,
            'clicks_count' => $this->whenCounted('clicks'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
