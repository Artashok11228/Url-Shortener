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

## وضعیت فاز ۱

- Docker/Sail با MySQL و Redis آماده است.
- Redis در Docker بالا می‌آید، ولی `CACHE_STORE=file` و `QUEUE_CONNECTION=sync` است تا بعداً آگاهانه به Redis وصل شوند.
- برنامهٔ فازها در `DEVELOPMENT.md` و نکات یادگیری در `LEARNING.md` است.
