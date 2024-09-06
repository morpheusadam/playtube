<p align="center">
  <img src="upload/screenshots/unnamed.png" alt="Screenshot 1">
</p>


## زبان‌ها
- [انگلیسی](readme.md)
- [فارسی](readme-fa.md)
- [کوردی](readme-kurdish.md)


# PlayTube - بهترین پلتفرم اشتراک‌گذاری ویدیو با PHP

PlayTube یک اسکریپت PHP برای اشتراک‌گذاری و پخش ویدیو است. این بهترین راه برای شروع وب‌سایت اشتراک‌گذاری ویدیو خودتان مانند YouTube است! پلتفرم ما سریع، امن و به‌طور منظم به‌روزرسانی می‌شود. PlayTube به‌طور کامل از اپلیکیشن‌های موبایل بومی پشتیبانی می‌کند، به لطف سیستم API پیشرفته ما!

## ویژگی‌ها
- اشتراک‌گذاری و پخش ویدیو
- پشتیبانی از اپلیکیشن موبایل بومی
- به‌روزرسانی‌های منظم
- سریع و امن

## نیازمندی‌ها
- PHP 7.1 یا بالاتر
- MySQLi
- افزونه PHP کتابخانه GD
- افزونه PHP mbstring
- افزونه PHP calendar
- تابع PHP shell_exec
- cURL + allow_url_fopen فعال

## نصب
مراحل زیر را برای راه‌اندازی سایت خود دنبال کنید:

1. بسته دانلود شده را از حالت فشرده خارج کنید و پوشه `/Script` را باز کنید تا تمام فایل‌های اسکریپت را پیدا کنید. شما باید این فایل‌ها را به سرور میزبانی وب خود با استفاده از FTP یا localhost آپلود کنید تا بتوانید از آن در وب‌سایت خود استفاده کنید.
2. ساختار پوشه زیر باید به دایرکتوری ریشه وب‌سایت یا localhost شما آپلود شود:

   <p align="center">
     <img src="upload/screenshots/folder_structure.png" alt="Folder Structure">
   </p>

3. باید تمام فایل‌ها را آپلود کنید.
4. پس از اتمام آپلود، مرورگر خود را باز کنید (Google Chrome توصیه می‌شود).
5. به `http://www.YOURSITE.com/install` بروید.
6. با شرایط استفاده موافقت کنید و سپس روی دکمه بعدی کلیک کنید.
7. مهم! قبل از شروع نصب، لطفاً مطمئن شوید که موارد زیر روی سرور شما نصب شده‌اند:

   <p align="center">
     <img src="upload/screenshots/server_requirements.png" alt="Server Requirements">
   </p>

8. در صفحه دوم، مطمئن شوید که اطلاعات مورد نیاز را پر کرده‌اید:

   <p align="center">
     <img src="upload/screenshots/installation_form.png" alt="Installation Form">
   </p>

   - **کد خرید** - کد خرید Envato. این چیست؟
   - **نام میزبان SQL** - نام میزبان MySQL، به عنوان مثال: localhost
   - **نام کاربری SQL** - نام کاربری MySQL.
   - **رمز عبور SQL** - رمز عبور کاربر MySQL.
   - **پایگاه داده SQL** - نام پایگاه داده MySQL.
   - **آدرس سایت** - آدرس وب‌سایت شما، مثال‌ها:
     - `https://siteurl.com`
     - `https://www.siteurl.com`
     - `https://siteurl.com`
     - `https://subdomain.siteurl.com`
     - `http://localhost`
     - `https://siteurl.com/subfolder`
   - **نام سایت** - نام سایت شما، حداکثر 32 کاراکتر.
   - **عنوان سایت** - عنوان سایت شما، حداکثر 100 کاراکتر.
   - **ایمیل سایت** - ایمیل سایت شما، به عنوان مثال: info@yourdomain.com، Gmail یا Hotmail پشتیبانی نمی‌شود. باید یکی از ایمیل‌های سرور شما باشد.
   - **نام کاربری مدیر** - نام کاربری مدیر خود را انتخاب کنید.
   - **رمز عبور مدیر** - رمز عبور مدیر خود را انتخاب کنید.

9. پس از وارد کردن اطلاعات، لطفاً روی دکمه نصب کلیک کنید و منتظر بمانید، فرآیند نصب ممکن است تا 5 دقیقه طول بکشد.
10. ما آماده شروع هستیم!

### استفاده از Nginx؟
اگر سرور شما از Nginx استفاده می‌کند، لطفاً مراحل زیر را دنبال کنید:

1. فایل `nginx.conf` ریشه سرور خود را باز کنید، بیشتر اوقات در: `/etc/nginx/nginx.conf` قرار دارد.
2. دایرکتوری اصلی اسکریپت را باز کنید، باید بتوانید این فایل `nginx.conf` را پیدا کنید.
3. فایل پیدا شده را باز کنید و محتوای آن را به فایل `nginx.conf` ریشه خود کپی کنید: `/etc/nginx/nginx.conf`
4. اگر انجام این کار برای شما دشوار است، لطفاً با ارائه‌دهنده میزبانی خود تماس بگیرید و آنها به راحتی این کار را برای شما انجام می‌دهند.

### مرحله بعدی چیست؟
مهم! پس از اتمام نصب، باید کرون‌جاب را تنظیم کنید. لطفاً از فرمان زیر استفاده کنید و آن را به کرون‌جاب سرور خود اضافه کنید.

1. یک ترمینال لینوکس باز کنید یا از طریق SSH وارد شوید.
2. اجرا کنید: `crontab -e`
3. این کد را به لیست اضافه کنید: `*/15 * * * * php -f {PATH_TO_SCRIPT_FROM_ROOT}/cronjob.php > /dev/null 2>&1`.
   - `{PATH_TO_SCRIPT_FROM_ROOT}` را با مسیر کامل فایل جایگزین کنید، به عنوان مثال: `/home/playtube/public_html/cronjob.php`
4. ذخیره کنید و خارج شوید.

اگر از cPanel استفاده می‌کنید، لطفاً این مراحل را دنبال کنید و نام فایل را به `cronjob.php` تغییر دهید و مطمئن شوید که کرون‌جاب هر 15 دقیقه یک بار اجرا می‌شود.

## نسخه فعلی
v3.1.1

## تصاویر
در اینجا تعدادی از تصاویر پلتفرم را مشاهده می‌کنید:

<p align="center">
  <img src="upload/screenshots/Screenshot_1.png" alt="Screenshot 1">
  <img src="upload/screenshots/Screenshot_2.png" alt="Screenshot 2">
  <img src="upload/screenshots/Screenshot_3.png" alt="Screenshot 3">
  <img src="upload/screenshots/Screenshot_4.png" alt="Screenshot 4">
  <img src="upload/screenshots/Screenshot_5.png" alt="Screenshot 5">
</p>

