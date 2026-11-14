<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Link;
use App\Models\Tag;
use Illuminate\Support\Collection;

class SyncLinkTags
{
    /**
     * Replace the link's tags with the given names.
     * Tags always belong to the link owner so another user's tag names never attach.
     *
     * @param  list<string>  $names
     */
    public function handle(Link $link, array $names): Link
    {
        $owner = $link->user;

        // Normalize: trim, drop empties, unique within this request.
        $normalized = Collection::make($names)
            ->map(fn (string $name): string => trim($name))
            ->filter(fn (string $name): bool => $name !== '')
            ->unique()
            ->values();

        $tagIds = $normalized->map(function (string $name) use ($owner): int {
            /** @var Tag $tag */
            $tag = $owner->tags()->firstOrCreate(['name' => $name]);

            return $tag->id;
        });

        $link->tags()->sync($tagIds->all());

        return $link->load('tags');
    }
}
