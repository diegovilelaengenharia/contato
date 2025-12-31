<?php
// editar_cliente.php

session_start();
// Debug para erro 500
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['admin_logado']) || $_SESSION['admin_logado'] !== true) {
    header('Location: index.php');
    exit;
}

// Output Buffering para evitar erro de Header sent
ob_start();

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
// Buscar detalhes
$stmtDet = $pdo->prepare("SELECT * FROM processo_detalhes WHERE cliente_id = ?");
$stmtDet->execute([$cliente_id]);
$detalhes = $stmtDet->fetch();

// Buscar campos extras
try {
    $stmtEx = $pdo->prepare("SELECT * FROM processo_campos_extras WHERE cliente_id = ?");
    $stmtEx->execute([$cliente_id]);
    $campos_extras = $stmtEx->fetchAll();
} catch (Exception $e) {
    // Tabela não existe? Criar agora.
    $pdo->exec("CREATE TABLE IF NOT EXISTS processo_campos_extras (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cliente_id INT NOT NULL,
        titulo VARCHAR(255) NOT NULL,
        valor TEXT,
        FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
    )");
    $campos_extras = [];
}

// Arrays para dropdowns
$tipos_pessoa = ['Fisica', 'Juridica'];
$estados_civil = ['Solteiro', 'Casado', 'Divorciado', 'Viuvo', 'Uniao Estavel'];

// Salvar Alterações
if (isset($_POST['btn_salvar_tudo'])) {
    try {
        // DEBUG: Gravar POST em arquivo para análise
        // file_put_contents('debug_post.log', print_r($_POST, true)); 
        
        // --- UPLOAD LOGIC ---
        // 1. Avatar (Profile)
        if(isset($_FILES['avatar_upload']) && $_FILES['avatar_upload']['error'] == 0) {
            $ext = strtolower(pathinfo($_FILES['avatar_upload']['name'], PATHINFO_EXTENSION));
            if(in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $new_name = 'avatar_' . $cliente_id . '.' . $ext;
                $target = 'uploads/avatars/' . $new_name;
                if(!is_dir('uploads/avatars/')) mkdir('uploads/avatars/', 0755, true);
                if(move_uploaded_file($_FILES['avatar_upload']['tmp_name'], $target)) {
                    // Update DB column 'foto_perfil' if it exists, or rely on naming convention
                    // Ideally we should update a column, but for now we follow existing convention or add if able.
                    // Assuming column might not exist, we just save file. 
                    // If you want to save to DB:
                    // $pdo->prepare("UPDATE clientes SET foto_perfil=? WHERE id=?")->execute([$target, $cliente_id]);
                }
            }
        }

        // 2. Work Cover (Foto Capa Obra)
        if(isset($_FILES['foto_capa_obra']) && $_FILES['foto_capa_obra']['error'] == 0) {
            $ext = strtolower(pathinfo($_FILES['foto_capa_obra']['name'], PATHINFO_EXTENSION));
            if(in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $new_name = 'capa_obra_' . $cliente_id . '_' . time() . '.' . $ext;
                $target = 'uploads/obras/' . $new_name;
                if(!is_dir('uploads/obras/')) mkdir('uploads/obras/', 0755, true);
                if(move_uploaded_file($_FILES['foto_capa_obra']['tmp_name'], $target)) {
                    $pdo->prepare("UPDATE processo_detalhes SET foto_capa_obra=? WHERE cliente_id=?")->execute([$target, $cliente_id]);
                }
            }
        }

        
        // DDL causes implicit commit in MySQL, so run it before transaction
        $pdo->exec("CREATE TABLE IF NOT EXISTS processo_campos_extras (
            id INT AUTO_INCREMENT PRIMARY KEY,
            cliente_id INT NOT NULL,
            titulo VARCHAR(255) NOT NULL,
            valor TEXT,
            FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
        )");

        $pdo->beginTransaction();

        // 1. Atualizar Clientes (Login)
        // 1. Atualizar Clientes (Login + Nome)
        if (!empty($_POST['nova_senha'])) {
            $nova_senha_hash = password_hash($_POST['nova_senha'], PASSWORD_DEFAULT);
            $stmtUp = $pdo->prepare("UPDATE clientes SET nome=?, usuario=?, senha=? WHERE id=?");
            $stmtUp->execute([trim($_POST['nome']), $_POST['usuario'], $nova_senha_hash, $cliente_id]);
        } else {
            $stmtUp = $pdo->prepare("UPDATE clientes SET nome=?, usuario=? WHERE id=?");
            $stmtUp->execute([trim($_POST['nome']), $_POST['usuario'], $cliente_id]);
        }

        // 2. Atualizar Detalhes
        if ($detalhes) {
            $sqlDet = "UPDATE processo_detalhes SET 
                tipo_pessoa=?, cpf_cnpj=?, rg_ie=?, nacionalidade=?, contato_email=?, contato_tel=?, 
                res_rua=?, res_numero=?, res_bairro=?, res_complemento=?, res_cidade=?, res_uf=?,
                profissao=?, estado_civil=?, imovel_rua=?, imovel_numero=?,
                imovel_bairro=?, imovel_complemento=?, imovel_cidade=?, imovel_uf=?, inscricao_imob=?,

                num_matricula=?, imovel_area_lote=?, area_construida=?
                WHERE cliente_id=?";
        } else {
            $sqlDet = "INSERT INTO processo_detalhes (
                tipo_pessoa, cpf_cnpj, rg_ie, nacionalidade, contato_email, contato_tel, 
                res_rua, res_numero, res_bairro, res_complemento, res_cidade, res_uf,
                profissao, estado_civil, imovel_rua, imovel_numero,
                imovel_bairro, imovel_complemento, imovel_cidade, imovel_uf, inscricao_imob,
                num_matricula, imovel_area_lote, area_construida, cliente_id
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        }
        
        $stmtDetUp = $pdo->prepare($sqlDet);
        $stmtDetUp->execute([
            $_POST['tipo_pessoa'], $_POST['cpf_cnpj'], $_POST['rg_ie'], $_POST['nacionalidade']??'', $_POST['contato_email'], $_POST['contato_tel'],
            $_POST['res_rua'], $_POST['res_numero'], $_POST['res_bairro'], $_POST['res_complemento'], $_POST['res_cidade'], $_POST['res_uf'],
            $_POST['profissao'], $_POST['estado_civil'], $_POST['imovel_rua'], $_POST['imovel_numero'],
            $_POST['imovel_bairro'], $_POST['imovel_complemento'], $_POST['imovel_cidade'], $_POST['imovel_uf'], $_POST['inscricao_imob'],
            $_POST['num_matricula'], $_POST['imovel_area_lote'], $_POST['area_construida'],
            $cliente_id
        ]);

        // 3. Atualizar Campos Extras
        // (Tabela verificada antes da transação)

        $pdo->prepare("DELETE FROM processo_campos_extras WHERE cliente_id = ?")->execute([$cliente_id]);
        
        if (isset($_POST['extra_titulos']) && is_array($_POST['extra_titulos'])) {
            $titulos = $_POST['extra_titulos'];
            $valores = $_POST['extra_valores'] ?? [];
            
            $stmtInsEx = $pdo->prepare("INSERT INTO processo_campos_extras (cliente_id, titulo, valor) VALUES (?, ?, ?)");
            
            foreach ($titulos as $key => $titulo) {
                // Remove espaços em branco e verifica se tem conteudo
                $titulo_limpo = trim($titulo);
                $valor_limpo = trim($valores[$key] ?? '');
                
                if (!empty($titulo_limpo)) {
                    $stmtInsEx->execute([$cliente_id, $titulo_limpo, $valor_limpo]);
                }
            }
        }

        $pdo->commit();
        echo "<script>
            alert('✅ Alterações salvas com sucesso!');
            // window.close(); // Desabilitado por solicitação
            // Recarrega a página para mostrar dados atualizados
            window.location.href = 'editar_cliente.php?id=" . $cliente_id . "&success=1';
        </script>";
        
        // Recarregar dados
        $stmt->execute([$cliente_id]); $cliente = $stmt->fetch();
        $stmt->execute([$cliente_id]); $cliente = $stmt->fetch();
        $stmtDet->execute([$cliente_id]); $detalhes = $stmtDet->fetch();
        $stmtEx->execute([$cliente_id]); $campos_extras = $stmtEx->fetchAll();

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
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
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
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
        
        body { 
            background: var(--bg-page); 
            font-family: 'Outfit', sans-serif; 
            color: var(--text-main);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }

        .main-wrapper {
            max-width: 1600px; 
            margin: 0 auto; 
            animation: fadeIn 0.4s ease;
        }

        @keyframes fadeIn { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }

        /* Header */
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            background: white;
            padding: 15px 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
        }

        .page-title h1 {
            margin: 0;
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--primary);
            display: flex; 
            align-items: center; 
            gap: 10px;
        }

        .page-title span {
            font-size: 0.9rem;
            color: var(--text-sub);
            font-weight: 400;
            background: #eee;
            padding: 2px 8px;
            border-radius: 4px;
        }

        .btn-close {
            background: #eef2f5;
            color: #555;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-close:hover { background: #dfe4ea; color: #333; }

        /* Form Structure */
        .form-container {
            background: var(--bg-card);
            border-radius: 16px;
            box-shadow: 0 4px 25px rgba(0,0,0,0.04);
            overflow: hidden;
            border: 1px solid var(--border-color);
        }

        .section-header {
            background: #fdfdfd;
            padding: 12px 25px;
            border-bottom: 1px solid var(--border-color);
            border-top: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section-header:first-child { border-top: none; }

        .section-header h2 {
            margin: 0;
            font-size: 1.1rem;
            color: var(--primary);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .section-icon {
            width: 32px; height: 32px;
            background: #e6f4ea;
            color: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        .section-body {
            padding: 20px 25px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        .form-group {
            margin-bottom: 5px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 12px 15px;
            font-size: 1rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-main);
            background: #fbfbfb;
            transition: all 0.2s;
            font-family: 'Outfit', sans-serif;
        }

        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(20, 108, 67, 0.1);
        }

        .form-group input[readonly] {
            background: #eee;
            color: #888;
            cursor: not-allowed;
        }

        /* Sticky Footer */
        .sticky-footer {
            position: sticky;
            bottom: 0;
            background: white;
            padding: 20px 30px;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            align-items: center;
            box-shadow: 0 -5px 20px rgba(0,0,0,0.03);
            gap: 15px;
        }
        
        .btn-save {
            background: var(--primary);
            color: white;
            border: none;
            padding: 14px 40px;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 15px rgba(20, 108, 67, 0.3);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .btn-save:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
        }

        /* Responsive */
        @media(max-width: 768px) {
            body { padding: 10px; }
            .section-body { padding: 20px; }
            .sticky-footer { flex-direction: column; }
            .btn-save { width: 100%; justify-content: center; }
        }
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
            <a href="gestao_admin_99.php?cliente_id=<?= $cliente_id ?>&tab=andamento" class="btn-close">
                ⬅️ Voltar ao Painel
            </a>
        </div>

        <div class="form-container">
            
            <!-- SECTION 1: LOGIN -->
            <div class="section-header">
                <div class="section-icon">🔐</div>
                <h2>Acesso & Segurança</h2>
            </div>
            <div class="section-body">
                <div class="grid">
                    <!-- Campo Nome movido para Dados do Titular -->
                    <div class="form-group">
                        <label>Usuário (Login)</label>
                        <input type="text" name="usuario" value="<?= htmlspecialchars($cliente['usuario']) ?>" required style="font-family:monospace; color:#2980b9;">
                    </div>
                    <div class="form-group">
                        <label>Senha de Acesso</label>
                        <input type="text" name="nova_senha" placeholder="Preencha apenas se for trocar a senha" style="border-style:dashed;">
                    </div>
                </div>
            </div>

            <!-- SECTION 2: PERSONAL -->
            <div class="section-header">
                <div class="section-icon">👤</div>
                <h2>Dados do Titular</h2>
            </div>
            <div class="section-body">
                <div class="grid">
                    <div class="form-group" style="grid-column: span 2;">
                        <label>ID do Cliente</label>
                        <input type="text" value="<?= str_pad($cliente['id'], 3, '0', STR_PAD_LEFT) ?>" readonly style="background:#e9ecef; color:#555; font-weight:bold; width:80px; text-align:center;">
                    </div>
                    
                    <div class="form-group" style="grid-column: span 2;">
                        <label>📸 Foto de Perfil</label>
                         <div style="display:flex; gap:10px; align-items:center;">
                            <input type="file" name="avatar_upload" accept="image/*" style="padding:10px; border:1px solid #ddd; border-radius:8px; width:100%;">
                            <?php 
                                $avatar = glob("uploads/avatars/avatar_{$cliente['id']}.*");
                                if(!empty($avatar)) echo "<img src='{$avatar[0]}?".time()."' style='width:40px; height:40px; border-radius:50%; object-fit:cover; border:1px solid #ddd;'>";
                            ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Natureza Jurídica</label>
                        <select name="tipo_pessoa">
                            <option value="Fisica" <?= ($detalhes['tipo_pessoa']??'')=='Fisica'?'selected':'' ?>>Pessoa Física (CPF)</option>
                            <option value="Juridica" <?= ($detalhes['tipo_pessoa']??'')=='Juridica'?'selected':'' ?>>Pessoa Jurídica (CNPJ)</option>
                        </select>
                    </div>
                </div>
                
                <div class="grid" style="margin-top:20px;">
                    <div class="form-group" style="grid-column: span 2;">
                        <label>Nome Completo</label>
                        <input type="text" name="nome" value="<?= htmlspecialchars($cliente['nome']) ?>" required placeholder="Nome Completo">
                    </div>
                    <div class="form-group">
                        <label>CPF / CNPJ</label>
                        <input type="text" name="cpf_cnpj" value="<?= htmlspecialchars($detalhes['cpf_cnpj']??'') ?>">
                    </div>
                    <div class="form-group">
                        <label>RG / Inscrição Estadual</label>
                        <input type="text" name="rg_ie" value="<?= htmlspecialchars($detalhes['rg_ie']??'') ?>">
                    </div>
                </div>
                
                <div class="grid" style="margin-top:25px;">
                    <div class="form-group">
                        <label>Email Principal</label>
                        <input type="email" name="contato_email" value="<?= htmlspecialchars($detalhes['contato_email']??'') ?>">
                    </div>
                    <div class="form-group">
                        <label>WhatsApp / Telefone</label>
                        <input type="text" name="contato_tel" value="<?= htmlspecialchars($detalhes['contato_tel']??'') ?>">
                    </div>
                </div>

                <div class="grid" style="margin-top:25px;">
                    <div class="form-group">
                        <label>Nacionalidade</label>
                        <input type="text" name="nacionalidade" value="<?= htmlspecialchars($detalhes['nacionalidade']??'') ?>" placeholder="Ex: Brasileira">
                    </div>
                    <div class="form-group">
                        <label>Profissão</label>
                        <input type="text" name="profissao" value="<?= htmlspecialchars($detalhes['profissao']??'') ?>">
                    </div>
                    <div class="form-group">
                        <label>Estado Civil</label>
                        <input type="text" name="estado_civil" value="<?= htmlspecialchars($detalhes['estado_civil']??'') ?>">
                    </div>
                </div>
                
                <div class="grid" style="margin-top:25px;">
                     <!-- Endereço Residencial Dividido -->
                    <div class="form-group" style="grid-column: span 3;">
                        <h4 style="margin:10px 0 5px 0; color:var(--text-main); font-size:0.9rem;">Endereço Residencial</h4>
                    </div>
                </div>
                <div class="grid" style="grid-template-columns: 3fr 1fr;">
                    <div class="form-group">
                        <label>Logradouro (Rua/Av)</label>
                        <input type="text" name="res_rua" value="<?= htmlspecialchars($detalhes['res_rua']??'') ?>">
                    </div>
                    <div class="form-group">
                        <label>Número</label>
                        <input type="text" name="res_numero" value="<?= htmlspecialchars($detalhes['res_numero']??'') ?>">
                    </div>
                </div>
                <div class="grid" style="margin-top:15px;">
                    <div class="form-group">
                        <label>Bairro</label>
                        <input type="text" name="res_bairro" value="<?= htmlspecialchars($detalhes['res_bairro']??'') ?>">
                    </div>
                    <div class="form-group">
                        <label>Complemento</label>
                        <input type="text" name="res_complemento" value="<?= htmlspecialchars($detalhes['res_complemento']??'') ?>">
                    </div>
                    <div class="form-group">
                        <label>Cidade</label>
                        <input type="text" name="res_cidade" value="<?= htmlspecialchars($detalhes['res_cidade']??'') ?>">
                    </div>
                    <div class="form-group">
                        <label>UF</label>
                        <input type="text" name="res_uf" value="<?= htmlspecialchars($detalhes['res_uf']??'') ?>" maxlength="2">
                    </div>
                </div>
            </div>

            <!-- SECTION 3: PROPERTY -->
            <div class="section-header">
                <div class="section-icon">🏠</div>
                <h2>Dados do Imóvel (Obra)</h2>
            </div>
            <div class="section-body">
                <!-- Foto Obra -->
                <div class="form-group" style="margin-bottom:20px;">
                    <label>🖼️ Foto da Capa (Obra/Fachada)</label>
                    <div style="display:flex; gap:10px; align-items:center;">
                        <input type="file" name="foto_capa_obra" accept="image/*" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px; background:#f9f9f9;">
                        <?php if(!empty($detalhes['foto_capa_obra'])): ?>
                            <a href="<?= $detalhes['foto_capa_obra'] ?>" target="_blank">
                                <img src="<?= $detalhes['foto_capa_obra'] ?>" style="height:50px; border-radius:4px; border:1px solid #ddd;">
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="grid" style="grid-template-columns: 3fr 1fr;">
                    <div class="form-group">
                        <label>Logradouro (Rua/Av)</label>
                        <input type="text" name="imovel_rua" value="<?= htmlspecialchars($detalhes['imovel_rua']??'') ?>">
                    </div>
                    <div class="form-group">
                        <label>Número</label>
                        <input type="text" name="imovel_numero" value="<?= htmlspecialchars($detalhes['imovel_numero']??'') ?>">
                    </div>
                </div>
                
                <div class="grid" style="margin-top:25px;">
                    <div class="form-group">
                        <label>Bairro</label>
                        <input type="text" name="imovel_bairro" value="<?= htmlspecialchars($detalhes['imovel_bairro']??'') ?>">
                    </div>
                    <div class="form-group">
                        <label>Complemento</label>
                        <input type="text" name="imovel_complemento" value="<?= htmlspecialchars($detalhes['imovel_complemento']??'') ?>">
                    </div>
                </div>

                <div class="grid" style="margin-top:25px;">
                    <div class="form-group">
                        <label>Cidade</label>
                        <input type="text" name="imovel_cidade" value="<?= htmlspecialchars($detalhes['imovel_cidade']??'') ?>">
                    </div>
                    <div class="form-group">
                        <label>Estado (UF)</label>
                        <input type="text" name="imovel_uf" value="<?= htmlspecialchars($detalhes['imovel_uf']??'') ?>" maxlength="2" style="text-transform:uppercase;">
                    </div>
                </div>

                <div class="grid" style="margin-top:25px;">
                    <div class="form-group">
                        <label>Inscrição Imobiliária (IPTU)</label>
                        <input type="text" name="inscricao_imob" value="<?= htmlspecialchars($detalhes['inscricao_imob']??'') ?>">
                    </div>
                    <div class="form-group">
                        <label>Matrícula do Cartório</label>
                        <input type="text" name="num_matricula" value="<?= htmlspecialchars($detalhes['num_matricula']??'') ?>">
                    </div>
                </div>

                <div class="grid" style="margin-top:25px;">
                    <div class="form-group">
                        <label>Área do Lote (m²)</label>
                        <input type="text" name="imovel_area_lote" value="<?= htmlspecialchars($detalhes['imovel_area_lote']??($detalhes['area_terreno']??'')) ?>">
                    </div>
                    <div class="form-group">
                        <label>Área Construída Presumida (m²)</label>
                        <input type="text" name="area_construida" value="<?= htmlspecialchars($detalhes['area_construida']??'') ?>">
                    </div>
                </div>
            </div>

            <!-- Seção Técnica Removida conforme solicitação -->

            <!-- SECTION 5: CUSTOM FIELDS (DINÂMICOS) -->
            <div class="section-header">
                <div class="section-icon">📝</div>
                <h2>Outras Informações</h2>
            </div>
            <div class="section-body">
                <p style="font-size:0.9rem; color:#666; margin-bottom:20px;">Use esta seção para adicionar dados personalizados (Ex: CNH, Nome do Cônjuge, etc).</p>
                
                <div id="container-campos-extras" style="display:flex; flex-direction:column; gap:15px;">
                    <?php foreach($campos_extras as $ex): ?>
                        <div class="extra-field-row" style="background:#f9f9f9; padding:15px; border-radius:8px; border:1px solid #eee; display:flex; gap:15px; align-items:flex-end;">
                            <div style="flex:1;">
                                <label style="display:block; margin-bottom:5px; font-size:0.8rem; font-weight:bold; color:#555;">Título do Campo</label>
                                <input type="text" name="extra_titulos[]" value="<?= htmlspecialchars($ex['titulo']) ?>" placeholder="Ex: CNH" style="width:100%; border:1px solid #ddd; padding:10px; border-radius:6px;">
                            </div>
                            <div style="flex:2;">
                                <label style="display:block; margin-bottom:5px; font-size:0.8rem; font-weight:bold; color:#555;">Informação / Valor</label>
                                <input type="text" name="extra_valores[]" value="<?= htmlspecialchars($ex['valor']) ?>" placeholder="Digite a informação..." style="width:100%; border:1px solid #ddd; padding:10px; border-radius:6px;">
                            </div>
                            <button type="button" onclick="this.parentElement.remove()" style="height:42px; width:42px; display:flex; align-items:center; justify-content:center; background:#fff; color:#e74c3c; border:1px solid #e74c3c; border-radius:6px; cursor:pointer; transition:0.2s;" onmouseover="this.style.background='#e74c3c'; this.style.color='white';" onmouseout="this.style.background='#fff'; this.style.color='#e74c3c';">
                                <span class="material-symbols-rounded">delete</span>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>

                <button type="button" onclick="addExtraField()" style="margin-top:20px; display:flex; align-items:center; gap:8px; background:#f0f8f5; color:#146c43; border:1px dashed #146c43; padding:12px 20px; border-radius:8px; cursor:pointer; font-weight:600; width:100%; justify-content:center; transition:0.2s;" onmouseover="this.style.background='#e6f4ea'">
                    <span class="material-symbols-rounded">add_circle</span> Adicionar Novo Campo
                </button>

                <script>
                    function addExtraField() {
                        const div = document.createElement('div');
                        div.className = 'extra-field-row';
                        div.style.cssText = 'background:#f9f9f9; padding:15px; border-radius:8px; border:1px solid #eee; display:flex; gap:15px; align-items:flex-end; animation:fadeIn 0.3s; margin-top:10px;';
                        div.innerHTML = `
                            <div style="flex:1;">
                                <label style="display:block; margin-bottom:5px; font-size:0.8rem; font-weight:bold; color:#555;">Título do Campo</label>
                                <input type="text" name="extra_titulos[]" placeholder="Ex: CNH" style="width:100%; border:1px solid #ddd; padding:10px; border-radius:6px;">
                            </div>
                            <div style="flex:2;">
                                <label style="display:block; margin-bottom:5px; font-size:0.8rem; font-weight:bold; color:#555;">Informação / Valor</label>
                                <input type="text" name="extra_valores[]" placeholder="Digite a informação..." style="width:100%; border:1px solid #ddd; padding:10px; border-radius:6px;">
                            </div>
                            <button type="button" onclick="this.parentElement.remove()" style="height:42px; width:42px; display:flex; align-items:center; justify-content:center; background:#fff; color:#e74c3c; border:1px solid #e74c3c; border-radius:6px; cursor:pointer; transition:0.2s;" onmouseover="this.style.background='#e74c3c'; this.style.color='white';" onmouseout="this.style.background='#fff'; this.style.color='#e74c3c';">
                                <span class="material-symbols-rounded">delete</span>
                            </button>
                        `;
                        document.getElementById('container-campos-extras').appendChild(div);
                    }
                </script>
            </div>
            </div>

            <!-- Sticky Save -->
            <div class="sticky-footer">
                <span style="font-size:0.9rem; color:var(--text-sub); margin-right:auto;">
                    ⚠️ Todas as alterações são salvas imediatamente no banco.
                </span>
                <a href="gestao_admin_99.php?cliente_id=<?= $cliente_id ?>&tab=andamento" class="btn-close" style="background:none; text-decoration:none;">Cancelar</a>
                <button type="submit" name="btn_salvar_tudo" class="btn-save">
                    💾 Salvar Alterações
                </button>
            </div>

        </div>
    </form>

    <script>
        // Helper para copiar CPF ou Telefone para o Login
        function copiarParaLogin(origem) {
            let valor = '';
            if(origem === 'cpf') {
                const cpfInput = document.querySelector('input[name="cpf_cnpj"]');
                if(cpfInput) valor = cpfInput.value.replace(/\D/g, ''); // Apenas numeros
            } else if(origem === 'tel') {
                const telInput = document.querySelector('input[name="contato_tel"]');
                if(telInput) valor = telInput.value.replace(/\D/g, '');
            }
            
            if(valor) {
                document.getElementById('campo_login').value = valor;
                // alert('Login atualizado para: ' + valor);
            } else {
                alert('Campo de origem (' + origem + ') está vazio!');
            }
        }

        // --- MÁSCARAS E VALIDAÇÃO ---
        // --- MÁSCARAS E VALIDAÇÃO ---
        document.addEventListener('DOMContentLoaded', function() {
            
            // Helpers
            const maskPhone = (v) => {
                v = v.replace(/\D/g, "");
                v = v.replace(/^(\d{2})(\d)/g, "($1) $2");
                v = v.replace(/(\d)(\d{4})$/, "$1-$2");
                return v;
            }

            const maskCpfCnpj = (v) => {
                v = v.replace(/\D/g, "");
                if (v.length <= 11) {
                    v = v.replace(/(\d{3})(\d)/, "$1.$2");
                    v = v.replace(/(\d{3})(\d)/, "$1.$2");
                    v = v.replace(/(\d{3})(\d{1,2})$/, "$1-$2");
                } else {
                    v = v.replace(/^(\d{2})(\d)/, "$1.$2");
                    v = v.replace(/^(\d{2})\.(\d{3})(\d)/, "$1.$2.$3");
                    v = v.replace(/\.(\d{3})(\d)/, ".$1/$2");
                    v = v.replace(/(\d{4})(\d)/, "$1-$2");
                }
                return v;
            }

            const maskCep = (v) => {
                v = v.replace(/\D/g, "");
                v = v.replace(/^(\d{5})(\d)/, "$1-$2");
                return v;
            }

            const maskArea = (v) => {
                // Allows 1234.56 or 1234,56
                return v.replace(/[^0-9.,]/g, ''); 
            }

            // Apply Masks
            const inputs = {
                'contato_tel': { mask: maskPhone, limit: 15 },
                'telefone': { mask: maskPhone, limit: 15 },
                'cpf_cnpj': { mask: maskCpfCnpj, limit: 18 },
                // 'cep': { mask: maskCep, limit: 9 }, // Se houver campo CEP
                'imovel_area_lote': { mask: maskArea, limit: 10 },
                'area_construida': { mask: maskArea, limit: 10 }
            };

            for (const [name, config] of Object.entries(inputs)) {
                document.querySelectorAll(`input[name="${name}"]`).forEach(input => {
                    input.addEventListener('input', (e) => {
                        e.target.value = config.mask(e.target.value);
                    });
                });
            }
        });
    </script>
</body>
</html>
