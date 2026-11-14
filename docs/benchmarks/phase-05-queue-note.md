# فاز ۵ — تغییر شرایط اندازه‌گیری نسبت به فاز ۴

## چه چیزی عوض شد؟

روی مسیر موفق redirect، بعد از resolve/انقضا، رویداد `LinkClicked` dispatch می‌شود و Listener یک `RecordClickJob` به صف می‌فرستد.

- با `QUEUE_CONNECTION=redis`: هزینهٔ اضافه روی درخواست عمدتاً یک push به Redis است؛ INSERT کلیک روی worker است.
- با `QUEUE_CONNECTION=sync` (مثل `phpunit.xml`): job داخل همان درخواست اجرا می‌شود و INSERT هم روی latency همان درخواست می‌نشیند.

## چرا اعداد فاز ۴ را مستقیم با این فاز مقایسه نکنیم؟

شرایط آزمایش فاز ۴ (`docs/benchmarks/phase-04-results.md`) **بدون** ثبت کلیک بود. حالا حتی روی cache hit هم یک event + queue push داریم. اگر دوباره k6 بزنی، اختلاف ممکن است از queue/کلیک باشد نه از cache؛ پس baseline فاز ۴ و عدد جدید هم‌جنس نیستند مگر آزمایش را با همان تنظیمات صف و با/بدون listener جدا تکرار کنی.

## بررسی واقعی worker (نه فقط تست fake)

| مورد | مقدار |
|---|---|
| تاریخ | 2026-11-12 |
| صف | `QUEUE_CONNECTION=redis` روی Redis محلی `127.0.0.1:6379` |
| دستور | `php artisan queue:work redis --once` |
| نتیجه | طول صف قبل از worker = ۱؛ بعد از DONE = ۰؛ یک ردیف در `clicks` با `user_agent=curl/worker-check` |

جزئیات رفتار retry و احتمال ثبت تکراری در `README.md` و `LEARNING.md` آمده است.
