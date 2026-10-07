<?php
// includes/helpers.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Send JSON response and exit
 */
function sendJsonResponse(bool $success, string $message, $data = null, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data'    => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

/**
 * Get current logged in user or null
 */
function getCurrentUser(): ?array {
    return $_SESSION['user'] ?? null;
}

/**
 * Require authentication middleware
 */
function requireAuth(): array {
    $user = getCurrentUser();
    if (!$user) {
        sendJsonResponse(false, 'Unauthorized. Silakan login terlebih dahulu.', null, 401);
    }
    return $user;
}

/**
 * Require specific role middleware
 */
function requireRole(string $role): array {
    $user = requireAuth();
    if ($user['role'] !== $role) {
        sendJsonResponse(false, 'Forbidden. Anda tidak memiliki akses ke fitur ini.', null, 403);
    }
    return $user;
}

/**
 * Require owner role - shortcut
 */
function requireOwnerAuth(): array {
    return requireRole('owner');
}

/**
 * Generate unique random receipt number (No. Resi)
 * Format: RESI-XXXXXX (e.g. RESI-583921) - random, tidak rumit, dan dijamin unik
 */
function generateTransactionCode(PDO $db): string {
    do {
        $randomNum = mt_rand(100000, 999999);
        $code = 'RESI-' . $randomNum;
        $stmt = $db->prepare("SELECT id FROM transactions WHERE transaction_code = ? LIMIT 1");
        $stmt->execute([$code]);
    } while ($stmt->fetch());

    return $code;
}

/**
 * Format Currency Rupiah
 */
function formatRupiah(float $amount): string {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}
