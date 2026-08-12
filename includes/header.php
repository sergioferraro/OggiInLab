<!DOCTYPE html>
<!--
  header.php
  OggiInLab
  Copyright (c) 2026 Sergio Ferraro
  Licensed under the MIT License
-->
<html lang="it" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <link rel="icon" href="favicon.png" type="image/x-icon" />
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= htmlspecialchars($pageTitle ?? 'OggiInLab') ?></title>

    <!-- csrf-token (se la pagina lo richiede) -->
    <?php if (!empty($pageCsrf ?? '')): ?>
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
    <?php endif; ?>

    <!-- Dark theme Bootswatch Cyborg -->
    <link href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.0/dist/cyborg/bootstrap.min.css"
          rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans"
          rel="stylesheet" type="text/css" />
    <!-- Common dark-theme styles -->
    <link rel="stylesheet" href="assets/css/common.css" type="text/css" />

    <!-- Page-specific CSS files (opzionale: definire $pageCssFiles = ['file1.css', ...]) -->
    <?php if (!empty($pageCssFiles ?? [])): ?>
        <?php foreach ($pageCssFiles as $cssFile): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($cssFile) ?>" type="text/css" />
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Page-specific inline styles (opzionale: definire $pageStyles = '...') -->
    <?php if (!empty($pageStyles ?? '')): ?>
    <style>
        <?= $pageStyles ?>
    </style>
    <?php endif; ?>

    <!-- Page-specific inline scripts in <head> (opzionale: definire $pageHeadScripts = '...') -->
    <?php if (!empty($pageHeadScripts ?? '')): ?>
    <?= $pageHeadScripts ?>
    <?php endif; ?>
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

<li class="nav-item">
    <a href="prestiti.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'prestiti.php') ? 'active' : '' ?>">Prestiti</a>
</li>

<!-- Prenotazioni Dropdown -->
<li class="nav-item dropdown">
    <a href="#" class="nav-link dropdown-toggle <?= (in_array(basename($_SERVER['PHP_SELF']), ['prenota-day.php', 'active_proj.php', 'add-project.php', 'prenota-deleted.php'])) ? 'active' : '' ?>"
       data-bs-toggle="dropdown" aria-expanded="false">Prenotazioni</a>
    <ul class="dropdown-menu">
        <li><a href="prenota-day.php" class="dropdown-item <?= (basename($_SERVER['PHP_SELF']) == 'prenota-day.php') ? 'active' : '' ?>">Prenotazioni future</a></li>
        <li><a href="active_proj.php" class="dropdown-item <?= (basename($_SERVER['PHP_SELF']) == 'active_proj.php' && (!isset($_GET['status']) || $_GET['status'] !== 'done')) ? 'active' : '' ?>">Progetti attivi</a></li>
        <li><a href="active_proj.php?status=done" class="dropdown-item <?= (basename($_SERVER['PHP_SELF']) == 'active_proj.php' && isset($_GET['status']) && $_GET['status'] === 'done') ? 'active' : '' ?>">Progetti terminati</a></li>
        <li><a href="add-project.php" class="dropdown-item <?= (basename($_SERVER['PHP_SELF']) == 'add-project.php') ? 'active' : '' ?>">Aggiungi progetto</a></li>
        <li><a href="prenota-deleted.php" class="dropdown-item <?= (basename($_SERVER['PHP_SELF']) == 'prenota-deleted.php') ? 'active' : '' ?>">Prenotazioni annullate</a></li>
    </ul>
</li>

<!-- Impostazioni Dropdown -->
<li class="nav-item dropdown">
    <a href="#" class="nav-link dropdown-toggle <?= (in_array(basename($_SERVER['PHP_SELF']), ['add_aula.php', 'orario_lab.php', 'servizi.php', 'manage_docenti.php', 'calend_ann.php'])) ? 'active' : '' ?>"
       data-bs-toggle="dropdown" aria-expanded="false">Impostazioni</a>
    <ul class="dropdown-menu">
        <li><a href="add_aula.php" class="dropdown-item <?= (basename($_SERVER['PHP_SELF']) == 'add_aula.php') ? 'active' : '' ?>">Gestisci aule</a></li>
        <li><a href="orario_lab.php" class="dropdown-item <?= (basename($_SERVER['PHP_SELF']) == 'orario_lab.php') ? 'active' : '' ?>">Orario labs</a></li>
        <li><a href="servizi.php" class="dropdown-item <?= (basename($_SERVER['PHP_SELF']) == 'servizi.php') ? 'active' : '' ?>">Servizi e Manut.</a></li>
        <li><a href="manage_docenti.php" class="dropdown-item <?= (basename($_SERVER['PHP_SELF']) == 'manage_docenti.php') ? 'active' : '' ?>">Gestisci docenti</a></li>
        <li><a href="calend_ann.php" class="dropdown-item <?= (basename($_SERVER['PHP_SELF']) == 'calend_ann.php') ? 'active' : '' ?>">Calendario scolastico</a></li>
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
