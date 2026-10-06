# autohouseautomotive.com

The AutoHouse Automotive of Chicagoland website (Wood Dale, IL; hosted on cPanel as account `aachicago`).

- Standalone site: no shared includes and no RO Engine booking popup. The Request Appointment button goes to `schedule.php`, which emails the request.
- `schedule.php` is still only on the server; the rewrite will bring it into the repo.
- Sister site: [autohousenwa.com](https://autohousenwa.com) (`autohousenwa-web`).
- The host injects `<script src='/google_analytics_auto.js'>` into served pages. It isn't in the source; leave it out of commits.

Deploy: push here, then in cPanel → Git Version Control → Manage: "Update from Remote", "Deploy HEAD Commit" (`.cpanel.yml`). Deploys copy/overwrite; they never delete.

Test locally (host runs PHP 7.3; raise it in MultiPHP Manager before the rewrite): `docker run --rm -p 127.0.0.1:8089:80 -v "$PWD":/var/www/html:ro php:7.3-apache`
