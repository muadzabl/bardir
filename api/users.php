<?php
// api/users.php - Manajemen Pengguna & Kasir oleh Owner
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$db = Database::getConnection();

// GET: List all users (Owner only)
if ($method === 'GET') {
    requireRole('owner');
    $stmt = $db->query("SELECT id, name, email, role, created_at, updated_at FROM users ORDER BY role ASC, id ASC");
    $users = $stmt->fetchAll();
    sendJsonResponse(true, 'Data pengguna berhasil dimuat.', $users);
}

// POST: Add new user / cashier
if ($method === 'POST') {
    requireRole('owner');
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $name = trim($input['name'] ?? '');
    $email = trim($input['email'] ?? '');
    $password = trim($input['password'] ?? '');
    $role = in_array($input['role'] ?? '', ['owner', 'cashier']) ? $input['role'] : 'cashier';

    if (empty($name) || empty($email) || empty($password)) {
        sendJsonResponse(false, 'Nama, email, dan password wajib diisi.', null, 400);
    }

    // Check duplicate email
    $stmtCheck = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmtCheck->execute([$email]);
    if ($stmtCheck->fetch()) {
        sendJsonResponse(false, 'Email sudah terdaftar. Gunakan email lain.', null, 400);
    }

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $db->prepare("INSERT INTO users (name, email, password, role, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
    $stmt->execute([$name, $email, $hashedPassword, $role]);
    $newId = $db->lastInsertId();

    sendJsonResponse(true, 'Pengguna baru berhasil ditambahkan.', [
        'id' => $newId,
        'name' => $name,
        'email' => $email,
        'role' => $role,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ], 201);
}

// PUT / PATCH: Update user
if ($method === 'PUT' || $method === 'PATCH') {
    $currentOwner = requireRole('owner');
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $id = (int)($input['id'] ?? 0);
    $name = trim($input['name'] ?? '');
    $email = trim($input['email'] ?? '');
    $role = in_array($input['role'] ?? '', ['owner', 'cashier']) ? $input['role'] : 'cashier';
    $password = trim($input['password'] ?? '');

    if ($id <= 0 || empty($name) || empty($email)) {
        sendJsonResponse(false, 'Data pengguna tidak lengkap.', null, 400);
    }

    // Check duplicate email for other users
    $stmtCheck = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
    $stmtCheck->execute([$email, $id]);
    if ($stmtCheck->fetch()) {
        sendJsonResponse(false, 'Email sudah digunakan oleh akun lain.', null, 400);
    }

    if (!empty($password)) {
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, role = ?, password = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$name, $email, $role, $hashed, $id]);
    } else {
        $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, role = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$name, $email, $role, $id]);
    }

    // If updating currently logged in user, update session immediately!
    $isSelf = false;
    if (isset($_SESSION['user']) && (int)$_SESSION['user']['id'] === $id) {
        $_SESSION['user']['name'] = $name;
        $_SESSION['user']['email'] = $email;
        $_SESSION['user']['role'] = $role;
        $isSelf = true;
    }

    sendJsonResponse(true, 'Data pengguna berhasil diperbarui.', [
        'id' => $id,
        'name' => $name,
        'email' => $email,
        'role' => $role,
        'updated_at' => date('Y-m-d H:i:s'),
        'is_self' => $isSelf,
        'current_user' => $_SESSION['user'] ?? null
    ]);
}

// DELETE: Delete user
if ($method === 'DELETE') {
    $currentOwner = requireRole('owner');
    $input = json_decode(file_get_contents('php://input'), true) ?? $_GET;
    $id = (int)($input['id'] ?? 0);

    if ($id <= 0) {
        sendJsonResponse(false, 'ID pengguna tidak valid.', null, 400);
    }

    if ($id === (int)$currentOwner['id']) {
        sendJsonResponse(false, 'Anda tidak dapat menghapus akun Anda sendiri.', null, 400);
    }

    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);

    sendJsonResponse(true, 'Pengguna berhasil dihapus.');
}

sendJsonResponse(false, 'Metode HTTP tidak didukung.', null, 405);
