<?php
/**
 * Admin Helpers: Funções utilitárias para o painel administrativo
 */

if(!function_exists('renderFinTable')) {
    /**
     * Renderiza uma tabela financeira no admin
     */
    function renderFinTable($stmt, $title, $color, $cid) {
        if(!$stmt) return;
        $rows = $stmt->fetchAll();
        
        // S6: sanitiza todos os valores antes de emitir HTML
        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $safeColor = htmlspecialchars($color, ENT_QUOTES, 'UTF-8');
        $safeCid   = (int) $cid;

        echo "<div class='admin-tab-content' style='border-top: 4px solid {$safeColor}; margin-top:30px;'>
                <h3 class='admin-title' style='color:{$safeColor}; margin-bottom:20px;'>{$safeTitle}</h3>";
        
        if(count($rows) == 0) {
            echo "<p class='admin-subtitle' style='font-style:italic;'>Nenhum lançamento encontrado nesta categoria.</p>";
        } else {
            echo "<div class='admin-table-container'>
                  <table class='admin-table' style='min-width:600px;'>
                    <thead><tr>
                        <th>Descrição</th>
                        <th>Valor</th>
                        <th>Vencimento</th>
                        <th style='text-align:center;'>Status</th>
                        <th style='text-align:center;'>Ação</th>
                        <th></th>
                    </tr></thead><tbody>";
            foreach($rows as $r) {
                $badge_class = 'status-badge';
                $safeStatus = htmlspecialchars($r['status'], ENT_QUOTES, 'UTF-8');
                $status_label = ucfirst($safeStatus);

                switch($r['status']){
                    case 'pago': $badge_class.=' success'; break;
                    case 'pendente': $badge_class.=' warning'; break;
                    case 'atrasado': $badge_class.=' danger'; break;
                    case 'isento': $badge_class.=' info'; break;
                }
                $valor = number_format($r['valor'], 2, ',', '.');
                $data = date('d/m/Y', strtotime($r['data_vencimento']));
                $safeDescricao = htmlspecialchars($r['descricao'], ENT_QUOTES, 'UTF-8');
                $safeId = (int) $r['id'];

                // S6: link_comprovante — valida protocolo (só http/https) para evitar javascript: URLs
                $link = '--';
                if (!empty($r['link_comprovante'])) {
                    $href = $r['link_comprovante'];
                    if (preg_match('#^https?://#i', $href)) {
                        $safeHref = htmlspecialchars($href, ENT_QUOTES, 'UTF-8');
                        $link = "<a href='{$safeHref}' target='_blank' rel='noopener' style='color:var(--text-info); font-weight:600; text-decoration:none;'>Ver Doc</a>";
                    } else {
                        $link = "<span style='opacity:0.5' title='Link inválido'>--</span>";
                    }
                } else {
                    $link = "<span style='opacity:0.5'>--</span>";
                }
                
                echo "<tr>
                        <td style='font-weight:500;'>{$safeDescricao}</td>
                        <td style='font-weight:bold;'>R$ {$valor}</td>
                        <td>{$data}</td>
                        <td style='text-align:center;'>
                             <span class='{$badge_class}' onclick=\"openStatusFinModal({$safeId}, '{$safeStatus}')\" style='cursor:pointer;' title='Alterar Status'>
                                {$st_icon} {$status_label}
                             </span>
                        </td>
                        <td style='text-align:center;'>{$link}</td>
                        <td style='text-align:right;'>
                            <a href='actions/admin/financeiro_delete.php?cliente_id={$safeCid}&del_fin={$safeId}' onclick='confirmAction(event, \"Tem certeza que deseja EXCLUIR este lançamento financeiro?\")' class='btn-icon danger' title='Excluir lançamento'>
                                <span class='material-symbols-rounded'>delete</span>
                            </a>
                        </td>
                      </tr>";
            }
            echo "</tbody></table></div>";
        }
        echo "</div>";
    }
}

