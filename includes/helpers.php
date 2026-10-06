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
 * Generate unique transaction invoice code (e.g. TRX-20261005-001)
 */
function generateTransactionCode(PDO $db): string {
    $todayPrefix = 'TRX-' . date('Ymd') . '-';
    $stmt = $db->prepare("SELECT transaction_code FROM transactions WHERE transaction_code LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$todayPrefix . '%']);
    $lastTrx = $stmt->fetch();

    if ($lastTrx) {
        $parts = explode('-', $lastTrx['transaction_code']);
        $lastSeq = (int)end($parts);
        $newSeq = str_pad($lastSeq + 1, 3, '0', STR_PAD_LEFT);
    } else {
        $newSeq = '001';
    }

    return $todayPrefix . $newSeq;
}

/**
 * Format Currency Rupiah
 */
function formatRupiah(float $amount): string {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}
