<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ResolveLink;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RedirectController extends Controller
{
    /**
     * Resolve a short code and send the visitor to the original URL.
     *
     * Missing, soft-deleted, and inactive links look the same to the visitor (404).
     * Expired links return 410 so the client can tell "gone" from "never existed".
     */
    public function __invoke(string $code, ResolveLink $resolveLink): RedirectResponse
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

        return redirect()->away($link->original_url, 302);
    }
}
