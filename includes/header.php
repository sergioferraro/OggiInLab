<!DOCTYPE html>
<!--
  header.php
  OggiInLab
  Copyright (c) 2025 Sergio Ferraro
  Licensed under the MIT License
-->
<html lang="it" xmlns="http://www.w3.org/1999/xhtml">
<head>
    
    <link rel="icon" href="../../favicon.png" type="image/x-icon" />
    <!-- In includes/header.php -->
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Navbar</title>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a href="dashboard.php" class="navbar-brand">
            <img src="assets/img/logo.png" alt="Logo"/>
        </a>
        
        <!-- Toggler Button for Mobile View -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" 
                data-bs-target="#navbarToggler" aria-controls="navbarToggler" 
                aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar Content -->
        <div class="collapse navbar-collapse justify-content-end" id="navbarToggler">
            <ul class="navbar-nav mb-2 mb-lg-0">
            <li class="nav-item">
    <a href="dashboard.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : '' ?>">Dashboard</a>
</li>

<li class="nav-item">
    <a href="social.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'social.php') ? 'active' : '' ?>">Bacheca</a>
</li>

<!-- Prenotazioni Dropdown -->
<li class="nav-item dropdown">
    <a href="#" class="nav-link dropdown-toggle <?= (in_array(basename($_SERVER['PHP_SELF']), ['prenota-day.php', 'active_proj.php', 'all_project.php', 'add-project.php', 'prenota-deleted.php'])) ? 'active' : '' ?>" 
       data-bs-toggle="dropdown" aria-expanded="false">Prenotazioni</a>
    <ul class="dropdown-menu">
        <li><a href="prenota-day.php" class="dropdown-item <?= (basename($_SERVER['PHP_SELF']) == 'prenota-day.php') ? 'active' : '' ?>">Elenco prenotazioni</a></li>
        <li><a href="active_proj.php" class="dropdown-item <?= (basename($_SERVER['PHP_SELF']) == 'active_proj.php') ? 'active' : '' ?>">Progetti attivi</a></li>
        <li><a href="all_project.php" class="dropdown-item <?= (basename($_SERVER['PHP_SELF']) == 'all_project.php') ? 'active' : '' ?>">Progetti terminati</a></li>
        <li><a href="add-project.php" class="dropdown-item <?= (basename($_SERVER['PHP_SELF']) == 'add-project.php') ? 'active' : '' ?>">Aggiungi progetto</a></li>
        <li><a href="prenota-deleted.php" class="dropdown-item <?= (basename($_SERVER['PHP_SELF']) == 'prenota-deleted.php') ? 'active' : '' ?>">Prenotazioni annullate</a></li>
        <li><a href="campanella.php" class="dropdown-item <?= (basename($_SERVER['PHP_SELF']) == 'campanella.php') ? 'active' : '' ?>">Campanella</a></li>
    </ul>
</li>

<!-- Impostazioni Dropdown -->
<li class="nav-item dropdown">
    <a href="#" class="nav-link dropdown-toggle <?= (in_array(basename($_SERVER['PHP_SELF']), ['add_aula.php', 'orario_lab.php', 'servizi.php', 'manage_docenti.php', 'calend_ann.php','campanella.php'])) ? 'active' : '' ?>" 
       data-bs-toggle="dropdown" aria-expanded="false">Impostazioni</a>
    <ul class="dropdown-menu">
        <li><a href="add_aula.php" class="dropdown-item <?= (basename($_SERVER['PHP_SELF']) == 'add_aula.php') ? 'active' : '' ?>">Gestisci aule</a></li>
        <li><a href="orario_lab.php" class="dropdown-item <?= (basename($_SERVER['PHP_SELF']) == 'orario_lab.php') ? 'active' : '' ?>">Orario labs</a></li>
        <li><a href="servizi.php" class="dropdown-item <?= (basename($_SERVER['PHP_SELF']) == 'servizi.php') ? 'active' : '' ?>">Servizi e Manut.</a></li>
        <li><a href="manage_docenti.php" class="dropdown-item <?= (basename($_SERVER['PHP_SELF']) == 'manage_docenti.php') ? 'active' : '' ?>">Gestisci docenti</a></li>
        <li><a href="calend_ann.php" class="dropdown-item <?= (basename($_SERVER['PHP_SELF']) == 'calend_ann.php') ? 'active' : '' ?>">Calendario scolastico</a></li>
        <li><a href="campanella.php" class="dropdown-item <?= (basename($_SERVER['PHP_SELF']) == 'campanella.php') ? 'active' : '' ?>">Campanella</a></li>
    </ul>
</li>

<!-- User Avatar Dropdown -->
<?php if(isset($_SESSION['alogin'])): ?>
    <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle d-flex align-items-center <?= (basename($_SERVER['PHP_SELF']) == 'change-password.php' || basename($_SERVER['PHP_SELF']) == 'edit-profile.php' || basename($_SERVER['PHP_SELF']) == 'add-admin.php') ? 'active' : '' ?>" 
           href="#" data-bs-toggle="dropdown" data-bs-reference="parent" data-bs-auto-close="outside" aria-expanded="false">
            <img src="assets/img/user.png" alt="Avatar" class="rounded-circle me-2" style="width:25px; height:25px;">
            <?= htmlspecialchars($_SESSION['nomeCompleto'] ?? '') ?>
        </a>
        <ul class="dropdown-menu dropdown-menu-end" style="min-width: 150px;">
            <li><a href="change-password.php" class="dropdown-item <?= (basename($_SERVER['PHP_SELF']) == 'change-password.php') ? 'active' : '' ?>">Cambia password</a></li>
            <li><a href="edit-profile.php" class="dropdown-item <?= (basename($_SERVER['PHP_SELF']) == 'edit-profile.php') ? 'active' : '' ?>">Modifica profilo</a></li>
            <li><a href="add-admin.php" class="dropdown-item <?= (basename($_SERVER['PHP_SELF']) == 'add-admin.php') ? 'active' : '' ?>">Gestisci admin</a></li>
            <li><a href="logout.php" class="dropdown-item">DISCONNETTI</a></li>
        </ul>
    </li>
<?php else: ?>
    <li class="nav-item">
        <a href="index.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : '' ?>">Accedi</a>
    </li>
<?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

</body>
</html>
