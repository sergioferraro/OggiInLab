<?php
// print_project.php

/*
 * OggiInLab
 * Copyright (c) 2025 Sergio Ferraro
 * Licensed under the MIT License
 */
include "../../includes/config.php";
date_default_timezone_set('Europe/Rome');
$idProgetto = $_GET['id'] ?? 1;

// Main query
$stmt = $dbh->prepare("SELECT * FROM progetto WHERE idProgetto = :id");
$stmt->execute(['id' => $idProgetto]);
$progetto = $stmt->fetch(PDO::FETCH_ASSOC);

// Query for Tutor/Expert
$stmt = $dbh->prepare("
    SELECT 
        (SELECT cognome FROM docente WHERE idDocente = :idTutor) AS Tutor,
        (SELECT cognome FROM docente WHERE idDocente = :idEsperto) AS Esperto
");
$stmt->execute([
    'idTutor' => $progetto['idTutor'],
    'idEsperto' => $progetto['idEsperto']
]);
$docenti = $stmt->fetch(PDO::FETCH_ASSOC);
// Appointments query
$stmt = $dbh->prepare("
    SELECT 
        appuntamento.data,
        TIME_FORMAT(appuntamento.oraInizio, '%H:%i') AS oraInizio,
        TIME_FORMAT(appuntamento.oraFine, '%H:%i') AS oraFine,
        aula.nAula
    FROM appuntamento
    LEFT JOIN aula ON aula.idAula = appuntamento.luogo
    WHERE idCorso = :idProgetto
");
$stmt->execute(['idProgetto' => $idProgetto]);
$appuntamenti = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dettagli Progetto #<?php echo $idProgetto; ?></title>
    <style>
     body {
            font-family: monospace;
            white-space: pre;
            font-size: 12px;
            background-color: #1e1e1e; /* Dark background */
            color: #ffffff; /* Light text */
        }
        th, td {
                border: 1px solid #ffffff !important;
                color:rgb(255, 255, 255);
                white-space: nowrap !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
            }
    h1 {
        text-align: center;
    }
    .columns {
        display: flex;
        gap: 20px;
    }
    .left-col, .right-col {
        flex: 1;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    
    
    @media print {
        body {
                background-color: white !important; /* White background */
                color: black !important;           /* Black text */
            }
        @page{
                size:landscape;
            }
        .print-button,.chiudi-button {
             display: none;
        }
        th, td {
        border: 1px solid #000 !important;
        color: #000 !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        max-width: 150px;
        min-height: 20px;
        vertical-align: top;
    }
    }
    .print-button, .chiudi-button {
        margin-top: 20px;
        padding: 10px 20px;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
    }
    .print-button {
        background-color: #4CAF50;
    }
    .chiudi-button {
        background-color: rgb(170, 70, 43);
    }
    .print-button:hover {
        background-color: #367c39;
    }
    .chiudi-button:hover {
        background-color: rgb(201, 23, 23);
    }
</style>
</head>
<body>
    <h1><?php echo ($progetto['nomeProgetto'] !== 'prenotazione') ? $progetto['nomeProgetto'] : $progetto['descProgetto']; ?></h1>

    <div class="left-col">
        Tutor: <?php echo $docenti['Tutor']; ?><br>
        Esperto: <?php echo $docenti['Esperto']; ?>
    </div>

    <div style="clear: both">
        <h3>Appuntamenti:</h3>
        <table>
            <tr>
                <th>Data</th>
                <th>Ora Inizio</th>
                <th>Ora Fine</th>
                <th>Aula</th>
            </tr>
            <?php foreach ($appuntamenti as $app): ?>
            <tr>
                <td><?php echo date_format(date_create($app['data']), 'd-m-Y'); ?></td>
                <td><?php echo $app['oraInizio']; ?></td>
                <td><?php echo $app['oraFine']; ?></td>
                <td><?php echo $app['nAula']; ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <br><br><br>
        <th>documento generato automaticamente il </th><?php echo date('d-m-Y H:i:s'); ?>
    </div>

    <button class="print-button" onclick="window.print()">Stampa questa scheda</button>
    <button onclick="closeTab()" class="chiudi-button">Chiudi questa scheda</button>
    <script>
        function closeTab() {
            // Attempt to close the tab
            window.close();
        }
    </script>
</body>
</html>