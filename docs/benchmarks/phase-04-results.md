# فاز ۴ — نتایج اندازه‌گیری redirect

## ابزار

- **k6** (به‌جای ab): روی این ماشین Windows باینری محلی در `tools/` (gitignore) استفاده شد؛ اسکریپت تکرارپذیر: [`scripts/redirect-bench.js`](../../scripts/redirect-bench.js).
- `maxRedirects` / `redirects: 0` تا فقط پاسخ اول اپ (`302`) اندازه گرفته شود و سایت مقصد دنبال نشود.
- اسکریپت جایگزین ab: [`scripts/benchmark-redirect.sh`](../../scripts/benchmark-redirect.sh) (وقتی `ab` یا Docker در دسترس باشد).

## شرایط محیط

| مورد | مقدار |
|---|---|
| تاریخ (UTC تقریبی) | 2026-11-12 |
| OS | Windows 10 (Git Bash) |
| PHP | 8.4.27 via `php artisan serve` روی `127.0.0.1:8000` |
| DB | SQLite محلی (`.env` توسعه) |
| Redis | redis-server 5.0.14 Windows روی `127.0.0.1:6379` |
| لینک آزمایش | `GET /bench` → `302` به `https://example.com/bench-target` |
| حجم | ۱ لینک فعال؛ concurrency و مدت در هر اجرا مشخص شده |
| cold | قبل از اجرا `FLUSHDB` روی Redis |
| warm | حداقل یک درخواست موفق قبل از شروع k6 |

محدودیت مهم: `php artisan serve` تک‌نخی است. با ۲۰ VU صف طولانی می‌شود و زمان پاسخ را باد می‌کند؛ برای مقایسهٔ latency مسیر cache از `VUS=1` استفاده شد.

## خلاصهٔ اعداد

### هم‌زمانی ۲۰ (نشان‌دهندهٔ صف سرور، نه تفاوت cache)

| اجرا | req/s | avg duration | errors |
|---|---|---|---|
| before-null | 0.71 | 18.12s | 0% |
| after-redis-cold | 0.67 | 20.16s | 0% |
| after-redis-warm | 0.62 | 21.39s | 0% |

### تک‌درخواست (`VUS=1`) با `SESSION_DRIVER=database`

| اجرا | req/s | avg duration | errors |
|---|---|---|---|
| before-null-vus1 | 0.65 | 1.53s | 0% |
| after-redis-cold-vus1 | 0.57 | 1.74s | 0% |
| after-redis-warm-vus1 | 0.54 | 1.83s | 0% |

### تک‌درخواست با `SESSION_DRIVER=array` (حذف نوشتن session روی DB)

| اجرا | req/s | avg duration | errors |
|---|---|---|---|
| before-null-nosess-vus1 | 0.88 | 1.13s | 0% |
| after-redis-warm-nosess-vus1 | 0.75 | 1.32s | 0% |

خروجی خام در همین پوشه با پیشوند `k6-` ذخیره شده است.

## تفسیر

در این محیط، **بهبود واضحی از Redis دیده نشد**. علت محتمل: هزینهٔ bootstrap لاراول + `artisan serve` + (در حالت پیش‌فرض) نوشتن session دیتابیسی، خیلی بزرگ‌تر از یک `SELECT` روی یک ردیف لینک است. Redis همچنان برای correctness و فازهای بعدی (و محیط Sail/php-fpm واقعی) درست پیکربندی شده؛ صحت ابطال با تست Pest پوشش داده شده است.

ادعای سرعت بدون شاهد نمی‌کنیم؛ همین اعداد واقعی ثبت شدند حتی وقتی بهبودی نبود.
