<?php
// 1. Atualizar Etapa (Aba Andamento)
if (isset($_POST['atualizar_etapa'])) {
    $nova_etapa = $_POST['nova_etapa'];
    $obs_etapa = $_POST['observacao_etapa'] ?? '';
    $cid = $_POST['cliente_id'];
    
    try {
        // Atualiza a fase atual
        $pdo->prepare("UPDATE processo_detalhes SET etapa_atual = ? WHERE cliente_id = ?")->execute([$nova_etapa, $cid]);
        
        // Registra histórico
        $titulo = "Mudança de Fase: " . $nova_etapa;
        
        // Formatação do comentário: Título padrão + Delimitador + Obs do usuário (se houver)
        $desc = "Fase atualizada pelo administrador.";
        if (trim($obs_etapa) !== '') {
            $desc .= "\n||COMENTARIO_USER||" . $obs_etapa;
        }
        
        $sql = "INSERT INTO processo_movimentos (cliente_id, titulo_fase, data_movimento, descricao, status_tipo) VALUES (?, ?, NOW(), ?, 'conclusao')";
        $pdo->prepare($sql)->execute([$cid, $titulo, $desc]);

        // --- AUTOMAÇÃO WHATSAPP ---
        try {
            $stmtC = $pdo->prepare("SELECT nome, contato_tel FROM processo_detalhes WHERE cliente_id = ?");
            $stmtC->execute([$cid]);
            $client_data = $stmtC->fetch();
            
            if ($client_data && !empty($client_data['contato_tel'])) {
                $raw_phone = preg_replace('/[^0-9]/', '', $client_data['contato_tel']);
                if (strlen($raw_phone) >= 10) { // Valid phone check
                     // Format message
                     $first_name = explode(' ', trim($client_data['nome'] ?? 'Cliente'))[0];
                     $msg = "Olá {$first_name}, tudo bem? 👋\n\n📢 Atualização do seu processo: *{$nova_etapa}*.\n\nAcesse seu painel para ver mais detalhes: https://vilelaengenharia.com/area-cliente/";
                     
                     if (trim($obs_etapa) !== '') {
                        $msg .= "\n\nobs: {$obs_etapa}";
                     }

                     $wpp_link = "https://wa.me/55{$raw_phone}?text=" . urlencode($msg);
                     $sucesso = "Fase atualizada! Preparando notificação...";
                     
                     // Injeta script para abrir modal
                     $trigger_wpp = true;
                } else {
                    $sucesso = "Fase atualizada! (Telefone inválido para whats)";
                }
            } else {
                 $sucesso = "Fase atualizada! (Sem telefone cadastrado)";
            }
        } catch (Exception $e) { 
            $sucesso = "Fase atualizada, mas erro ao gerar link whats.";
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

        // Separar Nome e Sobrenome na criação
        $partes_nome = explode(' ', trim($nome_original), 2);
        $primeiro_nome = $partes_nome[0];
        $sobrenome = $partes_nome[1] ?? '';

        // Agora salva com sobrenome
        $pdo->prepare("INSERT INTO clientes (nome, sobrenome, usuario, senha) VALUES (?, ?, ?, ?)")->execute([$primeiro_nome, $sobrenome, $usuario_final, $pass]);
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
        $stmt = $pdo->prepare("INSERT INTO processo_financeiro (cliente_id, categoria, descricao, valor, data_vencimento, status, link_comprovante) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $cid, 
            $_POST['categoria'], 
            $_POST['descricao'], 
            str_replace(',', '.', $_POST['valor']), 
            $_POST['data_vencimento'], 
            $_POST['status'], 
            $_POST['link_comprovante']
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
