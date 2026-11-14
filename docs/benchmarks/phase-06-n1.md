# فاز ۶ — نمونهٔ N+1 و اصلاح با eager loading

تاریخ اندازه‌گیری: ۲۰۲۶-۱۱-۱۴  
محیط: SQLite محلی، `php artisan tinker` روی همان منطق بارگذاری لیست (بدون HTTP)

## چه چیزی شمارش شد؟

فقط queryهای SQL هنگام خواندن لینک‌ها و دسترسی به:

- رابطهٔ `tags`
- شمارش کلیک‌ها (`clicks()->count()` در نسخهٔ بد، یا `clicks_count` از `withCount` در نسخهٔ خوب)

Setup (ساخت کاربر/لینک/تگ/کلیک) در شمارش نیست.

## نسخهٔ مشکل‌دار (lazy loading)

```php
$links = $user->links()->latest()->get();

foreach ($links as $link) {
    $link->tags->count();      // یک SELECT به‌ازای هر لینک
    $link->clicks()->count();  // یک SELECT COUNT به‌ازای هر لینک
}
```

| تعداد لینک | تعداد query |
|---|---|
| ۵ | ۱۱ (= ۱ لیست + ۵ تگ + ۵ شمارش کلیک) |
| ۲۰ | ۴۱ (= ۱ لیست + ۲۰ تگ + ۲۰ شمارش کلیک) |

رشد تقریباً خطی است: حدود `1 + 2N`.

## نسخهٔ اصلاح‌شده (همان منطق داشبورد)

```php
$user->links()
    ->with('tags')
    ->withCount('clicks')
    ->latest()
    ->get();
```

| تعداد لینک | تعداد query |
|---|---|
| ۵ | ۲ |
| ۲۰ | ۲ |

`with('tags')` یک query برای همهٔ تگ‌های همان صفحه می‌زند.  
`withCount('clicks')` شمارش را به‌صورت subquery روی همان SELECT لینک‌ها می‌آورد، بدون hydrate کردن ردیف‌های `clicks`.

## داشبورد HTTP

`DashboardController@index` همین الگوی اصلاح‌شده را با `paginate(15)` استفاده می‌کند. صفحه‌بندی یک query اضافه برای `COUNT` کل ردیف‌ها می‌زند، ولی باز هم با زیاد شدن لینک‌های داخل یک صفحه، تعداد query خطی رشد نمی‌کند.

شواهد خودکار: `tests/Feature/DashboardQueryCountTest.php`.

## نتیجه

نسخهٔ نهایی کد فقط الگوی eager را نگه می‌دارد. اعداد بالا شواهد نسخهٔ دارای N+1 برای توضیح درخواست ادغام هستند.
