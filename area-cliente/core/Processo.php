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
        return $stmt->fetch();
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
        return $stmt->fetchAll();
    }

    public static function getFinanceiro($cliente_id) {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM processo_financeiro WHERE cliente_id = ? ORDER BY data_vencimento ASC");
        $stmt->execute([$cliente_id]);
        return $stmt->fetchAll();
    }
}
