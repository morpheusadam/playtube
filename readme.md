<p align="center">
  <img src="upload/screenshots/unnamed.png" alt="Screenshot 1">
</p>



## Languages
<p align="center">
  <a href="readme.md">English</a> |
  <a href="readme-fa.md">Persian</a> |
  <a href="readme-kurdish.md">Kurdish</a>
</p>


# PlayTube - The Ultimate PHP Video CMS & Video Sharing Platform

PlayTube is a video sharing & streaming PHP Script. It is the best way to start your own video sharing website like YouTube! Our platform is fast, secure, and it will be regularly updated. PlayTube fully supports native mobile apps, thanks to our advanced API system!

## Features
- Video sharing and streaming
- Native mobile app support
- Regular updates
- Secure and fast

## Requirements
- PHP 7.1 or Higher
- MySQLi
- GD Library PHP extension
- mbstring PHP extension
- calendar PHP extension
- shell_exec PHP function
- cURL + allow_url_fopen enabled

## Installation
Follow the steps below to set up your site:

1. Unzip the downloaded package and open the `/Script` folder to find all the script files. You will need to upload these files to your hosting web server using FTP or localhost in order to use it on your website.
2. The folder structure below should be uploaded to your website or localhost root directory:

   <p align="center">
     <img src="upload/screenshots/folder_structure.png" alt="Folder Structure">
   </p>

3. You should upload all the files.
4. Once you are done uploading, open your browser (Google Chrome is recommended).
5. Go to `http://www.YOURSITE.com/install`
6. Agree to the Terms of Use then click Next.
7. Important! Before we start the installation, please make sure you have the following installed on your server:

   <p align="center">
     <img src="upload/screenshots/server_requirements.png" alt="Server Requirements">
   </p>

8. On the second page, make sure to fill in the required data:

   <p align="center">
     <img src="upload/screenshots/installation_form.png" alt="Installation Form">
   </p>

   - **Purchase Code** - Envato purchase code. What is this?
   - **SQL Host name** - MySQL host name, e.g: localhost
   - **SQL Username** - MySQL username.
   - **SQL Password** - MySQL user password.
   - **SQL Database** - MySQL database name.
   - **Site URL** - Your Website URL, examples:
     - `https://siteurl.com`
     - `https://www.siteurl.com`
     - `https://siteurl.com`
     - `https://subdomain.siteurl.com`
     - `http://localhost`
     - `https://siteurl.com/subfolder`
   - **Site Name** - Your site name, max 32 characters.
   - **Site Title** - Your site title, max 100 characters.
   - **Site E-mail** - Your site email, e.g: info@yourdomain.com, Gmail or Hotmail is not supported. It should be one of your server's emails.
   - **Admin Username** - Choose your admin username.
   - **Admin Password** - Choose your admin password.

9. Once you have entered the information, please click the install button and wait for a while, the installation process may take up to 5 minutes.
10. We are ready to go!

### Using Nginx?
If your server is using Nginx, please follow the steps below:

1. Open your server's root `nginx.conf` file, most of the time it's located in: `/etc/nginx/nginx.conf`
2. Open the home directory of the script, you should be able to find this file `nginx.conf`.
3. Open the located file, and copy its content to your root `nginx.conf` file: `/etc/nginx/nginx.conf`
4. If you find it difficult to do, please contact your hosting provider, and they will do it for you easily.

### What's Next?
Important! After the installation is completed, you have to set the cronjob. Please use the command below and add it to your server's cronjob.

1. Open a Linux terminal or login through SSH.
2. Run: `crontab -e`
3. Add this code to the list: `*/15 * * * * php -f {PATH_TO_SCRIPT_FROM_ROOT}/cronjob.php > /dev/null 2>&1`.
   - Replace `{PATH_TO_SCRIPT_FROM_ROOT}` with the full path to the file, e.g: `/home/playtube/public_html/cronjob.php`
4. Save and exit.

If you are using cPanel, please follow these steps and replace the file name with `cronjob.php`, and make sure the cronjob runs once every 15 minutes.

## Current Version
v3.1.1

## Screenshots
Here are some screenshots of the platform:

<p align="center">
  <img src="upload/screenshots/Screenshot_1.png" alt="Screenshot 1">
  <img src="upload/screenshots/Screenshot_2.png" alt="Screenshot 2">
  <img src="upload/screenshots/Screenshot_3.png" alt="Screenshot 3">
  <img src="upload/screenshots/Screenshot_4.png" alt="Screenshot 4">
  <img src="upload/screenshots/Screenshot_5.png" alt="Screenshot 5">
</p>

## 📞 Contact Me
<div align="center">
    <a href="https://www.linkedin.com/in/hesam-ahmadpour" style="color: red; font-size: 20px; text-decoration: none;">LinkedIn</a> |
    <a href="https://t.me/morpheusadam" style="color: red; font-size: 20px; text-decoration: none;">Telegram</a>
</div>

