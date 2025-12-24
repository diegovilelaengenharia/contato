<?php
// editar_cliente.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
        // Se senha nova foi informada
        if (!empty($_POST['nova_senha'])) {
            $nova_senha_hash = password_hash($_POST['nova_senha'], PASSWORD_DEFAULT);
            $stmtUp = $pdo->prepare("UPDATE clientes SET nome=?, usuario=?, senha=? WHERE id=?");
            $stmtUp->execute([$_POST['nome'], $_POST['usuario'], $nova_senha_hash, $cliente_id]);
        } else {
            $stmtUp = $pdo->prepare("UPDATE clientes SET nome=?, usuario=? WHERE id=?");
            $stmtUp->execute([$_POST['nome'], $_POST['usuario'], $cliente_id]);
        }

        // 2. Atualizar Detalhes
        // Verificar se já existe detalhes
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
        echo "<script>alert('Dados alterados com sucesso!'); window.close();</script>"; // Feedback simples e fecha, ou redireciona
        echo "<div style='padding:20px; text-align:center; font-family:sans-serif;'>✅ Dados salvos com sucesso! Pode fechar esta aba.</div>";
        // Recarregar dados
        $stmt->execute([$cliente_id]); $cliente = $stmt->fetch();
        $stmtDet->execute([$cliente_id]); $detalhes = $stmtDet->fetch();

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Erro ao salvar: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Cliente | Vilela Engenharia</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin_style.css">
    <style>
        body { background: #f0f2f5; padding: 20px; }
        .editor-container { max-width: 900px; margin: 0 auto; background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .section-title { font-size: 1.1rem; color: var(--color-primary); border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px; font-weight:700; margin-top: 30px; }
        .section-title:first-child { margin-top: 0; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; font-size: 0.9rem; color: #555; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-family: inherit; }
        .btn-submit { width: 100%; padding: 15px; background: var(--color-primary); color: white; border: none; border-radius: 8px; font-size: 1.1rem; font-weight: 700; cursor: pointer; margin-top: 30px; transition: 0.2s; }
        .btn-submit:hover { filter: brightness(1.1); }
    </style>
</head>
<body>
    <div class="editor-container">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h1 style="margin:0; font-size:1.5rem;">✏️ Editando: <?= htmlspecialchars($cliente['nome']) ?></h1>
            <button onclick="window.close()" style="background:#eee; border:none; padding:8px 15px; border-radius:6px; cursor:pointer;">Fechar</button>
        </div>

        <form method="POST">
            
            <div class="section-title">🔐 Acesso ao Sistema</div>
            <div class="form-grid">
                <div class="form-group"><label>Nome no Painel</label><input type="text" name="nome" value="<?= htmlspecialchars($cliente['nome']) ?>" required></div>
                <div class="form-group"><label>Usuário (Login)</label><input type="text" name="usuario" value="<?= htmlspecialchars($cliente['usuario']) ?>" required></div>
                <div class="form-group"><label>Nova Senha (deixe em branco p/ manter)</label><input type="text" name="nova_senha" placeholder="Digite apenas se quiser alterar"></div>
            </div>

            <div class="section-title">👤 Dados Pessoais</div>
            <div class="form-grid">
                <div class="form-group">
                    <label>Tipo Pessoa</label>
                    <select name="tipo_pessoa">
                        <option value="Fisica" <?= ($detalhes['tipo_pessoa']??'')=='Fisica'?'selected':'' ?>>Física</option>
                        <option value="Juridica" <?= ($detalhes['tipo_pessoa']??'')=='Juridica'?'selected':'' ?>>Jurídica</option>
                    </select>
                </div>
                <div class="form-group"><label>CPF / CNPJ</label><input type="text" name="cpf_cnpj" value="<?= htmlspecialchars($detalhes['cpf_cnpj']??'') ?>"></div>
                <div class="form-group"><label>RG / Inscrição Estadual</label><input type="text" name="rg_ie" value="<?= htmlspecialchars($detalhes['rg_ie']??'') ?>"></div>
            </div>
            <div class="form-grid" style="margin-top:15px;">
                <div class="form-group"><label>Email</label><input type="text" name="contato_email" value="<?= htmlspecialchars($detalhes['contato_email']??'') ?>"></div>
                <div class="form-group"><label>Telefone / WhatsApp</label><input type="text" name="contato_tel" value="<?= htmlspecialchars($detalhes['contato_tel']??'') ?>"></div>
                <div class="form-group"><label>Profissão</label><input type="text" name="profissao" value="<?= htmlspecialchars($detalhes['profissao']??'') ?>"></div>
                <div class="form-group"><label>Estado Civil</label><input type="text" name="estado_civil" value="<?= htmlspecialchars($detalhes['estado_civil']??'') ?>"></div>
            </div>
            <div class="form-group" style="margin-top:15px;"><label>Endereço Residencial</label><input type="text" name="endereco_residencial" value="<?= htmlspecialchars($detalhes['endereco_residencial']??'') ?>"></div>

            <div class="section-title">🏠 Dados do Imóvel (Obra)</div>
            <div class="form-grid">
                <div class="form-group" style="flex:2;"><label>Logradouro</label><input type="text" name="imovel_rua" value="<?= htmlspecialchars($detalhes['imovel_rua']??'') ?>"></div>
                <div class="form-group"><label>Número</label><input type="text" name="imovel_numero" value="<?= htmlspecialchars($detalhes['imovel_numero']??'') ?>"></div>
            </div>
            <div class="form-grid" style="margin-top:15px;">
                <div class="form-group"><label>Bairro</label><input type="text" name="imovel_bairro" value="<?= htmlspecialchars($detalhes['imovel_bairro']??'') ?>"></div>
                <div class="form-group"><label>Complemento</label><input type="text" name="imovel_complemento" value="<?= htmlspecialchars($detalhes['imovel_complemento']??'') ?>"></div>
            </div>
            <div class="form-grid" style="margin-top:15px;">
                <div class="form-group"><label>Cidade</label><input type="text" name="imovel_cidade" value="<?= htmlspecialchars($detalhes['imovel_cidade']??'') ?>"></div>
                <div class="form-group"><label>UF</label><input type="text" name="imovel_uf" value="<?= htmlspecialchars($detalhes['imovel_uf']??'') ?>"></div>
            </div>
            <div class="form-grid" style="margin-top:15px;">
                <div class="form-group"><label>Inscrição Imob.</label><input type="text" name="inscricao_imob" value="<?= htmlspecialchars($detalhes['inscricao_imob']??'') ?>"></div>
                <div class="form-group"><label>Matrícula</label><input type="text" name="num_matricula" value="<?= htmlspecialchars($detalhes['num_matricula']??'') ?>"></div>
            </div>
            <div class="form-grid" style="margin-top:15px;">
                <div class="form-group"><label>Área Lote (m²)</label><input type="text" name="imovel_area_lote" value="<?= htmlspecialchars($detalhes['imovel_area_lote']??($detalhes['area_terreno']??'')) ?>"></div>
                <div class="form-group"><label>Área Construída (m²)</label><input type="text" name="area_construida" value="<?= htmlspecialchars($detalhes['area_construida']??'') ?>"></div>
            </div>

            <div class="section-title">👷 Responsabilidade Técnica</div>
            <div class="form-group"><label>Responsável Técnico</label><input type="text" name="resp_tecnico" value="<?= htmlspecialchars($detalhes['resp_tecnico']??'') ?>"></div>
            <div class="form-grid" style="margin-top:15px;">
                <div class="form-group"><label>Registro Profissional</label><input type="text" name="registro_prof" value="<?= htmlspecialchars($detalhes['registro_prof']??'') ?>"></div>
                <div class="form-group"><label>ART / RRT</label><input type="text" name="num_art_rrt" value="<?= htmlspecialchars($detalhes['num_art_rrt']??'') ?>"></div>
            </div>

            <button type="submit" name="btn_salvar_tudo" class="btn-submit">💾 Salvar Alterações</button>
        </form>
    </div>
</body>
</html>
