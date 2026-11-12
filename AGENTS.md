<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.4. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

# Pest

- This project uses Pest. Create tests with `php artisan make:test --pest {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.
- Do not delete tests or test files without approval. They are part of the application.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/pest` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

</laravel-boost-guidelines>

<!-- PHASED_DEVELOPMENT_START -->
# دستورالعمل توسعهٔ پروژه

این پروژه یک کوتاه‌کنندهٔ لینک با Laravel است و دو هدف دارد: یادگیری عملی توسعهٔ بک‌اند و ساخت یک پروژهٔ قابل ارائه در رزومه.
برنامهٔ فازها، معیارهای پذیرش و وضعیت پیشرفت در DEVELOPMENT.md قرار دارد. قبل از شروع هر کار آن را بخوان.
دستورهای موجود مخزن و قانون آموزشی .cursor/rules/learning.mdc را هم رعایت کن.

## سبک کدنویسی

- کد ساده، روان و قابل دنبال‌کردن بنویس؛ طوری که یک توسعه‌دهنده بتواند جریان آن را راحت توضیح بدهد.
- نام متغیرها و متدها روشن باشد و هر متد مسئولیت مشخصی داشته باشد.
- KISS را رعایت کن؛ ساده‌ترین راه‌حل درست و متناسب با مسئله را انتخاب کن.
- SOLID را متناسب با نیاز واقعی رعایت کن. برای رعایت ظاهری این اصول کلاس، interface یا لایهٔ اضافی نساز.
- از امکانات و الگوهای رایج Laravel استفاده کن.
- کد تکراری را زمانی جدا کن که مسئولیت مشترک آن روشن شده باشد.
- مقیاس‌پذیری را در طراحی دیتابیس، indexها، queryها و محدودیت مصرف منابع در نظر بگیر؛ بهینه‌سازی مخصوص فازهای بعد را زودتر اضافه نکن.
- کامنت فقط برای توضیح دلیل تصمیم یا رفتار غیرآشکار باشد.
- وابستگی جدید را فقط با نیاز روشن اضافه کن و دلیل آن را توضیح بده.

## شروع و ادامهٔ فاز

وقتی کاربر گفت «فاز N رو شروع کن»:

۱. قواعد مخزن، سند توسعه و وضعیت واقعی Git را بررسی کن.
۲. پیش‌نیازهای آن فاز و وضعیت فاز قبلی را بررسی کن.
۳. قبل از کدنویسی در ۳ تا ۵ جمله بگو چه می‌کنی، چرا و چطور آن را بررسی می‌کنی.
۴. اجرای همان فاز را با قدم‌های کوچک شروع کن؛ برای تصمیم‌های معمول دوباره تأیید نگیر.
۵. اگر ابهامی روی رفتار اصلی محصول اثر دارد، قبل از اجرای بخش وابسته سؤال کن.
۶. فقط همان فاز را انجام بده.

وقتی کاربر گفت «فاز N رو ادامه بده»، ابتدا کارهای انجام‌شده و باقی‌مانده را بررسی کن؛ از ابتدا شروع نکن.
به وضعیت ثبت‌شده در سند اکتفا نکن؛ آن را با کد و Git تطبیق بده. تغییرات قبلی کاربر را حفظ کن.

## Git و تحویل

- هر فاز یک branch مستقل و یک commit نهایی تمیز داشته باشد.
- نام branch را از DEVELOPMENT.md بگیر.
- branch جدید را از شاخهٔ اصلی به‌روز بساز. پیش از شروع فاز بعد، بررسی کن تغییرات لازم فاز قبلی وارد شاخهٔ اصلی شده باشند.
- اگر پیش‌نیاز فاز بعد هنوز merge نشده، وضعیت را توضیح بده و برای ادامهٔ وابسته از کاربر تصمیم بگیر.
- فقط تغییرات مربوط به همان فاز را commit کن. تغییرات نامرتبط کاربر را وارد commit نکن.
- پیش از commit، تست‌های مرتبط، Pint و تغییرات نهایی را بررسی کن.
- پس از موفقیت بررسی‌ها، commit و push را انجام بده؛ درخواست شروع فاز شامل اجازهٔ این دو کار است.
- خودت PR یا Merge Request نساز و merge نکن.
- پس از push، لینک ساخت PR یا Merge Request را متناسب با سرویس remote بفرست تا کاربر آن را بسازد. اگر ساخت این لینک ممکن نبود، لینک branch و شاخهٔ مقصد را بده.
- عنوان و توضیح کوتاه آمادهٔ درخواست ادغام را هم ارائه بده.
- بدون درخواست صریح، force push، بازنویسی تاریخچهٔ منتشرشده، deploy یا حذف داده انجام نده.
- اگر push یا اجرای بررسی‌ها ممکن نبود، علت و کار باقی‌مانده را دقیق گزارش بده. موفقیت را ادعا نکن.

## یادگیری

سطح توضیحات برای فردی با حدود یک سال تجربهٔ پراکنده با PHP و Laravel باشد.

- توضیحات فارسی، ساده، کوتاه و مرتبط با کار جاری باشند.
- قبل از پیاده‌سازی هر مفهوم مهم، با یک مثال کوتاه توضیح بده چه مسئله‌ای را حل می‌کند و چرا در این فاز لازم است.
- جایگزین‌های مهم و دلیل انتخاب راه‌حل را کوتاه توضیح بده.
- مطابق قانون آموزشی موجود، بعد از هر فیچر دو سؤال کوتاه برای بررسی یادگیری مطرح کن. اگر کاربر حالت تعاملی نخواست، اجرای بخش مستقل بعدی را معطل پاسخ نکن.
- اگر کاربر کدی نوشته، ابتدا آن را review کن و برای یادگیری راهنمایی کن.
- در پایان فاز توضیح بده مسیر اصلی درخواست چگونه کار می‌کند و کاربر بهتر است کدام چند فایل را بخواند.
- نکات یادگیری و علت تصمیم‌های مهم را همان موقع کوتاه در LEARNING.md ثبت کن.
- کد غیرضروری یا قابلیت اضافه را به اسم آموزش وارد پروژه نکن.

## تست و کیفیت

- تست‌ها را با Pest بنویس و روی رفتار قابل مشاهده و حالت‌های خطای مهم تمرکز کن.
- ورودی نامعتبر، دسترسی غیرمجاز و حالت‌های مرزی مرتبط را پوشش بده.
- برای راضی‌کردن تست، منطق اصلی را تضعیف نکن و تست ناموفق را بی‌دلیل حذف نکن.
- نتیجهٔ واقعی تست‌ها و محدودیت‌های بررسی را گزارش بده.
- هیچ secret یا اطلاعات اتصال واقعی وارد Git نکن.

## مستندات

- در فاز اول فقط README لازم برای راه‌اندازی و اجرای تست‌ها را بنویس.
- در طول فازها دستورهای اجرا را به‌روز نگه دار و نکات یادگیری و شواهد اندازه‌گیری را کوتاه ثبت کن.
- مستندات کامل پروژه را پس از تکمیل قابلیت‌ها در فاز آخر آماده کن.
- متن نهایی فارسی ساده و مناسب فرد تازه‌کار باشد. اصطلاح فنی ضروری را در اولین استفاده کوتاه توضیح بده.
- ادعاهای عملکرد و مقیاس‌پذیری باید به شواهد واقعی متکی باشند.

## گزارش پایان فاز

گزارش شامل این موارد باشد:

- چه چیزی پیاده‌سازی شد.
- نتیجهٔ تست‌ها و Pint.
- نکتهٔ آموزشی و سؤال‌های کوتاه.
- محدودیت یا کار باقی‌مانده، در صورت وجود.
- نام branch و شناسهٔ commit.
- لینک ساخت درخواست ادغام یا لینک branch.
- عنوان و توضیح پیشنهادی درخواست ادغام.

وضعیت و خلاصهٔ لازم برای ادامه در چت جدید را پیش از commit در DEVELOPMENT.md ثبت کن. نتیجهٔ push را در پیام تحویل اعلام کن.
بعد از تحویل متوقف شو و بدون درخواست کاربر وارد فاز بعد نشو.
<!-- PHASED_DEVELOPMENT_END -->
