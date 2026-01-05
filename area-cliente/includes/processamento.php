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

    // New Technical Fields (Oliveira/MG)
    $area_existente = $_POST['area_existente'] ?? null;
    $area_acrescimo = $_POST['area_acrescimo'] ?? null;
    $area_permeavel = $_POST['area_permeavel'] ?? null;
    $taxa_ocupacao = $_POST['taxa_ocupacao'] ?? null;
    $fator_aproveitamento = $_POST['fator_aproveitamento'] ?? null;
    $geo_coords = $_POST['geo_coords'] ?? null;
    
    // Upload de Foto de Capa (Obra)
    $foto_path = null;
    if(isset($_FILES['foto_capa_obra']) && $_FILES['foto_capa_obra']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['foto_capa_obra']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if(in_array($ext, $allowed)) {
            $new_name = "capa_obra_{$cid}_" . time() . ".$ext";
            $target_dir = __DIR__ . '/../uploads/obras/';
            if(!is_dir($target_dir)) mkdir($target_dir, 0755, true);
            
            if(move_uploaded_file($_FILES['foto_capa_obra']['tmp_name'], $target_dir . $new_name)) {
                $foto_path = "uploads/obras/" . $new_name;
            }
        }
    }

    try {
        // SQL Update with new Technical Columns
        if($foto_path) {
             $pdo->prepare("UPDATE processo_detalhes SET processo_numero=?, processo_objeto=?, processo_link_mapa=?, valor_venal=?, area_total_final=?, foto_capa_obra=?, area_existente=?, area_acrescimo=?, area_permeavel=?, taxa_ocupacao=?, fator_aproveitamento=?, geo_coords=? WHERE cliente_id=?")
                ->execute([$proc_num, $proc_obj, $proc_map, $valor_venal, $area_total, $foto_path, $area_existente, $area_acrescimo, $area_permeavel, $taxa_ocupacao, $fator_aproveitamento, $geo_coords, $cid]);
        } else {
             $pdo->prepare("UPDATE processo_detalhes SET processo_numero=?, processo_objeto=?, processo_link_mapa=?, valor_venal=?, area_total_final=?, area_existente=?, area_acrescimo=?, area_permeavel=?, taxa_ocupacao=?, fator_aproveitamento=?, geo_coords=? WHERE cliente_id=?")
                ->execute([$proc_num, $proc_obj, $proc_map, $valor_venal, $area_total, $area_existente, $area_acrescimo, $area_permeavel, $taxa_ocupacao, $fator_aproveitamento, $geo_coords, $cid]);
        }
        
        // Refresh to show changes immediately (managed by page reload usually)
        header("Location: ?cliente_id=$cid&tab=andamento&msg=header_updated");
        exit;
    } catch(PDOException $e) { $erro = "Erro ao atualizar dados do processo: " . $e->getMessage(); }
}

// 1. Atualizar Etapa & Adicionar Histórico
// 1. Atualizar Etapa & Adicionar Histórico
if (isset($_POST['atualizar_etapa'])) {
    $cid = $_POST['cliente_id'];
    $nova_etapa = $_POST['nova_etapa'] ?? null; 
    // $tipo_mov = $_POST['tipo_movimento']; // REMOVED - Auto-detected
    $titulo_ev = $_POST['titulo_evento'];
    $obs_etapa = $_POST['observacao_etapa'] ?? '';
    
    $tipo_mov = 'padrao'; // Default

    try {
        // 1. Update Current Phase if selected
        if (!empty($nova_etapa)) {
            $pdo->prepare("UPDATE processo_detalhes SET etapa_atual = ? WHERE cliente_id = ?")->execute([$nova_etapa, $cid]);
        }
        
        // 2. Prepare Description
        $sys_desc = $obs_etapa; 
        
        // 3. Handle File Upload if Document (Auto-detect)
        if (isset($_FILES['arquivo_documento']) && $_FILES['arquivo_documento']['error'] == 0) {
            $tipo_mov = 'documento'; // Auto-set type
            
            $ext = strtolower(pathinfo($_FILES['arquivo_documento']['name'], PATHINFO_EXTENSION));
            $new_name = "doc_{$cid}_" . time() . ".$ext";
            $target = __DIR__ . "/../uploads/entregaveis/" . $new_name;
            
            if (!is_dir(__DIR__ . "/../uploads/entregaveis/")) mkdir(__DIR__ . "/../uploads/entregaveis/", 0755, true);
            
            if (move_uploaded_file($_FILES['arquivo_documento']['tmp_name'], $target)) {
                $rel_path = "uploads/entregaveis/$new_name";
                $sys_desc .= "<br><br><a href='$rel_path' target='_blank' class='btn-download-doc'>📥 Baixar Documento</a>";
                // Also save to `arquivo_path` if column exists (optional based on schema, but let's stick to appending desc for safety with existing views)
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
        
        // PRG Pattern: Redirect to prevent form resubmission on F5
        header("Location: ?cliente_id=$cid&tab=andamento&msg=mov_added");
        exit;

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


// 6. EDITAR CLIENTE (Aba Configurações)
if (isset($_POST['btn_editar_cliente'])) {
    $cid = $_POST['cliente_id'];
    $nome = $_POST['nome'];
    $cpf = $_POST['cpf_cnpj'];
    $tel = $_POST['telefone'];
    $email = $_POST['email'] ?? '';
    
    // 1. Atualiza Tabela Base (Login/Acesso)
    $sql_base = "UPDATE clientes SET nome=?, cpf_cnpj=?, telefone=?, email=?";
    $params_base = [$nome, $cpf, $tel, $email];
    
    // Se digitou senha nova
    if(!empty($_POST['nova_senha'])) {
        $sql_base .= ", senha=?";
        $params_base[] = password_hash($_POST['nova_senha'], PASSWORD_DEFAULT);
    }
    
    $sql_base .= " WHERE id=?";
    $params_base[] = $cid;
    
    // 2. Atualiza Detalhes (Técnicos/Endereço)
    // Campos da tabela processo_detalhes
    $end = $_POST['endereco_imovel'] ?? '';
    $link = $_POST['link_drive'] ?? '';
    $a_exist = $_POST['area_existente'] ?? null;
    $a_acresc = $_POST['area_acrescimo'] ?? null;
    $a_perm = $_POST['area_permeavel'] ?? null;
    $tx_ocup = $_POST['taxa_ocupacao'] ?? null;
    $ft_aprov = $_POST['fator_aproveitamento'] ?? null;
    $geo = $_POST['geo_coords'] ?? null;
    
    // New fields missing in original logic
    $a_total_final = $_POST['area_total_final'] ?? null; 
    $valor_venal = $_POST['valor_venal'] ?? null;
    $obs_gerais = $_POST['observacoes_gerais'] ?? null;

    // Lógica Upload Foto Obra
    $foto_path = null;
    if(isset($_FILES['foto_capa_obra']) && $_FILES['foto_capa_obra']['error'] == 0) {
         $ext = strtolower(pathinfo($_FILES['foto_capa_obra']['name'], PATHINFO_EXTENSION));
         $allowed = ['jpg', 'jpeg', 'png', 'webp'];
         if(in_array($ext, $allowed)) {
             $new_name = "obra_{$cid}_" . time() . ".{$ext}";
             $target = __DIR__ . "/../uploads/obras/";
             if(!is_dir($target)) mkdir($target, 0755, true);
             
             if(move_uploaded_file($_FILES['foto_capa_obra']['tmp_name'], $target . $new_name)) {
                 $foto_path = "uploads/obras/" . $new_name;
             }
         }
    }

    try {
        $pdo->beginTransaction();
        
        // Execute Update Base
        $pdo->prepare($sql_base)->execute($params_base);
        
        // Execute Update Details (Check if exists first)
        $check = $pdo->prepare("SELECT id, foto_capa_obra FROM processo_detalhes WHERE cliente_id=?");
        $check->execute([$cid]);
        
        if($check->rowCount() > 0) {
            $curr = $check->fetch();
            // Mantém foto antiga se não enviou nova, ou substitui
            $final_foto = $foto_path ? $foto_path : ($curr['foto_capa_obra'] ?? null);
            
            $sql_det = "UPDATE processo_detalhes SET endereco_imovel=?, link_drive_pasta=?, area_existente=?, area_acrescimo=?, area_permeavel=?, taxa_ocupacao=?, fator_aproveitamento=?, geo_coords=?, foto_capa_obra=?, cpf_cnpj=?, contato_tel=?, contato_email=?, area_total_final=?, valor_venal=?, observacoes_gerais=? WHERE cliente_id=?";
            $pdo->prepare($sql_det)->execute([$end, $link, $a_exist, $a_acresc, $a_perm, $tx_ocup, $ft_aprov, $geo, $final_foto, $cpf, $tel, $email, $a_total_final, $valor_venal, $obs_gerais, $cid]);
        } else {
             $sql_det = "INSERT INTO processo_detalhes (cliente_id, endereco_imovel, link_drive_pasta, area_existente, area_acrescimo, area_permeavel, taxa_ocupacao, fator_aproveitamento, geo_coords, foto_capa_obra, cpf_cnpj, contato_tel, contato_email, area_total_final, valor_venal, observacoes_gerais) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
             $pdo->prepare($sql_det)->execute([$cid, $end, $link, $a_exist, $a_acresc, $a_perm, $tx_ocup, $ft_aprov, $geo, $foto_path, $cpf, $tel, $email, $a_total_final, $valor_venal, $obs_gerais]);
        }
        
        $pdo->commit();
        header("Location: ?cliente_id=$cid&tab=configuracoes&msg=client_updated");
        exit;
        
    } catch(PDOException $e) {
        $pdo->rollBack();
        $erro = "Erro ao atualizar: " . $e->getMessage();
    }
}

// 5. Novo Cliente (Logica Nova)
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
        // Inserção Detalhes (Campos Expandidos)
        $pdo->prepare("INSERT INTO processo_detalhes (
            cliente_id, 
            cpf_cnpj, 
            contato_tel, 
            rg_ie, 
            data_nascimento,
            profissao,
            estado_civil,
            nome_conjuge,
            tipo_servico,
            imovel_rua,
            imovel_numero, 
            imovel_bairro,
            imovel_cidade,
            endereco_imovel,
            endereco_residencial
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")->execute([
            $nid, 
            $cpf, 
            $tel, 
            $_POST['rg'] ?? null,
            $_POST['data_nascimento'] ?? null,
            $_POST['profissao'] ?? null,
            $_POST['estado_civil'] ?? null,
            $_POST['nome_conjuge'] ?? null,
            $_POST['tipo_servico'] ?? null,
            $_POST['imovel_rua'] ?? null,
            $_POST['imovel_numero'] ?? null,
            $_POST['imovel_bairro'] ?? null,
            $_POST['imovel_cidade'] ?? null,
            // Compõe endereço visual
            ($_POST['imovel_rua'] ?? '') . ', ' . ($_POST['imovel_numero'] ?? '') . ' - ' . ($_POST['imovel_bairro'] ?? '') . ' - ' . ($_POST['imovel_cidade'] ?? ''),
            '' // Endereço residencial vazio por enquanto no cadastro rápido
        ]);

        // AVATAR UPLOAD (NOVO)
        if(isset($_FILES['avatar_upload']) && $_FILES['avatar_upload']['error'] == 0) {
            $ext = strtolower(pathinfo($_FILES['avatar_upload']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if(in_array($ext, $allowed)) {
                $dir = __DIR__ . '/../uploads/avatars/'; // Subindo um nível pois estamos em includes/
                if(!is_dir($dir)) mkdir($dir, 0755, true);
                
                // Remove antigos
                array_map('unlink', glob($dir . "avatar_{$nid}.*"));
                
                if(move_uploaded_file($_FILES['avatar_upload']['tmp_name'], $dir . "avatar_{$nid}.{$ext}")) {
                    // Update DB with relative path or full path logic
                    // Matching editar_cliente logic: 'uploads/avatars/avatar_{id}.{ext}'
                    $avatar_db_path = "uploads/avatars/avatar_{$nid}.{$ext}";
                    // We are in includes/, so relative to root is ../uploads, but DB stores relative to root?
                    // editar_cliente stored 'uploads/avatars/...'
                    // Let's store consistent path.
                    $pdo->prepare("UPDATE clientes SET foto_perfil = ? WHERE id = ?")->execute([$avatar_db_path, $nid]);
                }
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

// 6.8 Excluir Histórico COMPLETO (Perigo Extremo)
if (isset($_GET['del_all_hist'])) {
    $cid = $_GET['cliente_id'];
    try {
        $pdo->prepare("DELETE FROM processo_movimentos WHERE cliente_id=?")->execute([$cid]);
        header("Location: ?cliente_id=$cid&tab=andamento&msg=all_hist_deleted");
        exit;
    } catch(PDOException $e) { $erro = "Erro ao apagar histórico: " . $e->getMessage(); }
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
// 9. Aprovar Cadastro (Via Modal)
if (isset($_POST['btn_confirmar_aprovacao'])) {
    $pid = $_POST['id_pre'];
    $nome_final = trim($_POST['nome_final']);
    $usuario_final = trim($_POST['usuario_final']);
    $senha_final = trim($_POST['senha_final']);
    $senha_hash = password_hash($senha_final, PASSWORD_DEFAULT);

    try {
        // 1. Pegar dados do pré-cadastro
        $pre = $pdo->prepare("SELECT * FROM pre_cadastros WHERE id = ?");
        $pre->execute([$pid]);
        $solicitacao = $pre->fetch();

        if ($solicitacao) {
            $pdo->beginTransaction();

            // 2. Criar Cliente na tabela oficial
            $sqlCliente = "INSERT INTO clientes (nome, usuario, senha) VALUES (?, ?, ?)";
            $pdo->prepare($sqlCliente)->execute([$nome_final, $usuario_final, $senha_hash]);
            $novo_id = $pdo->lastInsertId();

            // 3. Criar Detalhes
            $sqlDet = "INSERT INTO processo_detalhes (cliente_id, cpf_cnpj, contato_tel, contato_email, tipo_servico) VALUES (?, ?, ?, ?, ?)";
            $pdo->prepare($sqlDet)->execute([
                $novo_id, 
                $solicitacao['cpf_cnpj'], 
                $solicitacao['telefone'], 
                $solicitacao['email'],
                $solicitacao['tipo_servico']
            ]);

            // 4. Deletar Pré-Cadastro
            $pdo->prepare("DELETE FROM pre_cadastros WHERE id = ?")->execute([$pid]);
            
            $pdo->commit();
            
            // Redireciona para edição do novo cliente
            header("Location: editar_cliente.php?id=$novo_id&new=1");
            exit;
        }

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $erro = "Erro ao aprovar: " . $e->getMessage();
    }
}
?>
