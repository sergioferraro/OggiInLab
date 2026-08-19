<?php
/*
 * OggiInLab
 * Copyright (c) 2026 Sergio Ferraro
 * Licensed under the MIT License
 */
require_once __DIR__ . '/includes/session.php';
include('includes/config.php');
require_once __DIR__ . '/includes/Logger.php';
require_once __DIR__ . '/includes/login_rate_limit.php';

/*
 * Rate limiting del login (SECURITY_REPORT.md — M-3, CWE-307):
 *  1) throttling per IP (20 tentativi / 15 min, finestra mobile);
 *  2) lockout progressivo per username (5 fallimenti → 5 min di blocco,
 *     raddoppio a ogni +5 fallimenti, max 2h; reset al login riuscito);
 *  3) log di ogni attempt in logs/admin_actions.log (via Logger).
 * Stato su file JSON in logs/ (stesso meccanismo di public_today_gantt.php).
 */
// Hash fittizio (di nessun account) usato per equalizzare i tempi di risposta
// tra "utente non trovato" e "password errata" (anti timing-attack / enumerazione)
define('LOGIN_TIMING_HASH', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

if(isset($_POST['login'])) {
    $ip             = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $username       = trim((string)($_POST['userName'] ?? ''));
    $password_input = (string)($_POST['password'] ?? '');

    // Pulizia occasionale degli stati scaduti
    LoginRateLimit::cleanup();

    // Messaggio mostrato al client (stesso stile alert già in uso)
    $alert = static function (string $msg): void {
        echo '<script>alert(' . json_encode($msg, JSON_UNESCAPED_UNICODE) . ');</script>';
    };

    $clientInfo = [
        'ip_address'         => $ip,
        'username_attempted' => $username === '' ? null : $username,
    ];

    // 1) Throttling per IP
    $ipState = LoginRateLimit::ipAttempt($ip);
    if ($ipState['blocked']) {
        Logger::warning('login_ip_throttled', $clientInfo + [
            'wait_minutes'     => $ipState['wait'],
            'attempts_in_window' => $ipState['count'],
        ]);
        $alert('Troppi tentativi di accesso da questa rete. Riprova tra ' . $ipState['wait'] . ' minuti.');
    } elseif ($username === '' || $password_input === '') {
        Logger::warning('login_rejected', $clientInfo + ['reason' => 'credenziali vuote']);
        $alert('Inserisci utente e password.');
    } else {
        // 2) Lockout progressivo per username
        $userState = LoginRateLimit::checkUser($username);
        if ($userState['locked']) {
            Logger::critical('login_locked', $clientInfo + [
                'wait_minutes'    => $userState['wait'],
                'failed_attempts' => $userState['failures'],
            ]);
            $alert('Troppi tentativi falliti per questo utente. Riprova tra ' . $userState['wait'] . ' minuti.');
        } else {
            // 3) Recupero utente e verifica password
            $row = null;
            try {
                $sql = "SELECT id,userName, Password, nomeCompleto,is_super_admin,isActive FROM admin WHERE userName=:username AND isActive=1";
                $query = $dbh->prepare($sql);
                $query->bindParam(':username', $username, PDO::PARAM_STR);
                $query->execute();
                $row = $query->fetch(PDO::FETCH_ASSOC) ?: null;
            } catch (PDOException $e) {
                // Dettaglio solo nei log di server (M-4)
                error_log('OggiInLab login: errore query: ' . $e->getMessage());
                Logger::error('login_db_error', $clientInfo);
            }

            // password_verify viene sempre eseguito (su hash fittizio se l'utente
            // non esiste): tempi di risposta uniformi, anti enumerazione utenti
            $stored = (is_array($row) && is_string($row['Password'] ?? null)) ? $row['Password'] : LOGIN_TIMING_HASH;
            $valid  = is_array($row) && password_verify($password_input, $stored);

            if ($valid) {
                // Login riuscito: azzera eventuali fallimenti accumulati
                LoginRateLimit::recordSuccess($username);
                Logger::success('login_success', $clientInfo + [
                    'admin_id' => $row['id'],
                ]);

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
            }

            // 4) Login fallito: aggiorna contatori/lockout e logga
            $newState = LoginRateLimit::recordFailure($username);
            Logger::warning('login_failed', $clientInfo + [
                'failed_attempts' => $newState['failures'],
                'locked'          => $newState['locked'],
                'lock_minutes'    => $newState['wait'],
            ]);

            $msg = 'Username o password non validi.';
            if ($newState['locked']) {
                $msg .= ' Riprova tra ' . $newState['wait'] . ' minuti.';
            }
            $alert($msg);
        }
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