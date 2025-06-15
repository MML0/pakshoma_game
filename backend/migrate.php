<?php
// migrate.php
header('Content-Type: text/plain');

$config = require 'config.php';

try {
    $pdo = new PDO(
        "mysql:host={$config['database']['host']};dbname={$config['database']['dbname']};charset=utf8",
        $config['database']['username'],
        $config['database']['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

echo "Connected to database.\n";

// Drop table if exists
$pdo->exec("DROP TABLE IF EXISTS users");
echo "Dropped old 'users' table if existed.\n";

// Create new users table
$createTableSQL = "
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    score FLOAT DEFAULT 0,
    game_stat ENUM('in queue', 'playing', 'played') NOT NULL DEFAULT 'in queue',
    team_mate_id INT DEFAULT NULL,
    play_time FLOAT DEFAULT 0, -- In minutes
    phone_number VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (team_mate_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
";

$pdo->exec($createTableSQL);
echo "Created new 'users' table.\n";
