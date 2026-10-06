<?php
// Appointment request form for autohouseautomotive.com.
// GET shows the form; POST validates, emails the request to the shop, and
// shows the confirmation. Mail goes out through the host's PHP mail()
// (DKIM-signed for autohouseautomotive.com by the host).

// Recipients live in config.php, which exists ONLY on the host (this repo is
// public; personal addresses stay out of it). See config.example.php.
$config = is_file(__DIR__ . '/config.php') ? require __DIR__ . '/config.php' : [];
$appt_to = $config['appt_to'] ?? 'service@autohouseautomotive.com';

const APPT_FROM    = 'mail@autohouseautomotive.com';
const APPT_SUBJECT = 'Appointment Request (autohouseautomotive.com)';

date_default_timezone_set('America/Chicago');

function v(string $k): string {
    // ?service=... (from the homepage's service tiles) pre-fills the issue box.
    $fallback = $k === 'appt_issue' ? (string)($_GET['service'] ?? '') : '';
    return htmlspecialchars(trim((string)($_POST[$k] ?? $fallback)), ENT_QUOTES, 'UTF-8');
}
function sel(string $k, string $val, bool $default = false): string {
    if (!isset($_POST[$k])) return $default ? 'selected' : '';
    return (string)$_POST[$k] === $val ? 'selected' : '';
}
function field(string $k): string {
    // Single-line header-safe value.
    return trim(str_replace(["\r", "\n"], ' ', (string)($_POST[$k] ?? '')));
}

$sent  = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (trim((string)($_POST['website'] ?? '')) !== '') {
        $sent = true; // honeypot filled: pretend success, send nothing
    } else {
        $fname = field('appt_fname');
        $lname = field('appt_lname');
        $phone = field('appt_phone');
        $email = field('appt_email');
        $date  = field('appt_date');

        if ($fname === '' || $phone === '' || $date === '') {
            $error = 'Please fill in your first name, phone number, and preferred date.';
        } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'That email address doesn\'t look right. Please check it, or leave it blank.';
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $error = 'Please choose a preferred date.';
        } else {
            $time = field('appt_hour') . ':' . field('appt_minute') . field('appt_ampm');
            $contact = $phone . ($email !== '' ? ' | ' . $email : '') . ' (prefers: ' . field('appt_pref') . ')';
            $vehicle = trim(field('appt_year') . ' ' . field('appt_make') . ' ' . field('appt_model'));
            $body = "Appointment Request - AutoHouse Chicago\n"
                  . "Date/Time: $date @ $time\n"
                  . 'Client: ' . trim("$fname $lname") . "\n"
                  . "Contact: $contact\n"
                  . "Vehicle: $vehicle\n"
                  . 'Reason: ' . trim((string)($_POST['appt_issue'] ?? '')) . "\n\n"
                  . 'Sent: ' . date('g:iA M j, Y') . " (server time)\n";

            $headers = 'From: AutoHouse Website <' . APPT_FROM . ">\r\n"
                     . ($email !== '' ? "Reply-To: $email\r\n" : '')
                     . "Content-Type: text/plain; charset=UTF-8\r\n";

            if (mail($appt_to, APPT_SUBJECT, $body, $headers, '-f' . APPT_FROM)) {
                $sent = true;
            } else {
                $error = 'Sorry, your request could not be sent. Please call us at (630) 708-6586.';
            }
        }
    }
}

// The homepage's appointment modal posts with ajax=1 and wants JSON, not the page.
if (($_POST['ajax'] ?? '') === '1') {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['ok' => $sent, 'error' => $error]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Request an Appointment – AutoHouse Automotive | Wood Dale, IL</title>
<meta name="description" content="Schedule your auto repair appointment at AutoHouse Automotive in Wood Dale, IL. Ford, Chevy, BMW, and all makes.">
<style>
  :root {
    --black:   #0d0d0d;
    --steel:   #1c2b3a;
    --mid:     #2e4057;
    --rule:    #2a3f52;
    --silver:  #8fa8bc;
    --ice:     #c8d8e4;
    --white:   #f2f5f7;
    --accent:  #c8a84b;
    --error:   #e05c5c;
    --radius:  4px;
    --font-display: 'Georgia', 'Times New Roman', serif;
    --font-body: system-ui, -apple-system, 'Segoe UI', sans-serif;
  }

  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  html { scroll-behavior: smooth; }

  body {
    background: var(--black);
    color: var(--white);
    font-family: var(--font-body);
    font-size: 16px;
    line-height: 1.6;
    -webkit-font-smoothing: antialiased;
    min-height: 100vh;
  }

  /* Header */
  header {
    background: var(--steel);
    border-bottom: 1px solid var(--rule);
    display: flex; align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1.25rem; gap: 1rem;
  }
  .logo {
    font-family: var(--font-display);
    font-size: 1.05rem; letter-spacing: 0.04em;
    color: var(--white); text-decoration: none; line-height: 1.2;
  }
  .logo span {
    display: block; font-size: 0.65rem;
    letter-spacing: 0.12em; text-transform: uppercase;
    color: var(--silver); font-family: var(--font-body);
  }
  .btn-call {
    display: inline-flex; align-items: center; gap: 0.4rem;
    background: var(--accent); color: var(--black);
    font-weight: 700; font-size: 0.9rem;
    padding: 0.55rem 1.1rem; border-radius: var(--radius);
    text-decoration: none; white-space: nowrap;
  }

  /* Main */
  main {
    max-width: 560px;
    margin: 0 auto;
    padding: 2rem 1.25rem 3rem;
  }

  .page-eyebrow {
    font-size: 0.7rem; letter-spacing: 0.2em;
    text-transform: uppercase; color: var(--accent);
    margin-bottom: 0.5rem;
  }
  h1 {
    font-family: var(--font-display);
    font-size: clamp(1.6rem, 5vw, 2.2rem);
    font-weight: normal; line-height: 1.2;
    color: var(--white); margin-bottom: 0.5rem;
  }
  .page-sub {
    color: var(--silver); font-size: 0.9rem;
    margin-bottom: 2rem;
  }

  /* Success state */
  .success-box {
    background: var(--mid);
    border: 1px solid var(--accent);
    border-radius: var(--radius);
    padding: 2rem; text-align: center;
    margin-top: 1rem;
  }
  .success-box h2 {
    font-family: var(--font-display);
    font-size: 1.5rem; font-weight: normal;
    color: var(--accent); margin-bottom: 0.75rem;
  }
  .success-box p { color: var(--silver); margin-bottom: 0.5rem; }
  .success-box .back-link {
    display: inline-block; margin-top: 1.25rem;
    color: var(--accent); text-decoration: none; font-size: 0.9rem;
  }

  /* Error banner */
  .error-banner {
    background: rgba(224,92,92,0.15);
    border: 1px solid var(--error);
    border-radius: var(--radius);
    padding: 0.75rem 1rem;
    color: var(--error); font-size: 0.875rem;
    margin-bottom: 1.5rem;
  }

  /* Form */
  .form-section {
    margin-bottom: 1.75rem;
  }
  .form-section-label {
    font-size: 0.65rem; letter-spacing: 0.18em;
    text-transform: uppercase; color: var(--accent);
    margin-bottom: 0.85rem;
    padding-bottom: 0.4rem;
    border-bottom: 1px solid var(--rule);
  }
  .field-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
  }
  .field-row.single { grid-template-columns: 1fr; }
  .field-row.third { grid-template-columns: 1fr 1fr 1fr; }

  .field { display: flex; flex-direction: column; gap: 0.3rem; }
  .field label {
    font-size: 0.75rem; letter-spacing: 0.06em;
    text-transform: uppercase; color: var(--silver);
  }
  .field input,
  .field select,
  .field textarea {
    background: var(--steel);
    border: 1px solid var(--rule);
    border-radius: var(--radius);
    color: var(--white);
    font-family: var(--font-body);
    font-size: 1rem;
    padding: 0.65rem 0.75rem;
    width: 100%;
    transition: border-color 0.15s;
    -webkit-appearance: none;
    appearance: none;
  }
  .field input:focus,
  .field select:focus,
  .field textarea:focus {
    outline: none;
    border-color: var(--accent);
  }
  .field select {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%238fa8bc' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    padding-right: 2.25rem;
  }
  .field select option { background: var(--steel); }
  .field textarea { resize: vertical; min-height: 90px; }

  /* Time row */
  .time-row {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
  }

  /* Honeypot — hidden from humans */
  .hp-field {
    position: absolute;
    left: -9999px;
    opacity: 0;
    height: 0;
    overflow: hidden;
    pointer-events: none;
  }

  /* Submit */
  .submit-btn {
    width: 100%;
    background: var(--accent);
    color: var(--black);
    font-weight: 700;
    font-size: 1rem;
    letter-spacing: 0.03em;
    padding: 0.9rem 2rem;
    border: none;
    border-radius: var(--radius);
    cursor: pointer;
    transition: filter 0.15s;
    margin-top: 0.5rem;
  }
  .submit-btn:hover { filter: brightness(1.1); }
  .submit-btn:active { filter: brightness(0.95); }

  .form-note {
    font-size: 0.775rem; color: var(--silver);
    text-align: center; margin-top: 1rem;
  }

  /* Footer */
  footer {
    background: var(--black);
    border-top: 1px solid var(--rule);
    padding: 1.25rem;
    text-align: center;
    font-size: 0.75rem; color: var(--silver); line-height: 1.8;
  }
  footer a { color: var(--silver); text-decoration: none; }
  footer a:hover { color: var(--white); }

  @media (max-width: 400px) {
    .field-row { grid-template-columns: 1fr; }
    .field-row.third { grid-template-columns: 1fr 1fr; }
    .time-row { grid-template-columns: 1fr 1fr; }
  }
</style>
</head>
<body>

<header>
  <a href="/" class="logo">
    AutoHouse Automotive
    <span>Wood Dale, Illinois</span>
  </a>
  <a href="tel:+16307086586" class="btn-call">&#9742; Call Instead</a>
</header>

<main>
  <p class="page-eyebrow">Wood Dale, IL</p>
  <h1>Request an Appointment</h1>
  <p class="page-sub">Fill out the form below and we'll confirm your appointment by phone or email &mdash; usually same day.</p>

  <?php if ($sent): ?>
  <div class="success-box">
    <h2>Request Received</h2>
    <p>Thanks, <?= htmlspecialchars($_POST['appt_fname'] ?? '') ?>. We'll contact you to confirm your appointment &mdash; usually the same business day.</p>
    <p>Need us sooner? Call <a href="tel:+16307086586">(630) 708-6586</a>.</p>
    <a href="/" class="back-link">&larr; Back to AutoHouse</a>
  </div>
  <?php else: ?>
  <?php if ($error): ?><div class="error-banner"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <form method="POST" action="schedule.php">

    <!-- Honeypot field — hidden from humans, bots fill it -->
    <div class="hp-field" aria-hidden="true">
      <label for="website">Website</label>
      <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
    </div>

    <!-- Contact -->
    <div class="form-section">
      <div class="form-section-label">Your Information</div>

      <div class="field-row">
        <div class="field">
          <label for="appt_fname">First Name *</label>
          <input type="text" id="appt_fname" name="appt_fname"
                 value="<?= v('appt_fname') ?>"
                 autocomplete="given-name" required>
        </div>
        <div class="field">
          <label for="appt_lname">Last Name</label>
          <input type="text" id="appt_lname" name="appt_lname"
                 value="<?= v('appt_lname') ?>"
                 autocomplete="family-name">
        </div>
      </div>

      <div class="field-row">
        <div class="field">
          <label for="appt_phone">Phone *</label>
          <input type="tel" id="appt_phone" name="appt_phone"
                 value="<?= v('appt_phone') ?>"
                 autocomplete="tel" required>
        </div>
        <div class="field">
          <label for="appt_email">Email</label>
          <input type="email" id="appt_email" name="appt_email"
                 value="<?= v('appt_email') ?>"
                 autocomplete="email">
        </div>
      </div>

      <div class="field-row single">
        <div class="field">
          <label for="appt_pref">Preferred Contact Method</label>
          <select id="appt_pref" name="appt_pref">
            <option value="Phone" <?= sel('appt_pref', 'Phone') ?>>Phone</option>
            <option value="Email" <?= sel('appt_pref', 'Email') ?>>Email</option>
            <option value="Text" <?= sel('appt_pref', 'Text') ?>>Text Message</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Vehicle -->
    <div class="form-section">
      <div class="form-section-label">Your Vehicle</div>

      <div class="field-row third">
        <div class="field">
          <label for="appt_year">Year</label>
          <select id="appt_year" name="appt_year">
            <option value="">Year</option>
            <?php for ($y = (int)date('Y') + 1; $y >= 1990; $y--): ?><option value="<?= $y ?>" <?= sel('appt_year', (string)$y) ?>><?= $y ?></option><?php endfor; ?>          </select>
        </div>
        <div class="field">
          <label for="appt_make">Make</label>
          <input type="text" id="appt_make" name="appt_make"
                 placeholder="e.g. Ford"
                 value="<?= v('appt_make') ?>">
        </div>
        <div class="field">
          <label for="appt_model">Model</label>
          <input type="text" id="appt_model" name="appt_model"
                 placeholder="e.g. F-150"
                 value="<?= v('appt_model') ?>">
        </div>
      </div>
    </div>

    <!-- Appointment -->
    <div class="form-section">
      <div class="form-section-label">Requested Date &amp; Time</div>

      <div class="field-row single" style="margin-bottom:0.75rem;">
        <div class="field">
          <label for="appt_date">Preferred Date *</label>
          <input type="date" id="appt_date" name="appt_date"
                 value="<?= v('appt_date') ?>"
                 min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                 required>
        </div>
      </div>

      <div class="time-row">
        <div class="field">
          <label for="appt_hour">Hour</label>
          <select id="appt_hour" name="appt_hour">
            <?php foreach ([8,9,10,11,12,1,2,3,4,5] as $hr): ?><option value="<?= $hr ?>" <?= sel('appt_hour', (string)$hr) ?>><?= $hr ?></option><?php endforeach; ?>          </select>
        </div>
        <div class="field">
          <label for="appt_minute">Minute</label>
          <select id="appt_minute" name="appt_minute">
            <option value="00" <?= sel('appt_minute', '00') ?>>:00</option>
            <option value="30" <?= sel('appt_minute', '30') ?>>:30</option>
          </select>
        </div>
        <div class="field">
          <label for="appt_ampm">AM/PM</label>
          <select id="appt_ampm" name="appt_ampm">
            <option value="AM" <?= sel('appt_ampm', 'AM', true) ?>>AM</option>
            <option value="PM" <?= sel('appt_ampm', 'PM') ?>>PM</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Issue -->
    <div class="form-section">
      <div class="form-section-label">What's Going On?</div>
      <div class="field-row single">
        <div class="field">
          <label for="appt_issue">Describe the Issue or Service Needed</label>
          <textarea id="appt_issue" name="appt_issue"
                    placeholder="Check engine light, oil change, brakes, etc."><?= v('appt_issue') ?></textarea>
        </div>
      </div>
    </div>

    <button type="submit" class="submit-btn">Send Appointment Request</button>
    <p class="form-note">Hours: Mon–Fri 8AM–6PM &middot; Sat by appointment &middot; After-hours drop-off available</p>

  </form>
  <?php endif; ?>
  </main>

<footer>
  <p>AutoHouse Automotive &middot; 359 E Potter St, Wood Dale, IL 60191</p>
  <p><a href="tel:+16307086586">(630) 708-6586</a> &middot; <a href="mailto:service@autohouseautomotive.com">service@autohouseautomotive.com</a></p>
  <p style="margin-top:0.5rem;"><a href="/"><< Back to AutoHouse</a></p>
</footer>

</body>
</html>