<?php
// api/stock.php - Manajemen Stok Produk (CRUD)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$db     = Database::getConnection();

// GET: Daftar semua stok
if ($method === 'GET') {
    requireAuth();
    $search = $_GET['search'] ?? '';
    $sql = "SELECT * FROM stock_products ORDER BY name ASC";
    if ($search) {
        $sql = "SELECT * FROM stock_products WHERE name LIKE ? OR category LIKE ? ORDER BY name ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute(["%$search%", "%$search%"]);
    } else {
        $stmt = $db->query($sql);
    }
    sendJsonResponse(true, 'Data stok berhasil dimuat.', $stmt->fetchAll());
}

// POST: Tambah produk
if ($method === 'POST') {
    requireOwnerAuth();
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $name     = trim($input['name'] ?? '');
    $category = trim($input['category'] ?? 'Umum');
    $stock    = max(0, (int)($input['stock_qty'] ?? 0));
    $minStock = max(0, (int)($input['min_stock'] ?? 5));
    $buyPrice = max(0, (float)($input['buy_price'] ?? 0));
    $sellPrice= max(0, (float)($input['sell_price'] ?? 0));
    $unit     = trim($input['unit'] ?? 'pcs');

    if (!$name) sendJsonResponse(false, 'Nama produk wajib diisi.', null, 400);

    $stmt = $db->prepare("INSERT INTO stock_products (name, category, stock_qty, min_stock, buy_price, sell_price, unit) VALUES (?,?,?,?,?,?,?)");
    $stmt->execute([$name, $category, $stock, $minStock, $buyPrice, $sellPrice, $unit]);
    sendJsonResponse(true, 'Produk berhasil ditambahkan.', ['id' => $db->lastInsertId()], 201);
}

// PUT: Edit produk atau update stok
if ($method === 'PUT') {
    requireAuth();
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $id = (int)($input['id'] ?? 0);
    if (!$id) sendJsonResponse(false, 'ID produk tidak valid.', null, 400);

    // Jika hanya update qty (tambah/kurang stok)
    if (isset($input['adjust_qty'])) {
        $adjust = (int)$input['adjust_qty'];
        $note   = trim($input['note'] ?? '');
        $stmt = $db->prepare("UPDATE stock_products SET stock_qty = GREATEST(0, stock_qty + ?) WHERE id = ?");
        $stmt->execute([$adjust, $id]);

        // Log adjustment
        $db->prepare("INSERT INTO stock_logs (product_id, change_qty, note) VALUES (?,?,?)")
           ->execute([$id, $adjust, $note]);

        sendJsonResponse(true, 'Stok berhasil diperbarui.');
    }

    // Edit lengkap (owner only)
    requireOwnerAuth();
    $name      = trim($input['name'] ?? '');
    $category  = trim($input['category'] ?? 'Umum');
    $stock     = max(0, (int)($input['stock_qty'] ?? 0));
    $minStock  = max(0, (int)($input['min_stock'] ?? 5));
    $buyPrice  = max(0, (float)($input['buy_price'] ?? 0));
    $sellPrice = max(0, (float)($input['sell_price'] ?? 0));
    $unit      = trim($input['unit'] ?? 'pcs');

    if (!$name) sendJsonResponse(false, 'Nama produk wajib diisi.', null, 400);

    $stmt = $db->prepare("UPDATE stock_products SET name=?, category=?, stock_qty=?, min_stock=?, buy_price=?, sell_price=?, unit=? WHERE id=?");
    $stmt->execute([$name, $category, $stock, $minStock, $buyPrice, $sellPrice, $unit, $id]);
    sendJsonResponse(true, 'Produk berhasil diperbarui.');
}

// DELETE: Hapus produk
if ($method === 'DELETE') {
    requireOwnerAuth();
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $id = (int)($input['id'] ?? 0);
    if (!$id) sendJsonResponse(false, 'ID tidak valid.', null, 400);
    $db->prepare("DELETE FROM stock_products WHERE id = ?")->execute([$id]);
    sendJsonResponse(true, 'Produk berhasil dihapus.');
}

sendJsonResponse(false, 'Metode HTTP tidak didukung.', null, 405);
