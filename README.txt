============================================================
INTEGRATION README
Vaccine Certificate System — Backend Integration
============================================================

FILES DELIVERED
───────────────
  config.php          DB connection (edit credentials once)
  submit_handler.php  Receives POST from form.php → INSERT → redirect
  list.php            Admin list: all persons + View / Download buttons
  view.php            Public certificate page (wraps result.php)
  download.php        Triggers browser print-to-PDF for the certificate

DIRECTORY LAYOUT (put everything in the same folder)
─────────────────────────────────────────────────────
  /your-project/
    config.php            ← new
    submit_handler.php    ← new
    list.php              ← new
    view.php              ← new
    download.php          ← new
    form.php              ← yours (1 line change — see below)
    result.php            ← yours (3 lines change — see below)
    db.sql                ← yours (run once in MySQL)

────────────────────────────────────────────────────────────
STEP 1 — Edit config.php
────────────────────────────────────────────────────────────
Open config.php and set your actual DB credentials:

    define('DB_HOST', 'localhost');
    define('DB_NAME', 'your_database');   ← change
    define('DB_USER', 'root');            ← change
    define('DB_PASS', '');                ← change

────────────────────────────────────────────────────────────
STEP 2 — Import the database schema
────────────────────────────────────────────────────────────
Run once:

    mysql -u root -p your_database < db.sql

────────────────────────────────────────────────────────────
STEP 3 — ONE change in form.php
────────────────────────────────────────────────────────────
The form's action already points to submit_handler.php.
Confirm line 749 reads exactly:

    BEFORE (line 749 — already correct in your file):
        <form action="submit_handler.php" method="post">

    Nothing to change — it is already correct. ✓

    IF it pointed somewhere else, change it to:
        <form action="submit_handler.php" method="post">

────────────────────────────────────────────────────────────
STEP 4 — THREE changes in result.php
────────────────────────────────────────────────────────────
result.php defines two functions (fmt_display, d) at the top.
When view.php includes result.php, PHP will fatal-error on
"Cannot redeclare function". Add function_exists guards:

CHANGE A — line 30 of result.php
─────────────────────────────────
  FIND (exact text):
      function fmt_display(?string $d): string

  REPLACE WITH:
      if (!function_exists('fmt_display')) :
      function fmt_display(?string $d): string

CHANGE B — closing brace of fmt_display, ~line 39
──────────────────────────────────────────────────
  FIND (the closing brace right after `return $d;`):
      return $d;
  }

  REPLACE WITH:
      return $d;
  }
  endif;

CHANGE C — line 49 of result.php
─────────────────────────────────
  FIND:
      function d($data, $key, $fallback = '') {
          return isset($data[$key]) && $data[$key] !== '' && $data[$key] !== null
              ? htmlspecialchars((string)$data[$key])
              : $fallback;
      }

  REPLACE WITH:
      if (!function_exists('d')) {
          function d($data, $key, $fallback = '') {
              return isset($data[$key]) && $data[$key] !== '' && $data[$key] !== null
                  ? htmlspecialchars((string)$data[$key])
                  : $fallback;
          }
      }

Also remove the DB-fetch block at the top of result.php
(lines 1–56) if you only ever access it through view.php.
Alternatively, keep it — it will short-circuit when $unique_id
is already set, because the SELECT will just re-fetch the same
row. Either approach works.

────────────────────────────────────────────────────────────
STEP 5 — Add auto-print trigger to result.php (for download)
────────────────────────────────────────────────────────────
download.php redirects to view.php?id=…&print=1.
Add this snippet just before </body> in result.php:

  <!-- AUTO-PRINT for download.php -->
  <?php if (!empty($_GET['print'])): ?>
  <script>
    window.addEventListener('load', function() {
      setTimeout(function() { window.print(); }, 600);
    });
  </script>
  <?php endif; ?>

Exact position — find this line near the bottom of result.php:

  FIND:
      </body>

  INSERT BEFORE IT:
      <?php if (!empty($_GET['print'])): ?>
      <script>
        window.addEventListener('load', function() {
          setTimeout(function() { window.print(); }, 600);
        });
      </script>
      <?php endif; ?>

────────────────────────────────────────────────────────────
FLOW SUMMARY
────────────────────────────────────────────────────────────
  User fills form.php
    → POST → submit_handler.php
      → INSERT row, generate VC-XXXXXXXX
        → 302 redirect → view.php?id=VC-XXXXXXXX
          → fetches row, sets $data[], includes result.php
            → certificate displayed

  list.php
    → "View"     link → view.php?id=VC-XXXXXXXX
    → "Download" link → download.php?id=VC-XXXXXXXX
                         → 302 → view.php?id=VC-XXXXXXXX&print=1
                           → certificate + auto window.print()

============================================================
