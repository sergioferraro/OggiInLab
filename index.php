<?php
/*
 * OggiInLab
 * Copyright (c) 2026 Sergio Ferraro
 * Licensed under the MIT License
 */
session_start();
include('includes/config.php');
if(isset($_POST['login'])) {
    $username = $_POST['userName'];
    $password_input = $_POST['password'];
    // Query per recuperare l'utente per username
    $sql = "SELECT id,userName, Password, nomeCompleto,is_super_admin,isActive FROM admin WHERE userName=:username AND isActive=1";
    $query = $dbh->prepare($sql);
    $query->bindParam(':username', $username, PDO::PARAM_STR);
    $query->execute();

    if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
        // Confronta la password con password_verify()
        if (password_verify($password_input, $row['Password'])) {
            // Aggiorna timestamp ultimo login
            $updateSql = "UPDATE admin SET lastLogin = NOW() WHERE id = :id";
            $updateStmt = $dbh->prepare($updateSql);
            $updateStmt->bindParam(':id', $row['id'], PDO::PARAM_INT);
            $updateStmt->execute();

            // Accesso riuscito
            // Rigenera session ID per prevenire session fixation
            session_regenerate_id(true);
            $_SESSION['alogin'] = $username;
            $_SESSION['nomeCompleto'] = $row['nomeCompleto'];
            $_SESSION['id'] = $row['id'];
            $_SESSION['is_super_admin'] = $row['is_super_admin'];
            header("Location: dashboard.php");
            exit();
        } else {
            echo "<script>alert('Username o password non validi');</script>";
        }
    } else {
        echo "<script>alert('Username o password non validi');</script>";
    }
}

$pageTitle    = 'OggiInLab | Indice';
$pageCssFiles = ['assets/css/custom.css'];
$pageStyles   = '
/* ---- 80/20 split ---- */
.index-split {
    display: flex;
    gap: 20px;
    align-items: flex-start;
    margin-top: 20px;
}
.index-gantt-col {
    flex: 0 0 80%;
    min-width: 0;
}
.index-login-col {
    flex: 0 0 20%;
    min-width: 260px;
}

/* ---- Tabellone ferroviario ---- */
#public-tabellone {
    background: #000;
    color: #CC9900;
    font-family: "Courier New", Courier, monospace;
    border: 2px solid #CC9900;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 0 20px rgba(204, 153, 0, 0.15), inset 0 0 40px rgba(204, 153, 0, 0.05);
    min-height: 400px;
    display: flex;
    flex-direction: column;
}

#tabellone-date {
    text-align: center;
    font-size: 1.4rem;
    letter-spacing: 2px;
    margin-bottom: 12px;
    text-shadow: 0 0 30px #e6d194, 0 0 0 #584f34;
    text-transform: uppercase;
    flex-shrink: 0;
}

/* Intestazioni colonne */
.tabellone-headers {
    display: flex;
    border-bottom: 2px solid #CC9900;
    padding-bottom: 6px;
    margin-bottom: 8px;
    flex-shrink: 0;
}
.tabellone-headers .th {
    text-align: center;
    font-size: 1.1rem;
    font-weight: bold;
    padding: 0 10px;
    letter-spacing: 1px;
    text-shadow: 0 0 15px #e6d194;
}
.tabellone-headers .th:nth-child(1),
.tabellone-headers .th:nth-child(2) { flex: 0 0 90px; }
.tabellone-headers .th:nth-child(3) { flex: 0 0 140px; }
.tabellone-headers .th:nth-child(4),
.tabellone-headers .th:nth-child(5) { flex: 1; min-width: 100px; }

/* Container eventi */
.tabellone-events {
    flex: 1;
    overflow-y: auto;
    padding-top: 4px;
}

/* Singolo evento */
.tabellone-event {
    display: flex;
    min-height: 50px;
    margin-bottom: 8px;
    border-bottom: 1px solid rgba(204, 153, 0, 0.15);
    transition: background-color 0.3s;
}
.tabellone-event:last-child { border-bottom: none; }

/* Evento in corso */
.tabellone-event.active-event {
    background-color: rgba(204, 153, 0, 0.12);
    box-shadow: inset 3px 0 0 #CC9900;
}

/* Campi */
.tabellone-event .field {
    text-align: center;
    padding: 6px 10px;
    font-size: 1.15rem;
    line-height: 1.4;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    text-shadow: 0 0 30px #e6d194, 0 0 0 #584f34;
}
.tabellone-event .field:nth-child(1),
.tabellone-event .field:nth-child(2) { flex: 0 0 90px; }
.tabellone-event .field:nth-child(3) { flex: 0 0 140px; }
.tabellone-event .field:nth-child(4),
.tabellone-event .field:nth-child(5) { flex: 1; min-width: 100px; }

/* Descrizione più piccola e sfumata */
.desc-field {
    font-size: 0.95rem !important;
    color: rgba(204, 153, 0, 0.65) !important;
}

/* ---- Animazione scorrimento tipo tabellone ferroviario ---- */
.scrolling-text {
    overflow: hidden;
    width: 100%;
    white-space: nowrap;
}
.scrolling-text span {
    display: inline-block;
    padding-right: 2em;
    animation: tabellone-scroll var(--scroll-duration, 8s) linear infinite;
}
@keyframes tabellone-scroll {
    0%   { transform: translateX(100%); }
    100% { transform: translateX(-100%); }
}

/* Messaggi vuoti / errore */
.tabellone-empty {
    text-align: center;
    padding: 40px 20px;
    color: rgba(204, 153, 0, 0.5);
    font-style: italic;
    font-size: 1rem;
}

/* ---- Compact login panel ---- */
.login-compact {
    background: #2c2c2c;
    border: 1px solid #343a40;
    border-radius: 8px;
    padding: 20px;
}
.login-compact .panel-heading {
    text-align: center;
    margin-bottom: 15px;
    font-weight: bold;
    color: #0d6efd;
}
.login-compact label {
    font-size: 0.85rem;
    color: #ccc;
}
.login-compact .form-control {
    background: #1e1e1e;
    border-color: #444;
    color: #f8f9fa;
    font-size: 0.9rem;
}
.login-compact .btn.btn-info {
    width: 100%;
    margin-top: 10px;
}

/* ---- Responsive ---- */
@media (max-width: 900px) {
    .index-split { flex-direction: column; }
    .index-gantt-col { flex: 1 1 100%; }
    .index-login-col { flex: 1 1 100%; }
    .tabellone-headers .th:nth-child(1),
    .tabellone-headers .th:nth-child(2) { flex: 0 0 70px; }
    .tabellone-headers .th:nth-child(3) { flex: 0 0 100px; }
    .tabellone-event .field:nth-child(1),
    .tabellone-event .field:nth-child(2) { flex: 0 0 70px; }
    .tabellone-event .field:nth-child(3) { flex: 0 0 100px; }
}
@media (max-width: 600px) {
    #public-tabellone { padding: 12px; }
    #tabellone-date { font-size: 1rem; }
    .tabellone-headers .th { font-size: 0.85rem; }
    .tabellone-event .field { font-size: 0.9rem; padding: 4px 6px; }
    .desc-field { font-size: 0.8rem !important; }
}
';
?>
<?php include('includes/header.php');?>
<div class="content-wrapper">
<div class="container">
<div class="row pad-botm">
<div class="col-md-12">
<h4 class="header-line">OggiInLab 2026</h4>
</div>
</div>
             
<!-- SPLIT LAYOUT: Gantt 80% | Login 20% -->
<div class="index-split">

    <!-- LEFT: Tabellone (80%) -->
    <div class="index-gantt-col">
        <div id="public-tabellone">
            <div id="tabellone-date">Caricamento...</div>
            <div id="tabellone-content"></div>
        </div>
    </div>

    <!-- RIGHT: Login (20%) -->
    <div class="index-login-col">
        <div class="login-compact">
            <div class="panel-heading"><i class="fas fa-lock"></i> Accesso Admin</div>
            <form role="form" method="post">
                <div class="form-group">
                    <label>Utente</label>
                    <input class="form-control" type="text" name="userName" required />
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input class="form-control" type="password" name="password" required />
                </div>
                <button type="submit" name="login" class="btn btn-info">LOGIN</button>
            </form>
        </div>
    </div>

</div>

<script>
(function() {
    var container = document.getElementById('tabellone-content');
    var dateEl    = document.getElementById('tabellone-date');

    fetch('assets/utils/public_today_gantt.php')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.error) {
                container.innerHTML = '<div class="tabellone-empty">' + data.error + '</div>';
                return;
            }

            // Mostra la data odierna
            var d = new Date(data.today + 'T00:00:00');
            dateEl.textContent = d.toLocaleDateString('it-IT', {
                weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
            });

            var events = data.events;
            if (events.length === 0) {
                container.innerHTML = '<div class="tabellone-empty">Nessuna attività programmata oggi.</div>';
                return;
            }

            // Ordina per orario di inizio
            events.sort(function(a, b) { return a.startTime.localeCompare(b.startTime); });

            // Rileva l'ora corrente per evidenziare l'evento in corso
            var now  = new Date();
            var nowH = now.getHours();
            var nowM = now.getMinutes();
            var nowMin = nowH * 60 + nowM;

            // Intestazioni colonne
            var html = '<div class="tabellone-headers">';
            html += '<div class="th">ORA INIZIO</div>';
            html += '<div class="th">ORA FINE</div>';
            html += '<div class="th">LUOGO</div>';
            html += '<div class="th">NOME PROGETTO</div>';
            html += '<div class="th">DESCRIZIONE</div>';
            html += '</div>';

            // Container eventi
            html += '<div class="tabellone-events">';

            events.forEach(function(ev) {
                var startParts = ev.startTime.split(':').map(Number);
                var endParts   = ev.endTime.split(':').map(Number);
                var startTotal = startParts[0] * 60 + startParts[1];
                var endTotal   = endParts[0] * 60 + endParts[1];
                var isActive = (nowMin >= startTotal && nowMin <= endTotal);

                html += '<div class="tabellone-event' + (isActive ? ' active-event' : '') + '">';
                html += '<div class="field">' + ev.startTime + '</div>';
                html += '<div class="field">' + ev.endTime + '</div>';
                html += '<div class="field">' + ev.place + '</div>';
                // Nome progetto – animazione se troppo lungo
                html += '<div class="field"><div class="scrolling-text"><span>' + ev.title + '</span></div></div>';
                // Descrizione – animazione se troppo lunga
                html += '<div class="field desc-field"><div class="scrolling-text"><span>' + (ev.description || '') + '</span></div></div>';
                html += '</div>';
            });

            html += '</div>';
            container.innerHTML = html;

            // ---- Attiva/disattiva l'animazione solo quando il testo è effettivamente troppo lungo ----
            requestAnimationFrame(function() {
                var fields = container.querySelectorAll('.field');
                fields.forEach(function(field) {
                    var wrapper = field.querySelector('.scrolling-text');
                    if (!wrapper) return; // Salta i campi senza testo scorrevole
                    var inner   = wrapper.querySelector('span');
                    if (!inner) return;
                    // Confronta larghezza del contenuto con quella del contenitore
                    if (inner.scrollWidth <= field.clientWidth) {
                        // Testo sta dentro: disabilita animazione
                        inner.style.animation = 'none';
                        inner.style.paddingLeft = '0';
                        wrapper.classList.remove('scrolling-text');
                    } else {
                        // Testo troppo lungo: calcola durata proporzionale
                        var ratio = inner.scrollWidth / field.clientWidth;
                        var duration = Math.max(6, ratio * 4) + 's';
                        inner.style.setProperty('--scroll-duration', duration);
                    }
                });
            });
        })
        .catch(function() {
            container.innerHTML = '<div class="tabellone-empty">Impossibile caricare i dati.</div>';
        });
})();
</script>            
             
 
    </div>
    </div>
     <!-- CONTENT-WRAPPER SECTION END-->
 <?php include('includes/footer.php');?>