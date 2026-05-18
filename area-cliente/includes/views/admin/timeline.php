<?php
/**
 * View Admin: Timeline e Histórico do Processo
 */
?>
<div class="admin-tab-content">
    <!-- Modern Header -->
    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:30px; gap:20px;">
        <div>
            <h3 style="margin:0 0 6px 0; font-size:1.6rem; font-weight:800; color:#2c3e50; letter-spacing:-0.5px; display:flex; align-items:center; gap:10px;">
                Histórico do Processo
            </h3>
            <p style="margin:0; font-size:0.95rem; color:#6c757d; font-weight:400;">Linha do tempo completa e registros do cliente.</p>
        </div>
        
        <div style="display:flex; gap:12px; align-items:center;">
            <!-- Botão Novo Andamento (Integrado) -->
            <button type="button" onclick="document.getElementById('modalAndamento').showModal()" style="padding:12px 24px; background:#198754; border:none; border-radius:12px; font-size:0.95rem; font-weight:600; color:white; cursor:pointer; display:flex; align-items:center; gap:8px; transition:all 0.2s; box-shadow:0 4px 12px rgba(25, 135, 84, 0.25);">
                <span class="material-symbols-rounded">add_circle</span> Novo Andamento
            </button>

            <!-- Botão Ver Timeline (Popup) -->
            <button type="button" onclick="document.getElementById('modalVisualTimeline').showModal()" style="padding:12px 18px; background:#e9ecef; border:none; border-radius:12px; font-size:0.95rem; font-weight:600; color:#495057; cursor:pointer; display:flex; align-items:center; gap:8px; transition:all 0.2s;">
                <span class="material-symbols-rounded">visibility</span> Ver Timeline
            </button>
            
             <!-- Botão Apagar Histórico (Perigo) -->
            <a href="actions/admin/movimento_clear_all.php?cliente_id=<?= $cliente_ativo['id'] ?>&del_all_hist=true" 
               onclick="return confirm('ATENÇÃO EXTREMA: \n\nVocê está prestes a APAGAR TODO O HISTÓRICO deste processo.\n\nIsso limpará todas as movimentações, datas e logs.\n\nTem certeza absoluta que deseja fazer isso?');"
               style="background:#fff5f5; color:#dc3545; padding:11px 16px; border:1px solid #f5c2c7; border-radius:12px; font-size:0.9rem; text-decoration:none; font-weight:700; display:flex; align-items:center; gap:6px; transition:all 0.2s;" title="Limpar Tudo">
                <span class="material-symbols-rounded">delete_sweep</span> Limpar
            </a>
        </div>
    </div>
    <div class="admin-table-container">
        <table class="admin-table">
            <thead><tr><th>Data</th><th>Evento</th><th style="text-align:center;">Ação</th></tr></thead>
            <tbody>
                <?php 
                $hist = $pdo->prepare("SELECT * FROM processo_movimentos WHERE cliente_id=? ORDER BY data_movimento DESC");
                $hist->execute([$cliente_ativo['id']]);
                foreach($hist->fetchAll() as $h): 
                    // Style based on type
                    $row_style = "";
                    $icon_type = "📌";
                    if(($h['tipo_movimento']??'padrao') == 'fase_inicio') {
                        $row_style = "background:#f3e8ff; border-left:5px solid #6610f2;"; // Styled for History
                        $icon_type = "🚀";
                    } elseif(($h['tipo_movimento']??'padrao') == 'documento') {
                        $row_style = "background:#f8f9fa; border-left:5px solid #198754;";
                        $icon_type = "📄";
                    }
                ?>
                    <tr style="<?= $row_style ?>">
                        <td style="white-space:nowrap; vertical-align:top;">
                            <?= date('d/m/Y H:i', strtotime($h['data_movimento'])) ?>
                        </td>
                        <td>
                            <div style="font-weight:bold; margin-bottom:5px; color:#212529; font-size:1rem;"><?= htmlspecialchars($h['titulo_fase']) ?></div>
                            <?php 
                                // Lógica de exibição de comentários estilizados
                                $parts = explode("||COMENTARIO_USER||", $h['descricao']);
                                // Permite HTML rico da primeira parte (descrição do sistema/admin)
                                $sys_desc = $parts[0]; 
                                echo "<div style='color:var(--color-text-subtle); line-height:1.5; font-size:0.95rem;'>{$sys_desc}</div>";
                                
                                // Se tiver comentário do usuário
                                if (count($parts) > 1) {
                                    $user_comment = nl2br(htmlspecialchars($parts[1]));
                                    echo "<div style='margin-top:8px; border-left: 3px solid #d32f2f; padding-left:10px;'>
                                            <span style='font-weight:800; color:black;'>Comentário Diego Vilela:</span>
                                            <div style='color:#d32f2f; font-weight:bold; margin-top:2px;'>{$user_comment}</div>
                                          </div>";
                                }
                            ?>
                        </td>

                        <td style="text-align:center; vertical-align:top;">
                           <a href="actions/admin/movimento_delete.php?cliente_id=<?= $cliente_ativo['id'] ?>&del_hist=<?= $h['id'] ?>" onclick="confirmAction(event, 'ATENÇÃO: Deseja realmente apagar este histórico? Essa ação é irreversível.')" style="text-decoration:none; color:#dc3545; font-size:1.1rem; padding:8px; background:#fff5f5; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px; transition:0.2s;" title="Excluir Histórico">
                               <span class="material-symbols-rounded" style="font-size:1.1rem;">delete</span>
                           </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Timeline e Andamento -->
<?php require 'includes/modals/timeline.php'; ?>
