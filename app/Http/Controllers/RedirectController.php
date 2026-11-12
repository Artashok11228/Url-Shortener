<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Link;
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
    public function __invoke(string $code): RedirectResponse
    {
        $link = Link::query()->where('code', $code)->first();

        if ($link === null || ! $link->is_active) {
            throw new NotFoundHttpException;
        }

        if ($link->isExpired()) {
            throw new HttpException(410, 'This short link has expired.');
        }

        return redirect()->away($link->original_url, 302);
    }
}
