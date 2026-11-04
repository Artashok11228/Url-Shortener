# Plan

A small URL shortener with a JSON API. Users register, create short links, and
share them. Every visit to a short link is redirected and counted. Owners can
look at stats for their links. Admins can disable any link.

## Features

- Register and log in with a Sanctum personal access token.
- Create a short link from a long URL, optionally with a custom alias and an
  expiration date.
- Redirect `GET /{code}` to the original URL.
- Record each click (hashed IP, user agent, referrer, browser, device) in the
  background so the redirect stays fast.
- Per-link stats: total clicks, clicks per day for the last 30 days, top
  referrers, browsers and devices.
- List, update and delete your own links.
- QR code (SVG) for any link.
- Admin: list all links, disable or enable any link.

## API endpoints

All JSON endpoints live under `/api`. Authenticated routes expect
`Authorization: Bearer <token>`.

| Method | Path                            | Auth  | Purpose                                  |
|--------|---------------------------------|-------|------------------------------------------|
| POST   | /api/register                   | no    | Create account, returns token            |
| POST   | /api/login                      | no    | Returns token                            |
| POST   | /api/logout                     | user  | Revokes the current token                |
| GET    | /api/me                         | user  | Current user                             |
| GET    | /api/links                      | user  | Own links, paginated, filter and sort    |
| POST   | /api/links                      | user  | Create a link                            |
| GET    | /api/links/{link}               | owner | Show a link                              |
| PATCH  | /api/links/{link}               | owner | Update URL, alias, expiration, active    |
| DELETE | /api/links/{link}               | owner | Soft delete                              |
| GET    | /api/links/{link}/stats         | owner | Click statistics                         |
| GET    | /api/links/{link}/qr            | owner | SVG QR code of the short URL             |
| GET    | /api/admin/links                | admin | All links, paginated                     |
| PATCH  | /api/admin/links/{link}/disable | admin | Turn a link off                          |
| PATCH  | /api/admin/links/{link}/enable  | admin | Turn a link back on                      |
| GET    | /{code}                         | no    | Redirect to the original URL             |

Status codes for the redirect:

- `302` when the link is active and not expired.
- `404` when the code does not exist, is disabled, or was deleted.
- `410 Gone` when the link exists but has expired.

Error responses always have the same shape:

```json
{ "message": "Human readable text", "errors": { "field": ["..."] } }
```

`errors` is only present for validation failures.

## Database schema

```
users
  id, name, email (unique), password, role (enum: user|admin), timestamps

links
  id, user_id (fk users, cascade), original_url (text), code (varchar 32, unique),
  is_active (bool, default true), expires_at (nullable datetime),
  timestamps, deleted_at (soft delete)
  index: (user_id, created_at) for the owner's list
  index: expires_at for the purge command

clicks
  id, link_id (fk links, cascade), ip_hash (char 64), user_agent (nullable text),
  referrer (nullable text), country (nullable char 2), browser (nullable),
  device (nullable), clicked_at (datetime)
  index: (link_id, clicked_at) for per-day stats
  index: clicked_at for the purge command
```

Clicks have no `updated_at`. They are append-only facts, so a single
`clicked_at` column is enough.

## Architecture

Standard Laravel layering, nothing custom:

- `routes/api.php` and `routes/web.php` map URLs to controllers.
- Form Requests validate input. Custom rules (`SafeUrl`, `NotReservedAlias`)
  hold the tricky validation logic so it can be unit tested.
- Controllers are thin. They authorize via a Policy, call one Action, and return
  an API Resource.
- Action classes (`CreateLink`, `UpdateLink`, `RecordClick`) hold the business
  logic. They know nothing about HTTP.
- `ShortCodeGenerator` produces codes. `LinkResolver` finds the link for a code
  using Redis first and the database second.
- `RecordClickJob` is queued from the redirect so the visitor does not wait for
  the insert.
- `PurgeStaleData` is a scheduled command that removes expired links and old
  click rows.

### Why these choices

**Sanctum tokens instead of sessions.** The product is an API. Tokens are
stateless from the client's point of view, easy to test with curl, and Sanctum
ships with Laravel so there is no extra dependency.

**Redis for the redirect lookup.** The redirect is the hot path. A single
`GET` on Redis is faster than a database round trip and keeps the database
free for writes. The cache is invalidated whenever a link changes, so it is
never stale for longer than it takes the update request to finish.

**Queued click recording.** Writing to `clicks` on every visit would add a
database write to the hot path. Dispatching a job costs one Redis `LPUSH` and
the worker does the insert later. If the queue is down, redirects still work.

**Random base62 codes with collision retry.** Codes are 7 random characters
from `[a-zA-Z0-9]` (about 3.5 trillion combinations). The generator checks the
database and retries on the rare collision. Random codes do not leak how many
links exist, unlike encoding an auto-increment ID. Details in
`docs/DECISIONS.md`.

**302, not 301.** Browsers cache a 301 permanently, which means later clicks
never reach the server and are never counted, and a link that is disabled or
updated would keep redirecting from the browser cache. 302 is cheap enough.

**Soft deletes on links.** A deleted code should not be handed to another user
right away. Keeping the row (and the unique index on `code`) prevents that
without extra logic.

**Hashed IPs.** The raw IP is personal data. Hashing it with the app key as a
salt is enough to count unique visitors without storing who they were.

## Tooling

- MySQL in Docker (Laravel Sail), SQLite in memory for tests.
- Redis for cache and queue.
- PHPUnit for tests, Laravel Pint for formatting.
- GitHub Actions runs Pint and the test suite on every push.
