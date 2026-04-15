<?php
declare(strict_types=1);

define('INSTALL_BASE', dirname(__DIR__));

// ── Already installed guard ───────────────────────────────────────────────────
$lockFile = INSTALL_BASE . '/storage/installed.lock';
if (is_file($lockFile)) {
    // Allow the completion page to render once (step=6 in session), then lock out
    $sessionStep = isset($_SESSION) ? (int) ($_SESSION['install_step'] ?? 0) : 0;
    if ($sessionStep < 6) {
        header('Location: /');
        exit;
    }
}

// ── Session ───────────────────────────────────────────────────────────────────
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('install_wizard');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

// Re-check lock with session available
if (is_file($lockFile) && (int) ($_SESSION['install_step'] ?? 0) < 6) {
    header('Location: /');
    exit;
}

// ── Helpers ───────────────────────────────────────────────────────────────────

function h(mixed $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['install_csrf'])) {
        $_SESSION['install_csrf'] = bin2hex(random_bytes(32));
    }
    return (string) $_SESSION['install_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}

function csrf_verify(?string $token): bool
{
    $stored = (string) ($_SESSION['install_csrf'] ?? '');
    if ($token === null || $stored === '' || !hash_equals($stored, $token)) {
        return false;
    }
    $_SESSION['install_csrf'] = bin2hex(random_bytes(32));
    return true;
}

function make_pdo(array $db): PDO
{
    $opts = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    if (defined('PDO::MYSQL_ATTR_CONNECT_TIMEOUT')) {
        $opts[PDO::MYSQL_ATTR_CONNECT_TIMEOUT] = 5;
    }
    return new PDO(
        sprintf('mysql:host=%s;dbname=%s;charset=%s', $db['host'], $db['name'], $db['charset']),
        $db['user'],
        $db['pass'],
        $opts
    );
}

/**
 * @return array{checks: list<array{label:string,ok:bool,value:string,hint:string}>, all_pass: bool}
 */
function check_requirements(): array
{
    $checks = [];

    $phpOk    = PHP_VERSION_ID >= 80100;
    $checks[] = [
        'label' => 'PHP ≥ 8.1',
        'ok'    => $phpOk,
        'value' => PHP_VERSION,
        'hint'  => "Upgrade your server's PHP to version 8.1 or higher.",
    ];

    foreach (['pdo', 'pdo_mysql', 'mbstring', 'openssl'] as $ext) {
        $ok       = extension_loaded($ext);
        $checks[] = [
            'label' => "Extension: {$ext}",
            'ok'    => $ok,
            'value' => $ok ? 'loaded' : 'missing',
            'hint'  => "Enable the PHP {$ext} extension on your server.",
        ];
    }

    $gdOk     = extension_loaded('gd') || extension_loaded('imagick');
    $checks[] = [
        'label' => 'Extension: gd or imagick',
        'ok'    => $gdOk,
        'value' => $gdOk
            ? (extension_loaded('imagick') ? 'imagick' : 'gd')
            : 'missing',
        'hint'  => 'Enable the PHP gd or imagick extension on your server.',
    ];

    $storagePath = INSTALL_BASE . '/storage';
    $storageOk   = is_dir($storagePath) && is_writable($storagePath);
    $checks[]    = [
        'label' => 'storage/ writable',
        'ok'    => $storageOk,
        'value' => $storageOk ? 'writable' : 'not writable',
        'hint'  => 'Run: chmod -R 755 storage/',
    ];

    $configDirPath = INSTALL_BASE . '/config';
    $configOk      = is_dir($configDirPath) && is_writable($configDirPath);
    $checks[]      = [
        'label' => 'config/ writable',
        'ok'    => $configOk,
        'value' => $configOk ? 'writable' : 'not writable',
        'hint'  => 'Run: chmod 755 config/',
    ];

    $allPass = array_reduce(
        $checks,
        static fn (bool $carry, array $c): bool => $carry && $c['ok'],
        true
    );

    return ['checks' => $checks, 'all_pass' => $allPass];
}

/**
 * @return array{0: list<array{stmt:string,ok:bool,msg:string}>, 1: string|null}
 */
function run_schema(array $db): array
{
    $schemaFile = INSTALL_BASE . '/database/schema.sql';
    if (!is_file($schemaFile)) {
        return [[], 'Schema file not found at database/schema.sql.'];
    }

    $sql = file_get_contents($schemaFile);
    if ($sql === false) {
        return [[], 'Could not read database/schema.sql.'];
    }

    try {
        $pdo = make_pdo($db);
    } catch (PDOException) {
        return [[], 'Could not reconnect to the database.'];
    }

    $statements = array_values(array_filter(
        array_map('trim', explode(';', $sql)),
        static fn (string $s): bool => $s !== ''
    ));

    $results  = [];
    $hasFatal = false;

    foreach ($statements as $stmt) {
        $preview = substr(preg_replace('/\s+/', ' ', $stmt) ?: $stmt, 0, 80);
        try {
            $pdo->exec($stmt);
            $results[] = ['stmt' => $preview, 'ok' => true, 'msg' => 'OK'];
        } catch (PDOException $e) {
            $code = (int) $e->getCode();
            // 1050 table exists, 1060 duplicate column, 1061 duplicate key, 1091 can't drop
            if (in_array($code, [1050, 1060, 1061, 1091], true)) {
                $results[] = ['stmt' => $preview, 'ok' => true, 'msg' => 'Already exists (skipped)'];
            } else {
                $results[] = ['stmt' => $preview, 'ok' => false, 'msg' => 'Error ' . $code];
                $hasFatal  = true;
            }
        }
    }

    return [$results, $hasFatal ? 'One or more statements failed. See results below.' : null];
}

function generate_config(string $siteName, string $siteUrl, string $defaultLang, string $appKey, array $db): string
{
    $vName    = var_export($siteName, true);
    $vUrl     = var_export($siteUrl, true);
    $vLang    = var_export($defaultLang, true);
    $vKey     = var_export($appKey, true);
    $vHost    = var_export($db['host'] ?? 'localhost', true);
    $vDbName  = var_export($db['name'] ?? '', true);
    $vUser    = var_export($db['user'] ?? '', true);
    $vPass    = var_export($db['pass'] ?? '', true);
    $vCharset = var_export($db['charset'] ?? 'utf8mb4', true);

    return implode(PHP_EOL, [
        '<?php',
        'declare(strict_types=1);',
        '',
        'return [',
        "    'app' => [",
        "        'name'             => {$vName},",
        "        'url'              => {$vUrl},",
        "        'debug'            => false,",
        "        'default_language' => {$vLang},",
        "        'key'              => {$vKey},",
        '    ],',
        "    'database' => [",
        "        'host'    => {$vHost},",
        "        'name'    => {$vDbName},",
        "        'user'    => {$vUser},",
        "        'pass'    => {$vPass},",
        "        'charset' => {$vCharset},",
        '    ],',
        "    'session' => [",
        "        'name'          => 'vernocchi_session',",
        "        'lifetime'      => 1800,",
        "        'remember_days' => 30,",
        '    ],',
        "    'totp' => [",
        "        'issuer'    => {$vName},",
        "        'digits'    => 6,",
        "        'period'    => 30,",
        "        'algorithm' => 'sha1',",
        '    ],',
        "    'turnstile' => [",
        "        'site_key'   => '',",
        "        'secret_key' => '',",
        '    ],',
        "    'mail' => [",
        "        'smtp_debug_log' => '',",
        '    ],',
        '];',
        '',
    ]);
}

// ── Step state ────────────────────────────────────────────────────────────────
$maxStep = (int) ($_SESSION['install_step'] ?? 1);
if ($maxStep < 1) {
    $maxStep = 1;
}
if ($maxStep > 6) {
    $maxStep = 6;
}

// Allow navigating back to already-completed steps via ?step=N
$requestedStep = isset($_GET['step']) ? (int) $_GET['step'] : $maxStep;
$currentStep   = ($requestedStep >= 1 && $requestedStep <= $maxStep) ? $requestedStep : $maxStep;

$error       = '';
$stepResults = null; // schema run results shown on step-3 error

// ── POST handler ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedStep = (int) ($_POST['current_step'] ?? 0);

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid security token. Please reload the page and try again.';
    } elseif ($postedStep === 1) {
        $req = check_requirements();
        if (!$req['all_pass']) {
            $error = 'Please fix all failing requirements before continuing.';
        } else {
            $_SESSION['install_step'] = 2;
            header('Location: /install.php?step=2');
            exit;
        }
    } elseif ($postedStep === 2) {
        $dbHost    = trim((string) ($_POST['db_host']    ?? 'localhost'));
        $dbName    = trim((string) ($_POST['db_name']    ?? ''));
        $dbUser    = trim((string) ($_POST['db_user']    ?? ''));
        $dbPass    = (string) ($_POST['db_pass']    ?? '');
        $dbCharset = trim((string) ($_POST['db_charset'] ?? 'utf8mb4')) ?: 'utf8mb4';

        if ($dbHost === '' || $dbName === '' || $dbUser === '') {
            $error       = 'Host, database name, and username are required.';
            $currentStep = 2;
        } else {
            try {
                $testPdo = make_pdo([
                    'host'    => $dbHost,
                    'name'    => $dbName,
                    'user'    => $dbUser,
                    'pass'    => $dbPass,
                    'charset' => $dbCharset,
                ]);
                $testPdo->query('SELECT 1');
                $_SESSION['install_db'] = [
                    'host'    => $dbHost,
                    'name'    => $dbName,
                    'user'    => $dbUser,
                    'pass'    => $dbPass,
                    'charset' => $dbCharset,
                ];
                $_SESSION['install_step'] = 3;
                header('Location: /install.php?step=3');
                exit;
            } catch (PDOException) {
                $error       = 'Could not connect to the database. Please verify your credentials and try again.';
                $currentStep = 2;
            }
        }
    } elseif ($postedStep === 3) {
        $db = $_SESSION['install_db'] ?? null;
        if ($db === null) {
            header('Location: /install.php?step=2');
            exit;
        }
        [$results, $schemaError] = run_schema($db);
        $stepResults             = $results;
        if ($schemaError !== null) {
            $error       = $schemaError;
            $currentStep = 3;
        } else {
            $_SESSION['install_step'] = 4;
            header('Location: /install.php?step=4');
            exit;
        }
    } elseif ($postedStep === 4) {
        $username        = trim((string) ($_POST['username']         ?? ''));
        $email           = trim((string) ($_POST['email']            ?? ''));
        $password        = (string) ($_POST['password']        ?? '');
        $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

        if ($username === '' || $email === '' || $password === '') {
            $error       = 'All fields are required.';
            $currentStep = 4;
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error       = 'Please enter a valid email address.';
            $currentStep = 4;
        } elseif (strlen($password) < 8) {
            $error       = 'Password must be at least 8 characters.';
            $currentStep = 4;
        } elseif (!hash_equals($password, $passwordConfirm)) {
            $error       = 'Passwords do not match.';
            $currentStep = 4;
        } else {
            $db = $_SESSION['install_db'] ?? null;
            if ($db === null) {
                header('Location: /install.php?step=2');
                exit;
            }
            try {
                $pdo  = make_pdo($db);
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $pdo->prepare('INSERT INTO users (username, email, password_hash, mfa_enabled) VALUES (?, ?, ?, 0)')
                    ->execute([$username, $email, $hash]);
                $_SESSION['install_step'] = 5;
                header('Location: /install.php?step=5');
                exit;
            } catch (PDOException) {
                $error       = 'Could not create admin account. The username or email may already be in use.';
                $currentStep = 4;
            }
        }
    } elseif ($postedStep === 5) {
        $siteName    = trim((string) ($_POST['site_name']         ?? ''));
        $siteUrl     = rtrim(trim((string) ($_POST['site_url']    ?? '')), '/');
        $defaultLang = in_array($_POST['default_language'] ?? '', ['es', 'en'], true)
            ? (string) $_POST['default_language']
            : 'es';
        $appKey = trim((string) ($_POST['app_key'] ?? ''));

        if ($siteName === '' || $siteUrl === '') {
            $error       = 'Site name and URL are required.';
            $currentStep = 5;
        } elseif (!filter_var($siteUrl, FILTER_VALIDATE_URL)) {
            $error       = 'Please enter a valid URL (e.g. https://example.com).';
            $currentStep = 5;
        } elseif (strlen($appKey) < 32) {
            $error       = 'The app key must be at least 32 characters. Use the Generate button.';
            $currentStep = 5;
        } else {
            $db            = $_SESSION['install_db'] ?? null;
            $configPath    = INSTALL_BASE . '/config/config.php';
            $configContent = generate_config($siteName, $siteUrl, $defaultLang, $appKey, $db ?? []);

            if (file_put_contents($configPath, $configContent) === false) {
                $error       = 'Could not write config/config.php. Please check directory permissions.';
                $currentStep = 5;
            } else {
                chmod($configPath, 0644);
                // Update site title / language in the settings table (non-fatal)
                if ($db !== null) {
                    try {
                        $pdo = make_pdo($db);
                        $upd = $pdo->prepare('UPDATE settings SET setting_value = ? WHERE setting_key = ?');
                        $upd->execute([$siteName,    'site_title']);
                        $upd->execute([$defaultLang, 'default_language']);
                        $upd->execute([$siteName,    'meta_title_es']);
                        $upd->execute([$siteName,    'meta_title_en']);
                    } catch (PDOException) {
                        // Non-fatal; user can update via Admin → Settings
                    }
                }
                // Create the lock file — must happen before redirecting to step 6
                file_put_contents(INSTALL_BASE . '/storage/installed.lock', date('c') . PHP_EOL);
                // Clear sensitive session data now that we're done
                unset($_SESSION['install_db']);
                $_SESSION['install_step'] = 6;
                header('Location: /install.php?step=6');
                exit;
            }
        }
    }

    // Re-sync maxStep after POST (may have been unchanged due to error)
    $maxStep = (int) ($_SESSION['install_step'] ?? 1);
    if ($maxStep < 1) {
        $maxStep = 1;
    }
}

// ── Gather display data ───────────────────────────────────────────────────────
$reqData  = ($currentStep === 1) ? check_requirements() : ['checks' => [], 'all_pass' => false];
$dbFields = $_SESSION['install_db'] ?? [];

$stepLabels = [
    1 => 'Requirements',
    2 => 'Database',
    3 => 'DB Setup',
    4 => 'Admin Account',
    5 => 'Configuration',
    6 => 'Complete',
];

$stepHeadings = [
    1 => 'Requirements Check',
    2 => 'Database Connection',
    3 => 'Database Setup',
    4 => 'Admin Account',
    5 => 'Site Configuration',
    6 => 'Installation Complete',
];

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Setup Wizard · Step <?= h((string) $currentStep) ?> of 6</title>
    <style>
        :root {
            --bg:      #f8fafc;
            --surface: #ffffff;
            --text:    #0f172a;
            --muted:   #475569;
            --primary: #1d4ed8;
            --danger:  #b91c1c;
            --success: #15803d;
            --warning: #b45309;
            --border:  #cbd5e1;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: Inter, Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
            padding: 2rem 1rem 4rem;
            line-height: 1.5;
        }
        a { color: var(--primary); text-decoration: none; }
        a:hover { text-decoration: underline; }
        .wizard-wrap { max-width: 660px; margin: 0 auto; }
        .brand {
            font-size: 1.4rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 2rem;
            color: var(--text);
        }
        /* ── Stepper ── */
        .stepper {
            display: flex;
            align-items: flex-start;
            margin-bottom: 2rem;
            position: relative;
        }
        .step-item {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.4rem;
            position: relative;
            z-index: 1;
        }
        .step-item:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 1rem;
            left: 50%;
            width: 100%;
            height: 2px;
            background: var(--border);
            z-index: 0;
        }
        .step-item.done::after  { background: var(--primary); }
        .step-num {
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            border: 2px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.78rem;
            background: var(--surface);
            z-index: 1;
            position: relative;
        }
        .step-item.done   .step-num { background: var(--primary); border-color: var(--primary); color: #fff; }
        .step-item.active .step-num { border-color: var(--primary); color: var(--primary); }
        .step-label {
            font-size: 0.65rem;
            color: var(--muted);
            text-align: center;
            line-height: 1.2;
            max-width: 60px;
        }
        .step-item.active .step-label { color: var(--primary); font-weight: 600; }
        @media (max-width: 480px) { .step-label { display: none; } }
        /* ── Card ── */
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.75rem;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.06);
        }
        .card-title { font-size: 1.25rem; font-weight: 700; margin-bottom: 0.35rem; }
        .card-subtitle { color: var(--muted); margin-bottom: 1.5rem; font-size: 0.9rem; }
        /* ── Alerts ── */
        .alert {
            border-radius: 8px;
            padding: 0.75rem 1rem;
            margin-bottom: 1.25rem;
            font-size: 0.9rem;
        }
        .alert-error   { background: #fee2e2; color: var(--danger);  border: 1px solid #fecaca; }
        .alert-success { background: #dcfce7; color: var(--success); border: 1px solid #bbf7d0; }
        .alert-warning { background: #ffedd5; color: var(--warning); border: 1px solid #fed7aa; }
        /* ── Form ── */
        .form-stack { display: grid; gap: 1rem; }
        label { display: grid; gap: 0.3rem; font-weight: 600; font-size: 0.9rem; }
        .field-hint { font-weight: 400; color: var(--muted); font-size: 0.8rem; }
        input[type="text"],
        input[type="password"],
        input[type="email"],
        input[type="url"],
        select {
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 0.6rem 0.75rem;
            font-size: 1rem;
            width: 100%;
            font-family: inherit;
            background: #fff;
        }
        input:focus, select:focus {
            outline: 2px solid var(--primary);
            outline-offset: 1px;
            border-color: transparent;
        }
        .btn {
            border: 1px solid transparent;
            border-radius: 8px;
            padding: 0.7rem 1.25rem;
            font-weight: 600;
            cursor: pointer;
            font-size: 1rem;
            font-family: inherit;
            transition: background 0.15s;
        }
        .btn-primary   { background: var(--primary); color: #fff; }
        .btn-primary:hover { background: #1e40af; }
        .btn-secondary { background: var(--surface); color: var(--text); border-color: var(--border); }
        .btn-secondary:hover { background: var(--bg); }
        .btn-sm { padding: 0.5rem 0.9rem; font-size: 0.875rem; }
        .actions { display: flex; gap: 0.75rem; margin-top: 1.5rem; flex-wrap: wrap; }
        /* ── Requirements table ── */
        .req-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; margin-top: 0.25rem; }
        .req-table th,
        .req-table td { padding: 0.55rem 0.75rem; text-align: left; border-bottom: 1px solid var(--border); }
        .req-table th { font-size: 0.78rem; font-weight: 600; color: var(--muted); text-transform: uppercase; }
        .req-table tr:last-child td { border-bottom: none; }
        .badge {
            display: inline-block;
            padding: 0.2rem 0.55rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-ok   { background: #dcfce7; color: var(--success); }
        .badge-fail { background: #fee2e2; color: var(--danger); }
        .req-hint { color: var(--muted); font-size: 0.78rem; margin-top: 0.2rem; font-style: italic; }
        /* ── Schema results ── */
        .results-list { list-style: none; margin-top: 0.75rem; max-height: 280px; overflow-y: auto; border: 1px solid var(--border); border-radius: 8px; }
        .results-list li { display: flex; align-items: baseline; gap: 0.6rem; padding: 0.4rem 0.75rem; border-bottom: 1px solid var(--border); font-size: 0.8rem; }
        .results-list li:last-child { border-bottom: none; }
        .results-list .ri-stmt { flex: 1; color: var(--muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-family: monospace; }
        .results-list .ri-msg  { white-space: nowrap; font-weight: 600; }
        .ri-ok   { color: var(--success); }
        .ri-fail { color: var(--danger); }
        /* ── Key row ── */
        .key-row { display: flex; gap: 0.5rem; align-items: stretch; }
        .key-row input { flex: 1; font-family: monospace; font-size: 0.85rem; }
        /* ── Complete ── */
        .complete-icon { font-size: 3.5rem; text-align: center; margin-bottom: 1.25rem; }
        .complete-details { background: var(--bg); border: 1px solid var(--border); border-radius: 8px; padding: 1rem; margin: 1.25rem 0; font-size: 0.875rem; display: grid; gap: 0.4rem; }
        .complete-details strong { font-size: 0.78rem; text-transform: uppercase; color: var(--muted); }
    </style>
</head>
<body>
<div class="wizard-wrap">
    <div class="brand">🛠 Setup Wizard</div>

    <!-- Stepper -->
    <div class="stepper" role="list">
        <?php foreach ($stepLabels as $n => $label): ?>
            <?php
                $cls = 'step-item';
                if ($n < $currentStep) {
                    $cls .= ' done';
                } elseif ($n === $currentStep) {
                    $cls .= ' active';
                }
            ?>
            <div class="<?= h($cls) ?>" role="listitem" aria-current="<?= $n === $currentStep ? 'step' : 'false' ?>">
                <div class="step-num">
                    <?php if ($n < $currentStep): ?>✓<?php else: ?><?= h((string) $n) ?><?php endif; ?>
                </div>
                <div class="step-label"><?= h($label) ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Card -->
    <div class="card">
        <div class="card-title"><?= h($stepHeadings[$currentStep] ?? 'Setup') ?></div>

        <?php if ($error !== ''): ?>
            <div class="alert alert-error" role="alert"><?= h($error) ?></div>
        <?php endif; ?>

        <?php if ($currentStep === 1): ?>
            <!-- ── Step 1: Requirements ── -->
            <p class="card-subtitle">Before continuing, your server must meet all of the following requirements.</p>
            <table class="req-table">
                <thead>
                    <tr>
                        <th>Requirement</th>
                        <th>Status</th>
                        <th>Value</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reqData['checks'] as $check): ?>
                        <tr>
                            <td>
                                <?= h($check['label']) ?>
                                <?php if (!$check['ok']): ?>
                                    <div class="req-hint"><?= h($check['hint']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?= $check['ok'] ? 'badge-ok' : 'badge-fail' ?>">
                                    <?= $check['ok'] ? 'OK' : 'FAIL' ?>
                                </span>
                            </td>
                            <td><?= h($check['value']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if ($reqData['all_pass']): ?>
                <div class="alert alert-success" style="margin-top:1rem;">All requirements are satisfied. You can proceed.</div>
            <?php endif; ?>
            <form method="post" action="/install.php">
                <?= csrf_field() ?>
                <input type="hidden" name="current_step" value="1">
                <div class="actions">
                    <button type="submit" class="btn btn-primary"<?= $reqData['all_pass'] ? '' : ' disabled style="opacity:.5;cursor:not-allowed;"' ?>>
                        Continue →
                    </button>
                </div>
            </form>

        <?php elseif ($currentStep === 2): ?>
            <!-- ── Step 2: Database connection ── -->
            <p class="card-subtitle">Enter your MySQL database credentials. The database must already exist.</p>
            <form method="post" action="/install.php?step=2" class="form-stack">
                <?= csrf_field() ?>
                <input type="hidden" name="current_step" value="2">
                <label>
                    Database Host
                    <input type="text" name="db_host" value="<?= h((string) ($_POST['db_host'] ?? $dbFields['host'] ?? 'localhost')) ?>" required placeholder="localhost">
                </label>
                <label>
                    Database Name
                    <input type="text" name="db_name" value="<?= h((string) ($_POST['db_name'] ?? $dbFields['name'] ?? '')) ?>" required placeholder="my_database">
                </label>
                <label>
                    Database Username
                    <input type="text" name="db_user" value="<?= h((string) ($_POST['db_user'] ?? $dbFields['user'] ?? '')) ?>" required placeholder="db_user" autocomplete="username">
                </label>
                <label>
                    Database Password
                    <input type="password" name="db_pass" value="" placeholder="••••••••" autocomplete="current-password">
                    <span class="field-hint">Leave blank if your database has no password.</span>
                </label>
                <label>
                    Character Set
                    <input type="text" name="db_charset" value="<?= h((string) ($_POST['db_charset'] ?? $dbFields['charset'] ?? 'utf8mb4')) ?>" placeholder="utf8mb4">
                    <span class="field-hint">Recommended: utf8mb4</span>
                </label>
                <div class="actions">
                    <a href="/install.php?step=1" class="btn btn-secondary">← Back</a>
                    <button type="submit" class="btn btn-primary">Test &amp; Continue →</button>
                </div>
            </form>

        <?php elseif ($currentStep === 3): ?>
            <!-- ── Step 3: Database setup ── -->
            <p class="card-subtitle">
                Click <strong>Install Database</strong> to create all required tables.
                If tables already exist they will be skipped safely.
            </p>
            <?php if ($stepResults !== null): ?>
                <ul class="results-list">
                    <?php foreach ($stepResults as $row): ?>
                        <li>
                            <span class="ri-stmt" title="<?= h($row['stmt']) ?>"><?= h($row['stmt']) ?></span>
                            <span class="ri-msg <?= $row['ok'] ? 'ri-ok' : 'ri-fail' ?>"><?= h($row['msg']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <form method="post" action="/install.php?step=3">
                <?= csrf_field() ?>
                <input type="hidden" name="current_step" value="3">
                <div class="actions">
                    <a href="/install.php?step=2" class="btn btn-secondary">← Back</a>
                    <button type="submit" class="btn btn-primary">Install Database →</button>
                </div>
            </form>

        <?php elseif ($currentStep === 4): ?>
            <!-- ── Step 4: Admin account ── -->
            <p class="card-subtitle">Create the administrator account you will use to log in.</p>
            <form method="post" action="/install.php?step=4" class="form-stack">
                <?= csrf_field() ?>
                <input type="hidden" name="current_step" value="4">
                <label>
                    Username
                    <input type="text" name="username" value="<?= h((string) ($_POST['username'] ?? '')) ?>" required placeholder="admin" autocomplete="username">
                </label>
                <label>
                    Email Address
                    <input type="email" name="email" value="<?= h((string) ($_POST['email'] ?? '')) ?>" required placeholder="admin@example.com" autocomplete="email">
                </label>
                <label>
                    Password
                    <input type="password" name="password" required placeholder="Min. 8 characters" autocomplete="new-password">
                </label>
                <label>
                    Confirm Password
                    <input type="password" name="password_confirm" required placeholder="Repeat password" autocomplete="new-password">
                </label>
                <div class="actions">
                    <button type="submit" class="btn btn-primary">Create Account →</button>
                </div>
            </form>

        <?php elseif ($currentStep === 5): ?>
            <!-- ── Step 5: Site configuration ── -->
            <p class="card-subtitle">Configure your site name, URL, and encryption key. You can update all other settings later via Admin → Settings.</p>
            <form method="post" action="/install.php?step=5" class="form-stack">
                <?= csrf_field() ?>
                <input type="hidden" name="current_step" value="5">
                <label>
                    Site Name
                    <input type="text" name="site_name" value="<?= h((string) ($_POST['site_name'] ?? '')) ?>" required placeholder="My Photography Portfolio">
                </label>
                <label>
                    Site URL
                    <input type="url" name="site_url" value="<?= h((string) ($_POST['site_url'] ?? '')) ?>" required placeholder="https://example.com">
                    <span class="field-hint">No trailing slash. Used for absolute links and redirects.</span>
                </label>
                <label>
                    Default Language
                    <select name="default_language">
                        <option value="en" <?= (($_POST['default_language'] ?? 'es') === 'en') ? 'selected' : '' ?>>English</option>
                        <option value="es" <?= (($_POST['default_language'] ?? 'es') === 'es') ? 'selected' : '' ?>>Español</option>
                    </select>
                </label>
                <label>
                    App Encryption Key
                    <span class="field-hint">Used to encrypt sensitive data. Must be exactly 32+ characters. Keep it secret.</span>
                    <div class="key-row">
                        <input type="text" name="app_key" id="app_key"
                               value="<?= h((string) ($_POST['app_key'] ?? '')) ?>"
                               required minlength="32" placeholder="Click Generate →"
                               spellcheck="false" autocomplete="off">
                        <button type="button" id="generate-key" class="btn btn-secondary btn-sm">Generate</button>
                    </div>
                </label>
                <div class="actions">
                    <button type="submit" class="btn btn-primary">Finish Setup →</button>
                </div>
            </form>
            <script>
            document.getElementById('generate-key').addEventListener('click', function () {
                var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                var arr   = new Uint8Array(32);
                crypto.getRandomValues(arr);
                var key   = '';
                arr.forEach(function (byte) { key += chars[byte % chars.length]; });
                document.getElementById('app_key').value = key;
            });
            </script>

        <?php elseif ($currentStep === 6): ?>
            <!-- ── Step 6: Complete ── -->
            <div class="complete-icon">🎉</div>
            <p class="card-subtitle" style="text-align:center;">
                Your portfolio site has been successfully set up and is ready to use.
            </p>
            <div class="complete-details">
                <strong>What to do next</strong>
                <span>Log in to the admin panel and customise your site settings, upload images, and create categories.</span>
                <span>You can configure SMTP mail, Cloudflare Turnstile, watermarks, social links, and more under <em>Admin → Settings</em>.</span>
            </div>
            <div class="actions" style="justify-content:center;">
                <a href="/admin/login" class="btn btn-primary">Go to Admin Login →</a>
            </div>

        <?php endif; ?>
    </div><!-- /.card -->
</div><!-- /.wizard-wrap -->
</body>
</html>
