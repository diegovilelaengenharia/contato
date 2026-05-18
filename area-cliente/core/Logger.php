<?php
class Logger {
    /**
     * Registra uma ação no audit_log.
     * @param string $action Ação realizada (LOGIN, CREATE, UPDATE, DELETE)
     * @param string $entity Entidade afetada (cliente, processo, financeiro)
     * @param int|null $entity_id ID da entidade afetada
     * @param array|null $payload Dados relevantes da ação
     */
    public static function log($action, $entity, $entity_id = null, $payload = null) {
        $pdo = Database::getInstance();
        $admin_user = $_SESSION['admin_user'] ?? 'system';
        
        $sql = "INSERT INTO audit_log (admin_user, action, entity, entity_id, payload_json, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $admin_user,
            $action,
            $entity,
            $entity_id,
            $payload ? json_encode($payload) : null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }
}
