<?php
// ── Database Connection ─────────────────────────────────────────────────────
// MySQL: localhost, root, no password, database: rfid-system

function getDB(): ?PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;
    try {
        $pdo = new PDO(
            'mysql:host=localhost;dbname=rfid-system;charset=utf8mb4',
            'root',
            '',
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
        // Auto-migration check for roles table and user role column
        static $migrated = false;
        if (!$migrated) {
            $migrated = true;
            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS roles (
                    role_id INT AUTO_INCREMENT PRIMARY KEY,
                    role_name VARCHAR(100) NOT NULL UNIQUE,
                    description VARCHAR(255),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB;");

                $pdo->exec("INSERT IGNORE INTO roles (role_name, description) VALUES
                    ('admin', 'Full system access and user management'),
                    ('engineer', 'Biomedical Engineer - Equipment maintenance & monitoring'),
                    ('technician', 'Field Technician - Equipment scanning & inspection');");

                $pdo->exec("ALTER TABLE users MODIFY COLUMN role VARCHAR(100) DEFAULT 'engineer';");
            } catch (Throwable $ignored) {}

            // Run schema migrations for new columns
            try {
                $migrateFile = __DIR__ . '/../setup/migrate.php';
                if (file_exists($migrateFile)) {
                    require_once $migrateFile;
                    runMigrations($pdo);
                }
            } catch (Throwable $ignored) {}
        }
    } catch (PDOException $e) {
        $pdo = null; // graceful fallback — file-based mode continues
    }
    return $pdo;
}
