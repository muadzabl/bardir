<?php
// config/database.php

date_default_timezone_set('Asia/Jakarta');

define('DB_HOST', 'localhost');
define('DB_NAME', 'barberos_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+07:00'"
            ];

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                if ($e->getCode() == 1049) {
                    throw new Exception("Database '" . DB_NAME . "' belum dibuat. Silakan import schema.sql terlebih dahulu.");
                }
                throw new Exception("Koneksi database gagal: " . $e->getMessage());
            }
        }
        return self::$instance;
    }
}
