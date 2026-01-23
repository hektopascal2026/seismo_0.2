<?php
/**
 * Database Configuration
 */
define('DB_HOST', 'localhost:3306');
define('DB_NAME', '8879_');
define('DB_USER', 'seismo');
define('DB_PASS', 'Hektopascal-p07');

/**
 * Application Settings
 */
define('CACHE_DURATION', 3600); // Cache feeds for 1 hour (in seconds)

/**
 * Database Connection
 */
function getDbConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    return $pdo;
}

/**
 * Initialize database tables
 */
function initDatabase() {
    $pdo = getDbConnection();
    
    // Create feeds table
    $pdo->exec("CREATE TABLE IF NOT EXISTS feeds (
        id INT AUTO_INCREMENT PRIMARY KEY,
        url VARCHAR(500) NOT NULL UNIQUE,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        link VARCHAR(500),
        category VARCHAR(100) DEFAULT NULL,
        last_fetched DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_url (url),
        INDEX idx_category (category),
        INDEX idx_last_fetched (last_fetched)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // Add category column if it doesn't exist (for existing installations)
    try {
        $pdo->exec("ALTER TABLE feeds ADD COLUMN category VARCHAR(100) DEFAULT NULL AFTER link");
    } catch (PDOException $e) {
        // Column might already exist, ignore error
        if (strpos($e->getMessage(), 'Duplicate column name') === false) {
            // Re-throw if it's a different error
            throw $e;
        }
    }
    
    // Add category index if it doesn't exist
    try {
        $pdo->exec("CREATE INDEX idx_category ON feeds(category)");
    } catch (PDOException $e) {
        // Index might already exist, ignore error
    }
    
    // Add disabled column if it doesn't exist (for existing installations)
    try {
        $pdo->exec("ALTER TABLE feeds ADD COLUMN disabled TINYINT(1) DEFAULT 0 AFTER category");
    } catch (PDOException $e) {
        // Column might already exist, ignore error
        if (strpos($e->getMessage(), 'Duplicate column name') === false) {
            // Re-throw if it's a different error
            throw $e;
        }
    }
    
    // Add disabled index if it doesn't exist
    try {
        $pdo->exec("CREATE INDEX idx_disabled ON feeds(disabled)");
    } catch (PDOException $e) {
        // Index might already exist, ignore error
    }
    
    // Create feed_items table
    $pdo->exec("CREATE TABLE IF NOT EXISTS feed_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        feed_id INT NOT NULL,
        guid VARCHAR(500) NOT NULL,
        title VARCHAR(500) NOT NULL,
        link VARCHAR(500),
        description TEXT,
        content TEXT,
        author VARCHAR(255),
        published_date DATETIME,
        cached_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_feed_id (feed_id),
        INDEX idx_guid (guid(255)),
        INDEX idx_published (published_date),
        UNIQUE KEY unique_feed_guid (feed_id, guid(255)),
        FOREIGN KEY (feed_id) REFERENCES feeds(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // Create emails table
    $pdo->exec("CREATE TABLE IF NOT EXISTS emails (
        id INT AUTO_INCREMENT PRIMARY KEY,
        subject VARCHAR(500) DEFAULT NULL,
        from_email VARCHAR(255) DEFAULT NULL,
        from_name VARCHAR(255) DEFAULT NULL,
        text_body TEXT,
        html_body TEXT,
        date_received DATETIME DEFAULT NULL,
        date_sent DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_created_at (created_at),
        INDEX idx_from_email (from_email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // Add missing columns to emails table if they don't exist (for existing installations)
    try {
        // Check which columns exist
        $existingColumns = [];
        $columnCheck = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'emails'");
        foreach ($columnCheck->fetchAll(PDO::FETCH_COLUMN) as $col) {
            $existingColumns[] = $col;
        }
        
        $emailColumns = [
            'subject' => "ALTER TABLE emails ADD COLUMN subject VARCHAR(500) DEFAULT NULL AFTER id",
            'from_email' => "ALTER TABLE emails ADD COLUMN from_email VARCHAR(255) DEFAULT NULL AFTER subject",
            'from_name' => "ALTER TABLE emails ADD COLUMN from_name VARCHAR(255) DEFAULT NULL AFTER from_email",
            'text_body' => "ALTER TABLE emails ADD COLUMN text_body TEXT AFTER from_name",
            'html_body' => "ALTER TABLE emails ADD COLUMN html_body TEXT AFTER text_body",
            'date_received' => "ALTER TABLE emails ADD COLUMN date_received DATETIME DEFAULT NULL AFTER html_body",
            'date_sent' => "ALTER TABLE emails ADD COLUMN date_sent DATETIME DEFAULT NULL AFTER date_received",
            'created_at' => "ALTER TABLE emails ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER date_sent"
        ];
        
        foreach ($emailColumns as $column => $sql) {
            if (!in_array($column, $existingColumns)) {
                try {
                    $pdo->exec($sql);
                } catch (PDOException $e) {
                    // Ignore if column already exists or other non-critical errors
                    if (strpos($e->getMessage(), 'Duplicate column name') === false) {
                        // Log but don't fail for other errors
                    }
                }
            }
        }
    } catch (PDOException $e) {
        // If INFORMATION_SCHEMA query fails, try to add columns anyway (will fail gracefully if they exist)
        // This is a fallback for older MySQL versions or permission issues
    }
    
    // Create sender_tags table for managing email sender tags
    $pdo->exec("CREATE TABLE IF NOT EXISTS sender_tags (
        id INT AUTO_INCREMENT PRIMARY KEY,
        from_email VARCHAR(255) NOT NULL UNIQUE,
        tag VARCHAR(100) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_from_email (from_email),
        INDEX idx_tag (tag)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

/**
 * Get base URL path for assets
 */
function getBasePath() {
    $path = dirname($_SERVER['PHP_SELF']);
    return $path === '/' ? '' : $path;
}
