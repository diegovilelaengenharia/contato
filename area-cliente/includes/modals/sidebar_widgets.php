<!-- Modal Aniversariantes -->
<dialog id="modalAniversariantes" style="border:none; border-radius:12px; padding:0; box-shadow:0 10px 40px rgba(0,0,0,0.2); max-width:400px; width:90%;">
    <div style="padding:20px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center; background:#fff3cd;">
        <h3 style="margin:0; color:#856404; display:flex; align-items:center; gap:10px;">
            <span class="material-symbols-rounded">cake</span> Aniversariantes
        </h3>
        <form method="dialog"><button style="background:none; border:none; font-size:1.2rem; cursor:pointer; color:#856404;">✕</button></form>
    </div>
    <div style="padding:20px; max-height:60vh; overflow-y:auto;">
        <?php if(empty($aniversariantes)): ?>
             <p style="text-align:center; color:#666;">Nenhum aniversariante neste mês.</p>
        <?php else: ?>
            <ul style="list-style:none; padding:0; margin:0;">
                <?php foreach($aniversariantes as $ani): 
                     $nome_ani = htmlspecialchars($ani['nome']);
                     $dia_ani = $ani['dia'];
                ?>
                <li style="padding:10px; border-bottom:1px solid #f0f0f0; display:flex; justify-content:space-between; align-items:center;">
                    <span style="font-weight:600; color:#333;"><?= $nome_ani ?></span>
                    <span style="background:#fff3cd; color:#856404; padding:2px 8px; border-radius:12px; font-weight:bold; font-size:0.8rem;">Dia <?= $dia_ani ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</dialog>

<!-- Modal Processos Parados -->
<dialog id="modalParados" style="border:none; border-radius:12px; padding:0; box-shadow:0 10px 40px rgba(0,0,0,0.2); max-width:500px; width:90%;">
    <div style="padding:20px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center; background:#f8d7da;">
        <h3 style="margin:0; color:#dc3545; display:flex; align-items:center; gap:10px;">
            <span class="material-symbols-rounded">timer_off</span> Processos Parados (+15d)
        </h3>
        <form method="dialog"><button style="background:none; border:none; font-size:1.2rem; cursor:pointer; color:#dc3545;">✕</button></form>
    </div>
    <div style="padding:20px; max-height:60vh; overflow-y:auto;">
        <?php if(empty($parados)): ?>
             <p style="text-align:center; color:#666;">Todos os processos estão em dia! 🚀</p>
        <?php else: ?>
            <ul style="list-style:none; padding:0; margin:0;">
                <?php foreach($parados as $p): 
                     $nome_p = htmlspecialchars($p['nome']);
                     $data_mov = date('d/m/Y', strtotime($p['ultima_mov']));
                     $dias_parado = (strtotime('now') - strtotime($p['ultima_mov'])) / (60 * 60 * 24);
                ?>
                <li style="padding:10px; border-bottom:1px solid #f0f0f0; display:flex; align-items:center; justify-content:space-between; gap:10px;">
                    <div style="display:flex; flex-direction:column;">
                        <span style="font-weight:600; color:#333;"><?= $nome_p ?></span>
                        <span style="font-size:0.8rem; color:#dc3545;">Sem mov. desde <?= $data_mov ?> (<?= floor($dias_parado) ?> dias)</span>
                    </div>
                    <a href="?cliente_id=<?= $p['id'] ?>&tab=andamento" class="btn-save" style="text-decoration:none; transform:scale(0.9); padding:5px 10px;">Ver</a>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</dialog>
