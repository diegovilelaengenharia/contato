<?php
require_once 'Database.php';

class Processo {
    public static $fases_padrao = [
        "Abertura de Processo (Guichê)", 
        "Fiscalização (Parecer Fiscal)", 
        "Triagem (Documentos Necessários)",
        "Comunicado de Pendências (Triagem)", 
        "Análise Técnica (Engenharia)", 
        "Comunicado (Pendências e Taxas)",
        "Confecção de Documentos", 
        "Avaliação (ITBI/Averbação)", 
        "Processo Finalizado (Documentos Prontos)"
    ];

    public static function getDetalhes($cliente_id) {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM processo_detalhes WHERE cliente_id = ?");
        $stmt->execute([$cliente_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function getProgresso($etapa_atual) {
        $etapa_atual = trim($etapa_atual);
        $index = array_search($etapa_atual, self::$fases_padrao);
        if ($index === false) return 0;
        return round((($index + 1) / count(self::$fases_padrao)) * 100);
    }

    public static function getPendencias($cliente_id) {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM processo_pendencias WHERE cliente_id = ? ORDER BY data_criacao DESC");
        $stmt->execute([$cliente_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getFinanceiro($cliente_id) {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM processo_financeiro WHERE cliente_id = ? ORDER BY data_vencimento ASC");
        $stmt->execute([$cliente_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getCliente($cliente_id) {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
        $stmt->execute([$cliente_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function getQtdPendenciasAbertas($cliente_id) {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT count(*) as qtd FROM processo_pendencias WHERE cliente_id = ? AND status != 'resolvido'");
        $stmt->execute([$cliente_id]);
        return $stmt->fetchColumn();
    }

    public static function getQtdFinanceiroPendente($cliente_id) {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT count(*) as qtd FROM processo_financeiro WHERE cliente_id = ? AND (status = 'pendente' OR status = 'atrasado')");
        $stmt->execute([$cliente_id]);
        return $stmt->fetchColumn();
    }

    public static function getMovimentacoesRecentes($cliente_id) {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT titulo_fase, data_movimento FROM processo_movimentos WHERE cliente_id = ? AND data_movimento >= DATE_SUB(NOW(), INTERVAL 15 DAY) ORDER BY data_movimento DESC LIMIT 3");
        $stmt->execute([$cliente_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getUltimaPendenciaAberta($cliente_id) {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT titulo, descricao FROM processo_pendencias WHERE cliente_id = ? AND status != 'resolvido' ORDER BY data_criacao DESC LIMIT 1");
        $stmt->execute([$cliente_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function getProximoFinanceiroPendente($cliente_id) {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT descricao, valor FROM processo_financeiro WHERE cliente_id = ? AND (status = 'pendente' OR status = 'atrasado') ORDER BY data_vencimento ASC LIMIT 1");
        $stmt->execute([$cliente_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function getObservacaoEtapaAtual($cliente_id, $etapa_atual) {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT descricao FROM processo_movimentos WHERE cliente_id = ? AND titulo_fase = ? ORDER BY data_movimento DESC LIMIT 1");
        $stmt->execute([$cliente_id, $etapa_atual]);
        return $stmt->fetchColumn();
    }

    public static function getMovimentosTodos($cliente_id) {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM processo_movimentos WHERE cliente_id = ? ORDER BY data_movimento DESC");
        $stmt->execute([$cliente_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getEntregaveis($cliente_id) {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM processo_entregaveis WHERE cliente_id = ? ORDER BY data_upload DESC");
        $stmt->execute([$cliente_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function atualizarStatusPendenciaEmAnalise($id, $cliente_id) {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("UPDATE processo_pendencias SET status='em_analise' WHERE id=? AND cliente_id=? AND status != 'resolvido'");
        return $stmt->execute([$id, $cliente_id]);
    }
}
