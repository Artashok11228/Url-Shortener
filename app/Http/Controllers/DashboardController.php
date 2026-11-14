<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\SyncLinkTags;
use App\Http\Requests\SyncLinkTagsRequest;
use App\Models\Link;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Link::class);

        $user = $request->user();

        $linksQuery = $user->isAdmin()
            ? Link::query()
            : $user->links();

        // Eager load tags and click counts so the list does not N+1.
        $links = $linksQuery
            ->with('tags')
            ->withCount('clicks')
            ->latest()
            ->paginate(15);

        return view('dashboard.index', [
            'links' => $links,
        ]);
    }

    public function editTags(Link $link): View
    {
        Gate::authorize('update', $link);

        $link->load('tags');

        return view('dashboard.edit-tags', [
            'link' => $link,
        ]);
    }

    public function updateTags(SyncLinkTagsRequest $request, Link $link, SyncLinkTags $syncLinkTags): RedirectResponse
    {
        $names = preg_split('/\s*,\s*/', (string) $request->validated('tags'), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $syncLinkTags->handle($link, $names);

        return redirect()
            ->route('dashboard')
            ->with('status', 'Tags updated.');
    }
}
