<?php
/*
 * OggiInLab
 * Copyright (c) 2025 Sergio Ferraro
 * Licensed under the MIT License
 */
include "../../includes/config.php";
date_default_timezone_set('Europe/Rome');

// Query appointments
$stmt = $dbh->prepare("
    SELECT 
        appuntamento.data AS data,
        TIME_FORMAT(appuntamento.oraInizio, '%H:%i') AS oraInizio,
        TIME_FORMAT(appuntamento.oraFine, '%H:%i') AS oraFine,
        aula.nAula AS posizione,
        appuntamento.descrizione AS descrizione,
        progetto.nomeProgetto AS corso
    FROM appuntamento
    LEFT JOIN aula ON aula.idAula = appuntamento.luogo
    LEFT JOIN progetto ON appuntamento.idCorso = progetto.idProgetto
    WHERE data>=CURRENT_DATE
    ORDER BY data
");
$stmt->execute();
$appuntamenti = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dettagli</title>
    <style>
    body {
            font-family: monospace;
            white-space: pre;
            font-size: 12px;
            background-color: #1e1e1e; /* dark background */
            color: #ffffff; /* light text */
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
                background-color: white !important; /* white background */
                color: black !important;           /* black text */
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
    <h1>Prenotazioni</h1>
    <button class="print-button" onclick="window.print()">Stampa questa scheda</button>
    <button onclick="closeTab()" class="chiudi-button">Chiudi questa scheda</button>
    <div style="clear: both">
        <h3>Appuntamenti:</h3>
        <table>
            <tr>
                <th>Data</th>
                <th>Ora Inizio</th>
                <th>Ora Fine</th>
                <th>Aula</th>
                <th>Descrizione</th>
            </tr>
            <?php foreach ($appuntamenti as $app): ?>
            <tr>
                <td><?php echo date_format(date_create($app['data']), 'd-m-Y'); ?></td>
                <td><?php echo $app['oraInizio']; ?></td>
                <td><?php echo $app['oraFine']; ?></td>
                <td><?php echo $app['posizione']; ?></td>
                <td><?php echo (empty($app['descrizione'])) ? $app['corso'] : $app['descrizione']; ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <br><br><br>
        <th>documento generato automaticamente il </th><?php echo date('d-m-Y H:i:s'); ?>
    </div>
    <script>
        function closeTab() {
            // Attempt to close the tab
            window.close();
        }
    </script>
</body>
</html>