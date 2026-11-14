<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ResolveLink;
use App\Events\LinkClicked;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RedirectController extends Controller
{
    /**
     * Resolve a short code and send the visitor to the original URL.
     *
     * Missing, soft-deleted, and inactive links look the same to the visitor (404).
     * Expired links return 410 so the client can tell "gone" from "never existed".
     * Successful redirects fire LinkClicked so a queued job can store the click.
     */
    public function __invoke(string $code, Request $request, ResolveLink $resolveLink): RedirectResponse
    {
        $link = $resolveLink->handle($code);

        if ($link === null || ! $link->is_active) {
            throw new NotFoundHttpException;
        }

        // Check expiry on every request — even cache hits — so TTL cannot
        // keep an already-expired link redirecting until the key dies.
        if ($link->isExpired()) {
            throw new HttpException(410, 'This short link has expired.');
        }

        // Fire after we know the redirect will succeed, including cache hits.
        // The visitor does not wait for the INSERT; the worker does that later.
        LinkClicked::dispatch(
            $link->id,
            $request->ip() ?? '0.0.0.0',
            $request->userAgent(),
            $request->headers->get('referer'),
            now(),
        );

        return redirect()->away($link->original_url, 302);
    }
}
