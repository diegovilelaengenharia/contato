<?php
// Ensure metrics are available (or default to 0)
$kpi_pre_pendentes = $kpi_pre_pendentes ?? 0;
$count_ani = count($aniversariantes ?? []);
$count_par = count($parados ?? []);
?>

<!-- FAB Removed by user request (Moved to Top Header) -->

<!-- Mobile Branding Footer (Optional: Fixed at bottom left or removed?) 
     User requested discreet buttons. Let's keep it clean and remove branding from screen, 
     maybe just keep it in the header if it exists or footer if we add one.
     For now, removed from sidebar as requested.
-->

<script>
    function toggleFab() {
        document.querySelector('.fab-container').classList.toggle('active');
        const icon = document.querySelector('.fab-main .material-symbols-rounded');
        // Icon rotation handled by CSS
    }
    
    // Auto-close on click outside
    document.addEventListener('click', function(event) {
        const fab = document.querySelector('.fab-container');
        const isClickInside = fab.contains(event.target);
        if (!isClickInside && fab.classList.contains('active')) {
            fab.classList.remove('active');
        }
    });
</script>
