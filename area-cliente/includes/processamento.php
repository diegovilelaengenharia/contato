<?php
// 0. Update Process Header (Top of Client Page)
if (isset($_POST['update_processo_header'])) {
    $cid = $_POST['cliente_id'];
    $proc_num = $_POST['processo_numero'];
    $proc_obj = $_POST['processo_objeto'];
    $proc_map = $_POST['processo_link_mapa'];
    
    // New Fields from "Maria" Spec
    $valor_venal = $_POST['valor_venal'] ?? null;
    $area_total = $_POST['area_total_final'] ?? null;
    
    try {
        // Upsert logic handled by update since record usually created on signup
        $pdo->prepare("UPDATE processo_detalhes SET processo_numero=?, processo_objeto=?, processo_link_mapa=?, valor_venal=?, area_total_final=? WHERE cliente_id=?")
            ->execute([$proc_num, $proc_obj, $proc_map, $valor_venal, $area_total, $cid]);
        
        // Refresh to show changes immediately (managed by page reload usually)
        header("Location: ?cliente_id=$cid&tab=andamento&msg=header_updated");
        exit;
    } catch(PDOException $e) { $erro = "Erro ao atualizar dados do processo: " . $e->getMessage(); }
}

// 1. Atualizar Etapa & Adicionar Histórico
if (isset($_POST['atualizar_etapa'])) {
    $cid = $_POST['cliente_id'];
    $nova_etapa = $_POST['nova_etapa']; // Can be empty if just adding history
    $tipo_mov = $_POST['tipo_movimento']; // padrao, fase_inicio, documento
    $titulo_ev = $_POST['titulo_evento'];
    $obs_etapa = $_POST['observacao_etapa'] ?? '';
    
    try {
        // 1. Update Current Phase if selected
        if (!empty($nova_etapa)) {
            $pdo->prepare("UPDATE processo_detalhes SET etapa_atual = ? WHERE cliente_id = ?")->execute([$nova_etapa, $cid]);
        }
        
        // 2. Prepare Description
        $sys_desc = $obs_etapa; // Default description is what user typed
        
        // 3. Handle File Upload if Document
        if ($tipo_mov == 'documento' && isset($_FILES['arquivo_documento']) && $_FILES['arquivo_documento']['error'] == 0) {
            $ext = strtolower(pathinfo($_FILES['arquivo_documento']['name'], PATHINFO_EXTENSION));
            $new_name = "doc_{$cid}_" . time() . ".$ext";
            $target = __DIR__ . "/../uploads/entregaveis/" . $new_name;
            
            if (!is_dir(__DIR__ . "/../uploads/entregaveis/")) mkdir(__DIR__ . "/../uploads/entregaveis/", 0755, true);
            
            if (move_uploaded_file($_FILES['arquivo_documento']['tmp_name'], $target)) {
                $rel_path = "uploads/entregaveis/$new_name";
                $sys_desc .= "<br><br><a href='$rel_path' target='_blank' class='btn-download-doc'>📥 Baixar Documento</a>";
            }
        }
        
        // 4. Insert Movement
        $sql = "INSERT INTO processo_movimentos (cliente_id, titulo_fase, data_movimento, descricao, status_tipo, tipo_movimento) VALUES (?, ?, NOW(), ?, 'conclusao', ?)";
        $pdo->prepare($sql)->execute([$cid, $titulo_ev, $sys_desc, $tipo_mov]);

        // --- AUTOMAÇÃO WHATSAPP (Apenas se mudou fase ou é documento) ---
        if (!empty($nova_etapa) || $tipo_mov == 'documento') {
             // ... logic to prepare whatsapp check ...
             $sucesso = "Movimentação registrada com sucesso!";
        } else {
             $sucesso = "Evento adicionado ao histórico!";
        }

    } catch(PDOException $e) {
        $erro = "Erro: " . $e->getMessage();
    }
}

// 2. Salvar Dados Cadastrais (Aba Cadastro)
if (isset($_POST['btn_salvar_cadastro'])) {
    $cid = $_POST['cliente_id'];
    $campos = [
        'tipo_pessoa', 'cpf_cnpj', 'rg_ie', 'estado_civil', 'profissao', 'endereco_residencial', 'contato_email', 'contato_tel',
        'inscricao_imob', 'num_matricula', 'imovel_rua', 'imovel_numero', 'imovel_bairro', 'imovel_complemento', 'imovel_cidade', 'imovel_uf', 'endereco_imovel', 'imovel_area_lote', 'area_construida', 
        'tipo_responsavel', 'resp_tecnico', 'registro_prof', 'num_art_rrt'
    ];
    
    // Concatena endereço completo para manter a compatibilidade com campo antigo 'endereco_imovel' se necessário, 
    // ou apenas para visualização rápida. Mas vamos salvar os campos separados.
    // Vamos montar o endereco_imovel com base nos novos campos para manter retrocompatibilidade em locais que só leem esse campo
    $_POST['endereco_imovel'] = ($_POST['imovel_rua'] ?? '') . ', ' . ($_POST['imovel_numero'] ?? '') . ' - ' . ($_POST['imovel_bairro'] ?? '') . ' - ' . ($_POST['imovel_cidade'] ?? '') . '/' . ($_POST['imovel_uf'] ?? '');
    
    // Verifica se existe registro
    $exists = $pdo->prepare("SELECT id FROM processo_detalhes WHERE cliente_id = ?");
    $exists->execute([$cid]);
    
    if ($exists->fetch()) {
        $set = implode('=?, ', $campos) . '=?'; // ultimo ?
        $sql = "UPDATE processo_detalhes SET " . implode('=?, ', $campos) . "=? WHERE cliente_id=?";
    } else {
        $sql = "INSERT INTO processo_detalhes (" . implode(', ', $campos) . ", cliente_id) VALUES (" . str_repeat('?,', count($campos)) . "?)";
    }
    
    $params = [];
    foreach($campos as $c) $params[] = $_POST[$c] ?? null;
    $params[] = $cid;

    // Verify and Update Main Name (clientes table)
    if (!empty($_POST['nome_principal'])) {
        $pdo->prepare("UPDATE clientes SET nome = ? WHERE id = ?")->execute([$_POST['nome_principal'], $cid]);
    }

    // AVATAR UPLOAD (EDIT)
    if(isset($_FILES['avatar_upload']) && $_FILES['avatar_upload']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['avatar_upload']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if(in_array($ext, $allowed)) {
            $dir = __DIR__ . '/../uploads/avatars/';
            if(!is_dir($dir)) mkdir($dir, 0755, true);
            
            // Remove antigos
            array_map('unlink', glob($dir . "avatar_{$cid}.*"));
            
            move_uploaded_file($_FILES['avatar_upload']['tmp_name'], $dir . "avatar_{$cid}.{$ext}");
        }
    }

    try { $pdo->prepare($sql)->execute($params); $sucesso = "Cadastro atualizado (Nome e Foto inclusos)!"; } 
    catch (PDOException $e) { $erro = "Erro: " . $e->getMessage(); }
}

// 3. Salvar Pendências (Lógica Nova - CRUD Individual via AJAX/Form)
// (Mantido compatibilidade com forms antigos se houver, mas o foco é a nova lista)
if (isset($_POST['btn_adicionar_pendencia'])) {
    $cid = $_POST['cliente_id'];
    $texto = trim($_POST['descricao_pendencia']);
    
    if (!empty($texto)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO processo_pendencias (cliente_id, descricao, status, data_criacao) VALUES (?, ?, 'pendente', NOW())");
            $stmt->execute([$cid, $texto]);
            
            // PRG para evitar duplicidade
            header("Location: ?cliente_id=$cid&tab=pendencias&msg=pend_added");
            exit;
        } catch(PDOException $e) { $erro = "Erro: " . $e->getMessage(); }
    }
}

if (isset($_POST['btn_editar_pendencia'])) {
    $pid = $_POST['pendencia_id'];
    $cid = $_POST['cliente_id'];
    $texto = trim($_POST['descricao_pendencia']);
    
    if (!empty($texto)) {
        try {
            $pdo->prepare("UPDATE processo_pendencias SET descricao = ? WHERE id = ? AND cliente_id = ?")->execute([$texto, $pid, $cid]);
            // PRG
            header("Location: ?cliente_id=$cid&tab=pendencias&msg=pend_updated");
            exit;
        } catch(PDOException $e) { $erro = "Erro: " . $e->getMessage(); }
    }
}

// Ação de Resolver/Reabrir via GET (Toggle)
if (isset($_GET['toggle_pendencia'])) {
    $pid = $_GET['toggle_pendencia'];
    $cid = $_GET['cliente_id'];
    
    try {
        $curr = $pdo->query("SELECT status FROM processo_pendencias WHERE id=$pid")->fetchColumn();
        $new = ($curr == 'pendente') ? 'resolvido' : 'pendente';
        $pdo->prepare("UPDATE processo_pendencias SET status = ? WHERE id = ? AND cliente_id = ?")->execute([$new, $pid, $cid]);
        
        // Redireciona para limpar URL
        header("Location: ?cliente_id=$cid&tab=pendencias");
        exit;
    } catch(PDOException $e) { $erro = "Erro ao alterar status: " . $e->getMessage(); }
}

// Ação de Excluir Pendência
if (isset($_GET['delete_pendencia'])) {
    $pid = $_GET['delete_pendencia'];
    $cid = $_GET['cliente_id'];
    try {
        $pdo->prepare("DELETE FROM processo_pendencias WHERE id = ? AND cliente_id = ?")->execute([$pid, $cid]);
        header("Location: ?cliente_id=$cid&tab=pendencias");
        exit;
    } catch(PDOException $e) { $erro = "Erro ao excluir: " . $e->getMessage(); }
}

// Ação de Atualizar Status Financeiro (Via Modal)
if (isset($_POST['btn_update_status_fin'])) {
    $fid = $_POST['fin_id'];
    $cid = $_POST['cliente_id'];
    $new_status = $_POST['novo_status'];
    
    try {
        $pdo->prepare("UPDATE processo_financeiro SET status = ? WHERE id = ? AND cliente_id = ?")->execute([$new_status, $fid, $cid]);
        header("Location: ?cliente_id=$cid&tab=financeiro&msg=status_updated");
        exit;
    } catch(PDOException $e) { $erro = "Erro ao atualizar status: " . $e->getMessage(); }
}

// 4. Salvar Arquivos/Links (Aba Arquivos)
if (isset($_POST['btn_salvar_arquivos'])) {
    $cid = $_POST['cliente_id'];
    try {
        $sql = "UPDATE processo_detalhes SET link_drive_pasta = ? WHERE cliente_id = ?";
        $pdo->prepare($sql)->execute([$_POST['link_drive_pasta'], $cid]);
        $sucesso = "Links de arquivos atualizados!";
    } catch(PDOException $e) { $erro = "Erro: " . $e->getMessage(); }
}

// 5. Novo Cliente
if (isset($_POST['novo_cliente'])) {
    $nome_original = $_POST['nome'];
    $cpf = $_POST['cpf_cnpj'];
    $tel = $_POST['telefone'];
    $senha_plain = $_POST['senha'];
    $tipo_login = $_POST['tipo_login']; // 'cpf' ou 'telefone'

    // Lógica de Login Automático
    $usuario_final = '';
    if ($tipo_login == 'cpf') {
        $usuario_final = preg_replace('/[^0-9]/', '', $cpf);
        if(empty($usuario_final)) throw new Exception("Para usar CPF como login, o campo CPF não pode estar vazio.");
    } else {
        $usuario_final = preg_replace('/[^0-9]/', '', $tel);
        if(empty($usuario_final)) throw new Exception("Para usar Telefone como login, o campo Telefone não pode estar vazio.");
    }

    // Validação Básica
    if(empty($nome_original) || empty($senha_plain)) throw new Exception("Nome e Senha são obrigatórios.");

    $pass = password_hash($senha_plain, PASSWORD_DEFAULT);
    
    try {
        // Verifica se usuario ja existe
        $check = $pdo->prepare("SELECT id FROM clientes WHERE usuario = ?");
        $check->execute([$usuario_final]);

        if($check->rowCount() > 0) throw new Exception("Este login ($usuario_final) já está em uso por outro cliente.");

        // Inserir nome completo (Schema simplificado, sem sobrenome separado)
        $pdo->prepare("INSERT INTO clientes (nome, usuario, senha) VALUES (?, ?, ?)")->execute([trim($nome_original), $usuario_final, $pass]);
        $nid = $pdo->lastInsertId();
        
        // Inserção Detalhes (Campos não preenchidos vão vazios para serem completados na edição)
        $pdo->prepare("INSERT INTO processo_detalhes (
            cliente_id, 
            cpf_cnpj, 
            contato_tel, 
            rg_ie, 
            endereco_residencial, 
            endereco_imovel
        ) VALUES (?, ?, ?, ?, ?, ?)")->execute([$nid, $cpf, $tel, '', '', '']);

        // AVATAR UPLOAD (NOVO)
        if(isset($_FILES['avatar_upload']) && $_FILES['avatar_upload']['error'] == 0) {
            $ext = strtolower(pathinfo($_FILES['avatar_upload']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if(in_array($ext, $allowed)) {
                $dir = __DIR__ . '/../uploads/avatars/'; // Subindo um nível pois estamos em includes/
                if(!is_dir($dir)) mkdir($dir, 0755, true);
                
                // Remove antigos
                array_map('unlink', glob($dir . "avatar_{$nid}.*"));
                
                move_uploaded_file($_FILES['avatar_upload']['tmp_name'], $dir . "avatar_{$nid}.{$ext}");
            }
        }
        
        // REDIRECIONAMENTO IMEDIATO PARA EDITOR COMPLETÃO
        header("Location: editar_cliente.php?id=$nid&msg=welcome");
        exit;

    } catch (Exception $e) { 
        $erro = "Erro ao criar cliente: " . $e->getMessage(); 
    }
}

// 5.5 Atualizar Acesso (Nome/Login/Senha)
if (isset($_POST['btn_salvar_acesso'])) {
    $cid = $_POST['cliente_id'];
    $nome = $_POST['nome'];
    $user = $_POST['usuario'];
    $nova_senha = $_POST['nova_senha'];

    try {
        if (!empty($nova_senha)) {
            $pass = password_hash($nova_senha, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE clientes SET nome=?, usuario=?, senha=? WHERE id=?")->execute([$nome, $user, $pass, $cid]);
            $sucesso = "Dados de acesso e Senha atualizados!";
        } else {
            $pdo->prepare("UPDATE clientes SET nome=?, usuario=? WHERE id=?")->execute([$nome, $user, $cid]);
            $sucesso = "Dados de acesso atualizados (Senha mantida)!";
        }
        // Atualiza var local p/ refletir na hora
        $refresh = $pdo->prepare("SELECT * FROM clientes WHERE id=?"); $refresh->execute([$cid]);
        $cliente_ativo = $refresh->fetch();

    } catch (PDOException $e) { $erro = "Erro ao atualizar acesso: " . $e->getMessage(); }
}

// 6. Financeiro - Adicionar
if (isset($_POST['btn_salvar_financeiro'])) {
    $cid = $_POST['cliente_id'];
    try {
        $stmt = $pdo->prepare("INSERT INTO processo_financeiro (cliente_id, categoria, descricao, valor, data_vencimento, status, link_comprovante, referencia_legal) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $cid, 
            $_POST['categoria'], 
            $_POST['descricao'], 
            str_replace(',', '.', $_POST['valor']), 
            $_POST['data_vencimento'], 
            $_POST['status'], 
            $_POST['link_comprovante'] ?? null,
            $_POST['referencia_legal'] ?? null
        ]);
        $sucesso = "Lançamento financeiro adicionado!";
    } catch(PDOException $e) { $erro = "Erro: " . $e->getMessage(); }
}

// 6.7 Excluir Histórico (Movimentação)
if (isset($_GET['del_hist'])) {
    $hid = $_GET['del_hist'];
    $cid = $_GET['cliente_id'];
    $pdo->prepare("DELETE FROM processo_movimentos WHERE id=? AND cliente_id=?")->execute([$hid, $cid]);
    header("Location: ?cliente_id=$cid&tab=andamento&msg=hist_deleted");
    exit;
}

// 6.5 Salvar Dados Gerais Financeiro (Link da Pasta)
if (isset($_POST['btn_salvar_dados_financeiros'])) {
    $cid = $_POST['cliente_id'];
    try {
        $pdo->prepare("UPDATE processo_detalhes SET link_pasta_pagamentos = ? WHERE cliente_id = ?")->execute([$_POST['link_pasta_pagamentos'], $cid]);
        $sucesso = "Link da pasta de pagamentos salvo!";
    } catch(PDOException $e) { $erro = "Erro: " . $e->getMessage(); }
}

// 7. Financeiro - Excluir
if (isset($_GET['del_fin'])) {
    $fid = $_GET['del_fin'];
    $cid = $_GET['cliente_id']; // para manter na pag
    $pdo->prepare("DELETE FROM processo_financeiro WHERE id=? AND cliente_id=?")->execute([$fid, $cid]);
    header("Location: ?cliente_id=$cid&tab=financeiro");
    exit;
}

// 7.5 Alternar Status Financeiro
if (isset($_GET['toggle_status'])) {
    $fid = $_GET['toggle_status'];
    $cid = $_GET['cliente_id'];
    
    // Ciclo: pendente -> pago -> atrasado -> isento -> pendente
    $atual = $pdo->query("SELECT status FROM processo_financeiro WHERE id=$fid")->fetchColumn();
    $novo = 'pendente';
    if($atual == 'pendente') $novo = 'pago';
    elseif($atual == 'pago') $novo = 'atrasado';
    elseif($atual == 'atrasado') $novo = 'isento';
    elseif($atual == 'isento') $novo = 'pendente';
    
    $pdo->prepare("UPDATE processo_financeiro SET status=? WHERE id=?")->execute([$novo, $fid]);
    
    header("Location: ?cliente_id=$cid&tab=financeiro");
    exit;
}

// Delete
if (isset($_GET['delete_cliente'])) {
    $pdo->prepare("DELETE FROM clientes WHERE id = ?")->execute([$_GET['delete_cliente']]);
    header("Location: ?"); exit;
}

// 8. Importar Pré-Cadastro (Aprovar)
if (isset($_GET['aprovar_cadastro'])) {
    $pid = $_GET['aprovar_cadastro'];
    try {
        $pre = $pdo->query("SELECT * FROM pre_cadastros WHERE id=$pid")->fetch();
        if ($pre) {
            // Cria Cliente
            $nome = $pre['nome'];
            $user_sugerido = strtolower(explode(' ', trim($nome))[0]) . rand(100,999);
            $pass_padrao = password_hash("Mudar123", PASSWORD_DEFAULT); // Senha temporaria
            
            $pdo->prepare("INSERT INTO clientes (nome, usuario, senha) VALUES (?, ?, ?)")->execute([$nome, $user_sugerido, $pass_padrao]);
            $nid = $pdo->lastInsertId();
            
            // Renomeia
            $nome_final = sprintf("Cliente %03d - %s", $nid, $nome);
            $pdo->prepare("UPDATE clientes SET nome = ? WHERE id = ?")->execute([$nome_final, $nid]);
            
            // Cria Detalhes e popula com o que veio
            $sql_det = "INSERT INTO processo_detalhes (cliente_id, cpf_cnpj, contato_email, contato_tel, endereco_imovel, profissao) VALUES (?, ?, ?, ?, ?, ?)";
            // Usamos 'profissao' para guardar tipo servico temp, ou observacao
            $pdo->prepare($sql_det)->execute([$nid, $pre['cpf_cnpj'], $pre['email'], $pre['telefone'], $pre['endereco_obra'], $pre['tipo_servico']]);

            // Atualiza status do pré
            $pdo->query("UPDATE pre_cadastros SET status='aprovado' WHERE id=$pid");
            
            $sucesso = "Cadastro importado com sucesso! Cliente criado: $nome_final (Login: $user_sugerido / Senha: Mudar123)";
        }
    } catch (Exception $e) { $erro = "Erro ao importar: " . $e->getMessage(); }
}
?>
