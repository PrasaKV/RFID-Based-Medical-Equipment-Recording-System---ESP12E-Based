<?php
// ── Auto-Migration Script ──────────────────────────────────────────────────
// Safely adds new columns to existing tables. Can be run multiple times.
// Called automatically from db.php on each connection, or manually.

function runMigrations(PDO $db): void {
    $migrations = [
        // ── issues_tickets: resolution tracking ──
        "ALTER TABLE issues_tickets ADD COLUMN resolution_notes TEXT NULL AFTER status",
        "ALTER TABLE issues_tickets ADD COLUMN repair_method TEXT NULL AFTER resolution_notes",

        // ── part_replacements: link to issue ticket + description ──
        "ALTER TABLE part_replacements ADD COLUMN ticket_id INT NULL AFTER repair_id",
        "ALTER TABLE part_replacements ADD COLUMN description TEXT NULL AFTER part_name",

        // ── repair_logs: delivery tracking fields ──
        "ALTER TABLE repair_logs ADD COLUMN delivery_department VARCHAR(200) NULL AFTER received_by",
        "ALTER TABLE repair_logs ADD COLUMN delivery_remarks TEXT NULL AFTER delivery_department",

        // ── departments & categories management ──
        "CREATE TABLE IF NOT EXISTS departments (
            department_id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(200) NOT NULL UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB;",

        "CREATE TABLE IF NOT EXISTS categories (
            category_id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(200) NOT NULL UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB;",

        "INSERT IGNORE INTO departments (name) VALUES
            ('ICU'),('Emergency'),('Ward A'),('Ward B'),('OPD'),('Radiology'),('Lab'),('Storage'),('Surgery');",

        "INSERT IGNORE INTO categories (name) VALUES
            ('Imaging'),('Life Support'),('Monitoring'),('Lab'),('Other');",

        // ── equipment: optional description ──
        "ALTER TABLE equipment ADD COLUMN description TEXT NULL AFTER model",

        // ── issues_tickets: title & reporter_name ──
        "ALTER TABLE issues_tickets ADD COLUMN title VARCHAR(255) NULL AFTER equipment_id",
        "ALTER TABLE issues_tickets ADD COLUMN reporter_name VARCHAR(150) NULL AFTER reported_by",

        // ── part_replacements: optional department ──
        "ALTER TABLE part_replacements MODIFY COLUMN department VARCHAR(200) NULL",
    ];

    foreach ($migrations as $sql) {
        try {
            $db->exec($sql);
        } catch (PDOException $e) {
            // "Duplicate column name" = column already exists, skip silently
            if (!str_contains($e->getMessage(), 'Duplicate column name')) {
                error_log("Migration warning: " . $e->getMessage());
            }
        }
    }
}

// If run directly from CLI or browser
if (php_sapi_name() === 'cli' || basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'migrate.php') {
    require_once __DIR__ . '/../includes/db.php';
    $db = getDB();
    if ($db) {
        runMigrations($db);
        echo "Migrations completed successfully.\n";
    } else {
        echo "Database connection failed.\n";
    }
}
