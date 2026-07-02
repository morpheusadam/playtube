<div align="center">
# 🎬 PlayTube — PHP Video CMS & Video Sharing Platform
### Launch your own YouTube‑style video sharing & streaming website with a fast, secure, API‑driven PHP video CMS.

<p align="center">
  <img src="upload/screenshots/unnamed.png" alt="PlayTube — PHP Video Sharing CMS" />
</p>

<p>
  <img src="https://img.shields.io/github/license/morpheusadam/PlayTube?style=for-the-badge&color=4c1" alt="License" />
  <img src="https://img.shields.io/github/stars/morpheusadam/PlayTube?style=for-the-badge&color=ffca28" alt="Stars" />
  <img src="https://img.shields.io/github/forks/morpheusadam/PlayTube?style=for-the-badge&color=42a5f5" alt="Forks" />
  <img src="https://img.shields.io/github/last-commit/morpheusadam/PlayTube?style=for-the-badge&color=8e44ad" alt="Last commit" />
  <img src="https://img.shields.io/github/repo-size/morpheusadam/PlayTube?style=for-the-badge&color=e67e22" alt="Repo size" />
</p>

<p>
  <img src="https://img.shields.io/badge/PHP-7.1%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP" />
  <img src="https://img.shields.io/badge/MySQL-MySQLi-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL" />
  <img src="https://img.shields.io/badge/Node.js-Socket.IO-339933?style=for-the-badge&logo=nodedotjs&logoColor=white" alt="Node.js" />
  <img src="https://img.shields.io/badge/JavaScript-ES6-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black" alt="JavaScript" />
  <img src="https://img.shields.io/badge/FFmpeg-Transcoding-007808?style=for-the-badge&logo=ffmpeg&logoColor=white" alt="FFmpeg" />
  <img src="https://img.shields.io/badge/REST-API-FF6C37?style=for-the-badge&logo=postman&logoColor=white" alt="REST API" />
</p>

#### 🌐 Languages

<a href="readme.md">English</a> ·
<a href="readme-fa.md">فارسی (Persian)</a> ·
<a href="readme-kurdish.md">Kurdî (Kurdish)</a>

</div>

---

## 📖 Overview

**PlayTube** is a complete, self‑hosted **PHP video CMS and video sharing platform** — the fastest way to build your own **YouTube‑style website** for uploading, streaming, and monetizing video. It ships as a ready‑to‑deploy PHP script with a full admin panel, a multi‑theme front end, and an advanced REST **API** that powers native **mobile apps**.

Out of the box you get user channels, subscriptions, comments, playlists, live streaming, Shorts, monetization (Pro plans, paid videos, wallet, ads & affiliates), and social login — backed by a MySQL database and **FFmpeg** transcoding. A lightweight **Node.js + Socket.IO** service adds real‑time features on top of the core PHP application.

PlayTube is built for **content creators, startups, agencies, and media companies** who want to own their platform instead of relying on third‑party hosts. It is **fast, secure, regularly updated**, and designed to scale from a single‑server install to a full production deployment behind Apache or Nginx.

> 🔎 **Keywords:** PHP video CMS, video sharing platform, YouTube clone, video streaming script, self‑hosted video site, live streaming PHP, video monetization, REST API video app, FFmpeg transcoding, PlayTube.

---

## ✨ Features

- 🎥 **Video sharing & streaming** — upload, transcode (FFmpeg), and stream videos with adaptive playback.
- 📡 **Live streaming & Shorts** — go live and publish short‑form vertical videos.
- 👤 **Channels & subscriptions** — user channels, subscribe feeds, watch history, liked & saved videos.
- 💬 **Engagement** — comments, posts/timeline, articles, hashtags, and notifications.
- 💰 **Monetization** — Pro upgrades, paid videos, wallet & transactions, ads, and an affiliate system.
- 📱 **Native mobile app support** — a full versioned REST **API** (`app_api/v1.0`) for iOS/Android clients.
- 🔐 **Social login & 2FA** — sign in via popular providers plus two‑factor authentication.
- 💳 **Payment gateways** — Stripe, Braintree, Authorize.Net, PayFast, 2Checkout, SecurionPay, iyzico, QIWI, Alipay and more.
- ☁️ **Flexible storage** — local, FTP, or Amazon S3 video storage.
- 🛠️ **Powerful admin panel** — dashboards, content moderation, analytics, and site configuration.
- 🎨 **Multi‑theme front end** — ships with `default` and `youplay` themes.
- ⚡ **Real‑time layer** — optional **Node.js + Socket.IO** service for live updates.
- 🌍 **Multi‑language** — English, Persian, and Kurdish documentation included.

---

## 🛠️ Tech Stack

| Layer | Technology |
| --- | --- |
| Backend | PHP 7.1+ (MySQLi) |
| Database | MySQL (`playtube.sql` schema) |
| Media | FFmpeg, getID3 |
| Real‑time | Node.js, Express, Socket.IO |
| Front end | JavaScript, HTML, CSS, theme engine |
| API | Versioned REST API for mobile apps |
| Web server | Apache (`.htaccess`) or Nginx (`nginx.conf`) |

---

## 🚀 Getting Started

### Prerequisites

- **PHP 7.1 or higher**
- **MySQLi**
- PHP extensions: **GD Library**, **mbstring**, **calendar**
- **cURL** enabled, with `allow_url_fopen`
- The **`shell_exec`** PHP function enabled (for FFmpeg)
- A web server (Apache or Nginx) and a MySQL database

### Installation

1. Upload all script files to your web server root (via FTP) or place them in your localhost root directory.
2. Open your browser and navigate to:

   ```text
   http://www.YOURSITE.com/install
   ```

3. Accept the Terms of Use and continue.
4. Make sure your server meets the listed requirements (the installer verifies them).
5. Fill in the installation form:

   - **Purchase Code** — your Envato purchase code
   - **SQL Host / Username / Password / Database** — your MySQL connection details
   - **Site URL** — e.g. `https://yoursite.com`
   - **Site Name**, **Site Title**, **Site E‑mail** (use a server email, not Gmail/Hotmail)
   - **Admin Username** and **Admin Password**

6. Click **Install** and wait — installation can take up to ~5 minutes.

### Using Nginx?

Copy the contents of the bundled `nginx.conf` into your server's root `nginx.conf`
(usually `/etc/nginx/nginx.conf`), then reload Nginx.

### Set up the cron job

After installation, add this to your server's crontab so background tasks run:

```bash
*/15 * * * * php -f {PATH_TO_SCRIPT}/cronjob.php > /dev/null 2>&1
```

Replace `{PATH_TO_SCRIPT}` with the absolute path, e.g. `/home/playtube/public_html`.

### Optional: real‑time Node.js service

```bash
cd nodejs
npm install
npm start
```

---

## 🗂️ Project Structure

```text
playtube/
├── index.php             # Front controller
├── admincp.php           # Admin control panel
├── api.php               # API entry point
├── ajax.php              # AJAX endpoints
├── cronjob.php           # Scheduled background tasks
├── secure_video.php      # Secure video delivery
├── playtube.sql          # Database schema
├── nginx.conf            # Nginx configuration sample
├── admin-panel/          # Admin dashboard UI & assets
├── app_api/v1.0/         # Versioned REST API for mobile apps
├── sources/              # Page/feature modules (watch, upload, live, shorts, …)
├── assets/libs/          # Payment, storage, social & media libraries
├── themes/               # Front-end themes (default, youplay)
├── nodejs/               # Node.js + Socket.IO real-time service
├── install/              # Web installer
└── upload/               # User uploads & screenshots
```

---

## 📸 Screenshots

<p align="center">
  <img src="upload/screenshots/Screenshot_1.png" alt="PlayTube screenshot 1" width="45%" />
  <img src="upload/screenshots/Screenshot_2.png" alt="PlayTube screenshot 2" width="45%" />
  <img src="upload/screenshots/Screenshot_3.png" alt="PlayTube screenshot 3" width="45%" />
  <img src="upload/screenshots/Screenshot_4.png" alt="PlayTube screenshot 4" width="45%" />
  <img src="upload/screenshots/Screenshot_5.png" alt="PlayTube screenshot 5" width="45%" />
</p>

---

## 📌 Current Version

**v3.1.1**

---

## 🤝 Contributing

Contributions are welcome! Read [`CONTRIBUTING.md`](CONTRIBUTING.md) and the
[Code of Conduct](CODE_OF_CONDUCT.md), then open an
[issue](https://github.com/morpheusadam/PlayTube/issues) or submit a pull request.
Please report security issues responsibly as described in [`SECURITY.md`](SECURITY.md).

## 📜 License

Distributed under the **MIT License**. See [`LICENSE`](LICENSE) for details.

---

## ⭐ Star History

<a href="https://star-history.com/#morpheusadam/PlayTube&Date">
  <img src="https://api.star-history.com/svg?repos=morpheusadam/PlayTube&type=Date" alt="Star History Chart" width="70%" />
</a>

---

<div align="center">

### 👤 Author — Morpheus Adam

Web developer & cheerful hacker · PHP · Laravel · Go

<p>
  <a href="https://github.com/morpheusadam"><img src="https://img.shields.io/badge/GitHub-morpheusadam-181717?style=for-the-badge&logo=github&logoColor=white" alt="GitHub" /></a>
  <a href="https://sam.zeonic.me"><img src="https://img.shields.io/badge/Website-sam.zeonic.me-4c1?style=for-the-badge&logo=googlechrome&logoColor=white" alt="Website" /></a>
  <a href="mailto:morpheusadam95@gmail.com"><img src="https://img.shields.io/badge/Email-Contact-D14836?style=for-the-badge&logo=gmail&logoColor=white" alt="Email" /></a>
</p>

⭐ **If PlayTube helps you launch your own video platform, please give it a star!** ⭐

</div>
