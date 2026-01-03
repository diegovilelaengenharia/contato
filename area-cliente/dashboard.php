<?php
session_set_cookie_params(0, '/');
session_name('CLIENTE_SESSID');
session_start();
// Auth Check
if (!isset($_SESSION['cliente_id'])) {
    header("Location: index.php?error=sessao_expirada");
    exit;
}
?>
<!doctype html>
<html lang="pt-BR">
  <head>
    <meta charset="UTF-8" />
    <link rel="icon" type="image/png" href="../assets/logo.png" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Vilela Engenharia | Área do Cliente</title>
    <!-- Updated Assets from 'client-app' build -->
    <script type="module" crossorigin src="./app/assets/index-D2e2zR_M.js"></script>
    <link rel="stylesheet" crossorigin href="./app/assets/index-D-oW3_17.css">
  </head>
  <body>
    <div id="root"></div>
  </body>
</html>
