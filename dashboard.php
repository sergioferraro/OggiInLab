<?php
/**
 * dashboard.php – OggiInLab Dashboard
 *
 * OggiInLab
 * Copyright (c) 2026 Sergio Ferraro
 * Licensed under the MIT License
 */
declare(strict_types=1);

session_start();

require_once __DIR__ . '/includes/config.php';

// Error handling: in production errors are logged but never shown to the user
error_reporting(APP_DEBUG ? E_ALL : E_ERROR | E_WARNING | E_PARSE);
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', 1);

// -------------------------------------------------------------------
// Auth guard
// -------------------------------------------------------------------
if (empty($_SESSION['alogin'])) {
    header('Location: index.php');
    exit;
}

// -------------------------------------------------------------------
// Data fetching helpers
// -------------------------------------------------------------------
function fetchHolidays(PDO $dbh): array
{
    try {
        $stmt = $dbh->prepare(
            'SELECT giorno AS date, nomeChiusura AS name FROM calendario'
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Database Error (holidays): ' . $e->getMessage());
        return [];
    }
}

function fetchAppointments(PDO $dbh): array
{
    try {
        $stmt = $dbh->prepare(
            'SELECT DISTINCT
                    a.idAppuntamento,
                    a.data,
                    a.oraInizio,
                    a.oraFine,
                    a.descrizione,
                    au.nAula    AS luogo,
                    au.idAula   AS idLuogo,
                    p.nomeProgetto AS nomeproj,
                    p.idProgetto
                FROM appuntamento a
                LEFT JOIN aula      au ON a.luogo    = au.idAula
                LEFT JOIN progetto  p  ON a.idCorso  = p.idProgetto
                WHERE a.isDeleted = 0
                ORDER BY a.data, a.oraInizio'
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Database Error (appointments): ' . $e->getMessage());
        return [];
    }
}

function fetchAulas(PDO $dbh): array
{
    try {
        $stmt = $dbh->prepare('SELECT idAula, nAula FROM aula ORDER BY nAula ASC');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Database Error (aulas): ' . $e->getMessage());
        return [];
    }
}

// -------------------------------------------------------------------
// Color helper
// -------------------------------------------------------------------
function getReadableColor(string $nome): string
{
    $palette = [
        'D7263D', // Dark Vivid Red
        '1B998B', // Oil Green
        '2E294E', // Night Blue
        'F46036', // Rust Orange
        '0E79B2', // Deep Blue
        '5F0F40', // Dark Purple
        '028090', // Deep Water Green
        '8D0801', // Intense Red
    ];

    $label = $nome ?: 'default';
    $hex = substr(md5($label), 0, 6);

    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));

    $luminance = 0.299 * $r + 0.587 * $g + 0.114 * $b;

    if ($luminance < 50 || $luminance > 120) {
        $index = crc32($label) % count($palette);
        $hex = $palette[$index];
    }

    return '#' . strtoupper($hex);
}

// -------------------------------------------------------------------
// Build data
// -------------------------------------------------------------------
$festivi = fetchHolidays($dbh);
$appointments = fetchAppointments($dbh);
$aulas = fetchAulas($dbh);

$jsEvents = array_map(
    static function (array $appt): array {
        return [
            'id'          => $appt['idAppuntamento'],
            'idCorso'     => $appt['idProgetto'],
            'resourceId'  => $appt['idLuogo'] ?? '',
            'title'       => $appt['nomeproj'] ?? '',
            'date'        => $appt['data'],
            'startTime'   => substr($appt['oraInizio'], 0, 5),
            'endTime'     => substr($appt['oraFine'], 0, 5),
            'place'       => $appt['luogo'] ?? 'N/D',
            'descrizione' => $appt['descrizione'] ?? 'N/D',
            'color'       => getReadableColor($appt['nomeproj'] ?? 'default'),
        ];
    },
    $appointments
);

$holidayDates = array_column($festivi, 'date');

$pageTitle    = 'OggiInLab | Timeline';
$pageCssFiles = ['assets/css/dash.css'];
$pageCsrf     = true;
$pageScriptFiles = ['assets/js/dashboard.js'];
?>
<?php include "includes/header.php"; ?>

<div class="container main-container">
    <!-- Calendar title row -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="mb-0" id="calendar-title">Aprile 2025</h2>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Left column: calendar + news -->
        <div class="col-md-4 col-sm-12">
            <div class="calendar-container">
                <h4>Calendario mensile</h4>
                <div class="btn-group w-100">
                    <button class="btn btn-outline-primary" id="prev" style="flex:1">&laquo; Prec</button>
                    <button class="btn btn-primary" id="today" style="flex:1">Oggi</button>
                    <button class="btn btn-outline-primary" id="next" style="flex:1">Succ &raquo;</button>
                    <button class="btn btn-primary" id="stampa" style="flex:1" title="Stampa"><i class="fa-solid fa-print"></i></button>
                </div>
                <div id="calendar-days-placeholder"
                     style="min-height:300px; border:1px dashed #ccc; padding:10px;">
                </div>
            </div>

            <div class="news-container">
                <h4>News</h4>
                <div class="app_annullati"></div>
                <div class="app_modificati"></div>
                <div class="app_creati"></div>
            </div>
        </div>

        <!-- Right column: timeline -->
        <div class="col-md-8 col-sm-12">
            <div class="timeline-container">
                <div class="d-flex flex-column h-100">
                    <div class="daily-timeline" style="position:relative;">
                        <div class="time-ruler"></div>
                        <div class="event-container"></div>
                    </div>
                    <div class="event-grid"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Appointment detail / edit modal -->
    <div class="modal fade" id="appointmentDetailsEditModal" tabindex="-1"
         aria-labelledby="appointmentDetailsEditModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="appointmentDetailsEditModalLabel">
                        Dettagli Appuntamento
                    </h5>
                    <button type="button" class="btn-close"
                            data-bs-dismiss="modal" aria-label="Chiudi"></button>
                </div>

                <div class="modal-body">
                    <!-- Tabs -->
                    <ul class="nav nav-tabs" id="appointmentTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="details-tab"
                                    data-bs-toggle="tab" data-bs-target="#details-pane"
                                    type="button" role="tab"
                                    aria-controls="details-pane" aria-selected="true">
                                Dettagli
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="edit-tab"
                                    data-bs-toggle="tab" data-bs-target="#edit-pane"
                                    type="button" role="tab"
                                    aria-controls="edit-pane" aria-selected="false">
                                Modifica
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="appointmentTabsContent">
                        <!-- Details pane -->
                        <div class="tab-pane fade show active" id="details-pane"
                             role="tabpanel" aria-labelledby="details-tab" tabindex="0">
                            <div id="eventDetails">
                                <p>Data: <span id="detail-data"></span></p>
                                <p>Ora Inizio: <span id="detail-oraInizio"></span></p>
                                <p>Ora Fine: <span id="detail-oraFine"></span></p>
                                <p>Luogo: <span id="detail-luogo"></span></p>
                                <p>Descrizione: <span id="detail-descrizione"></span></p>
                            </div>
                            <a id="manageProjectBtn" class="btn btn-warning mt-3" style="display:none;" href="manage-project.php">
                                <i class="fa-solid fa-pen-to-square"></i> Modifica Progetto
                            </a>
                            <button class="btn btn-secondary mt-3" id="switch-to-edit-btn">
                                Modifica Appuntamento
                            </button>
                        </div>

                        <!-- Edit pane -->
                        <div class="tab-pane fade" id="edit-pane"
                             role="tabpanel" aria-labelledby="edit-tab" tabindex="0">
                            <form id="editAppointmentForm">
                                <input type="hidden" name="idCorso"
                                       id="editAppointmentProjectId">
                                <input type="hidden" name="idAppuntamento"
                                       id="editAppointmentId">

                                <div class="mb-3">
                                    <label for="editAppointmentData" class="form-label">
                                        Data
                                    </label>
                                    <input type="date" class="form-control"
                                           id="editAppointmentData" name="data" required>
                                </div>
                                <div class="mb-3">
                                    <label for="editAppointmentOraInizio" class="form-label">
                                        Ora Inizio
                                    </label>
                                    <input type="time" class="form-control"
                                           id="editAppointmentOraInizio" name="oraInizio" required>
                                </div>
                                <div class="mb-3">
                                    <label for="editAppointmentOraFine" class="form-label">
                                        Ora Fine
                                    </label>
                                    <input type="time" class="form-control"
                                           id="editAppointmentOraFine" name="oraFine" required>
                                </div>
                                <div class="mb-3">
                                    <label for="editAppointmentLuogo" class="form-label">
                                        Luogo
                                    </label>
                                    <select class="form-control"
                                            id="editAppointmentLuogo"
                                            name="idLuogo" required>
                                        <?php foreach ($aulas as $aula): ?>
                                            <option value="<?= htmlspecialchars((string) $aula['idAula']) ?>">
                                                <?= htmlspecialchars($aula['nAula']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="editAppointmentDescrizione" class="form-label">
                                        Descrizione
                                    </label>
                                    <input type="text" class="form-control"
                                           id="editAppointmentDescrizione"
                                           name="descrizione" readonly>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary"
                            data-bs-dismiss="modal">Chiudi</button>
                    <button class="btn btn-danger" id="deleteAppointmentBtnFooter">
                        Annulla appuntamento
                    </button>
                    <button type="submit" class="btn btn-primary"
                            id="saveAppointmentBtn"
                            form="editAppointmentForm">
                        Aggiorna Appuntamento
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bridge dati PHP → JS (deve arrivare PRIMA di dashboard.js) -->
<script>
    window.__DASHBOARD_DATA__ = {
        appointments: <?= json_encode($jsEvents) ?>,
        holidays:     <?= json_encode($festivi) ?>,
        holidayDates: <?= json_encode($holidayDates) ?>,
        csrfToken:    <?= json_encode($_SESSION['csrf_token'] ?? '') ?>
    };
</script>

<?php include('includes/footer.php'); ?>
