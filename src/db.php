<?php

date_default_timezone_set('America/New_York');

// Absolute path to the SQLite file — anchored to this file's location (/src/db.php)
// so it resolves correctly regardless of where PHP is invoked from.
define('DB_PATH', realpath(__DIR__ . '/../database/portfolio.sqlite')
    ?: __DIR__ . '/../database/portfolio.sqlite');

function get_db_path(): string {
    return DB_PATH;
}

function get_db(): PDO {
    static $db = null;

    if ($db === null) {
        $path = get_db_path();
        $dir  = dirname($path);

        if (!file_exists($path)) {
            db_fatal(
                'Database file not found',
                "Expected at: <code>$path</code>",
                ['Make sure the file exists and the path is correct.',
                 'If deploying fresh, run the init script: <code>php database/init.php</code>']
            );
        }

        if (!is_writable($path) || !is_writable($dir)) {
            $fp = is_writable($path) ? '✓ writable' : '✗ not writable';
            $fd = is_writable($dir)  ? '✓ writable' : '✗ not writable';
            db_fatal(
                'Database is read-only',
                "File: <code>$path</code> — $fp<br>Directory: <code>$dir</code> — $fd",
                ["<code>chmod 664 $path</code>",
                 "<code>chmod 775 $dir</code>",
                 "<code>chown www-data:www-data $path $dir</code>",
                 'Then reload this page.']
            );
        }

        try {
            $db = new PDO('sqlite:' . $path);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            db_fatal(
                'Database connection failed',
                htmlspecialchars($e->getMessage()),
                ['Check that the SQLite extension is enabled in PHP.',
                 'Verify the database file is not corrupted.']
            );
        }
    }

    return $db;
}

function db_fatal(string $title, string $detail, array $steps): never {
    $is_admin = str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin');
    if (!$is_admin) {
        http_response_code(503);
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Unavailable</title></head>'
           . '<body style="font-family:sans-serif;padding:4rem;color:#555"><h2>Service temporarily unavailable.</h2></body></html>';
        exit;
    }

    $steps_html = implode('', array_map(fn($s) => "<li style='margin-bottom:.4rem'>$s</li>", $steps));
    http_response_code(500);
    echo <<<HTML
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <title>Admin — {$title}</title>
      <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: sans-serif; background: #f4f4f4; color: #222; display: flex;
               align-items: center; justify-content: center; min-height: 100vh; padding: 2rem; }
        .card { background: #fff; border: 1px solid #ddd; padding: 2rem 2.5rem; max-width: 640px; width: 100%; }
        .badge { display: inline-block; background: #fee; color: #c00; font-size: .75rem;
                 font-weight: 600; letter-spacing: .08em; text-transform: uppercase;
                 padding: .3rem .7rem; margin-bottom: 1.25rem; }
        h2 { font-size: 1.2rem; margin-bottom: .75rem; }
        .detail { font-size: .85rem; color: #888; background: #f9f9f9; border: 1px solid #eee;
                  padding: .6rem .9rem; margin-bottom: 1.5rem; line-height: 1.6; }
        h3 { font-size: .75rem; text-transform: uppercase; letter-spacing: .1em; color: #888;
             font-weight: 600; margin-bottom: .6rem; }
        ol { padding-left: 1.25rem; font-size: .88rem; line-height: 1.8; color: #444; }
        code { background: #f0f0f0; padding: .1rem .4rem; font-size: .82rem; font-family: monospace; }
      </style>
    </head>
    <body>
      <div class="card">
        <span class="badge">Admin Error</span>
        <h2>{$title}</h2>
        <div class="detail">{$detail}</div>
        <h3>How to fix</h3>
        <ol>{$steps_html}</ol>
      </div>
    </body>
    </html>
    HTML;
    exit;
}
