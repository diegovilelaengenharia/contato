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
            <a href="../actions/admin/cliente_impersonate.php?id=<?php echo $cliente['id']; ?>" target="_blank" class="top-nav-btn" style="background: var(--color-primary-tint); color: var(--color-primary-dark); border-color: var(--color-primary-soft);">
                <span class="material-symbols-rounded">visibility</span> Ver como Cliente
            </a>
            <!-- Excluir Cliente -->
            <form action="../actions/admin/cliente_delete.php" method="POST" onsubmit="return confirmDelete(event, 'ATENÇÃO EXTREMA: Isso apagará DEFINITIVAMENTE o cliente, histórico de obras, pendências e parcelas financeiras. Deseja prosseguir?')">
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
    <div x-data="clientAdmin(<?php echo $cliente['id']; ?>)">
    
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
        <?php require_once __DIR__ . '/partials/timeline.php'; ?>
    </div>

    <!-- ------------------ ABA 2: FINANCEIRO ------------------ -->
    <div class="admin-tab-content tab-pane" id="pane-financeiro">
        <?php require_once __DIR__ . '/partials/financeiro.php'; ?>
    </div>

    <!-- ------------------ ABA 3: PENDÊNCIAS ------------------ -->
    <div class="admin-tab-content tab-pane" id="pane-pendencias">
        <?php require_once __DIR__ . '/partials/pendencias.php'; ?>
    </div>

    <!-- ------------------ ABA 4: DOCUMENTAÇÃO ------------------ -->
    <div class="admin-tab-content tab-pane" id="pane-documentos">
        <?php require_once __DIR__ . '/partials/documentos.php'; ?>
    </div>

    <!-- ------------------ ABA 5: DADOS CADASTRAIS ------------------ -->
    <div class="admin-tab-content tab-pane" id="pane-dados">
        <?php 
        // Template clássico robusto do cadastro integrado
        include __DIR__ . '/../../includes/form_cliente_template.php'; 
        ?>
    </div>

    <!-- PAINEL DE NOTAS INTERNAS PRIVADAS (FEAT-05) -->
    <div class="form-card" style="margin-top: 30px; border-top: 3px solid var(--color-danger); padding: 20px;">
        <h3 class="admin-title" style="margin: 0 0 10px 0; border: none; padding: 0; display: flex; align-items: center; gap: 8px;">
            <span class="material-symbols-rounded" style="color: var(--color-danger);">edit_note</span> 
            Notas Internas Privadas (Uso Exclusivo do Diego)
        </h3>
        <p class="admin-subtitle" style="margin-bottom: 15px;">Estas notas são confidenciais e <strong>não</strong> serão compartilhadas com o cliente no portal ou app.</p>
        
        <form action="../actions/admin/processo_notas_update.php" method="POST" @submit.prevent="submitForm($event)">
            <?php echo Csrf::getHtmlField(); ?>
            <input type="hidden" name="cliente_id" value="<?php echo $cliente['id']; ?>">
            
            <div class="form-group" style="margin-bottom: 15px;">
                <textarea name="notas_internas" id="notas_internas_privadas" rows="4" placeholder="Insira aqui anotações internas sobre a obra, conversas telefônicas, acordos verbais ou detalhes cadastrais de controle..." class="admin-form-input" style="background: #fafbfc; border: 1px solid var(--color-border); resize: vertical; font-family: inherit; font-size: 0.9rem;"><?php echo htmlspecialchars($detalhes['notas_internas'] ?? ''); ?></textarea>
            </div>
            
            <button type="submit" class="btn-save btn-danger" style="display: inline-flex; align-items: center; gap: 8px; border: none; padding: 10px 18px; border-radius: 8px; font-weight: 600; font-size: 0.88rem; cursor: pointer;">
                <span class="material-symbols-rounded" style="font-size: 1.1rem;">save</span> Salvar Notas Internas
            </button>
        </form>
    </div>

    <!-- ==================== MODAIS DE CRIAÇÃO E STATUS ==================== -->
    <!-- Os modais modulares foram movidos para as suas respectivas parciais -->

    <!-- JS de Navegação de Abas e Status Financeiro -->
    <script>
        // Componente Alpine.js para gerenciar as ações de forma assíncrona
        function clientAdmin(clienteId) {
            return {
                clienteId: clienteId,
                loading: false,
                
                async submitForm(event) {
                    event.preventDefault();
                    this.loading = true;
                    
                    const form = event.target;
                    const formData = new FormData(form);
                    formData.append('format', 'json');
                    
                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: formData
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            Toastify({
                                text: data.message || 'Operação realizada com sucesso!',
                                duration: 5000,
                                gravity: "top",
                                position: "right",
                                style: {
                                    background: "var(--color-primary)",
                                    borderRadius: "10px",
                                    fontWeight: "600",
                                    boxShadow: "var(--shadow)"
                                }
                            }).showToast();
                            
                            // Fechar modais
                            document.querySelectorAll('dialog[open]').forEach(dialog => dialog.close());
                            
                            // Recarregar os painéis
                            await this.reloadTabs();
                        } else {
                            throw new Error(data.error || 'Erro na requisição');
                        }
                    } catch (err) {
                        Toastify({
                            text: err.message,
                            duration: 5000,
                            gravity: "top",
                            position: "right",
                            style: {
                                background: "var(--color-danger)",
                                borderRadius: "10px",
                                fontWeight: "600",
                                boxShadow: "var(--shadow)"
                            }
                        }).showToast();
                    } finally {
                        this.loading = false;
                    }
                },
                
                async deleteItem(event, confirmMsg) {
                    event.preventDefault();
                    
                    const result = await Swal.fire({
                        title: 'Confirmar exclusão',
                        text: confirmMsg,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#dc3545',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Sim, excluir',
                        cancelButtonText: 'Cancelar'
                    });
                    
                    if (result.isConfirmed) {
                        this.loading = true;
                        const form = event.target;
                        const formData = new FormData(form);
                        formData.append('format', 'json');
                        
                        try {
                            const response = await fetch(form.action, {
                                method: 'POST',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: formData
                            });
                            
                            const data = await response.json();
                            
                            if (data.success) {
                                Toastify({
                                    text: data.message || 'Excluído com sucesso!',
                                    duration: 5000,
                                    gravity: "top",
                                    position: "right",
                                    style: {
                                        background: "var(--color-primary)",
                                        borderRadius: "10px",
                                        fontWeight: "600",
                                        boxShadow: "var(--shadow)"
                                    }
                                }).showToast();
                                
                                await this.reloadTabs();
                            } else {
                                throw new Error(data.error || 'Erro ao excluir');
                            }
                        } catch (err) {
                            Toastify({
                                text: err.message,
                                duration: 5000,
                                gravity: "top",
                                position: "right",
                                style: {
                                    background: "var(--color-danger)",
                                    borderRadius: "10px",
                                    fontWeight: "600",
                                    boxShadow: "var(--shadow)"
                                }
                            }).showToast();
                        } finally {
                            this.loading = false;
                        }
                    }
                },
                
                async reloadTabs() {
                    const response = await fetch(window.location.href);
                    const html = await response.text();
                    
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    
                    const tabs = ['timeline', 'financeiro', 'pendencias', 'documentos'];
                    tabs.forEach(tab => {
                        const newPane = doc.getElementById('pane-' + tab);
                        const currentPane = document.getElementById('pane-' + tab);
                        if (newPane && currentPane) {
                            currentPane.innerHTML = newPane.innerHTML;
                        }
                    });
                    
                    const newNotes = doc.getElementById('notas_internas_privadas');
                    const currentNotes = document.getElementById('notas_internas_privadas');
                    if (newNotes && currentNotes) {
                        currentNotes.value = newNotes.value;
                    }
                    
                    const newHead = doc.querySelector('.page-head');
                    const currentHead = document.querySelector('.page-head');
                    if (newHead && currentHead) {
                        currentHead.innerHTML = newHead.innerHTML;
                    }
                }
            }
        }

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
    </div>
<?php endif; ?>
