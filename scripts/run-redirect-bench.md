# اجرای اندازه‌گیری redirect

1. اپ را بالا بیاور (`sail up` یا `php artisan serve`).
2. یک لینک با کد ثابت بساز (مثلاً `bench`).
3. برای حالت «قبل از cache»: `CACHE_STORE=null` و سرویس را ری‌استارت کن.
4. برای حالت Redis: `CACHE_STORE=redis`؛ برای cold اول `redis-cli FLUSHDB`.
5. اجرا:

```bash
k6 run -e BASE_URL=http://127.0.0.1:8000 -e CODE=bench -e VUS=1 -e DURATION=20s -e LABEL=label-name scripts/redirect-bench.js
```

یا با ab:

```bash
./scripts/benchmark-redirect.sh http://127.0.0.1:8000/bench label-name 2000 20
```
