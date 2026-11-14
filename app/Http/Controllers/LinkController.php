<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateLink;
use App\Actions\UpdateLink;
use App\Http\Requests\StoreLinkRequest;
use App\Http\Requests\UpdateLinkRequest;
use App\Http\Resources\LinkResource;
use App\Models\Link;
use App\Services\LinkCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class LinkController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Link::class);

        $user = $request->user();

        // Policy says "may list"; the query decides "which rows". Admins see everyone.
        $linksQuery = $user->isAdmin()
            ? Link::query()
            : $user->links();

        $links = $linksQuery->withCount('clicks')->latest()->paginate();

        return LinkResource::collection($links);
    }

    public function store(StoreLinkRequest $request, CreateLink $createLink): JsonResponse
    {
        Gate::authorize('create', Link::class);

        $link = $createLink->handle(
            $request->user(),
            $request->validated('original_url'),
            $request->validated('alias'),
            $request->date('expires_at'),
        );

        return LinkResource::make($link)->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Link $link): LinkResource
    {
        Gate::authorize('view', $link);

        return LinkResource::make($link->loadCount('clicks'));
    }

    public function update(UpdateLinkRequest $request, Link $link, UpdateLink $updateLink): LinkResource
    {
        Gate::authorize('update', $link);

        return LinkResource::make($updateLink->handle($link, $request->changes()));
    }

    public function destroy(Link $link, LinkCache $linkCache): Response
    {
        Gate::authorize('delete', $link);

        $code = $link->code;
        $link->delete();
        $linkCache->forget($code);

        return response()->noContent();
    }
}
