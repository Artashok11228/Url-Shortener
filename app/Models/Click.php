<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ClickFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['link_id', 'ip_hash', 'user_agent', 'referrer', 'country', 'browser', 'device', 'clicked_at'])]
class Click extends Model
{
    /** @use HasFactory<ClickFactory> */
    use HasFactory;

    // A click is an immutable event, so created_at/updated_at add nothing.
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'clicked_at' => 'datetime',
        ];
    }

    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }
}
