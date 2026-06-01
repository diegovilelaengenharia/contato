<?php
/**
 * cliente_detalhes.php — View Mestre do Cliente (Detalhes e Gerenciamento).
 *
 * Oferece interface unificada em 5 abas integradas sem recarregar a página
 * para gerenciar: Timeline/WhatsApp, Financeiro, Pendências, Documentos e
 * Dados Cadastrais completíssimos. Também serve para criar novo cliente.
 */

// --- DETERMINA MODO (CREATE vs EDIT) ---
$is_new = isset($_GET['action']) && $_GET['action'] === 'new';
$cliente_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

$cliente = [];
$detalhes = [];
$campos_extras = [];

if (!$is_new) {
    if (!$cliente_id) {
        echo "<div class='form-card'><h2>Erro</h2><p>ID do cliente não fornecido.</p><a href='?route=clientes' class='btn-save'>Voltar</a></div>";
        return;
    }

    // Carrega dados principais do Cliente
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
    $stmt->execute([$cliente_id]);
    $cliente = $stmt->fetch();

    if (!$cliente) {
        echo "<div class='form-card'><h2>Erro</h2><p>Cliente não encontrado.</p><a href='?route=clientes' class='btn-save'>Voltar</a></div>";
        return;
    }

    // Carrega processo_detalhes
    $stmtDet = $pdo->prepare("SELECT * FROM processo_detalhes WHERE cliente_id = ?");
    $stmtDet->execute([$cliente_id]);
    $detalhes = $stmtDet->fetch() ?: [];

    // Carrega campos extras
    try {
        $stmtEx = $pdo->prepare("SELECT * FROM processo_campos_extras WHERE cliente_id = ?");
        $stmtEx->execute([$cliente_id]);
        $campos_extras = $stmtEx->fetchAll();
    } catch (Exception $e) {
        $campos_extras = [];
    }
}

// Configurações do Checklist de Documentos exigidos
$docs_config_path = __DIR__ . '/../../config/docs_config.php';
$processos_opts = [];
$todos_docs = [];
if (file_exists($docs_config_path)) {
    $docs_data_conf = require $docs_config_path;
    $processos_opts = $docs_data_conf['processes'] ?? [];
    $todos_docs = $docs_data_conf['document_registry'] ?? [];
}

$active_proc_key = $detalhes['tipo_processo_chave'] ?? '';
?>

<!-- CABEÇALHO DO CLIENTE OU TÍTULO DE NOVO CADASTRO -->
<div class="page-head" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 24px; border-bottom: 1px solid var(--color-border); padding-bottom: 20px;">
    <div>
        <?php if ($is_new): ?>
            <h1>Cadastrar Novo Cliente</h1>
            <p>Insira as credenciais de acesso, dados pessoais e informações da obra.</p>
        <?php else: ?>
            <div style="display: flex; align-items: center; gap: 16px;">
                <div class="avatar" style="width: 58px; height: 58px; border-radius: 50%; background: var(--color-primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.5rem; box-shadow: var(--shadow-sm);">
                    <?php 
                    $iniciais = '';
                    $words = explode(' ', $cliente['nome']);
                    $iniciais .= substr($words[0] ?? '', 0, 1);
                    if (isset($words[1])) $iniciais .= substr($words[1], 0, 1);
                    echo strtoupper($iniciais ?: 'CL');
                    ?>
                </div>
                <div>
                    <h1 style="margin: 0;"><?php echo htmlspecialchars($cliente['nome']); ?></h1>
                    <p style="margin: 4px 0 0 0;">Etapa atual: <strong style="color: var(--color-primary-dark);"><?php echo htmlspecialchars($detalhes['etapa_atual'] ?? 'Não informada'); ?></strong></p>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <a href="?route=clientes" class="top-nav-btn">
            <span class="material-symbols-rounded">arrow_back</span> Voltar
        </a>
        <?php if (!$is_new): ?>
            <!-- Simular área do cliente -->
            <a href="../actions/admin/cliente_impersonate.php?cliente_id=<?php echo $cliente['id']; ?>" target="_blank" class="top-nav-btn" style="background: var(--color-primary-tint); color: var(--color-primary-dark); border-color: var(--color-primary-soft);">
                <span class="material-symbols-rounded">visibility</span> Ver como Cliente
            </a>
            <!-- Excluir Cliente -->
            <form action="../actions/admin/cliente_delete.php" method="POST" onsubmit="return confirm('ATENÇÃO EXTREMA: Isso apagará DEFINITIVAMENTE o cliente, histórico de obras, pendências e parcelas financeiras. Deseja prosseguir?')">
                <?php echo Csrf::getHtmlField(); ?>
                <input type="hidden" name="cliente_id" value="<?php echo $cliente['id']; ?>">
                <button type="submit" class="top-nav-btn" style="background: var(--bg-danger); color: var(--color-danger); border-color: var(--bg-danger);">
                    <span class="material-symbols-rounded">delete</span> Excluir Cliente
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if ($is_new): ?>
    <!-- ==================== FORMULÁRIO DE CRIAÇÃO DO ZERO ==================== -->
    <div class="form-card">
        <?php 
        // Define as variáveis em branco para o template legar segurança
        $cliente = []; $detalhes = []; $campos_extras = [];
        include __DIR__ . '/../../includes/form_cliente_template.php'; 
        ?>
    </div>
<?php else: ?>
    <!-- ==================== INTERFACE EM ABAS DO CLIENTE ==================== -->
    
    <!-- PÍLULAS DE ABA DE NAVEGAÇÃO -->
    <div class="nav-pills" id="detailTabsContainer">
        <button class="nav-pill active" onclick="switchDetailTab('timeline', this)">
            <span class="material-symbols-rounded">history</span> Timeline & WhatsApp
        </button>
        <button class="nav-pill" onclick="switchDetailTab('financeiro', this)">
            <span class="material-symbols-rounded">paid</span> Financeiro
        </button>
        <button class="nav-pill" onclick="switchDetailTab('pendencias', this)">
            <span class="material-symbols-rounded">warning</span> Pendências
        </button>
        <button class="nav-pill" onclick="switchDetailTab('documentos', this)">
            <span class="material-symbols-rounded">folder_open</span> Documentação
        </button>
        <button class="nav-pill" onclick="switchDetailTab('dados', this)">
            <span class="material-symbols-rounded">person</span> Dados Cadastrais
        </button>
    </div>

    <!-- ------------------ ABA 1: TIMELINE & WHATSAPP ------------------ -->
    <div class="admin-tab-content tab-pane active" id="pane-timeline">
        <div style="display: grid; grid-template-columns: 1fr 320px; gap: 24px; align-items: start;">
            
            <!-- Linha do Tempo de Movimentos -->
            <div>
                <div class="admin-header-row">
                    <div>
                        <h3 class="admin-title" style="margin: 0; border: none; padding: 0;">Histórico do Processo</h3>
                        <p class="admin-subtitle">Eventos, despachos técnicos e datas registradas.</p>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button type="button" class="btn-save" onclick="document.getElementById('modalAndamentoNew').showModal()">
                            <span class="material-symbols-rounded">add_circle</span> Novo Andamento
                        </button>
                        <a href="../actions/admin/movimento_clear_all.php?cliente_id=<?php echo $cliente['id']; ?>&del_all_hist=true"
                           class="btn-save btn-danger"
                           onclick="return confirm('ATENÇÃO: Deseja apagar TODO o histórico deste cliente? Essa ação é irreversível.')">
                            <span class="material-symbols-rounded">delete_sweep</span> Limpar Tudo
                        </a>
                    </div>
                </div>

                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Movimentação / Evento</th>
                                <th style="text-align: right; padding-right: 20px;">Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmtMov = $pdo->prepare("SELECT * FROM processo_movimentos WHERE cliente_id = ? ORDER BY data_movimento DESC");
                            $stmtMov->execute([$cliente['id']]);
                            $movimentos = $stmtMov->fetchAll();

                            if (empty($movimentos)): ?>
                                <tr>
                                    <td colspan="3" style="padding: 40px; text-align: center; color: var(--color-muted); font-style: italic;">
                                        Nenhuma movimentação registrada para este processo ainda.
                                    </td>
                                </tr>
                            <?php else: foreach ($movimentos as $m): 
                                $accent = '';
                                $tipo = $m['tipo_movimento'] ?? 'padrao';
                                if ($tipo === 'fase_inicio') {
                                    $accent = 'border-left: 4px solid #6610f2; background: rgba(102, 16, 242, 0.02);';
                                } elseif ($tipo === 'documento') {
                                    $accent = 'border-left: 4px solid var(--color-primary); background: var(--color-primary-tint);';
                                }
                            ?>
                                <tr style="<?php echo $accent; ?>">
                                    <td style="white-space: nowrap; vertical-align: top; color: var(--color-text-subtle); padding: 14px;">
                                        <?php echo date('d/m/Y H:i', strtotime($m['data_movimento'])); ?>
                                    </td>
                                    <td style="padding: 14px; vertical-align: top;">
                                        <div style="font-weight: 700; color: var(--color-text); margin-bottom: 4px;"><?php echo htmlspecialchars($m['titulo_fase']); ?></div>
                                        <?php 
                                        $parts = explode("||COMENTARIO_USER||", $m['descricao']);
                                        echo "<div style='color: var(--color-text-subtle); line-height: 1.5; font-size: 0.9rem;'>{$parts[0]}</div>";
                                        if (count($parts) > 1 && !empty(trim($parts[1]))) {
                                            echo "<div style='margin-top: 8px; border-left: 3px solid var(--color-danger); padding-left: 10px; color: var(--color-danger); font-weight: 600; font-size: 0.88rem;'>
                                                    <strong>Diego Vilela:</strong> " . nl2br(htmlspecialchars($parts[1])) . "
                                                  </div>";
                                        }
                                        ?>
                                    </td>
                                    <td style="text-align: right; vertical-align: top; padding: 14px 20px 14px 14px;">
                                        <a href="../actions/admin/movimento_delete.php?cliente_id=<?php echo $cliente['id']; ?>&del_hist=<?php echo $m['id']; ?>" 
                                           class="btn-icon danger" onclick="return confirm('Deseja excluir esta movimentação?')" title="Excluir">
                                            <span class="material-symbols-rounded">delete</span>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Painel Lateral: Atualizar Etapa e WhatsApp -->
            <div class="form-card" style="padding: 20px; border: 1px solid var(--color-border); background: #fafbfc; position: sticky; top: 10px;">
                <h4 style="margin: 0 0 14px 0; color: var(--color-primary-dark); font-size: 1rem; display: flex; align-items: center; gap: 8px;">
                    <span class="material-symbols-rounded">update</span> Atualizar Etapa da Obra
                </h4>
                
                <form action="../actions/admin/etapa_update.php" method="POST">
                    <?php echo Csrf::getHtmlField(); ?>
                    <input type="hidden" name="cliente_id" value="<?php echo $cliente['id']; ?>">
                    <input type="hidden" name="titulo_evento" value="Atualização de Etapa">
                    <input type="hidden" name="observacao_etapa" value="Etapa alterada pelo painel de controle.">
                    
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="font-size: 0.8rem; font-weight: 700; color: var(--color-text-subtle);">Etapa Atual do Processo</label>
                        <select name="nova_etapa" class="proc-select" required style="background: white; border: 1px solid var(--color-border);">
                            <?php foreach (Processo::$fases_padrao as $fase): ?>
                                <option value="<?php echo $fase; ?>" <?php echo ($detalhes['etapa_atual'] ?? '') === $fase ? 'selected' : ''; ?>>
                                    <?php echo $fase; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" name="atualizar_etapa" class="btn-save btn-primary" style="width: 100%; padding: 11px; font-size: 0.88rem; border-radius: 8px;">
                        Salvar Nova Etapa
                    </button>
                </form>
                
                <!-- DISPARADOR INTEGRADO DE WHATSAPP -->
                <div style="margin-top: 20px; border-top: 1px solid var(--color-border); padding-top: 20px;">
                    <h4 style="margin: 0 0 8px 0; color: #128c7e; font-size: 0.95rem; display: flex; align-items: center; gap: 8px;">
                        <span class="material-symbols-rounded">chat</span> Disparar WhatsApp
                    </h4>
                    <p style="font-size: 0.8rem; color: var(--color-text-subtle); margin-bottom: 14px;">Envie uma mensagem formatada ao cliente sobre o andamento atualizado da obra com 1 clique.</p>
                    
                    <?php 
                    $cli_tel_clean = preg_replace('/\D/', '', $detalhes['contato_tel'] ?? '');
                    if (substr($cli_tel_clean, 0, 2) !== '55' && strlen($cli_tel_clean) >= 10) {
                        $cli_tel_clean = '55' . $cli_tel_clean;
                    }
                    $whats_msg = "*Vilela Engenharia — Atualização do Processo* \n\nOlá, " . $cliente['nome'] . "! Atualizamos o andamento do seu processo na Vilela Engenharia para a etapa:\n\n📍 *_" . ($detalhes['etapa_atual'] ?? 'Não iniciada') . "_*\n\nVocê pode conferir o andamento completo, pendências e baixar documentos na sua Área do Cliente em:\n🌐 *vilela.eng.br/area-cliente*";
                    $whats_url = "https://wa.me/" . $cli_tel_clean . "?text=" . urlencode($whats_msg);
                    ?>
                    
                    <?php if (!empty($cli_tel_clean)): ?>
                        <a href="<?php echo $whats_url; ?>" target="_blank" class="btn-save" style="background: #25d366; color: white; display: inline-flex; align-items: center; justify-content: center; gap: 8px; width: 100%; border: none; text-decoration: none; border-radius: 8px; font-size: 0.88rem; padding: 11px;">
                            <span class="material-symbols-rounded">send</span> Enviar pelo WhatsApp
                        </a>
                    <?php else: ?>
                        <button type="button" class="btn-save btn-ghost" style="width: 100%; opacity: 0.5; font-size: 0.85rem;" disabled>
                            Telefone não cadastrado
                        </button>
                    <?php endif; ?>
                </div>

            </div>

        </div>
    </div>

    <!-- ------------------ ABA 2: FINANCEIRO ------------------ -->
    <div class="admin-tab-content tab-pane" id="pane-financeiro">
        <div class="admin-header-row">
            <div>
                <h3 class="admin-title" style="margin: 0; border: none; padding: 0;">Lançamentos Financeiros</h3>
                <p class="admin-subtitle">Honorários técnicos e taxas governamentais da obra.</p>
            </div>
            <button type="button" class="btn-save" onclick="document.getElementById('modalFinanceiroNew').showModal()">
                <span class="material-symbols-rounded">add_circle</span> Novo Lançamento
            </button>
        </div>

        <?php
        try {
            // Honorários Vilela Engenharia
            $stmtHon = $pdo->prepare("SELECT * FROM processo_financeiro WHERE cliente_id = ? AND categoria = 'honorarios' ORDER BY data_vencimento ASC");
            $stmtHon->execute([$cliente['id']]);
            $honorarios = $stmtHon->fetchAll();

            // Taxas Governamentais
            $stmtTax = $pdo->prepare("SELECT * FROM processo_financeiro WHERE cliente_id = ? AND categoria = 'taxas' ORDER BY data_vencimento ASC");
            $stmtTax->execute([$cliente['id']]);
            $taxas = $stmtTax->fetchAll();
            
            // Função interna para desenhar tabelas financeiras elegantes no novo painel
            function renderFinTableNew($rows, $title, $color, $cliente_id) {
                echo "<div style='margin-top: 24px; border-top: 3px solid {$color}; padding-top: 15px;'>";
                echo "<h4 style='color: {$color}; font-size: 1.05rem; margin: 0 0 14px 0;'>{$title}</h4>";
                
                if (empty($rows)) {
                    echo "<p style='font-style: italic; color: var(--color-muted); font-size: 0.88rem;'>Nenhum lançamento nesta categoria.</p>";
                } else {
                    echo "<div class='admin-table-container'>";
                    echo "<table class='admin-table'>";
                    echo "<thead><tr>
                            <th>Descrição</th>
                            <th>Valor</th>
                            <th>Vencimento</th>
                            <th style='text-align: center;'>Status</th>
                            <th style='text-align: center;'>Comprovante</th>
                            <th style='text-align: right; padding-right: 20px;'>Ação</th>
                          </tr></thead><tbody>";
                    foreach ($rows as $r) {
                        $badge = 'status-badge';
                        $status_text = 'Pendente';
                        switch ($r['status']) {
                            case 'pago': $badge .= ' success'; $status_text = 'Pago'; break;
                            case 'pendente': $badge .= ' warning'; $status_text = 'Pendente'; break;
                            case 'atrasado': $badge .= ' danger'; $status_text = 'Atrasado'; break;
                            case 'isento': $badge .= ' info'; $status_text = 'Isento'; break;
                        }
                        
                        $valor = number_format($r['valor'], 2, ',', '.');
                        $data = date('d/m/Y', strtotime($r['data_vencimento']));
                        
                        // Link do comprovante
                        $link = '<span style="opacity: 0.5;">--</span>';
                        if (!empty($r['link_comprovante']) && preg_match('#^https?://#i', $r['link_comprovante'])) {
                            $link = '<a href="'.htmlspecialchars($r['link_comprovante']).'" target="_blank" style="color: var(--color-primary-dark); font-weight: 700; text-decoration: none;">Ver Doc</a>';
                        }
                        
                        echo "<tr>
                                <td style='font-weight: 600;'>".htmlspecialchars($r['descricao'])."</td>
                                <td style='font-weight: bold;'>R$ {$valor}</td>
                                <td>{$data}</td>
                                <td style='text-align: center;'>
                                    <span class='{$badge}' onclick='openStatusFinanceiro({$r['id']}, \"{$r['status']}\")' style='cursor: pointer;' title='Alterar Status'>
                                        {$status_text}
                                    </span>
                                </td>
                                <td style='text-align: center;'>{$link}</td>
                                <td style='text-align: right; padding-right: 20px;'>
                                    <a href='../actions/admin/financeiro_delete.php?cliente_id={$cliente_id}&del_fin={$r['id']}' 
                                       class='btn-icon danger' onclick='return confirm(\"Deseja excluir este lançamento?\")' title='Excluir'>
                                        <span class='material-symbols-rounded'>delete</span>
                                    </a>
                                </td>
                              </tr>";
                    }
                    echo "</tbody></table></div>";
                }
                echo "</div>";
            }

            renderFinTableNew($honorarios, "Honorários e Serviços Técnicos (Vilela Engenharia)", "var(--color-primary)", $cliente['id']);
            renderFinTableNew($taxas, "Taxas Administrativas, Multas e Cartórios", "#c9871a", $cliente['id']);

        } catch (Exception $e) {
            echo "<p style='color: var(--color-danger);'>Erro ao carregar dados financeiros.</p>";
        }
        ?>
    </div>

    <!-- ------------------ ABA 3: PENDÊNCIAS ------------------ -->
    <div class="admin-tab-content tab-pane" id="pane-pendencias">
        <div class="admin-header-row">
            <div>
                <h3 class="admin-title" style="margin: 0; border: none; padding: 0;">Gestão de Pendências</h3>
                <p class="admin-subtitle">Acompanhe solicitações de documentos e ações pendentes do cliente.</p>
            </div>
            <button type="button" class="btn-save" onclick="document.getElementById('modalPendenciaNew').showModal()">
                <span class="material-symbols-rounded">add_circle</span> Solicitar Documento / Pendência
            </button>
        </div>

        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Descrição da Solicitação / Pendência</th>
                        <th>Data de Abertura</th>
                        <th>Anexo Cliente</th>
                        <th style="text-align: right; padding-right: 20px;">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmtPen = $pdo->prepare("SELECT * FROM processo_pendencias WHERE cliente_id = ? ORDER BY data_criacao DESC");
                    $stmtPen->execute([$cliente['id']]);
                    $pendencias = $stmtPen->fetchAll();

                    if (empty($pendencias)): ?>
                        <tr>
                            <td colspan="5" style="padding: 40px; text-align: center; color: var(--color-muted); font-style: italic;">
                                Nenhuma pendência cadastrada para este cliente.
                            </td>
                        </tr>
                    <?php else: foreach ($pendencias as $p): 
                        $status_badge = $p['status'] === 'pendente' ? 'status-badge warning' : 'status-badge success';
                        $status_label = $p['status'] === 'pendente' ? 'Aberto' : 'Resolvido';
                        
                        $anexo = '<span style="opacity: 0.5;">--</span>';
                        if (!empty($p['arquivo_path'])) {
                            $anexo = '<a href="'.htmlspecialchars($p['arquivo_path']).'" target="_blank" style="color: var(--color-primary-dark); font-weight: 700; text-decoration: none;">Ver Anexo</a>';
                        }
                    ?>
                        <tr>
                            <td>
                                <form action="../actions/admin/pendencia_status_toggle.php" method="POST" style="display: inline;">
                                    <?php echo Csrf::getHtmlField(); ?>
                                    <input type="hidden" name="cliente_id" value="<?php echo $cliente['id']; ?>">
                                    <input type="hidden" name="pendencia_id" value="<?php echo $p['id']; ?>">
                                    <button type="submit" class="<?php echo $status_badge; ?>" style="border: none; cursor: pointer; font-family: inherit;">
                                        <?php echo $status_label; ?>
                                    </button>
                                </form>
                            </td>
                            <td style="font-weight: 600;"><?php echo htmlspecialchars($p['descricao']); ?></td>
                            <td style="color: var(--color-text-subtle);"><?php echo date('d/m/Y H:i', strtotime($p['data_criacao'])); ?></td>
                            <td><?php echo $anexo; ?></td>
                            <td style="text-align: right; padding-right: 20px;">
                                <a href="../actions/admin/pendencia_delete.php?cliente_id=<?php echo $cliente['id']; ?>&del_pen=<?php echo $p['id']; ?>" 
                                   class="btn-icon danger" onclick="return confirm('Deseja excluir esta pendência?')" title="Excluir">
                                    <span class="material-symbols-rounded">delete</span>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ------------------ ABA 4: DOCUMENTAÇÃO ------------------ -->
    <div class="admin-tab-content tab-pane" id="pane-documentos">
        
        <!-- Checklist de Documentos do Processo -->
        <div style="border-bottom: 1px solid var(--color-border); padding-bottom: 24px; margin-bottom: 24px;">
            <form id="formDocsGlobalNew" action="../actions/admin/documentos_checklist_update.php" method="POST">
                <?php echo Csrf::getHtmlField(); ?>
                <input type="hidden" name="cliente_id" value="<?php echo $cliente['id']; ?>">
                <input type="hidden" name="update_docs_settings" value="1">
                
                <div class="admin-header-row">
                    <div>
                        <h3 class="admin-title" style="margin: 0; border: none; padding: 0;">Checklist de Documentação Obrigatória</h3>
                        <p class="admin-subtitle">Selecione o tipo de processo para habilitar a lista de exigências do cliente.</p>
                    </div>
                    <button type="submit" class="btn-save">
                        <span class="material-symbols-rounded">save</span> Salvar Configuração
                    </button>
                </div>
                
                <div class="docs-header" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label>Tipo de Processo</label>
                        <select name="tipo_processo_chave" class="proc-select" onchange="this.form.submit()" style="background: white;">
                            <option value="">-- Selecione o Processo --</option>
                            <?php foreach ($processos_opts as $key => $proc): ?>
                                <option value="<?php echo $key; ?>" <?php echo $active_proc_key === $key ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($proc['titulo']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label>Instruções / Observações de Documentação</label>
                        <textarea name="observacoes_gerais" class="admin-form-input" rows="1" style="background: white;" placeholder="Instruções para o cliente sobre entrega..."><?php echo htmlspecialchars($detalhes['observacoes_gerais'] ?? ''); ?></textarea>
                    </div>
                </div>
            </form>

            <!-- Renderizador de Documentos Iniciais do Cliente -->
            <?php if ($active_proc_key && isset($processos_opts[$active_proc_key])): 
                $proc_data = $processos_opts[$active_proc_key];
                
                // Mapeia docs entregues
                $stmtMap = $pdo->prepare("SELECT doc_chave, arquivo_path, nome_original, status FROM processo_docs_entregues WHERE cliente_id = ?");
                $stmtMap->execute([$cliente['id']]);
                $entregues_map = [];
                foreach ($stmtMap->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $entregues_map[$row['doc_chave']] = $row;
                }
                
                // Função auxiliar local para renderizar cada card de documento
                function renderDocCardNew($label, $key, $entregues_map, $active_proc_key, $obrigatoriedade, $cliente_id) {
                    $doc_data = $entregues_map[$key] ?? null;
                    $status = $doc_data['status'] ?? 'pendente';
                    $has_file = !empty($doc_data['arquivo_path']);
                    
                    $color = '#9aa8a1'; $icon = 'check_box_outline_blank'; $status_bg = '#f1f4f2'; $status_txt = 'Pendente';
                    
                    if ($status === 'pendente') {
                        if ($obrigatoriedade === 'obrigatorio') {
                            $color = '#a32530'; $icon = 'priority_high'; $status_bg = '#fbe0e2'; $status_txt = 'Pendente (Obrigatório)';
                        } else {
                            $color = '#8a6400'; $icon = 'warning'; $status_bg = '#fdf2cf'; $status_txt = 'Pendente (Opcional)';
                        }
                    } else {
                        if ($status === 'em_analise') {
                            $color = 'var(--color-primary)'; $icon = 'hourglass_top'; $status_bg = 'var(--color-primary-tint)'; $status_txt = 'Enviado / Em Análise';
                        } elseif ($status === 'aprovado') {
                            $color = '#14654f'; $icon = 'check_circle'; $status_bg = '#d8f0e2'; $status_txt = 'Aprovado';
                        } elseif ($status === 'rejeitado') {
                            $color = '#a32530'; $icon = 'error'; $status_bg = '#fbe0e2'; $status_txt = 'Rejeitado / Pendente';
                        }
                    }
                    
                    echo '<div class="doc-card-admin" style="border-left: 4px solid '.$color.'; margin-bottom: 12px; padding: 15px;">';
                        echo '<div class="dca-info">';
                            echo '<div class="dca-icon" style="background: '.$status_bg.'; color: '.$color.'; width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center;"><span class="material-symbols-rounded">'.$icon.'</span></div>';
                            echo '<div class="dca-text" style="margin-left: 12px;">';
                                echo '<h4 style="margin: 0; font-size: 0.9rem; font-weight: 700;">'.htmlspecialchars($label).'</h4>';
                                echo '<span class="doc-status" style="background: '.$status_bg.'; color: '.$color.'; font-size: 0.68rem; font-weight: 700; padding: 2px 8px; border-radius: 4px; display: inline-block; margin-top: 4px;">'.$status_txt.'</span>';
                            echo '</div>';
                        echo '</div>';
                        
                        if ($has_file) {
                            echo '<a href="'.htmlspecialchars($doc_data['arquivo_path']).'" target="_blank" class="dca-file" style="margin-left: auto; margin-right: 15px; font-size: 0.78rem;" title="'.htmlspecialchars($doc_data['nome_original']).'">';
                                echo '<span class="material-symbols-rounded" style="font-size: 1rem;">description</span> Baixar Arquivo';
                            echo '</a>';
                        }
                        
                        echo '<div class="dca-actions" style="display: flex; gap: 6px; margin-left: '.($has_file ? '0' : 'auto').';">';
                            $hidden = Csrf::getHtmlField() . '
                                <input type="hidden" name="cliente_id" value="'.$cliente_id.'">
                                <input type="hidden" name="update_docs_settings" value="1">
                                <input type="hidden" name="doc_chave" value="'.htmlspecialchars($key).'">
                                <input type="hidden" name="tipo_processo_chave" value="'.htmlspecialchars($active_proc_key).'">';
                            $action = "../actions/admin/documentos_checklist_update.php";
                            
                            if ($status === 'aprovado') {
                                echo '<form action="'.$action.'" method="POST">'.$hidden.'<input type="hidden" name="action_doc" value="reopen"><button type="submit" class="btn-act" title="Reabrir / Desaprovar"><span class="material-symbols-rounded">undo</span></button></form>';
                            } else {
                                echo '<form action="'.$action.'" method="POST">'.$hidden.'<input type="hidden" name="action_doc" value="approve"><button type="submit" class="btn-act" style="background: var(--color-primary-soft); color: var(--color-primary-dark); border: none;" title="Aprovar"><span class="material-symbols-rounded">check</span></button></form>';
                                if ($status !== 'pendente' || $has_file) {
                                    echo '<form action="'.$action.'" method="POST" onsubmit="return confirm(\'Deseja rejeitar este documento?\')">'.$hidden.'<input type="hidden" name="action_doc" value="reject"><button type="submit" class="btn-act danger" title="Rejeitar"><span class="material-symbols-rounded">close</span></button></form>';
                                }
                            }
                        echo '</div>';
                    echo '</div>';
                }
            ?>
                <div class="docs-grid" style="margin-top: 20px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        
                        <!-- Coluna Obrigatórios -->
                        <div>
                            <div class="section-title" style="margin-bottom: 15px;"><span class="material-symbols-rounded" style="color: var(--color-danger);">assignment_late</span> Documentos Obrigatórios</div>
                            <?php foreach ($proc_data['docs_obrigatorios'] as $doc_key): 
                                $doc_label = $todos_docs[$doc_key] ?? $doc_key;
                                renderDocCardNew($doc_label, $doc_key, $entregues_map, $active_proc_key, 'obrigatorio', $cliente['id']);
                            endforeach; ?>
                        </div>
                        
                        <!-- Coluna Opcionais / Excepcionais -->
                        <div>
                            <div class="section-title" style="margin-bottom: 15px;"><span class="material-symbols-rounded" style="color: #c9871a;">assignment</span> Documentos Adicionais</div>
                            <?php if (!empty($proc_data['docs_excepcionais'])): ?>
                                <?php foreach ($proc_data['docs_excepcionais'] as $doc_key): 
                                    $doc_label = $todos_docs[$doc_key] ?? $doc_key;
                                    renderDocCardNew($doc_label, $doc_key, $entregues_map, $active_proc_key, 'excepcional', $cliente['id']);
                                endforeach; ?>
                            <?php else: ?>
                                <p style="font-style: italic; color: var(--color-muted); font-size: 0.88rem; padding: 15px 0;">Sem documentos adicionais exigidos para este processo.</p>
                            <?php endif; ?>
                        </div>
                        
                    </div>
                </div>
            <?php else: ?>
                <div class="docs-empty">
                    <span class="material-symbols-rounded" style="font-size: 3rem; display: block; margin-bottom: 10px; opacity: 0.5;">assignment</span>
                    Selecione um tipo de processo acima para carregar o checklist de documentos.
                </div>
            <?php endif; ?>
        </div>

        <!-- Documentos Finais Entregáveis (Sua Emissão -> Cliente) -->
        <div>
            <div class="admin-header-row">
                <div>
                    <h3 class="admin-title" style="margin: 0; border: none; padding: 0;">Projetos Concluídos & Entregáveis Finais</h3>
                    <p class="admin-subtitle">Envie projetos aprovados, alvarás, habites-se e certidões finais para o cliente.</p>
                </div>
                <button type="button" class="btn-save" onclick="document.getElementById('modalUploadEntregavel').showModal()">
                    <span class="material-symbols-rounded">cloud_upload</span> Disponibilizar Projeto / Certidão
                </button>
            </div>

            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Título do Documento Final</th>
                            <th>Data do Envio</th>
                            <th>Arquivo (.PDF / .DWG)</th>
                            <th style="text-align: right; padding-right: 20px;">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmtEnt = $pdo->prepare("SELECT * FROM processo_entregaveis WHERE cliente_id = ? ORDER BY data_upload DESC");
                        $stmtEnt->execute([$cliente['id']]);
                        $entregaveis = $stmtEnt->fetchAll();

                        if (empty($entregaveis)): ?>
                            <tr>
                                <td colspan="4" style="padding: 30px; text-align: center; color: var(--color-muted); font-style: italic;">
                                    Nenhum documento final ou projeto disponibilizado ainda.
                                </td>
                            </tr>
                        <?php else: foreach ($entregaveis as $ent): ?>
                            <tr>
                                <td style="font-weight: 700; color: var(--color-primary-dark);"><?php echo htmlspecialchars($ent['titulo']); ?></td>
                                <td style="color: var(--color-text-subtle);"><?php echo date('d/m/Y H:i', strtotime($ent['data_upload'])); ?></td>
                                <td>
                                    <a href="<?php echo htmlspecialchars($ent['arquivo_path']); ?>" target="_blank" class="dca-file" style="font-size: 0.78rem;">
                                        <span class="material-symbols-rounded" style="font-size: 1rem;">description</span> Ver Documento
                                    </a>
                                </td>
                                <td style="text-align: right; padding-right: 20px;">
                                    <a href="../actions/admin/entregavel_delete.php?cliente_id=<?php echo $cliente['id']; ?>&del_ent=<?php echo $ent['id']; ?>" 
                                       class="btn-icon danger" onclick="return confirm('Deseja excluir este documento entregável?')" title="Excluir">
                                        <span class="material-symbols-rounded">delete</span>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- ------------------ ABA 5: DADOS CADASTRAIS ------------------ -->
    <div class="admin-tab-content tab-pane" id="pane-dados">
        <?php 
        // Template clássico robusto do cadastro integrado
        include __DIR__ . '/../../includes/form_cliente_template.php'; 
        ?>
    </div>

    <!-- ==================== MODAIS DE CRIAÇÃO E STATUS ==================== -->
    
    <!-- Modal 1: Novo Andamento / Movimentação -->
    <dialog id="modalAndamentoNew">
        <div style="background: var(--color-primary); padding: 20px; display: flex; justify-content: space-between; align-items: center; color: white;">
            <h3 style="margin: 0; font-size: 1.2rem; display: flex; align-items: center; gap: 8px;">
                <span class="material-symbols-rounded">history</span> Novo Andamento do Processo
            </h3>
            <button type="button" onclick="document.getElementById('modalAndamentoNew').close()" style="background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <div style="padding: 25px;">
            <form action="../actions/admin/etapa_update.php" method="POST" enctype="multipart/form-data">
                <?php echo Csrf::getHtmlField(); ?>
                <input type="hidden" name="cliente_id" value="<?php echo $cliente['id']; ?>">
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Fase / Etapa Correspondente</label>
                    <select name="nova_etapa" class="proc-select" style="background: #fafbfc; border: 1px solid var(--color-border);">
                        <option value="">Manter atual: <?php echo htmlspecialchars($detalhes['etapa_atual'] ?? 'Não iniciada'); ?></option>
                        <?php foreach (Processo::$fases_padrao as $f): ?>
                            <option value="<?php echo $f; ?>"><?php echo $f; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Título da Atualização <span style="color: red;">*</span></label>
                    <input type="text" name="titulo_evento" required placeholder="Ex: Entrada do Projeto Realizada na Prefeitura" class="admin-form-input">
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Comentários e Detalhes</label>
                    <textarea name="observacao_etapa" rows="3" placeholder="Insira informações de prazos, taxas ou andamento da análise técnica..." class="admin-form-input" style="resize: vertical; font-family: inherit;"></textarea>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Anexar Documento Técnico (Opcional)</label>
                    <input type="file" name="arquivo_documento" class="admin-form-input">
                    <small style="color: var(--color-text-subtle); display: block; margin-top: 4px;">Ex: PDF de taxas ou comprovante de protocolo.</small>
                </div>

                <button type="submit" name="atualizar_etapa" class="btn-save btn-primary" style="width: 100%; padding: 12px; font-weight: 700;">
                    Gravar Andamento
                </button>
            </form>
        </div>
    </dialog>

    <!-- Modal 2: Novo Lançamento Financeiro -->
    <dialog id="modalFinanceiroNew">
        <div style="background: var(--color-primary); color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 1.2rem; display: flex; align-items: center; gap: 8px;">💰 Novo Lançamento Financeiro</h3>
            <button type="button" onclick="document.getElementById('modalFinanceiroNew').close()" style="background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        
        <form action="../actions/admin/financeiro_create.php" method="POST" style="padding: 25px;">
            <?php echo Csrf::getHtmlField(); ?>
            <input type="hidden" name="cliente_id" value="<?php echo $cliente['id']; ?>">
            
            <div class="form-group" style="margin-bottom: 15px;">
                <label>Descrição do Lançamento</label>
                <input type="text" name="descricao" required placeholder="Ex: Honorários Técnicos - Regularização de Casa" class="admin-form-input">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div class="form-group">
                    <label>Categoria</label>
                    <select name="categoria" required class="proc-select" style="background: white;">
                        <option value="honorarios">Honorários (Vilela Engenharia)</option>
                        <option value="taxas">Taxas e Multas (Prefeitura/Cartório)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Valor (R$)</label>
                    <input type="number" step="0.01" name="valor" required placeholder="0.00" class="admin-form-input">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                <div class="form-group">
                    <label>Data de Vencimento</label>
                    <input type="date" name="data_vencimento" required class="admin-form-input">
                </div>
                <div class="form-group">
                    <label>Status de Pagamento</label>
                    <select name="status" class="proc-select" style="background: white;">
                        <option value="pendente">⏳ Pendente</option>
                        <option value="pago">✅ Pago</option>
                        <option value="atrasado">❌ Atrasado</option>
                        <option value="isento">⚪ Isento</option>
                    </select>
                </div>
            </div>

            <button type="submit" name="btn_salvar_financeiro" class="btn-save btn-primary" style="width: 100%; padding: 12px; font-weight: 700;">
                Adicionar Fatura
            </button>
        </form>
    </dialog>

    <!-- Modal 3: Alterar Status de Lançamento Financeiro -->
    <dialog id="modalStatusFinanceiroEdit" style="border: none; border-radius: var(--radius); max-width: 420px; width: 90%; box-shadow: var(--shadow-lg);">
        <div style="background: var(--color-primary); color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 1.05rem;">Alterar Status da Fatura</h3>
            <button type="button" onclick="document.getElementById('modalStatusFinanceiroEdit').close()" style="background: none; border: none; color: white; font-size: 1.3rem; cursor: pointer;">&times;</button>
        </div>
        <form action="../actions/admin/financeiro_status_update.php" method="POST" style="padding: 20px;">
            <?php echo Csrf::getHtmlField(); ?>
            <input type="hidden" name="cliente_id" value="<?php echo $cliente['id']; ?>">
            <input type="hidden" name="financeiro_id" id="edit_fin_id">
            
            <div class="form-group" style="margin-bottom: 20px;">
                <label>Novo Status</label>
                <select name="novo_status" id="edit_fin_status" class="proc-select" style="background: white;">
                    <option value="pendente">⏳ Pendente</option>
                    <option value="pago">✅ Pago</option>
                    <option value="atrasado">❌ Atrasado</option>
                    <option value="isento">⚪ Isento</option>
                </select>
            </div>
            
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn-std btn-ghost" style="padding: 8px 16px;" onclick="document.getElementById('modalStatusFinanceiroEdit').close()">Cancelar</button>
                <button type="submit" name="btn_update_status_fin" class="btn-std btn-primary" style="padding: 8px 16px;">Salvar Status</button>
            </div>
        </form>
    </dialog>

    <!-- Modal 4: Nova Pendência para Cliente -->
    <dialog id="modalPendenciaNew">
        <div style="background: var(--color-primary); color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 1.2rem; display: flex; align-items: center; gap: 8px;">⚠️ Solicitar Pendência do Cliente</h3>
            <button type="button" onclick="document.getElementById('modalPendenciaNew').close()" style="background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <form action="../actions/admin/pendencia_create.php" method="POST" style="padding: 25px;">
            <?php echo Csrf::getHtmlField(); ?>
            <input type="hidden" name="cliente_id" value="<?php echo $cliente['id']; ?>">
            
            <div class="form-group" style="margin-bottom: 20px;">
                <label>Descrição do Documento ou Ação Exigida <span style="color: red;">*</span></label>
                <textarea name="descricao" required rows="4" placeholder="Ex: Cópia autenticada da certidão de casamento e espelho do IPTU de 2024..." class="admin-form-input" style="resize: vertical; font-family: inherit;"></textarea>
            </div>

            <button type="submit" name="btn_criar_pendencia" class="btn-save btn-primary" style="width: 100%; padding: 12px; font-weight: 700;">
                Abrir Solicitação
            </button>
        </form>
    </dialog>

    <!-- Modal 5: Upload de Entregável Final (Diego -> Cliente) -->
    <dialog id="modalUploadEntregavel">
        <div style="background: var(--color-primary); color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 1.2rem; display: flex; align-items: center; gap: 8px;">📂 Disponibilizar Documento Final</h3>
            <button type="button" onclick="document.getElementById('modalUploadEntregavel').close()" style="background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <form action="../actions/admin/entregavel_upload.php" method="POST" enctype="multipart/form-data" style="padding: 25px;">
            <?php echo Csrf::getHtmlField(); ?>
            <input type="hidden" name="cliente_id" value="<?php echo $cliente['id']; ?>">
            
            <div class="form-group" style="margin-bottom: 15px;">
                <label>Título do Documento Final <span style="color: red;">*</span></label>
                <input type="text" name="titulo" required placeholder="Ex: Planta Arquitetônica Aprovada - Habite-se" class="admin-form-input">
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label>Selecionar Arquivo Técnico (.PDF ou .ZIP) <span style="color: red;">*</span></label>
                <input type="file" name="arquivo_entregavel" required class="admin-form-input">
            </div>

            <button type="submit" name="btn_upload_entregavel" class="btn-save btn-primary" style="width: 100%; padding: 12px; font-weight: 700;">
                Enviar para o Portal do Cliente
            </button>
        </form>
    </dialog>

    <!-- JS de Navegação de Abas e Status Financeiro -->
    <script>
        // Alternador de Abas Dinâmico
        function switchDetailTab(tabId, btnElement) {
            // Remove classe ativa de todos os botões de aba
            const tabButtons = document.querySelectorAll("#detailTabsContainer .nav-pill");
            tabButtons.forEach(btn => btn.classList.remove("active"));
            
            // Remove classe ativa de todos os painéis
            const tabPanes = document.querySelectorAll(".tab-pane");
            tabPanes.forEach(pane => pane.classList.remove("active"));
            
            // Ativa o botão selecionado e seu painel correspondente
            btnElement.classList.add("active");
            document.getElementById("pane-" + tabId).classList.add("active");
            
            // Salva no localStorage para persistência de refresh
            localStorage.setItem("vilela_active_client_tab_" + <?php echo $cliente['id']; ?>, tabId);
        }

        // Abre o modal de status financeiro
        function openStatusFinanceiro(id, currentStatus) {
            document.getElementById('edit_fin_id').value = id;
            document.getElementById('edit_fin_status').value = currentStatus;
            document.getElementById('modalStatusFinanceiroEdit').showModal();
        }

        // Lógica de Persistência da Aba ao Carregar
        document.addEventListener("DOMContentLoaded", function() {
            const savedTab = localStorage.getItem("vilela_active_client_tab_" + <?php echo $cliente['id']; ?>);
            
            // Se houver parâmetro explícito 'tab' na URL
            const urlParams = new URLSearchParams(window.location.search);
            const urlTab = urlParams.get('tab');
            
            const activeTab = urlTab || savedTab || 'timeline';
            const activePill = document.querySelector(`#detailTabsContainer button[onclick*="'${activeTab}'"]`);
            
            if (activePill) {
                activePill.click();
            }
        });
    </script>
<?php endif; ?>
