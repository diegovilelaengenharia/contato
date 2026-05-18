<?php
require_once __DIR__ . '/Database.php';

class Migrations {
    /**
     * Executa todas as migrations pendentes na pasta migrations/
     */
    public static function run() {
        $pdo = Database::getInstance();
        $migration_dir = __DIR__ . '/../migrations/';
        
        // Garante que a tabela de controle existe
        $pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration_name VARCHAR(255) NOT NULL UNIQUE,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $files = glob($migration_dir . '*.sql');
        sort($files);

        $executed = 0;
        foreach ($files as $file) {
            $name = basename($file);
            
            // Verifica se já foi executada
            $stmt = $pdo->prepare("SELECT id FROM migrations WHERE migration_name = ?");
            $stmt->execute([$name]);
            
            if (!$stmt->fetch()) {
                $sql = file_get_contents($file);
                try {
                    $pdo->exec($sql);
                    $pdo->prepare("INSERT INTO migrations (migration_name) VALUES (?)")->execute([$name]);
                    $executed++;
                    error_log("Migration executada com sucesso: $name");
                } catch (Exception $e) {
                    error_log("ERRO NA MIGRATION $name: " . $e->getMessage());
                    return false;
                }
            }
        }
        return $executed;
    }
}
