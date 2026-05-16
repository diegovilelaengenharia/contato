<?php
session_set_cookie_params(0, '/');
session_name('CLIENTE_SESSID');
session_start();
session_destroy();
header("Location: index.php");
exit;
?>
