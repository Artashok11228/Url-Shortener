# URL Shortener

کوتاه‌کنندهٔ لینک با Laravel برای یادگیری بک‌اند و ارائه در رزومه.

با این پروژه می‌توانی لینک کوتاه بسازی، آن را عمومی باز کنی (redirect)، کلیک را در پس‌زمینه ثبت کنی، و در داشبورد وب لینک‌ها، تگ‌ها و تعداد کلیک را ببینی. مدیریت لینک از API با توکن Sanctum هم ممکن است. هدف آموزشی است: مسیر درخواست، اعتبارسنجی، دسترسی، Redis cache، صف، و جلوگیری از N+1.

برنامهٔ فازها در [`DEVELOPMENT.md`](DEVELOPMENT.md) و جزئیات یادگیری در [`LEARNING.md`](LEARNING.md) است.

## پیش‌نیازها

- **پیشنهادی:** Docker Desktop (روی ویندوز با WSL2) برای اجرای Sail
- **بدون Docker:** PHP 8.3+ (پروژه روی ۸.۴ توسعه داده شده)، Composer، و یک Redis در دسترس
- اختیاری برای تکرار اندازه‌گیری redirect: [k6](https://k6.io/)

## راه‌اندازی از صفر

### با Sail (پیشنهادی)

Sail همان Docker Compose مخصوص Laravel است: اپ، MySQL و Redis را با هم بالا می‌آورد. مقادیر پیش‌فرض `.env.example` برای همین محیط است (`DB_HOST=mysql`, `REDIS_HOST=redis`, `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis`).

```bash
cp .env.example .env
composer install
# فقط اگر compose.yaml هنوز نداری:
php artisan sail:install
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan db:seed
```

اگر روی ویندوز از Git Bash استفاده می‌کنی و `sail` خطا داد، دستورها را داخل WSL اجرا کن.

برنامه معمولاً روی `http://localhost` است.

بررسی اتصال:

```bash
./vendor/bin/sail artisan db:show
./vendor/bin/sail redis redis-cli ping
```

خروجی Redis باید `PONG` باشد. برای ثبت کلیک یک worker جدا لازم است:

```bash
./vendor/bin/sail artisan queue:work redis
```

### بدون Docker (محلی)

برای توسعهٔ سریع می‌توانی SQLite استفاده کنی. برای رفتار واقعی redirect و کلیک همچنان Redis لازم است.

```bash
cp .env.example .env
# در .env مثلاً:
# DB_CONNECTION=sqlite
# # و DB_HOST / DB_DATABASE مربوط به MySQL را خالی بگذار یا مطابق سند Laravel تنظیم کن
# CACHE_STORE=redis
# QUEUE_CONNECTION=redis
# REDIS_HOST=127.0.0.1
composer install
php artisan key:generate
touch database/database.sqlite   # اگر لازم بود
php artisan migrate
php artisan db:seed
php artisan serve
```

ترمینال دوم:

```bash
php artisan queue:work redis
```

**هشدار:** `migrate:fresh` همهٔ جداول را پاک می‌کند. برای demo روی دادهٔ روزمرهٔ خودت اجرا نکن؛ فقط روی محیط تازه یا دیتابیس جدا.

## دادهٔ نمونه و مسیر demo

بعد از `db:seed`:

| نقش | ایمیل | رمز |
|---|---|---|
| ادمین | `admin@example.com` | `password` |
| کاربر demo | `demo@example.com` | `password` |

لینک ثابت demo: `GET /laravel` → `https://laravel.com/docs` (با تگ‌های `docs` و `promo` و تعدادی کلیک از قبل).

### چک‌لیست demo (حدود ۵ دقیقه)

1. اپ و Redis را بالا بیاور؛ worker را روشن کن (`queue:work redis`).
2. مرورگر: `http://localhost/login` با `demo@example.com` / `password`.
3. `/dashboard` را باز کن؛ لینک `laravel`، تگ‌ها و تعداد کلیک را ببین.
4. از «Edit tags» یک تگ جدید اضافه کن و ذخیره کن.
5. در ترمینال دیگر: `curl -I http://localhost/laravel` — باید `302` ببینی.
6. با worker روشن، یک بار دیگر همان URL را باز کن و در داشبورد ببین شمارش کلیک زیاد شده (یا در دیتابیس جدول `clicks`).
7. API (اختیاری): با curl لاگین کن، لینک بساز، سپس `GET /{code}` را بزن.

ورود API و ساخت لینک:

```bash
curl -X POST http://localhost/api/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"demo@example.com","password":"password"}'

curl -X POST http://localhost/api/links \
  -H "Authorization: Bearer TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"original_url":"https://example.com","alias":"my-launch"}'

curl -I http://localhost/my-launch
```

رفتار redirect: فعال → `302`؛ ناموجود / حذف‌شده / غیرفعال → `404`؛ منقضی → `410`.

## مسیرهای اصلی (خواندن کد)

| مسیر | چه اتفاقی می‌افتد؟ | فایل‌های کلیدی |
|---|---|---|
| ساخت لینک | اعتبارسنجی URL امن، تولید یا alias برای `code`، ذخیره با مالک | `app/Actions/CreateLink.php`, `StoreLinkRequest`, `SafeUrl`, `ShortCodeGenerator`, `LinkPolicy` |
| Redirect + cache | خواندن از Redis یا دیتابیس، چک انقضا، `302`، ابطال cache هنگام ویرایش/حذف | `RedirectController`, `ResolveLink`, `LinkCache` |
| ثبت کلیک | بعد از redirect موفق، event → job روی صف Redis → INSERT در worker | `LinkClicked`, `DispatchRecordClickJob`, `RecordClickJob`, `RecordClick` |
| دسترسی | API با Bearer Sanctum؛ داشبورد با session؛ هر کاربر لینک خودش (ادمین همه) | `AuthController`, `WebAuthController`, `LinkPolicy` |
| داشبورد | لیست صفحه‌بندی‌شده با تگ و شمارش کلیک بدون N+1 | `DashboardController` (`with('tags')`, `withCount('clicks')`) |

برای شروع خواندن کد، همین ترتیب پیشنهاد می‌شود: `CreateLink` → `ResolveLink` / `LinkCache` → `RecordClickJob` → `DashboardController`.

## Cache، صف و worker

- **Cache:** کلید `links:code:{code}`. روی hit هم انقضا دوباره چک می‌شود. ویرایش یا حذف لینک کلید را پاک می‌کند.
- **صف:** بعد از redirect موفق، `LinkClicked` یک `RecordClickJob` به Redis می‌فرستد تا بازدیدکننده منتظر INSERT نماند.
- **retry:** تا ۳ بار با فاصلهٔ ۱، ۵، ۱۵ ثانیه. شکست نهایی در `failed_jobs` + warning در لاگ؛ redirect کاربر از قبل انجام شده.
- **ثبت تکراری:** صف at-least-once است؛ در حالت نادر ممکن است یک کلیک دو بار ذخیره شود. برای شمارش تقریبی این پروژه پذیرفته شده است.

نکته: اضافه شدن queue شرایط latency را نسبت به فاز ۴ عوض می‌کند؛ اعداد را مستقیم مقایسه نکن. توضیح: [`docs/benchmarks/phase-05-queue-note.md`](docs/benchmarks/phase-05-queue-note.md).

## شواهد اندازه‌گیری (cache و N+1)

ادعای سرعت بدون شرایط آزمایش نمی‌کنیم؛ نتایج واقعی ثبت شده‌اند.

### Redirect + Redis cache (فاز ۴)

- ابزار: k6، بدون دنبال‌کردن Location مقصد (`redirects: 0`).
- محیط: Windows، `php artisan serve` روی `127.0.0.1:8000`، SQLite محلی، Redis محلی.
- برای latency معنی‌دار از `VUS=1` استفاده شد؛ با ۲۰ VU عمدتاً صف سرور تک‌نخی دیده می‌شود.
- **نتیجه:** در این محیط بهبود واضحی از Redis دیده نشد؛ هزینهٔ bootstrap لاراول و سرور تک‌نخی از یک `SELECT` لینک بزرگ‌تر بود. صحت cache و ابطال با تست پوشش داده شده است.

جزئیات و جداول: [`docs/benchmarks/phase-04-results.md`](docs/benchmarks/phase-04-results.md).

تکرار تقریبی:

```bash
k6 run -e BASE_URL=http://127.0.0.1:8000 -e CODE=bench -e VUS=1 -e DURATION=20s scripts/redirect-bench.js
```

### N+1 در داشبورد (فاز ۶)

| حالت | ۵ لینک | ۲۰ لینک |
|---|---|---|
| lazy (مشکل‌دار) | ۱۱ query | ۴۱ query |
| eager (`with` + `withCount`) | ۲ query | ۲ query |

جزئیات: [`docs/benchmarks/phase-06-n1.md`](docs/benchmarks/phase-06-n1.md).

## تست و قالب‌بندی

```bash
# با Sail
./vendor/bin/sail artisan test
./vendor/bin/sail pint

# بدون Sail
php artisan test
vendor/bin/pint
```

تست‌ها با Pest روی SQLite حافظه‌ای (`:memory:`) اجرا می‌شوند و به دادهٔ لوکال تو دست نمی‌زنند. Pint فقط استایل PHP را یکدست می‌کند؛ جایگزین تست نیست.

## خارج از محدوده و ایدهٔ آینده

عمداً پیاده‌سازی نشده‌اند (مرز محصول آموزشی):

- Analytics پیشرفته و نمودار
- Rate limit سخت‌تر، captcha، تشخیص سوءاستفاده
- Idempotency key برای جلوگیری از کلیک تکراری
- Frontend جدا (SPA)
- دامنهٔ اختصاصی / چندمستأجری
- CRUD کامل تگ از طریق API (تگ فقط از داشبورد)

اگر بعداً ادامه بدهی، منطقی‌ترین قدم‌ها معمولاً idempotency کلیک و سخت‌کردن abuse روی ساخت لینک هستند.

## وضعیت یادگیری فازها (خلاصه)

1. پایه: Sail، MySQL، Redis، Pest، Pint
2. هستهٔ لینک: ساخت، alias، انقضا، redirect
3. کاربر و دسترسی: Sanctum، Policy، مالکیت
4. cache و اندازه‌گیری با k6
5. event و queue برای ثبت کلیک
6. داشبورد Blade، تگ، رفع N+1
7. آماده‌سازی ارائه و مستندات نهایی
