<?php
require_once 'includes/config.php';

session_start();
session_destroy();

header('Location: public/index.html');
exit;
?>
