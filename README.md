# autohouseautomotive.com

The AutoHouse Automotive of Chicagoland website (Wood Dale, IL; hosted on cPanel as account `aachicago`).

- `index.html` is the homepage: a scroll-driven drive down the interstate into the Chicago skyline (canvas/WebGL, images in `assets/`), adapted from autohousenwa.com/concept. Its appointment buttons open a modal that posts to `schedule.php` with `ajax=1` (JSON reply); `/#book` opens the modal directly. The previous homepage is in `legacy/` (not deployed).
- Standalone site: no shared includes and no RO Engine booking popup. The Request Appointment button goes to `schedule.php`, which emails the request through PHP `mail()` from `mail@autohouseautomotive.com` (Reply-To = the customer).
- **Recipients live in `config.php` on the host only** (see `config.example.php`). This repo is public (cPanel clones over plain HTTPS), so never commit addresses, keys or passwords.
- The shop runs Shop-Ware. Online requests go to `schedule.php`, never ROe `/book`, until the shop actually uses ROe.
- Sister site: [autohousenwa.com](https://autohousenwa.com) (`autohousenwa-web`).
- The host injects `<script src='/google_analytics_auto.js'>` into served pages. It isn't in the source; leave it out of commits.

Deploy: push here, then in cPanel → Git Version Control → Manage: "Update from Remote", "Deploy HEAD Commit" (`.cpanel.yml`). Deploys copy/overwrite; they never delete.

Test locally (host runs PHP 7.3; `schedule.php` is tested on 7.3 and 8.3): `docker run --rm -p 127.0.0.1:8089:80 -v "$PWD":/var/www/html:ro php:7.3-apache`
