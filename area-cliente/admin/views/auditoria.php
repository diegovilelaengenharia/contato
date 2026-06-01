<?php
/**
 * auditoria.php — View Administrativa de Logs de Auditoria.
 *
 * Exibe a listagem histórica de todas as ações de escrita, exclusão ou login
 * realizadas pelos administradores na plataforma para transparência e rastreio.
 */

$audit_logs = [];

try {
    // Carrega os 100 logs de auditoria mais recentes (evita lentidão em tabelas muito grandes)
    $audit_logs = $pdo->query("
        SELECT id, admin_user, action, entity, entity_id, payload_json, ip_address, user_agent, created_at 
        FROM audit_log 
        ORDER BY created_at DESC 
        LIMIT 100
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Erro ao carregar logs de auditoria: " . $e->getMessage());
}
?>

<div class="page-head" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 24px;">
    <div>
        <h1>Registros de Auditoria</h1>
        <p>Acompanhe em tempo real quem realizou cada alteração de andamento, faturas ou dados cadastrais.</p>
    </div>
    
    <!-- Barra de Pesquisa Local -->
    <div style="position: relative; width: 100%; max-width: 320px;">
        <input type="text" id="searchAuditPage" placeholder="Filtrar por ação, usuário ou IP..." 
               style="padding: 10px 14px 10px 38px; width: 100%; border: 1px solid var(--color-border); border-radius: 10px; font-size: 0.9rem;" 
               onkeyup="searchAuditLogs()">
        <span class="material-symbols-rounded" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-size: 1.2rem; color: var(--color-muted);">search</span>
    </div>
</div>

<div class="form-card" style="padding: 0; overflow: hidden;">
    <div class="admin-table-container" style="border: none; border-radius: 0;">
        <table class="admin-table" id="auditLogsTable">
            <thead>
                <tr>
                    <th>Data / Hora</th>
                    <th>Operador Admin</th>
                    <th>Operação</th>
                    <th>Entidade Afetada</th>
                    <th>IP Operacional</th>
                    <th>Dados Adicionais</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($audit_logs as $log): 
                    // Formata a Ação com cores diferenciadas
                    $badge_class = 'status-badge';
                    switch ($log['action']) {
                        case 'CREATE':
                            $badge_class .= ' success';
                            break;
                        case 'UPDATE':
                        case 'UPDATE_PASSWORD':
                            $badge_class .= ' info';
                            break;
                        case 'DELETE':
                            $badge_class .= ' danger';
                            break;
                        case 'BACKUP':
                            $badge_class .= ' warning';
                            break;
                        default:
                            $badge_class .= ' info';
                            break;
                    }
                ?>
                <tr class="audit-row">
                    <td class="audit-time" style="padding: 14px; white-space: nowrap; color: var(--color-text-subtle); font-size: 0.85rem;">
                        <?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?>
                    </td>
                    <td class="audit-user" style="padding: 14px; font-weight: 700; color: var(--color-primary-dark);">
                        <?php echo htmlspecialchars($log['admin_user']); ?>
                    </td>
                    <td class="audit-action" style="padding: 14px;">
                        <span class="<?php echo $badge_class; ?>" style="font-size: 0.7rem; padding: 3px 8px;">
                            <?php echo htmlspecialchars($log['action']); ?>
                        </span>
                    </td>
                    <td class="audit-entity" style="padding: 14px; font-size: 0.88rem; font-weight: 500;">
                        <?php echo htmlspecialchars($log['entity']); ?>
                        <?php if ($log['entity_id']): ?>
                            <span style="opacity: 0.6; font-size: 0.8rem;">(ID: <?php echo $log['entity_id']; ?>)</span>
                        <?php endif; ?>
                    </td>
                    <td class="audit-ip" style="padding: 14px; font-family: monospace; font-size: 0.85rem; color: var(--color-text-subtle);">
                        <?php echo htmlspecialchars($log['ip_address'] ?: '--'); ?>
                    </td>
                    <td style="padding: 14px;">
                        <?php if (!empty($log['payload_json'])): 
                            $payload_formatted = json_decode($log['payload_json'], true);
                        ?>
                            <button type="button" class="btn-save btn-ghost" style="padding: 4px 10px; font-size: 0.78rem; border-radius: 6px;" 
                                    onclick="showAuditPayload(<?php echo $log['id']; ?>)">
                                <span class="material-symbols-rounded" style="font-size: 0.95rem;">visibility</span> Inspecionar
                            </button>
                            <!-- Dados em JSON Escondidos para o Modal -->
                            <div id="payload-data-<?php echo $log['id']; ?>" style="display: none;">
                                <?php echo htmlspecialchars(json_encode($payload_formatted, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?>
                            </div>
                        <?php else: ?>
                            <span style="opacity: 0.4; font-size: 0.85rem;">--</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($audit_logs)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; color: var(--color-muted); padding: 50px;">
                        Nenhum registro de auditoria disponível no momento.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal para Visualização de Payload de Auditoria -->
<dialog id="modalAuditPayloadInspect" style="border: none; border-radius: var(--radius); max-width: 520px; width: 90%; box-shadow: var(--shadow-lg);">
    <div style="background: var(--color-primary); color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center;">
        <h3 style="margin: 0; font-size: 1.05rem; display: flex; align-items: center; gap: 8px;">
            <span class="material-symbols-rounded">pageview</span> Inspecionar Payload de Dados
        </h3>
        <button type="button" onclick="document.getElementById('modalAuditPayloadInspect').close()" style="background: none; border: none; color: white; font-size: 1.3rem; cursor: pointer;">&times;</button>
    </div>
    <div style="padding: 20px;">
        <p style="font-size: 0.85rem; color: var(--color-text-subtle); margin-bottom: 12px;">Dados modificados nesta operação (JSON):</p>
        <pre id="auditPayloadPre" style="background: #fafbfc; border: 1px solid var(--color-border); padding: 15px; border-radius: 8px; font-family: monospace; font-size: 0.82rem; overflow-x: auto; white-space: pre-wrap; word-wrap: break-word; color: #2980b9; font-weight: bold; max-height: 300px; overflow-y: auto;"></pre>
        
        <div style="margin-top: 20px; text-align: right;">
            <button type="button" class="btn-std btn-primary" style="padding: 8px 16px;" onclick="document.getElementById('modalAuditPayloadInspect').close()">Fechar Inspeção</button>
        </div>
    </div>
</dialog>

<!-- Scripts de Filtro Local e Modal -->
<script>
function searchAuditLogs() {
    const input = document.getElementById("searchAuditPage");
    const filter = input.value.toUpperCase();
    const rows = document.querySelectorAll(".audit-row");
    
    rows.forEach(row => {
        const userText = row.querySelector(".audit-user").textContent || row.querySelector(".audit-user").innerText;
        const actionText = row.querySelector(".audit-action").textContent || row.querySelector(".audit-action").innerText;
        const entityText = row.querySelector(".audit-entity").textContent || row.querySelector(".audit-entity").innerText;
        const ipText = row.querySelector(".audit-ip").textContent || row.querySelector(".audit-ip").innerText;
        const timeText = row.querySelector(".audit-time").textContent || row.querySelector(".audit-time").innerText;
        
        if (userText.toUpperCase().indexOf(filter) > -1 || 
            actionText.toUpperCase().indexOf(filter) > -1 || 
            entityText.toUpperCase().indexOf(filter) > -1 || 
            ipText.toUpperCase().indexOf(filter) > -1 || 
            timeText.toUpperCase().indexOf(filter) > -1) {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    });
}

function showAuditPayload(id) {
    const rawData = document.getElementById("payload-data-" + id).textContent;
    document.getElementById("auditPayloadPre").textContent = rawData;
    document.getElementById("modalAuditPayloadInspect").showModal();
}
</script>
