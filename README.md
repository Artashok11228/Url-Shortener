# URL Shortener

کوتاه‌کنندهٔ لینک با Laravel برای یادگیری بک‌اند و ارائه در رزومه.

## پیش‌نیازها

- Docker Desktop (روی ویندوز با WSL2)
- Composer و PHP 8.3+ فقط اگر بخواهی خارج از Docker کار کنی

## راه‌اندازی با Sail (پیشنهادی)

Sail همان Docker Compose مخصوص Laravel است: اپ، MySQL و Redis را با هم بالا می‌آورد.

```bash
cp .env.example .env
composer install
php artisan sail:install   # فقط اگر compose.yaml هنوز نداری
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
```

اگر روی ویندوز از Git Bash استفاده می‌کنی و `sail` خطا داد، دستورها را داخل WSL اجرا کن.

برنامه معمولاً روی `http://localhost` در دسترس است.

### بررسی اتصال

```bash
./vendor/bin/sail artisan db:show
./vendor/bin/sail redis redis-cli ping
```

خروجی Redis باید `PONG` باشد.

## اجرای محلی بدون Docker

برای توسعهٔ سریع می‌توانی SQLite استفاده کنی. از فاز ۴ برای redirect باید `CACHE_STORE=redis` باشد (و Redis در حال اجرا). صف هنوز `sync` است تا فاز ۵.

```bash
cp .env.example .env
# DB_CONNECTION=sqlite و CACHE_STORE=file و QUEUE_CONNECTION=sync
composer install
php artisan key:generate
touch database/database.sqlite   # اگر لازم بود
php artisan migrate
php artisan serve
```

## تست و قالب‌بندی

```bash
# با Sail
./vendor/bin/sail artisan test
./vendor/bin/sail pint

# بدون Sail
php artisan test
vendor/bin/pint
```

تست‌ها با Pest اجرا می‌شوند. Pint فقط استایل کد را یکدست می‌کند؛ جایگزین تست نیست.

## وضعیت فازها

- فاز ۱: Docker/Sail با MySQL و Redis؛ در آن فاز cache هنوز file بود.
- فاز ۲: ساخت/ویرایش/حذف لینک، alias، انقضا، و redirect عمومی `GET /{code}`.
- فاز ۳: ورود با توکن Sanctum، مالکیت لینک، و کنترل دسترسی با Policy (کاربر / ادمین).
- فاز ۴: cache ردیاب redirect با Redis، ابطال هنگام ویرایش/حذف، و اندازه‌گیری با k6.
- برنامهٔ فازها در `DEVELOPMENT.md` و نکات یادگیری در `LEARNING.md` است.

### Cache و اندازه‌گیری (فاز ۴)

Redirect از `LinkCache` می‌خواند (`links:code:{code}`). با ویرایش یا حذف لینک، همان کلید پاک می‌شود. انقضا روی هر درخواست (حتی cache hit) دوباره چک می‌شود.

```bash
# نمونهٔ اندازه‌گیری (دنبال‌کردن Location خاموش است)
k6 run -e BASE_URL=http://127.0.0.1:8000 -e CODE=bench -e VUS=1 -e DURATION=20s scripts/redirect-bench.js
```

نتایج واقعی این فاز: [`docs/benchmarks/phase-04-results.md`](docs/benchmarks/phase-04-results.md).

### احراز هویت API (فاز ۳)

روش انتخاب‌شده: **توکن Bearer با Sanctum** (مناسب مصرف‌کنندهٔ API تا قبل از داشبورد).

کاربر نمونه بعد از `php artisan db:seed`:

- ادمین: `admin@example.com` / `password`
- کاربر: `demo@example.com` / `password`

```bash
# ورود و دریافت توکن
curl -X POST http://localhost/api/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"demo@example.com","password":"password"}'

# پروفایل جاری
curl http://localhost/api/me \
  -H "Authorization: Bearer TOKEN" \
  -H "Accept: application/json"

# خروج (حذف توکن جاری)
curl -X POST http://localhost/api/logout \
  -H "Authorization: Bearer TOKEN" \
  -H "Accept: application/json"
```

### API لینک

مدیریت لینک‌ها زیر `/api/links` است و به توکن نیاز دارد. Redirect عمومی است و auth نمی‌خواهد.
کاربر فقط لینک‌های خودش را می‌بیند/ویرایش می‌کند؛ ادمین همه را.

```bash
# ساخت لینک (نیاز به Bearer token)
curl -X POST http://localhost/api/links \
  -H "Authorization: Bearer TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"original_url":"https://example.com","alias":"my-launch"}'

# باز کردن لینک کوتاه
curl -I http://localhost/my-launch
```

رفتار redirect: فعال → `302`؛ ناموجود/حذف‌شده/غیرفعال → `404`؛ منقضی → `410`.
