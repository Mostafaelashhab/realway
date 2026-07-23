# رفع EgTrain على Hostinger (Shared Hosting) — دليل مختصر

## 0) قبل الرفع (على جهازك)
```bash
npm run build          # يبني الأصول في public/build  (اتعمل ✅)
composer install --no-dev --optimize-autoloader   # vendor نضيف للإنتاج
```
ازيب المشروع **كامل** بما فيه: `vendor/` · `public/build/` · `public/enr/` · كل الكود.
(متزبّطش على git للرفع ده — vendor و build مستبعدين منه.)

## 1) قاعدة البيانات (hPanel > Databases > MySQL)
- اعمل database جديدة + user + password.
- سجّل: اسم الـ database و الـ user (بيبقوا بصيغة `uXXXX_egtrain`).

## 2) هيكل المجلدات (أهم نقطة في shared hosting)
مشكلة: الدومين بيفتح على `public_html`، بس Laravel لازم يفتح على `public/`.

**الحل الموصى به (أنضف):**
1. ارفع المشروع كله في مجلد جوه الهوم بره public_html، مثلاً: `/home/uXXXX/egtrain`
2. من hPanel: غيّر **Document Root** بتاع الدومين لـ `egtrain/public`
   (Hostinger: Website > Dashboard > Advanced > أو عند إضافة الدومين).

**لو مينفعش تغيّر الـ Document Root:**
- انقل **محتوى** `public/` جوه `public_html/`
- انقل باقي المشروع في `public_html/../egtrain_app/`
- عدّل `public_html/index.php`: خلّي المسارات تشاور على `egtrain_app` بدل `__DIR__.'/../'`.

## 3) ملف البيئة
- انسخ `.env.production` لملف اسمه `.env` جوه مجلد المشروع.
- املا `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` و `APP_URL`.
- `APP_KEY` موجود جاهز (أو `php artisan key:generate` لو عندك SSH).

## 4) أوامر ما بعد الرفع (SSH أو Terminal في hPanel)
```bash
php artisan migrate --force        # ينشئ الجداول
php artisan enr:import             # يستورد المحطات/القطارات/العربيات من public/enr
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
لو مفيش SSH: بعض الأوامر ممكن تتعمل عن طريق cron job لمرة واحدة، أو استخدم أداة Terminal في hPanel.

## 5) صلاحيات (لو ظهر خطأ 500)
```bash
chmod -R 775 storage bootstrap/cache
```

## 6) PHP version
اختَر **PHP 8.3+** من hPanel (MultiPHP / PHP Configuration).

## 7) الجداول (المواعيد)
- أول ما حد يبحث خط، التطبيق بيجيبه ويخزّنه أوتوماتيك (fallback).
- للتسخين المسبق للخطوط المشهورة (اختياري):
```bash
php artisan enr:harvest --delay=800        # الخطوط الرئيسية
```
- ممكن تعمله cron يومي/أسبوعي لتحديث الجداول.

## ملاحظات
- الكاش/الجلسات على قاعدة البيانات — مفيش احتياج Redis.
- التطبيق بيحتاج اتصال إنترنت خارج (للتوفّر الحي) — shared hosting بيسمح بيه عادة.
- APP_DEBUG=false في الإنتاج (متغيّرهاش لـ true على سيرفر عام).
