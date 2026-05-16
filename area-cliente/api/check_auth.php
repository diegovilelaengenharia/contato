<?php
// Force session cookie to be available to entire domain
session_set_cookie_params(0, '/');
session_name('CLIENTE_SESSID');
session_start();
header('Content-Type: application/json');

// NOTE: in production, origin checks might be needed if decoupled, 
// but checks are skipped here assuming same-origin or proxy usage.

try {
    if (isset($_SESSION['cliente_id'])) {
        // Use absolute path relative to this file to allow execution from anywhere
        $db_path = __DIR__ . '/../db.php';
        if (!file_exists($db_path)) {
            throw new Exception("DB Configuration file not found at: $db_path");
        }
        require $db_path;
        
        // Removed email/foto from explicit select to avoid 'Column not found' errors if they don't exist
        // login uses 'usuario', so we know that exists. 'nome' and 'id' also confirmed.
        $stmt = $pdo->prepare("SELECT id, nome, usuario FROM clientes WHERE id = ?");
        $stmt->execute([$_SESSION['cliente_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo json_encode([
                'authenticated' => true, 
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['nome'],
                    'usuario' => $user['usuario']
                ]
            ]);
        } else {
            // Session exists but user not found in DB
            session_destroy();
            http_response_code(401);
            echo json_encode([
                'authenticated' => false,
                'debug_reason' => 'User not found in DB for session: ' . $_SESSION['cliente_id']
            ]);
        }
    } else {
        // Return explicit reason for debugging (Session ID is null?)
        $debug = [
            'sess_name' => session_name(),
            'sess_id' => session_id(),
            'cookie_sent' => $_COOKIE[session_name()] ?? 'none',
            'has_session_var' => isset($_SESSION['cliente_id']) ? 'yes' : 'no'
        ];
        http_response_code(401);
        echo json_encode([
            'authenticated' => false,
            'debug' => $debug
        ]);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'authenticated' => false,
        'error' => 'Server Error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>
