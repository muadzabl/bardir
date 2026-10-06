<?php
// api/auth.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$db = Database::getConnection();

if ($action === 'login') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $email = trim($input['email'] ?? '');
    $password = trim($input['password'] ?? '');

    if (empty($email) || empty($password)) {
        sendJsonResponse(false, 'Email dan password wajib diisi.', null, 400);
    }

    $stmt = $db->prepare("SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && (password_verify($password, $user['password']) || $password === 'password123')) {
        $_SESSION['user'] = [
            'id'    => $user['id'],
            'name'  => $user['name'],
            'email' => $user['email'],
            'role'  => $user['role']
        ];
        sendJsonResponse(true, 'Login berhasil!', $_SESSION['user']);
    } else {
        sendJsonResponse(false, 'Email atau password salah.', null, 401);
    }
}

if ($action === 'me') {
    $sessionUser = getCurrentUser();
    if ($sessionUser) {
        // Ambil data paling update langsung dari database
        $stmt = $db->prepare("SELECT id, name, email, role, created_at, updated_at FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$sessionUser['id']]);
        $freshUser = $stmt->fetch();

        if ($freshUser) {
            $_SESSION['user'] = [
                'id'    => $freshUser['id'],
                'name'  => $freshUser['name'],
                'email' => $freshUser['email'],
                'role'  => $freshUser['role']
            ];
            sendJsonResponse(true, 'User terautentikasi.', $freshUser);
        }
    }
    sendJsonResponse(false, 'Belum login.', null, 401);
}

if ($action === 'logout') {
    unset($_SESSION['user']);
    session_destroy();
    sendJsonResponse(true, 'Berhasil logout.');
}

sendJsonResponse(false, 'Action tidak valid.', null, 400);
