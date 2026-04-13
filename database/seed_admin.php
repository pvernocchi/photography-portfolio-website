<?php
declare(strict_types=1);

$configPath = __DIR__ . '/../config/config.php';
if (!is_file($configPath)) {
    $configPath = __DIR__ . '/../config/config.example.php';
}

$config = require $configPath;
$db = $config['database'];

$dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $db['host'], $db['name'], $db['charset'] ?? 'utf8mb4');
$pdo = new PDO($dsn, $db['user'], $db['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$username = 'admin';
$email = 'admin@vernocchi.es';
$passwordHash = password_hash('changeme', PASSWORD_BCRYPT);

$statement = $pdo->prepare('INSERT INTO users (username, email, password_hash, mfa_enabled) VALUES (:username, :email, :password_hash, 0) ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), mfa_enabled = 0, totp_secret = NULL');
$statement->execute([
    ':username' => $username,
    ':email' => $email,
    ':password_hash' => $passwordHash,
]);

echo "Default admin user seeded successfully.\nUsername: admin\nPassword: changeme\n";
