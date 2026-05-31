<?php
/**
 * View Admin: Timeline e Histórico do Processo
 */
?>
<div class="admin-tab-content">
    <div class="admin-header-row">
        <div>
            <h3 class="admin-title">Histórico do Processo</h3>
            <p class="admin-subtitle">Linha do tempo completa e registros do cliente.</p>
        </div>

        <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
            <button type="button" class="btn-save" onclick="document.getElementById('modalAndamento').showModal()">
                <span class="material-symbols-rounded">add_circle</span> Novo Andamento
            </button>
            <button type="button" class="btn-save btn-ghost" onclick="document.getElementById('modalVisualTimeline').showModal()">
                <span class="material-symbols-rounded">visibility</span> Ver Timeline
            </button>
            <a href="actions/admin/movimento_clear_all.php?cliente_id=<?= $cliente_ativo['id'] ?>&del_all_hist=true"
               class="btn-save btn-danger"
               onclick="return confirm('ATENÇÃO EXTREMA: \n\nVocê está prestes a APAGAR TODO O HISTÓRICO deste processo.\n\nIsso limpará todas as movimentações, datas e logs.\n\nTem certeza absoluta que deseja fazer isso?');"
               title="Limpar todo o histórico">
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
                $movimentos = $hist->fetchAll();
                if (count($movimentos) == 0): ?>
                    <tr><td colspan="3" style="padding:30px; text-align:center; color:var(--color-muted); font-style:italic;">Nenhum registro no histórico ainda.</td></tr>
                <?php else: foreach($movimentos as $h):
                    // Realce sutil por tipo de movimento
                    $accent = '';
                    $tipo = $h['tipo_movimento'] ?? 'padrao';
                    if ($tipo == 'fase_inicio') {
                        $accent = 'border-left:4px solid #6610f2;';
                    } elseif ($tipo == 'documento') {
                        $accent = 'border-left:4px solid var(--color-primary);';
                    }
                ?>
                    <tr style="<?= $accent ?>">
                        <td style="white-space:nowrap; vertical-align:top; color:var(--color-text-subtle);">
                            <?= date('d/m/Y H:i', strtotime($h['data_movimento'])) ?>
                        </td>
                        <td>
                            <div style="font-weight:700; margin-bottom:5px; color:var(--color-text);"><?= htmlspecialchars($h['titulo_fase']) ?></div>
                            <?php
                                $parts = explode("||COMENTARIO_USER||", $h['descricao']);
                                $sys_desc = $parts[0];
                                echo "<div style='color:var(--color-text-subtle); line-height:1.5; font-size:0.92rem;'>{$sys_desc}</div>";
                                if (count($parts) > 1) {
                                    $user_comment = nl2br(htmlspecialchars($parts[1]));
                                    echo "<div style='margin-top:8px; border-left:3px solid var(--color-danger); padding-left:10px;'>
                                            <span style='font-weight:800;'>Comentário Diego Vilela:</span>
                                            <div style='color:var(--color-danger); font-weight:700; margin-top:2px;'>{$user_comment}</div>
                                          </div>";
                                }
                            ?>
                        </td>
                        <td style="text-align:center; vertical-align:top;">
                           <a href="actions/admin/movimento_delete.php?cliente_id=<?= $cliente_ativo['id'] ?>&del_hist=<?= $h['id'] ?>"
                              class="btn-icon danger"
                              onclick="confirmAction(event, 'ATENÇÃO: Deseja realmente apagar este histórico? Essa ação é irreversível.')"
                              title="Excluir histórico">
                               <span class="material-symbols-rounded">delete</span>
                           </a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Timeline e Andamento -->
<?php require 'includes/modals/timeline.php'; ?>
