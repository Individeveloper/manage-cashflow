<?php
// Database initialization script
require_once 'database.php';

$sql = "
-- Create accounts (rekening) table - MUST be created first for foreign keys
CREATE TABLE IF NOT EXISTS accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type ENUM('Utama', 'Tabungan', 'Dana Darurat', 'Investasi', 'E-Wallet', 'Lainnya') NOT NULL DEFAULT 'Utama',
    bank_name VARCHAR(100),
    account_number VARCHAR(50),
    balance DECIMAL(15, 2) DEFAULT 0,
    icon VARCHAR(50) DEFAULT 'university',
    color VARCHAR(20) DEFAULT '#4361ee',
    notes TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create income table
CREATE TABLE IF NOT EXISTS income (
    id INT AUTO_INCREMENT PRIMARY KEY,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    category VARCHAR(100) DEFAULT 'Umum',
    account_id INT,
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE SET NULL
);

-- Create expenses table
CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    category VARCHAR(100) DEFAULT 'Umum',
    account_id INT,
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE SET NULL
);

-- Create transfers table (antar rekening)
CREATE TABLE IF NOT EXISTS transfers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    from_account_id INT NOT NULL,
    to_account_id INT NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    description VARCHAR(255),
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (from_account_id) REFERENCES accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (to_account_id) REFERENCES accounts(id) ON DELETE CASCADE
);

-- Create investments table
CREATE TABLE IF NOT EXISTS investments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(100) NOT NULL,
    initial_amount DECIMAL(15, 2) NOT NULL,
    current_value DECIMAL(15, 2) NOT NULL,
    purchase_date DATE NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create assets table (for non-monetary / physical assets)
CREATE TABLE IF NOT EXISTS assets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(100) NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create expense categories
CREATE TABLE IF NOT EXISTS expense_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
);

-- Create income categories
CREATE TABLE IF NOT EXISTS income_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
);

-- Insert default expense categories
INSERT IGNORE INTO expense_categories (name) VALUES 
('Makanan & Minuman'),
('Transportasi'),
('Belanja'),
('Tagihan'),
('Hiburan'),
('Kesehatan'),
('Pendidikan'),
('Investasi'),
('Lainnya');

-- Insert default income categories
INSERT IGNORE INTO income_categories (name) VALUES 
('Gaji'),
('Bonus'),
('Freelance'),
('Investasi'),
('Hadiah'),
('Bunga'),
('Lainnya');

-- Insert default accounts if none exist
INSERT INTO accounts (name, type, bank_name, balance, icon, color, notes)
SELECT * FROM (SELECT 'Rekening Utama' as name, 'Utama' as type, 'Bank Umum' as bank_name, 0 as balance, 'university' as icon, '#4361ee' as color, 'Rekening utama untuk menerima pemasukan' as notes) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM accounts WHERE type = 'Utama' LIMIT 1);

INSERT INTO accounts (name, type, bank_name, balance, icon, color, notes)
SELECT * FROM (SELECT 'Tabungan' as name, 'Tabungan' as type, 'Bank Umum' as bank_name, 0 as balance, 'piggy-bank' as icon, '#06d6a0' as color, 'Rekening tabungan untuk menyimpan uang' as notes) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM accounts WHERE type = 'Tabungan' LIMIT 1);

INSERT INTO accounts (name, type, bank_name, balance, icon, color, notes)
SELECT * FROM (SELECT 'Dana Darurat' as name, 'Dana Darurat' as type, 'Bank Umum' as bank_name, 0 as balance, 'shield-alt' as icon, '#ef476f' as color, 'Dana darurat untuk keadaan mendesak' as notes) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM accounts WHERE type = 'Dana Darurat' LIMIT 1);
";

// Check database connection first
if ($dbError || !$pdo) {
    die("Database connection failed: " . ($dbError ?: "Unknown error") . "<br><br>Please check your environment variables: MYSQLHOST, MYSQLPORT, MYSQLDATABASE, MYSQLUSER, MYSQLPASSWORD");
}

try {
    // Execute main SQL
    $pdo->exec($sql);
    
    // Try to add account_id column to existing income table (for migration)
    try {
        $pdo->exec("ALTER TABLE income ADD COLUMN account_id INT AFTER category");
        $pdo->exec("ALTER TABLE income ADD FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE SET NULL");
    } catch (PDOException $e) {
        // Column might already exist, ignore
    }
    
    // Try to add account_id column to existing expenses table (for migration)
    try {
        $pdo->exec("ALTER TABLE expenses ADD COLUMN account_id INT AFTER category");
        $pdo->exec("ALTER TABLE expenses ADD FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE SET NULL");
    } catch (PDOException $e) {
        // Column might already exist, ignore
    }
    
    echo "<html><body style='font-family: sans-serif; padding: 40px; text-align: center;'>";
    echo "<h2 style='color: #059669;'>✅ Database berhasil diinisialisasi!</h2>";
    echo "<p>Tabel dan data default telah dibuat termasuk sistem rekening.</p>";
    echo "<p><strong>Rekening default:</strong> Rekening Utama, Tabungan, Dana Darurat</p>";
    echo "<br><a href='../index.php' style='background: #4361ee; color: white; padding: 12px 24px; border-radius: 10px; text-decoration: none;'>Ke Dashboard</a>";
    echo "</body></html>";
} catch (PDOException $e) {
    echo "Error creating tables: " . $e->getMessage();
}
?>
