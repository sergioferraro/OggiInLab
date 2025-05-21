<?php
// dashboard.php
/*
 * OggiInLab
 * Copyright (c) 2025 Sergio Ferraro
 * Licensed under the MIT License
 */
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include "includes/config.php";
// Redirect if not logged in
if (empty($_SESSION["alogin"])) {
    header("location: index.php");
    exit();
}
$festivi = [];
try {
    $query="SELECT giorno AS date,
            nomeChiusura AS name
            FROM calendario;";
    $stmt=$dbh->prepare($query);
    $stmt->execute();
    $festivi =$stmt->fetchAll(PDO::FETCH_ASSOC); 
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $festivi = []; 
}
// --- Fetch Appointments ---
$appointments = []; // Initialize as empty array
try {
    // Added DISTINCT to Luogo query
    $query = "SELECT DISTINCT
                a.idAppuntamento,
                a.data,
                a.oraInizio,
                a.oraFine,
                a.descrizione,
                au.nAula AS luogo,
                au.idAula AS idLuogo,
                p.nomeProgetto AS nomeproj,
                p.idProgetto
              FROM appuntamento a
              LEFT JOIN aula au ON a.luogo = au.idAula -- Use LEFT JOIN if luogo can be optional
              LEFT JOIN progetto p ON a.idCorso = p.idProgetto -- Use LEFT JOIN if project can be optional
              WHERE a.isDeleted = 0
              ORDER BY a.data, a.oraInizio;"; // Order by date first
    $stmt = $dbh->prepare($query);
    $stmt->execute();
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $appointments = []; // Ensure it's an empty array on error
}
// --- Prepare events for JavaScript ---
$jsEvents = [];
function getReadableColor($nome) {
    // fallback palette
    $colorPalette = [
        'D7263D', // Dark Vivid Red  
        '1B998B', // Oil Green  
        '2E294E', // Night Blue  
        'F46036', // Rust Orange  
        '0E79B2', // Deep Blue  
        '5F0F40', // Dark Purple  
        '028090', // Deep Water Green  
        '8D0801'  // Intense Red

    ];

    // If the name is null or empty, use 'default' as the color.
    $nome = (string)($nome ?? 'default');

    // Take the first 6 characters of the MD5 hash as the color.
    $colorHex = substr(md5($nome), 0, 6);

    // Convert the color to RGB values.
    $r = hexdec(substr($colorHex, 0, 2));
    $g = hexdec(substr($colorHex, 2, 2));
    $b = hexdec(substr($colorHex, 4, 2));

    // Calculate the perceived brightness.
    $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b);

    // If too dark or too light, use a color from the palette
    if ($luminance < 50 || $luminance > 120) {
        $index = crc32($nome) % count($colorPalette);
        $colorHex = $colorPalette[$index];
    }

    return '#' . strtoupper($colorHex);
}
foreach ($appointments as $appt) {
    // Ensure times are correctly formatted (HH:MM)
    
    
    $start_time = substr($appt['oraInizio'], 0, 5);
    $end_time = substr($appt['oraFine'], 0, 5);
    
    $jsEvents[] = [
        'id' => $appt['idAppuntamento'],
        'idCorso'=>$appt['idProgetto'],
        'resourceId' => $appt['idLuogo'] ?? '', // Handle potential null Luogo
        'title' => $appt['nomeproj'] ?? '', // Handle potential null project name
        'date' => $appt['data'], // Keep date separate
        'startTime' => $start_time, // HH:MM
        'endTime' => $end_time,     // HH:MM
        'place' => $appt['luogo'] ?? 'N/D', // Add place info directly
        'descrizione' => $appt['descrizione'] ?? 'N/D',
        //'color' => '#' . substr(md5($appt['nomeproj'] ?? 'default'), 0, 6)
        'color' => getReadableColor($appt['nomeproj'] ?? 'default')
    ];
    
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?>">


    <title>OggiInLab | Timeline</title>
    <!-- Dark theme Bootswatch Cyborg -->
    <link href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.0/dist/cyborg/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href='https://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
    <link rel="stylesheet" href="assets/css/dash.css" type='text/css' />
</head>
<style>
    #clock {
        font-family: Monospace;
        font-size: 2em;
        text-align: center;
        }
    .form-label {
        color: gray;
    }
    .form-control {
        background:rgb(146, 146, 146);
    }
</style>

<body>
<?php include "includes/header.php"; ?>
    <div class="container main-container">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="mb-0" id="calendar-title">Aprile 2025</h2>
                    <i class="fas fa-clock icon-clock"><span> </span><span id="clock"></span> <!-- Ora dinamica --></i> <!-- Icona -->
                    
                    <div class="btn-group">
                        <button class="btn btn-outline-primary" id="prev">&laquo; Prec</button>
                        <button class="btn btn-primary" id="today">Oggi</button>
                        <button class="btn btn-outline-primary" id="next">Succ &raquo;</button>
                        <button class="btn btn-primary" id="stampa">Stampa</button>

                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 col-sm-12">
                
                <div class="calendar-container">
                    <h4>Calendario mensile.</h4>
                    <div id="calendar-days-placeholder" style="min-height: 300px; border: 1px dashed #ccc; padding: 10px;">
                     </div>
                </div>
                <div class="news-container">
                    <h4>News</h4>
                    <label><input type="checkbox" id="filter-annullati"> Annullati</label>
                    <label><input type="checkbox" id="filter-modificati"> Modificati</label>
                    <label><input type="checkbox" id="filter-creati"> Creati</label>
                    <div class="app_annullati"></div>
                    <div class="app_modificati"></div>
                    <div class="app_creati"></div>
                </div>
            </div>
            <div class="col-md-8 col-sm-12">
            <div class="timeline-container">
                <div class="d-flex flex-column h-100">
                    <div class="daily-timeline" style="position: relative;">
                        <div class="time-ruler"></div>
                        <div class="event-container"></div>
                    </div>
                    <div class="event-grid">
                    </div>
                </div>
            </div>
            </div>
        </div>
        <div class="modal fade" id="appointmentDetailsEditModal" tabindex="-1" aria-labelledby="appointmentDetailsEditModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg"> <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="appointmentDetailsEditModalLabel">Dettagli Appuntamento</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
      </div>
      <div class="modal-body">

        <ul class="nav nav-tabs" id="appointmentTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="details-tab" data-bs-toggle="tab" data-bs-target="#details-pane" type="button" role="tab" aria-controls="details-pane" aria-selected="true">Dettagli</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="edit-tab" data-bs-toggle="tab" data-bs-target="#edit-pane" type="button" role="tab" aria-controls="edit-pane" aria-selected="false">Modifica</button>
          </li>
        </ul>

        <div class="tab-content" id="appointmentTabsContent">

          <div class="tab-pane fade show active" id="details-pane" role="tabpanel" aria-labelledby="details-tab" tabindex="0">
            <div id="eventDetails">
              <p>Data: <span id="detail-data"></span></p>
              <p>Ora Inizio: <span id="detail-oraInizio"></span></p>
              <p>Ora Fine: <span id="detail-oraFine"></span></p>
              <p>Luogo: <span id="detail-luogo"></span></p>
              <p>Descrizione: <span id="detail-descrizione"></span></p>
              </div>
            <button class="btn btn-secondary mt-3" id="switch-to-edit-btn">Modifica Appuntamento</button>

          </div>

          <div class="tab-pane fade" id="edit-pane" role="tabpanel" aria-labelledby="edit-tab" tabindex="0">
            <form id="editAppointmentForm">
              <input type="hidden" name="idCorso" id="editAppointmentProjectId">
              <input type="hidden" name="idAppuntamento" id="editAppointmentId">

              <div class="mb-3">
                <label for="editAppointmentData" class="form-label">Data</label>
                <input type="date" class="form-control" id="editAppointmentData" name="data" required>
              </div>
              <div class="mb-3">
                <label for="editAppointmentOraInizio" class="form-label">Ora Inizio</label>
                <input type="time" class="form-control" id="editAppointmentOraInizio" name="oraInizio" required>
              </div>
              <div class="mb-3">
                <label for="editAppointmentOraFine" class="form-label">Ora Fine</label>
                <input type="time" class="form-control" id="editAppointmentOraFine" name="oraFine" required>
              </div>
              <div class="mb-3">
                <label for="editAppointmentLuogo" class="form-label">Luogo</label>
                <select class="form-control" id="editAppointmentLuogo" name="idLuogo" required>
                  <?php
                    // populate
                    try {
                      $sql = "SELECT idAula, nAula FROM aula ORDER BY nAula ASC";
                      $stmt = $dbh->prepare($sql);
                      $stmt->execute();
                      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        echo "<option value='" . htmlspecialchars($row['idAula']) . "'>"
                             . htmlspecialchars($row['nAula']) . "</option>";
                      }
                    } catch (PDOException $e) {
                      error_log("Errore nel recupero delle aule: " . $e->getMessage());
                    }
                  ?>
                </select>
              </div>
              <div class="mb-3">
                <label for="editAppointmentDescrizione" class="form-label">Descrizione</label>
                <input type="text" class="form-control" id="editAppointmentDescrizione" name="descrizione" readonly>
                </div>
            </form>
          </div>

        </div> </div> <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button> <button class="btn btn-danger" id="deleteAppointmentBtnFooter">Annulla appuntamento</button>

        <button type="submit" class="btn btn-primary" id="saveAppointmentBtn" form="editAppointmentForm">Aggiorna Appuntamento</button>

      </div>
    </div>
  </div>
</div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

// Get an instance of the new unified modal
const appointmentDetailsEditModalElement = document.getElementById('appointmentDetailsEditModal');
const appointmentDetailsEditModal = new bootstrap.Modal(appointmentDetailsEditModalElement);

// Get references to buttons in the footer
const deleteAppointmentBtnFooter = document.getElementById('deleteAppointmentBtnFooter');
const saveAppointmentBtn = document.getElementById('saveAppointmentBtn');
const switchToArabicBtn = document.getElementById('switch-to-edit-btn'); // Bottone "Modifica" nella scheda dettagli

// Get references to form fields for modification
const editAppointmentForm = document.getElementById('editAppointmentForm');
const editAppointmentProjectId = document.getElementById('editAppointmentProjectId');
const editAppointmentId = document.getElementById('editAppointmentId');
const editAppointmentData = document.getElementById('editAppointmentData');
const editAppointmentOraInizio = document.getElementById('editAppointmentOraInizio');
const editAppointmentOraFine = document.getElementById('editAppointmentOraFine');
const editAppointmentLuogo = document.getElementById('editAppointmentLuogo');
const editAppointmentDescrizione = document.getElementById('editAppointmentDescrizione');

// Get references to spans on the details view
const detailData = document.getElementById('detail-data');
const detailOraInizio = document.getElementById('detail-oraInizio');
const detailOraFine = document.getElementById('detail-oraFine');
const detailLuogo = document.getElementById('detail-luogo');
const detailDescrizione = document.getElementById('detail-descrizione');


// --- Form Submission Handling ---
// Attach the submit listener directly to the form
if (editAppointmentForm) {
    editAppointmentForm.addEventListener('submit', (e) => {
        e.preventDefault();


        // Send an AJAX request
        fetch('assets/utils/edit-appointment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                idAppuntamento: editAppointmentId.value,
                idCorso: editAppointmentProjectId.value,
                data: editAppointmentData.value,
                oraInizio: editAppointmentOraInizio.value,
                oraFine: editAppointmentOraFine.value,
                luogo: editAppointmentLuogo.value, 
                descrizione: editAppointmentDescrizione.value,
                _token: csrfToken
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Appuntamento aggiornato con successo');
                appointmentDetailsEditModal.hide(); // Hide the unified modal
                location.reload(); // Refresh the page to see modifications
            } else {
                alert('Errore nell\'aggiornamento: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Errore AJAX:', error);
            alert('Si è verificato un errore durante l\'aggiornamento. Controlla i log.');
        });
    });
}


// --- Appointments Click Handling (Opening Detailed Modal) ---
document.body.addEventListener('click', function(event) {
    if (event.target.classList.contains('appointment')) {
        // Extract appointment data
        const currentEventId = event.target.dataset.appointmentId;
        const courseId = event.target.dataset.id; 
        const date = event.target.getAttribute('data-date');
        const startTime = event.target.getAttribute('data-start-time');
        const endTime = event.target.getAttribute('data-end-time');
        const resourceId = event.target.getAttribute('data-resource-id'); 
        const description = event.target.getAttribute('data-description');
        const luogoNome = event.target.getAttribute('data-place'); 


        // Populate fields in the Details sheet
        detailData.textContent = date;
        detailOraInizio.textContent = startTime;
        detailOraFine.textContent = endTime;
        detailLuogo.textContent = luogoNome; // Mostra il nome del luogo nei dettagli
        detailDescrizione.textContent = description;


        // Populate fields in the modify sheet
        editAppointmentProjectId.value = courseId;
        editAppointmentId.value = currentEventId;
        editAppointmentData.value = date;
        editAppointmentOraInizio.value = startTime;
        editAppointmentOraFine.value = endTime;
        // Select the correct option in the location select field
        
        if (editAppointmentLuogo && resourceId) {
             editAppointmentLuogo.value = resourceId;
        }
        editAppointmentDescrizione.value = description; 

        // Show the unified modal
        appointmentDetailsEditModal.show();

        // Activate the "Details" tab at opening
        const detailsTab = document.getElementById('details-tab');
        const tabInstance = bootstrap.Tab.getOrCreateInstance(detailsTab);
        tabInstance.show();

        // Update visibility of buttons in the footer (show "Details" buttons)
        updateFooterButtonVisibility('details');
    }
});

// --- Modify Appointment Click Handling ---
if (switchToArabicBtn) {
    switchToArabicBtn.addEventListener('click', function() {
        // Activate the "Modify" tab
        const editTab = document.getElementById('edit-tab');
        const tabInstance = bootstrap.Tab.getOrCreateInstance(editTab);
        tabInstance.show();

         // Update visibility of buttons in the footer (show "Modify" buttons)
         updateFooterButtonVisibility('edit');
    });
}


// --- Cancel Appointment Click Handling (Footer) ---
if (deleteAppointmentBtnFooter) {
    deleteAppointmentBtnFooter.addEventListener('click', function() {
        const courseId = editAppointmentProjectId.value; // Take the ID from the hidden field in the form
        const currentEventId = editAppointmentId.value;

        if (!currentEventId || !courseId) {
             alert("ID non validi per l'eliminazione.");
             return; // Exit the function if IDs are not valid
        }

        // Confirm with user before deleting
        if (confirm("Sei sicuro di voler annullare questo appuntamento?")) {
            fetch('assets/utils/invalida-appointment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `idCorso=${encodeURIComponent(courseId)}&idAppuntamento=${encodeURIComponent(currentEventId)}&_token=${csrfToken}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Appuntamento annullato con successo');
                    appointmentDetailsEditModal.hide(); // Hide the modal
                    location.reload(); // Refresh the page
                } else {
                    alert('Errore nell\'annullamento: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Errore AJAX:', error);
                alert('Si è verificato un errore durante l\'annullamento. Controlla i log.');
            });
        }
    });
}

// --- Footer Button Visibility Management ---
function updateFooterButtonVisibility(activeTabId) {
    if (activeTabId === 'details') {
        // Show buttons for both "Details" and "Modify" views
        if (deleteAppointmentBtnFooter) deleteAppointmentBtnFooter.style.display = 'inline-block'; 
        if (saveAppointmentBtn) saveAppointmentBtn.style.display = 'none';
        

    } else if (activeTabId === 'edit') {
        
        if (deleteAppointmentBtnFooter) deleteAppointmentBtnFooter.style.display = 'none';
        if (saveAppointmentBtn) saveAppointmentBtn.style.display = 'inline-block'; // O 'block'

    }
     // Show the "Close" button (data-bs-dismiss="modal") always
}

// --- Listener per il cambio di tab per aggiornare i bottoni del footer ---
appointmentDetailsEditModalElement.addEventListener('shown.bs.tab', function (event) {
    const activeTabId = event.target.id; // Active tab's ID (es. 'details-tab', 'edit-tab')

    if (activeTabId === 'details-tab') {
        updateFooterButtonVisibility('details');
         
        document.getElementById('appointmentDetailsEditModalLabel').textContent = 'Dettagli Appuntamento';
    } else if (activeTabId === 'edit-tab') {
        updateFooterButtonVisibility('edit');
         
         document.getElementById('appointmentDetailsEditModalLabel').textContent = 'Modifica Appuntamento';
    }
});

// Store the initial visibility of buttons when opening the modal for the first time or reloaded


      // Wait for the DOM to be fully loaded
      document.addEventListener('DOMContentLoaded', (event) => {
        function loadAppuntamentiAnnullati() {
            const xhr = new XMLHttpRequest();
            xhr.open('GET', 'assets/utils/get_deleted_appointments.php', true);
            xhr.onload = function() {
            if (this.status === 200) {
                const response = JSON.parse(this.responseText);
                let messaggio;
                if (response.error) {
                messaggio = 'Errore: ' + response.error;
                } else if (response.success) {
                let output = '';
                response.appointments.forEach(app => {
                    output += `${app.corso}\t` +
                    `${new Date(app.data).toLocaleDateString('it-IT', {day: 'numeric', month: 'long'})}\t` +
                    `${app.oraInizio.substring(0,5)}\t` +
                    `${app.oraFine.substring(0,5)}\t`+ `${app.descrizione}\t` +'ANNULLATO\n';
                });
                messaggio = output;
                } else {
                messaggio = response.message || 'Errore non specificato';
                }
                document.querySelector('.app_annullati').textContent = messaggio;
            } else {
                document.querySelector('.app_annullati').textContent = 'Errore di rete: ' + this.statusText;
            }
            };
            xhr.onerror = function() {
            document.querySelector('.app_annullati').textContent = 'Impossibile connettersi al server.';
            };
            xhr.send();
        }
        function loadAppuntamentiModificati(){
            const xhr = new XMLHttpRequest();
            xhr.open('GET', 'assets/utils/get_modified_appointments.php', true);
            xhr.onload = function() {
            if (this.status === 200) {
                const response = JSON.parse(this.responseText);
                let messaggio;
                if (response.error) {
                messaggio = 'Errore: ' + response.error;
                } else if (response.success) {
                let output = '';
                response.authors.forEach(app => {
                    output += '*' +`${app.autore}\t` +'ha modificato ' + `${app.titolo}` + ', descrizione: ' +`${app.descrizione}`+ ', nuova data: ' +
                    `${new Date(app.appData).toLocaleDateString('it-IT', {day: 'numeric', month: 'long'})}\t` +
                    `${app.oraInizio.substring(0,5)}` +'-'+
                    `${app.oraFine.substring(0,5)}` + '<br>';
                });
                messaggio = output;
                } else {
                messaggio = response.message || 'Errore non specificato';
                }
                document.querySelector('.app_modificati').innerHTML = messaggio;
            } else {
                document.querySelector('.app_modificati').textContent = 'Errore di rete: ' + this.statusText;
            }
            };
            xhr.onerror = function() {
            document.querySelector('.app_modificati').textContent = 'Impossibile connettersi al server.';
            };
            xhr.send();
        }
        function loadAppuntamentiCreati(){
            const xhr = new XMLHttpRequest();
            xhr.open('GET', 'assets/utils/get_today_created.php', true);
            xhr.onload = function() {
            if (this.status === 200) {
                const response = JSON.parse(this.responseText);
                let messaggio;
                if (response.error) {
                    messaggio = 'Errore: ' + response.error;
                } else if (response.success) {
                    let output = '';
                    response.authors.forEach(app => {
                        output += '*' + `${app.autore}\t` + 
                                'ha creato ' + `${app.titolo}` + 
                                ', descrizione: ' + `${app.descrizione}` + 
                                ', nuova data: ' +
                                `${new Date(app.appData).toLocaleDateString('it-IT', {day: 'numeric', month: 'long'})}\t` +
                                `${app.oraInizio.substring(0,5)}` + '-' +
                                `${app.oraFine.substring(0,5)}` + '<br>'; // Usa <br> invece di \n
                    });
                    messaggio = output;
                } else {
                    messaggio = response.message || 'Errore non specificato';
                }
                document.querySelector('.app_creati').innerHTML = messaggio; // Usa innerHTML
            }    else {
                document.querySelector('.app_creati').textContent = 'Errore di rete: ' + this.statusText;
            }
            };
            xhr.onerror = function() {
            document.querySelector('.app_creati').textContent = 'Impossibile connettersi al server.';
            };
            xhr.send();
        }
        loadAppuntamentiAnnullati();
        loadAppuntamentiModificati();
        loadAppuntamentiCreati();
        document.querySelectorAll('.news-container input[type="checkbox"]').forEach(checkbox => {
        checkbox.addEventListener('change', () => {
            const type = checkbox.id.replace('filter-', '');
            const section = document.querySelector(`.app_${type}`);
            if (checkbox.checked) {
                section.style.display = 'block';
            } else {
                section.style.display = 'none';
            }
            });
        });

        // Imposta la visibilità iniziale delle sezioni
        document.querySelectorAll('.news-container input[type="checkbox"]').forEach(checkbox => {
            if (checkbox.id === 'filter-annullati') {  // Solo questa checkbox è attiva di default
                checkbox.checked = true;
            } else {
                checkbox.checked = false;
            }
            const type = checkbox.id.replace('filter-', '');
            const section = document.querySelector(`.app_${type}`);
            if (checkbox.checked) {
                section.style.display = 'block';
            } else {
                section.style.display = 'none';
            }
        });
        function formatDateLocal(date) {
        const pad = n => String(n).padStart(2, '0');
        return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
        }
        // --- DOM Elements ---
        const calendarTitle = document.getElementById('calendar-title');
        const prevBtn = document.getElementById('prev');
        const nextBtn = document.getElementById('next');
        const todayBtn = document.getElementById('today');
        const stampaBtn = document.getElementById('stampa');
        addEventShowBtn = document.getElementById('addEventShowBtn'); // Button to open modal
        calendarDaysPlaceholder = document.getElementById('calendar-days-placeholder'); // Placeholder div
        // --- Global Variables ---
        let today = new Date(); // Use 'let' as it will be updated
        let currentMonth = today.getMonth();
        let currentYear = today.getFullYear();
        let selectedDate = today; // Keep track of the selected date for the modal
        const months = [
          "Gennaio", "Febbraio", "Marzo", "Aprile", "Maggio", "Giugno",
          "Luglio", "Agosto", "Settembre", "Ottobre", "Novembre", "Dicembre",
        ];
         const weekDays = [ // Italian short day names
             "Dom", "Lun", "Mar", "Mer", "Gio", "Ven", "Sab"
         ];
        // --- Event Data from PHP ---
        const allAppointments = <?php echo json_encode($jsEvents); ?>;
        const holidays = <?php echo json_encode($festivi); ?>;
        const holidayDates = holidays.map(h => h.date);
        // --- Core Functions ---
        function updateCalendarTitle() {
            if (calendarTitle) {
                calendarTitle.innerHTML = months[currentMonth] + " " + currentYear;
            }
        }
        function showDailyView(selectedDate) {
        const timelineContainer = document.querySelector('.daily-timeline');
        timelineContainer.innerHTML = ''; // Clean up previous content
        const dateString = formatDateLocal(selectedDate);
        const eventsForDay = allAppointments.filter(event => event.date === dateString);
        const locations = [...new Set(eventsForDay.map(e => e.place))];
    // General wrapper
    const wrapper = document.createElement('div');
    wrapper.className = 'timeline-container d-flex flex-row'; // Set a horizontal layout with Bootstrap
    // Left section (location labels)
    const left = document.createElement('div');
    left.className = 'left-section col-md-4 p-2 border-end'; // Column 1/3, right border
    locations.forEach(loc => {
        const locDiv = document.createElement('div');
        locDiv.className = 'location text-truncate mb-2';
        locDiv.textContent = loc;
        left.appendChild(locDiv);
    });
    // Right section (events + hours)
    const right = document.createElement('div');
    right.className = 'right-section col-md-8 d-flex flex-column p-2'; // Column 2/3, vertical layout
    // Container for appointments
    const appointmentsContainer = document.createElement('div');
    appointmentsContainer.className = 'appointments-container position-relative flex-grow-1 overflow-y-auto';
    // Time zone
    const scale = document.createElement('div');
    scale.className = 'hours-scale d-flex align-items-end justify-content-between p-2 bg-light';
    const startHour = 8;
    const endHour = 19; // End of hours
    const totalHours = endHour - startHour; // 11 hours
    const hourWidth = appointmentsContainer.clientWidth / totalHours;

for (let h = 0; h <= totalHours; h++) {
    const hourMark = document.createElement('div');
    hourMark.className = 'hour-mark text-center';
    hourMark.style.width = `${hourWidth}px`;
    hourMark.style.flex = '0 0 auto';
    hourMark.textContent = `${startHour + h}`;
    scale.appendChild(hourMark);
}
    right.appendChild(appointmentsContainer);
    right.appendChild(scale);
    setTimeout(() => {
        // Dynamically calculate row height
        const containerWidth = appointmentsContainer.clientWidth;
    if (containerWidth === 0) {
        console.warn("La larghezza del contenitore non può essere calcolata.");
        return;
    }
        const rowHeight = 40; // Each row height (es. 60px)
        appointmentsContainer.style.minHeight = `${locations.length * rowHeight}px`; // Set minimum height
        const totalMinutes = (endHour - startHour) * 60; // hours -> minutes
        const pxPerMin = containerWidth / totalMinutes;
        eventsForDay.forEach(event => {
            const rowIndex = locations.indexOf(event.place);
        const top = rowIndex * rowHeight;
        const [startH, startM] = event.startTime.split(':').map(Number);
        const [endH, endM] = event.endTime.split(':').map(Number);
        const startMin = (startH * 60 + startM) - (startHour * 60); // total minutes from 8:00 am
        const endMin = (endH * 60 + endM) - (startHour * 60);
        const duration = endMin - startMin;
        const leftPos = startMin * pxPerMin;
        const width = duration * pxPerMin;
        const appt = document.createElement('div');
        appt.className = 'appointment position-absolute text-white p-1 rounded';
        appt.style.top = `${top}px`;
        appt.style.left = `${leftPos}px`;
        appt.style.width = `${width}px`;
        appt.style.boxShadow = `
        2px -2px 4px rgba(255, 255, 255, 0.5),   /* Ombra chiara in alto a destra */
        -2px 2px 4px rgba(0, 0, 0, 0.3)        /* Ombra scura in basso a sinistra */
        `;
        appt.style.borderRadius = '10px';
        // Set data attributes
        appt.setAttribute('data-id', event.idCorso);
        appt.setAttribute('data-appointment-id', event.id);
        appt.setAttribute('data-description', event.descrizione || '');
        appt.setAttribute('data-start-time', event.startTime);
        appt.setAttribute('data-end-time', event.endTime);
        appt.setAttribute('data-place',  event.place);
        appt.setAttribute('data-date',event.date);
        appt.setAttribute('data-resource-id', event.resourceId);

        if (event.title !== "prenotazione" && event.title !== 'orario') {
            appt.textContent = event.title;
        } else {
            appt.textContent = event.descrizione;
        }
        appt.style.backgroundColor = event.color;
        appointmentsContainer.appendChild(appt);
    },0)
});
wrapper.appendChild(left);
wrapper.appendChild(right);
timelineContainer.appendChild(wrapper);
}
        // --- SIMPLIFIED Calendar Rendering ---
        function renderCalendarPlaceholder() {
            if (!calendarDaysPlaceholder) return;
            calendarDaysPlaceholder.innerHTML = '';
            const firstDayOfMonth = new Date(currentYear, currentMonth, 1);
            const lastDayOfMonth = new Date(currentYear, currentMonth + 1, 0);
            const firstDayIndex = firstDayOfMonth.getDay();
            const lastDateOfMonth = lastDayOfMonth.getDate();
            // Add weekday headers
            const headerRow = document.createElement('div');
            headerRow.style.display = 'flex';
            headerRow.style.fontWeight = 'bold';
            headerRow.style.marginBottom = '5px';
            headerRow.style.borderBottom = '1px solid var(--bs-primary)';
            weekDays.forEach(day => {
                const dayHeader = document.createElement('div');
                dayHeader.style.width = '14.28%';
                dayHeader.style.textAlign = 'center';
                dayHeader.textContent = day;
                headerRow.appendChild(dayHeader);
            });
            calendarDaysPlaceholder.appendChild(headerRow);
            // Create a grid container
            const grid = document.createElement('div');
            grid.style.display = 'flex';
            grid.style.flexWrap = 'wrap';
            // Add empty divs for padding before the 1st day
            for (let i = 0; i < firstDayIndex; i++) {
                const emptyCell = document.createElement('div');
                emptyCell.style.width = '14.28%';
                emptyCell.style.height = '50px';
                grid.appendChild(emptyCell);
            }
            // Add day cells for the current month
for (let day = 1; day <= lastDateOfMonth; day++) {
    const dayCell = document.createElement('div');
    dayCell.style.width = '14.28%';
    dayCell.style.height = '50px';
    dayCell.style.textAlign = 'center';
    dayCell.style.paddingTop = '5px';
    dayCell.style.cursor = 'pointer';
    dayCell.classList.add('calendar-day');
    dayCell.style.boxShadow = `
        2px -2px 4px rgba(255, 255, 255, 0.5),   /* Ombra chiara in alto a destra */
        -2px 2px 4px rgba(0, 0, 0, 0.3)        /* Ombra scura in basso a sinistra */
        `;
        dayCell.style.borderRadius = '10px';

    dayCell.textContent = day;
    dayCell.dataset.day = day;
    const cellDate = new Date(currentYear, currentMonth, day);
    const pad = n => String(n).padStart(2, '0');
    const dateString = formatDateLocal(cellDate);

    // Basic check for events on this day
    const hasEvent = allAppointments.some(event => event.date === dateString);
    const isHoliday = holidayDates.includes(dateString);
    const todayDateOnly = new Date(today.getFullYear(), today.getMonth(), today.getDate());

    if (isHoliday) {
        dayCell.style.backgroundColor = '#FF6B6B';
        
        dayCell.title = `${holidays.find(h => h.date === dateString)?.name || 'Festivo'}`;
    } else if (cellDate.getDay() === 0) { // Sunday
        dayCell.style.backgroundColor = '#FF6B6B';
        dayCell.title = 'Domenica';
    } else if (hasEvent) {
        dayCell.style.backgroundColor = 'rgba(179, 138, 221, 0.3)';
        dayCell.title = 'Eventi presenti';
    } else {
        dayCell.style.backgroundColor = 'var(--bs-bg-dark)';
    }

    // Highlight today
    if (cellDate.getTime() === todayDateOnly.getTime()) {
        dayCell.style.fontWeight = 'bold';
        dayCell.style.backgroundColor = 'green';
    }

    dayCell.originalBorder = dayCell.style.border;

    dayCell.addEventListener('mouseenter', function() {
        this.style.border = '2px solid red';
    });
    dayCell.addEventListener('mouseleave', function() {
        this.style.border = this.originalBorder;
    });

    dayCell.addEventListener('click', () => {
        selectedDate = cellDate;
        renderTimelinePlaceholder(selectedDate);
        showDailyView(selectedDate);

        // Reset all cells to original colors
        const allDayCells = document.querySelectorAll('.calendar-day');
        allDayCells.forEach(cell => {
            const cellDay = cell.dataset.day;
            const cellDateObj = new Date(currentYear, currentMonth, cellDay);
            const dateString = formatDateLocal(cellDateObj);
            const isEvent = allAppointments.some(event => event.date === dateString);
            const isHoliday = holidayDates.includes(dateString);
            const todayDateOnly = new Date(today.getFullYear(), today.getMonth(), today.getDate());

            if (isHoliday) {
                cell.style.backgroundColor = '#FF6B6B';
            } else if (cellDateObj.getDay() === 0) {
                cell.style.backgroundColor = '#FF6B6B';
            } else if (isEvent) {
                cell.style.backgroundColor = 'rgba(179, 138, 221, 0.3)';
            } else {
                cell.style.backgroundColor = 'var(--bs-bg-dark)';
            }

            if (cellDateObj.getTime() === todayDateOnly.getTime()) {
                cell.style.fontWeight = 'bold';
                cell.style.backgroundColor = 'green';
            }
        });

        // Color the selected cell blue
        dayCell.style.backgroundColor = 'blue';
    });

    grid.appendChild(dayCell);
}

calendarDaysPlaceholder.appendChild(grid);
        }
        // --- SIMPLIFIED Timeline Rendering ---
        function renderTimelinePlaceholder(dateToShow) {
            const timelineGrid = document.querySelector('.timeline-container .event-grid');
            if (!timelineGrid) return;
            const dateString = formatDateLocal(dateToShow);
            const eventsForDay = allAppointments.filter(event => event.date === dateString);
            timelineGrid.innerHTML = `Eventi per ${dateToShow.toLocaleDateString('it-IT')}:<br>`;
            
            const dataSelezionata = new Date(dateToShow);
            if(holidayDates.includes(dateString)){
                timelineGrid.innerHTML += `${holidays.find(h => h.date === dateString)?.name || 'Festivo'}`;
            } else if(dataSelezionata.getDay() === 0) {
                timelineGrid.innerHTML += '<i>Domenica.<br></i>';
            }
            else if (eventsForDay.length === 0) {
                timelineGrid.innerHTML += '<i>Nessun evento programmato.<br></i>';
                timelineGrid.innerHTML += `
                <a href="prenota-day.php?openModal=true&date=${dateString}" class="btn btn-primary">
                    Aggiungi
                </a>
                `;
            } else {
                eventsForDay.sort((a, b) => a.startTime.localeCompare(b.startTime));
                eventsForDay.forEach(event => {
                    const eventDiv = document.createElement('div');
                    eventDiv.style.borderLeft = `5px solid ${event.color || 'var(--bs-primary)'}`;
                    eventDiv.style.margin = '5px 0';
                    eventDiv.style.padding = '3px 5px';
                    eventDiv.style.fontSize = '0.9em';
                    eventDiv.innerHTML = `<b>${event.title}</b> (${event.startTime} - ${event.endTime}) @ ${event.place} - ${event.descrizione}`;
                    timelineGrid.appendChild(eventDiv);
                });
                timelineGrid.innerHTML += `
                <a href="prenota-day.php?openModal=true&date=${dateString}" class="btn btn-primary">
                    Aggiungi
                </a>
                `;
            }
            

        }
        function goToPrevMonth() {
            currentMonth--;
            if (currentMonth < 0) {
                currentMonth = 11;
                currentYear--;
            }
            updateCalendarTitle();
            renderCalendarPlaceholder();
        }
        function goToNextMonth() {
            currentMonth++;
            if (currentMonth > 11) {
                currentMonth = 0;
                currentYear++;
            }
            updateCalendarTitle();
            renderCalendarPlaceholder();
        }
        function goToToday() {
            const now = new Date();
            currentMonth = now.getMonth();
            currentYear = now.getFullYear();
            selectedDate = now;
            updateCalendarTitle();
            renderCalendarPlaceholder();
            showDailyView(selectedDate);
            renderTimelinePlaceholder(selectedDate);
        }
        function stampaFunction() {
            const dateObj = new Date(selectedDate);
            const year = dateObj.getFullYear();
            const month = String(dateObj.getMonth() + 1).padStart(2, '0'); // months are 0-index
            const day = String(dateObj.getDate()).padStart(2, '0');
            const dateToShow = `${year}-${month}-${day}`;
            window.open('assets/utils/print_today.php?data=' + dateToShow, '_blank');
        }
        // --- Event Listeners ---
        if (prevBtn) prevBtn.addEventListener("click", goToPrevMonth);
        if (nextBtn) nextBtn.addEventListener("click", goToNextMonth);
        if (todayBtn) todayBtn.addEventListener("click", goToToday);
        if (stampaBtn) stampaBtn.addEventListener("click", stampaFunction);
        // --- Initial Load ---
        updateCalendarTitle();
        renderCalendarPlaceholder();
        showDailyView(selectedDate);
        renderTimelinePlaceholder(selectedDate);
            });
            window.addEventListener('resize', () => {
   
});
function updateClock() {
            const now = new Date();
            let hours = String(now.getHours()).padStart(2, '0');
            let minutes = String(now.getMinutes()).padStart(2, '0');
            document.getElementById('clock').textContent = `${hours}:${minutes}`;
        }

        setInterval(updateClock, 60000);
        updateClock();
</script>
    <?php include 'includes/footer.php';?>
</body>
</html>