<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');

$config = require 'config.php';

try {
    $pdo = new PDO(
        "mysql:host={$config['database']['host']};dbname={$config['database']['dbname']};charset=utf8",
        $config['database']['username'],
        $config['database']['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($action) {
        case 'register':
            $name = $input['name'] ?? null;
            $last_name = $input['last_name'] ?? null;
            $phone_number = $input['phone_number'] ?? null;
            $team_mate_id = $input['team_mate_id'] ?? null;

            if (!$name || !$last_name || !$phone_number) {
                echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
                exit;
            }

            // Insert the new user first
            $stmt = $pdo->prepare("INSERT INTO users (name, last_name, phone_number, team_mate_id) VALUES (?, ?, ?, NULL)");
            $stmt->execute([$name, $last_name, $phone_number]);
            $newUserId = $pdo->lastInsertId();

            if ($team_mate_id) {
                // Check if teammate exists
                $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE id = ?");
                $stmtCheck->execute([$team_mate_id]);
                if ($stmtCheck->fetch()) {
                    // Update both users
                    $pdo->prepare("UPDATE users SET team_mate_id = ? WHERE id = ?")->execute([$team_mate_id, $newUserId]);
                    $pdo->prepare("UPDATE users SET team_mate_id = ? WHERE id = ?")->execute([$newUserId, $team_mate_id]);
                }
            }

            echo json_encode(['status' => 'success', 'message' => 'User registered']);
            exit;


        case 'update':
            $id = $input['id'] ?? null;
            $score = $input['score'] ?? null;
            $play_time = $input['play_time'] ?? null;
            $password = $input['password'] ?? null;

            if (!$id || !is_numeric($score) || !is_numeric($play_time) || $password !== $config['admin']['password']) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid input or password']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE users SET score = ?, play_time = ? WHERE id = ?");
            $stmt->execute([$score, $play_time, $id]);

            echo json_encode(['status' => 'success', 'message' => 'User updated']);
            exit;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Unknown action']);
            exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get the action from the URL, if provided
    $action = $_GET['action'] ?? null;
    
    // Default: No specific action provided, return a default list of users
    if (!$action) {
        $stmt = $pdo->prepare("SELECT * FROM users ORDER BY created_at DESC LIMIT 20");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $users]);
        exit;
    }
    
    // Switch on the action parameter for additional GET endpoints
    switch ($action) {
        case 'ready_check':
            // Get all users in queue with teammates
            $stmt = $pdo->prepare("
                SELECT * FROM users
                WHERE game_stat = 'in queue'
                AND team_mate_id IS NOT NULL
                ORDER BY created_at ASC
            ");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Loop to find a valid mutual team pair
            foreach ($users as $user) {
                foreach ($users as $other) {
                    if (
                        $user['id'] != $other['id'] &&
                        $user['team_mate_id'] == $other['id'] &&
                        $other['team_mate_id'] == $user['id']
                    ) {
                        // Determine who hasn't been seen yet
                        if ($user['seen_once'] == 0 && $other['seen_once'] == 0) {
                            // First request: return first player
                            $setSeen = $pdo->prepare("UPDATE users SET seen_once = 1 WHERE id = ?");
                            $setSeen->execute([$user['id']]);
                            echo json_encode([
                                'status' => 'success',
                                'data' => [$user]
                            ]);
                            exit;
                        } elseif ($user['seen_once'] == 1 && $other['seen_once'] == 0) {
                            // Second request: return second player, update both to playing
                            $setSeen = $pdo->prepare("UPDATE users SET game_stat = 'playing' WHERE id IN (?, ?)");
                            $setSeen->execute([$user['id'], $other['id']]);

                            $clearSeen = $pdo->prepare("UPDATE users SET seen_once = 0 WHERE id IN (?, ?)");
                            $clearSeen->execute([$user['id'], $other['id']]);

                            echo json_encode([
                                'status' => 'success',
                                'data' => [$other]
                            ]);
                            exit;
                        }
                    }
                }
            }

            // No ready pairs found
            echo json_encode([
                'status' => 'waiting',
                'message' => 'No valid teammate pair found.'
            ]);
            exit;

        case 'get_single_players':
            // Get all users who don't have a team_mate_id and aren’t assigned as someone else’s team_mate_id.
            $stmt = $pdo->prepare("
                SELECT u.id, u.name, u.last_name, u.phone_number 
                FROM users u
                LEFT JOIN users u2 ON u.id = u2.team_mate_id
                WHERE u.team_mate_id IS NULL AND u2.id IS NULL
                ORDER BY u.created_at DESC
            ");
            $stmt->execute();
            $singlePlayers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'success', 'data' => $singlePlayers]);
            exit;

        // Add additional GET cases as needed.
        default:
            echo json_encode(['status' => 'error', 'message' => 'Unknown GET action']);
            exit;
    }
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
