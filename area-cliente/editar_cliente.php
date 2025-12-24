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
            $sqlDet = "UPDATE processo_detalhes SET 
                tipo_pessoa=?, cpf_cnpj=?, rg_ie=?, contato_email=?, contato_tel=?, 
                endereco_residencial=?, profissao=?, estado_civil=?, imovel_rua=?, imovel_numero=?,
                imovel_bairro=?, imovel_complemento=?, imovel_cidade=?, imovel_uf=?, inscricao_imob=?,
                num_matricula=?, imovel_area_lote=?, area_construida=?, resp_tecnico=?, registro_prof=?, num_art_rrt=?
                WHERE cliente_id=?";
        } else {
            $sqlDet = "INSERT INTO processo_detalhes (
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
        
        body { 
            background: var(--bg-page); 
            font-family: 'Outfit', sans-serif; 
            color: var(--text-main);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }

        .main-wrapper {
            max-width: 1000px; 
            margin: 0 auto; 
            animation: fadeIn 0.4s ease;
        }

        @keyframes fadeIn { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }

        /* Header */
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
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
            padding: 15px 30px;
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
            padding: 30px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 25px;
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
                    <div class="form-group">
                        <label>Nome de Exibição</label>
                        <input type="text" name="nome" value="<?= htmlspecialchars($cliente['nome']) ?>" required>
                    </div>
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
                    <div class="form-group">
                        <label>Natureza Jurídica</label>
                        <select name="tipo_pessoa">
                            <option value="Fisica" <?= ($detalhes['tipo_pessoa']??'')=='Fisica'?'selected':'' ?>>Pessoa Física (CPF)</option>
                            <option value="Juridica" <?= ($detalhes['tipo_pessoa']??'')=='Juridica'?'selected':'' ?>>Pessoa Jurídica (CNPJ)</option>
                        </select>
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
                        <label>Profissão</label>
                        <input type="text" name="profissao" value="<?= htmlspecialchars($detalhes['profissao']??'') ?>">
                    </div>
                    <div class="form-group">
                        <label>Estado Civil</label>
                        <input type="text" name="estado_civil" value="<?= htmlspecialchars($detalhes['estado_civil']??'') ?>">
                    </div>
                </div>
                
                <div class="form-group" style="margin-top:25px;">
                    <label>Endereço Residencial Completo</label>
                    <input type="text" name="endereco_residencial" value="<?= htmlspecialchars($detalhes['endereco_residencial']??'') ?>" placeholder="Rua, Número, Bairro, Cidade - UF">
                </div>
            </div>

            <!-- SECTION 3: PROPERTY -->
            <div class="section-header">
                <div class="section-icon">🏠</div>
                <h2>Dados do Imóvel (Obra)</h2>
            </div>
            <div class="section-body">
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

            <!-- SECTION 4: TECHNICAL -->
            <div class="section-header">
                <div class="section-icon">👷</div>
                <h2>Responsabilidade Técnica</h2>
            </div>
            <div class="section-body">
                <div class="form-group">
                    <label>Nome do Responsável Técnico</label>
                    <input type="text" name="resp_tecnico" value="<?= htmlspecialchars($detalhes['resp_tecnico']??'') ?>">
                </div>
                <div class="grid" style="margin-top:25px;">
                    <div class="form-group">
                        <label>Registro Profissional (CREA/CAU)</label>
                        <input type="text" name="registro_prof" value="<?= htmlspecialchars($detalhes['registro_prof']??'') ?>">
                    </div>
                    <div class="form-group">
                        <label>Número ART / RRT</label>
                        <input type="text" name="num_art_rrt" value="<?= htmlspecialchars($detalhes['num_art_rrt']??'') ?>">
                    </div>
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

</body>
</html>
