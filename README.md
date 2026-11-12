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

برای توسعهٔ سریع می‌توانی SQLite و cache فایل استفاده کنی (مثل `.env` فعلی توسعه). Redis در این حالت اختیاری است و در فاز ۱ برای cache یا queue استفاده نمی‌شود.

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

- فاز ۱: Docker/Sail با MySQL و Redis؛ `CACHE_STORE=file` و `QUEUE_CONNECTION=sync`.
- فاز ۲: ساخت/ویرایش/حذف لینک، alias، انقضا، و redirect عمومی `GET /{code}`.
- برنامهٔ فازها در `DEVELOPMENT.md` و نکات یادگیری در `LEARNING.md` است.

### API لینک (فاز ۲)

مدیریت لینک‌ها زیر `/api/links` است و فعلاً به توکن Sanctum نیاز دارد (از scaffold قبلی). Redirect عمومی است و auth نمی‌خواهد.

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
