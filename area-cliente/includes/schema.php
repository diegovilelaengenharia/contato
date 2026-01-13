<?php
// Ensure Table Exists
$pdo->exec("CREATE TABLE IF NOT EXISTS processo_pendencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    descricao TEXT NOT NULL,
    status ENUM('pendente', 'resolvido') DEFAULT 'pendente',
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
)");

// Update Schema: Add ALL Missing Columns for processo_detalhes
$cols_needed = [
    'res_rua', 'res_numero', 'res_bairro', 'res_complemento', 'res_cidade', 'res_uf',
    'imovel_rua', 'imovel_numero', 'imovel_bairro', 'imovel_complemento', 'imovel_cidade', 'imovel_uf',
    'imovel_area_lote', 'area_construida',
    'num_matricula', 'inscricao_imob',
    'tipo_responsavel', 'resp_tecnico', 'registro_prof', 'num_art_rrt',
    'tipo_pessoa', 'cpf_cnpj', 'rg_ie', 'estado_civil', 'profissao', 'endereco_residencial', 'contato_email', 'contato_tel',
    // New Columns for Process Tracking
    'processo_numero', 'processo_objeto', 'processo_link_mapa',
    // New Columns for "Maria" Spec (Resumo do Patrimônio)
    'valor_venal', 'area_total_final', 'foto_capa_obra',
    // New Technical Columns (Oliveira/MG Spec)
    'area_existente', 'area_acrescimo', 'area_permeavel',
    'taxa_ocupacao', 'fator_aproveitamento', 'geo_coords',
    // New Personal Fields
    'data_nascimento', 'nome_conjuge', 'cpf_conjuge', 'nacionalidade', 'eh_procurador'
];

foreach($cols_needed as $col) {
    try {
        $pdo->query("SELECT $col FROM processo_detalhes LIMIT 1");
    } catch (Exception $e) {
        // Column doesn't exist, add it
        $type = ($col == 'processo_objeto') ? 'TEXT' : 'VARCHAR(255)';
        $pdo->exec("ALTER TABLE processo_detalhes ADD COLUMN $col $type DEFAULT NULL");
    }
}

// Update Schema: Add Columns for processo_financeiro
try {
    $pdo->query("SELECT referencia_legal FROM processo_financeiro LIMIT 1");
} catch (Exception $e) {
    $pdo->exec("ALTER TABLE processo_financeiro ADD COLUMN referencia_legal VARCHAR(255) DEFAULT NULL");
}

// Update Schema: Add Columns for processo_movimentos
try {
    $pdo->query("SELECT tipo_movimento FROM processo_movimentos LIMIT 1");
} catch (Exception $e) {
    // ENUM: 'padrao' (default bullet), 'fase_inicio' (Section Header), 'documento' (Downloadable).
    $pdo->exec("ALTER TABLE processo_movimentos ADD COLUMN tipo_movimento VARCHAR(50) DEFAULT 'padrao'");
}

// Create Dynamic Fields Table
$pdo->exec("CREATE TABLE IF NOT EXISTS processo_campos_extras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    valor TEXT,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
)");
// Update Schema: Add foto_perfil to clientes
try {
    $pdo->query("SELECT foto_perfil FROM clientes LIMIT 1");
} catch (Exception $e) {
    $pdo->exec("ALTER TABLE clientes ADD COLUMN foto_perfil VARCHAR(255) DEFAULT NULL");
}

// Create Admin Settings Table
$pdo->exec("CREATE TABLE IF NOT EXISTS admin_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) NOT NULL UNIQUE,
    setting_value TEXT
)");
?>
