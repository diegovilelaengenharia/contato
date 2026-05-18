<?php
/**
 * View Admin: Importar Cadastros do Site
 */
?>
<div class="form-card">
    <h2>Importar Cadastros do Site</h2>
    <p>Abaixo estão as solicitações de cadastro vindas da página pública.</p>
    <div class="table-responsive">
        <table style="width:100%; border-collapse:collapse; margin-top:20px;">
            <thead>
                <tr style="background:#eee;">
                    <th style="padding:10px;">Data</th>
                    <th style="padding:10px;">Nome</th>
                    <th style="padding:10px;">Contato</th>
                    <th style="padding:10px;">Serviço</th>
                    <th style="padding:10px;">Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                try {
                    $pendentes = $pdo->query("SELECT * FROM pre_cadastros WHERE status='pendente' ORDER BY data_solicitacao DESC")->fetchAll();
                    if(count($pendentes) == 0) echo "<tr><td colspan='5' style='padding:20px; text-align:center;'>Nenhuma solicitação pendente.</td></tr>";
                    foreach($pendentes as $p): ?>
                    <tr style="border-bottom:1px solid #eee;">
                        <td style="padding:10px;"><?= date('d/m/Y H:i', strtotime($p['data_solicitacao'])) ?></td>
                        <td style="padding:10px;"><strong><?= htmlspecialchars($p['nome']) ?></strong><br><small><?= $p['cpf_cnpj'] ?></small></td>
                        <td style="padding:10px;"><?= $p['telefone'] ?><br><small><?= $p['email'] ?></small></td>
                        <td style="padding:10px;"><?= $p['tipo_servico'] ?></td>
                        <td style="padding:10px; text-align:center;">
                            <button type="button" onclick="openAprovarModal(<?= $p['id'] ?>, '<?= addslashes($p['nome']) ?>', '<?= addslashes($p['cpf_cnpj']) ?>')" class="btn-save btn-success" style="padding:5px 10px; font-size:0.8rem; cursor:pointer; width:auto;">✅ Aprovar</button>
                        </td>
                    </tr>
                <?php endforeach; 
                } catch(Exception $e) { echo "<tr><td colspan='5'>Erro: Rode o setup_cadastro_db.php</td></tr>"; }
                ?>
            </tbody>
        </table>

        <?php require 'includes/modals/cadastro.php'; ?>
    </div>
</div>
