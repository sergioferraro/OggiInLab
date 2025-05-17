<?php
// privacy.php
/*
 * OggiInLab
 * Copyright (c) 2025 Sergio Ferraro
 * Licensed under the MIT License
 */
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Privacy & Cookie Policy - OggiInLab</title>
    <link rel="stylesheet" href="assets/css/custom.css">
    <!-- BOOTSTRAP CORE STYLE (CYBORG) -->
    <link href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.0/dist/cyborg/bootstrap.min.css" rel="stylesheet">

    <!-- FONT AWESOME STYLE  -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- GOOGLE FONT -->
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
</head>
<body>
    <?php include "includes/header.php"; ?>
    <div class="container mt-5">
        <h1>Privacy & Cookie Policy</h1>
        <p>Ultimo aggiornamento: 16 maggio 2025</p>

        <h2>Privacy Policy</h2>
        <p>
            Il sito <strong>OggiInLab</strong> è un'applicazione web ad accesso riservato per la gestione di appuntamenti e attività di laboratorio.
            L'accesso è consentito esclusivamente a utenti autorizzati mediante credenziali personali.
        </p>
        <p>
            Durante l'utilizzo della piattaforma vengono raccolti e trattati esclusivamente i dati personali necessari per la registrazione e la gestione delle sessioni di accesso (ad esempio: nome utente, password criptata e sessione attiva).
            Nessun dato personale viene ceduto a terzi.
        </p>

        <h2>Cookie Policy</h2>
        <p>
            Questo sito utilizza esclusivamente <strong>cookie tecnici di sessione</strong>, indispensabili per consentire la navigazione e l'autenticazione degli utenti.
            In particolare, viene utilizzato un cookie denominato <code>PHPSESSID</code>, generato dal server PHP, che permette di gestire la sessione utente autenticata.
        </p>
        <p>
            Nessun cookie di profilazione o di terze parti viene utilizzato.
        </p>
        <p>
            Secondo la normativa vigente (art. 122 del D.Lgs. 196/2003, come modificato dal D.Lgs. 101/2018 e dal GDPR), i cookie tecnici non richiedono il consenso preventivo da parte dell'utente.
        </p>

        <h2>Contatti</h2>
        <p>
            Per qualsiasi informazione o segnalazione relativa al trattamento dei dati personali, è possibile contattare l'amministratore del sistema:
        </p>
        <ul>
            <li><strong>Responsabile:</strong> Sergio Ferraro</li>
            <li><strong>Email:</strong> [email]</li>
        </ul>

        <p class="mt-4">
            Torna alla <a href="dashboard.php">pagina principale</a>.
        </p>
    </div>

    <?php
    include('includes/footer.php');
    ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.11.6/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>

</body>
</html>
