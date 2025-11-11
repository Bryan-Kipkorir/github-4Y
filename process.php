cat > process.php <<'PHP'
<?php
// --- config ---
$DB_HOST = '127.0.0.1';
$DB_NAME = 'appdb';
$DB_USER = 'appuser';
$DB_PASS = 'ChangeThisPassword!'; // change me

function db() {
    global $DB_HOST, $DB_NAME, $DB_USER, $DB_PASS;
    $dsn = "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4";
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    return $pdo;
}

function json($ok, $msg, $extra = []) {
    header('Content-Type: application/json');
    echo json_encode(array_merge(['ok'=>$ok, 'message'=>$msg], $extra));
    exit;
}

$action = $_POST['action'] ?? '';
$email  = trim($_POST['email'] ?? '');
$pass   = $_POST['password'] ?? '';

if (!$action || !$email || !$pass) {
    json(false, 'Missing fields: action, email, password');
}

try {
    $pdo = db();

    if ($action === 'signup') {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (email, password_hash) VALUES (?, ?)');
        $stmt->execute([$email, $hash]);
        json(true, 'Signup successful');

    } elseif ($action === 'login') {
        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) json(false, 'User not found');
        if (!password_verify($pass, $row['password_hash'])) json(false, 'Invalid credentials');
        json(true, 'Login successful');

    } else {
        json(false, 'Unknown action');
    }
} catch (Throwable $e) {
    json(false, 'Server error', ['error' => $e->getMessage()]);
}
PHP
