<?php 
// logout.php
/*
 * OggiInLab
 * Copyright (c) 2025 Sergio Ferraro
 * Licensed under the MIT License
 */
session_start();
unset($_SESSION['alogin']);
session_destroy();
header("Location: index.php");
exit();
?>
