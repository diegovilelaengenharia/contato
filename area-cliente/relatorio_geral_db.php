<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'includes/init.php';

// Auth Check
if (!isset($_SESSION['admin_logado'])) {
    die("Acesso negado. Apenas admin.");
}

// 1. Fetch all Clients
$clientes = $pdo->query("SELECT * FROM clientes ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório Técnico de Dados - Vilela Engenharia</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f4f4f4; }
        h1 { margin-bottom: 5px; }
        .card { background: white; padding: 20px; margin-bottom: 20px; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; font-size: 12px; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; vertical-align: top; }
        th { background: #eee; }
        .section-title { font-size: 1.2rem; font-weight: bold; margin-bottom: 10px; border-bottom: 2px solid #333; padding-bottom: 5px; color: #146c43; }
        .empty { color: #aaa; font-style: italic; }
        .raw-data { background: #222; color: #0f0; padding: 10px; border-radius: 5px; overflow-x: auto; white-space: pre-wrap; font-size: 11px; margin-top:5px; }
        details { margin-top: 5px; cursor: pointer; }
        summary { font-weight: bold; color: #0056b3; }
    </style>
</head>
<body>

    <h1>Relatório Técnico: Variáveis Salvas</h1>
    <p>Este relatório exibe <strong>dados brutos</strong> do banco de dados para conferência técnica.</p>
    <hr>

    <?php foreach($clientes as $cli): 
        $cid = $cli['id'];
        // Fetch Details
        $det = $pdo->query("SELECT * FROM processo_detalhes WHERE cliente_id = $cid")->fetch(PDO::FETCH_ASSOC);
        // Fetch Extras
        $extras = $pdo->query("SELECT * FROM processo_campos_extras WHERE cliente_id = $cid")->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <div class="card">
        <div class="section-title">
            #<?= $cid ?> | <?= htmlspecialchars($cli['nome']) ?>
            <span style="float:right; font-size:0.8rem; font-weight:normal;">Usuário: <?= $cli['usuario'] ?></span>
        </div>

        <h3>Tabela: Clientes</h3>
        <table>
            <tr>
                <th>ID</th><th>Nome</th><th>Usuário</th><th>Senha (Hash)</th><th>Tipo</th><th>Foto Perfil</th>
            </tr>
            <tr>
                <td><?= $cli['id'] ?></td>
                <td><?= $cli['nome'] ?></td>
                <td><?= $cli['usuario'] ?></td>
                <td><?= substr($cli['senha'], 0, 10) ?>...</td>
                <td><?= $cli['tipo'] ?></td>
                <td><?= $cli['foto_perfil'] ?? '<span class="empty">NULL</span>' ?></td>
            </tr>
        </table>

        <h3>Tabela: Processo Detalhes</h3>
        <?php if($det): ?>
            <table>
                <tr>
                    <th>Coluna</th>
                    <th>Valor Salvo</th>
                </tr>
                <?php foreach($det as $key => $val): 
                    if($key == 'id' || $key == 'cliente_id') continue; 
                ?>
                <tr>
                    <td><strong><?= $key ?></strong></td>
                    <td><?= $val !== null ? htmlspecialchars($val) : '<span class="empty">NULL</span>' ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p class="empty">Nenhum registro encontrado na tabela 'processo_detalhes'.</p>
        <?php endif; ?>

        <h3>Tabela: Campos Extras (Dinâmicos)</h3>
        <?php if(!empty($extras)): ?>
            <table>
                <tr><th>ID</th><th>Título</th><th>Valor</th></tr>
                <?php foreach($extras as $ex): ?>
                <tr>
                    <td><?= $ex['id'] ?></td>
                    <td><?= htmlspecialchars($ex['titulo']) ?></td>
                    <td><?= htmlspecialchars($ex['valor']) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p class="empty">Nenhum campo extra salvo.</p>
        <?php endif; ?>
        
        <details>
            <summary>Ver Dump Completo (RAW)</summary>
            <div class="raw-data">
CLIENTE: <?= print_r($cli, true) ?>
----------------------------------
DETALHES: <?= print_r($det, true) ?>
----------------------------------
EXTRAS: <?= print_r($extras, true) ?>
            </div>
        </details>
    </div>

    <?php endforeach; ?>

</body>
</html>
