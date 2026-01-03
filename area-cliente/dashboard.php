<?php
session_set_cookie_params(0, '/');
session_name('CLIENTE_SESSID');
session_start();
// Auth Check
if (!isset($_SESSION['cliente_id'])) {
    header("Location: index.php");
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
    <script type="module" crossorigin src="./assets/index-jGMwF09j.js"></script>
    <link rel="stylesheet" crossorigin href="./assets/index-CslgngmT.css">
  </head>
  <body>
    <div id="root"></div>
  </body>
</html>
