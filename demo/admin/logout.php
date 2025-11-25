<?php
// admin/logout.php
// Cierra la sesión del administrador y vuelve al login.

session_start();
session_unset();
session_destroy();

header("Location: login.php");
exit;
