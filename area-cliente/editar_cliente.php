<?php
// editar_cliente.php

session_start();
if (!isset($_SESSION['admin_logado']) || $_SESSION['admin_logado'] !== true) {
    header('Location: index.php');
    exit;
}

require 'db.php';

$cliente_id = $_GET['id'] ?? null;
if (!$cliente_id) {
    die("ID do cliente não fornecido.");
}

// Buscar dados atuais
$stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt->execute([$cliente_id]);
$cliente = $stmt->fetch();

if (!$cliente) {
    die("Cliente não encontrado.");
}

// Buscar detalhes
$stmtDet = $pdo->prepare("SELECT * FROM clientes_detalhes WHERE cliente_id = ?");
$stmtDet->execute([$cliente_id]);
$detalhes = $stmtDet->fetch();

// Arrays para dropdowns
$tipos_pessoa = ['Fisica', 'Juridica'];
$estados_civil = ['Solteiro', 'Casado', 'Divorciado', 'Viuvo', 'Uniao Estavel'];

// Salvar Alterações
if (isset($_POST['btn_salvar_tudo'])) {
    try {
        $pdo->beginTransaction();

        // 1. Atualizar Clientes (Login)
        if (!empty($_POST['nova_senha'])) {
            $nova_senha_hash = password_hash($_POST['nova_senha'], PASSWORD_DEFAULT);
            $stmtUp = $pdo->prepare("UPDATE clientes SET nome=?, usuario=?, senha=? WHERE id=?");
            $stmtUp->execute([$_POST['nome'], $_POST['usuario'], $nova_senha_hash, $cliente_id]);
        } else {
            $stmtUp = $pdo->prepare("UPDATE clientes SET nome=?, usuario=? WHERE id=?");
            $stmtUp->execute([$_POST['nome'], $_POST['usuario'], $cliente_id]);
        }

        // 2. Atualizar Detalhes
        if ($detalhes) {
            $sqlDet = "UPDATE clientes_detalhes SET 
                tipo_pessoa=?, cpf_cnpj=?, rg_ie=?, contato_email=?, contato_tel=?, 
                endereco_residencial=?, profissao=?, estado_civil=?, imovel_rua=?, imovel_numero=?,
                imovel_bairro=?, imovel_complemento=?, imovel_cidade=?, imovel_uf=?, inscricao_imob=?,
                num_matricula=?, imovel_area_lote=?, area_construida=?, resp_tecnico=?, registro_prof=?, num_art_rrt=?
                WHERE cliente_id=?";
        } else {
            $sqlDet = "INSERT INTO clientes_detalhes (
                tipo_pessoa, cpf_cnpj, rg_ie, contato_email, contato_tel, 
                endereco_residencial, profissao, estado_civil, imovel_rua, imovel_numero,
                imovel_bairro, imovel_complemento, imovel_cidade, imovel_uf, inscricao_imob,
                num_matricula, imovel_area_lote, area_construida, resp_tecnico, registro_prof, num_art_rrt, cliente_id
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        }
        
        $stmtDetUp = $pdo->prepare($sqlDet);
        $stmtDetUp->execute([
            $_POST['tipo_pessoa'], $_POST['cpf_cnpj'], $_POST['rg_ie'], $_POST['contato_email'], $_POST['contato_tel'],
            $_POST['endereco_residencial'], $_POST['profissao'], $_POST['estado_civil'], $_POST['imovel_rua'], $_POST['imovel_numero'],
            $_POST['imovel_bairro'], $_POST['imovel_complemento'], $_POST['imovel_cidade'], $_POST['imovel_uf'], $_POST['inscricao_imob'],
            $_POST['num_matricula'], $_POST['imovel_area_lote'], $_POST['area_construida'], $_POST['resp_tecnico'], $_POST['registro_prof'], $_POST['num_art_rrt'],
            $cliente_id
        ]);

        $pdo->commit();
        echo "<script>
            alert('✅ Alterações salvas com sucesso!');
            window.close(); // Tenta fechar
            // Fallback se não fechar
            window.location.href = 'editar_cliente.php?id=" . $cliente_id . "&success=1';
        </script>";
        
        // Recarregar dados
        $stmt->execute([$cliente_id]); $cliente = $stmt->fetch();
        $stmtDet->execute([$cliente_id]); $detalhes = $stmtDet->fetch();

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<script>alert('❌ Erro ao salvar: " . addslashes($e->getMessage()) . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Cliente | Vilela Engenharia</title>
    <link href="https://fonts.googleapis.com/css2?family=outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin_style.css">
    <style>
        /* Sincronizando com admin_style.css */
        :root {
            --primary: var(--color-primary, #146c43);
            --primary-hover: #0f5132;
            --bg-page: var(--color-bg, #f8f9fa);
            --bg-card: var(--color-surface, #ffffff);
            --text-main: var(--color-text, #2c3e50);
            --text-sub: var(--color-text-subtle, #7f8c8d);
            --border-color: var(--color-border, #e2e8f0);
        }
        /* ... outros estilos ... */
    </style>
</head>
<body>

    <form method="POST" class="main-wrapper">
        
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="page-title">
                <h1>
                    Configurações do Cliente
                    <span>ID #<?= str_pad($cliente['id'], 3, '0', STR_PAD_LEFT) ?></span>
                </h1>
            </div>
            <!-- Botão Voltar Robusto -->
            <a href="gestao_admin_99.php?cliente_id=<?= $cliente_id ?>&tab=cadastro" class="btn-close">
                ⬅️ Voltar ao Painel
            </a>
        </div>

        <div class="form-container">
            
            <!-- (Conteúdo omitido para brevidade permanece igual) -->
            <!-- ... -->

            <!-- Lazy Load Section Body (Assume sections are here) -->
            <?php // O conteúdo das seções permanece inalterado ?>
            
            <!-- Sticky Save -->
            <div class="sticky-footer">
                <span style="font-size:0.9rem; color:var(--text-sub); margin-right:auto;">
                    ⚠️ Todas as alterações são salvas imediatamente no banco.
                </span>
                <a href="gestao_admin_99.php?cliente_id=<?= $cliente_id ?>&tab=cadastro" class="btn-close" style="background:none; text-decoration:none;">Cancelar</a>
                <button type="submit" name="btn_salvar_tudo" class="btn-save">
                    💾 Salvar Alterações
                </button>
            </div>

        </div>
    </form>

        </div>
    </form>

</body>
</html>
