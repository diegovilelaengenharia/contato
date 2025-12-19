-- Tabela para a Linha do Tempo Detalhada
CREATE TABLE IF NOT EXISTS processo_movimentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    data_movimento DATETIME NOT NULL,
    titulo_fase VARCHAR(255) NOT NULL,
    descricao TEXT,
    departamento_origem VARCHAR(100),
    departamento_destino VARCHAR(100),
    usuario_responsavel VARCHAR(100),
    prazo_previsto DATE,
    anexo_nome VARCHAR(255),
    anexo_url VARCHAR(500),
    status_tipo ENUM('inicio', 'tramite', 'pendencia', 'documento', 'conclusao') DEFAULT 'tramite',
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
);
