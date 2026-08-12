/**
 * dashboard.js – OggiInLab Dashboard
 *
 * OggiInLab
 * Copyright (c) 2026 Sergio Ferraro
 * Licensed under the MIT License
 */

(function () {
    'use strict';

    // console.log('[dashboard.js] ✅ IIFE avviata');

    // ------------------------------------------------------------------
    // Debug helper – set DEBUG to true to enable console output
    // ------------------------------------------------------------------
    var DEBUG = false;
    function log() { if (DEBUG) console.log.apply(console, arguments); }

    // ------------------------------------------------------------------
    // Configuration
    // ------------------------------------------------------------------
    const CONFIG = Object.freeze({
        startHour: 8,
        endHour: 19,
        rowHeight: 40,
        months: [
            'Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno',
            'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre',
        ],
        weekDays: ['Dom', 'Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab'],
    });

    // ------------------------------------------------------------------
    // Data from PHP bridge
    // ------------------------------------------------------------------
    log('[dashboard.js] 🔍 Leggo window.__DASHBOARD_DATA__...');
    const DATA = window.__DASHBOARD_DATA__;

    if (!DATA) {
        console.error('[dashboard.js] ❌ window.__DASHBOARD_DATA__ non trovato!');
        return;
    }

    const allAppointments = DATA.appointments;
    const holidays = DATA.holidays;
    const holidayDates = DATA.holidayDates;
    const csrfToken = DATA.csrfToken;

    log('[dashboard.js] 📦 Dati caricati:', {
        appointments: allAppointments.length,
        holidays: holidays.length,
        holidayDates: holidayDates.length,
        csrfToken: csrfToken ? 'presente' : 'assente',
    });

    // ------------------------------------------------------------------
    // State
    // ------------------------------------------------------------------
    let today = new Date();
    let currentMonth = today.getMonth();
    let currentYear = today.getFullYear();
    let selectedDate = new Date(today);

    // ------------------------------------------------------------------
    // Navigation helpers (livello IIFE, accessibili ovunque)
    // ------------------------------------------------------------------
    function goPrev() {
        currentMonth--;
        if (currentMonth < 0) { currentMonth = 11; currentYear--; }
        updateCalendarTitle();
        renderCalendar();
    }

    function goNext() {
        currentMonth++;
        if (currentMonth > 11) { currentMonth = 0; currentYear++; }
        updateCalendarTitle();
        renderCalendar();
    }

    // Flag per evitare listener multipli
    var keydownListenerRegistered = false;

    // ------------------------------------------------------------------
    // DOM helpers
    // ------------------------------------------------------------------
    function $(sel) { return document.querySelector(sel); }
    function $$(sel) { return document.querySelectorAll(sel); }

    // ------------------------------------------------------------------
    // Date helpers
    // ------------------------------------------------------------------
    function pad(n) { return String(n).padStart(2, '0'); }

    function formatDateLocal(date) {
        return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
    }

    // ------------------------------------------------------------------
    // News: unified fetch-and-render helper
    // ------------------------------------------------------------------
    function loadNews(url, containerSelector, formatter) {
        log('[dashboard.js] 📡 loadNews:', url);
        const xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.onload = function () {
            const el = $(containerSelector);
            if (!el) {
                log('[dashboard.js] ⚠️ Contenitore non trovato:', containerSelector);
                return;
            }

            if (this.status !== 200) {
                console.error('[dashboard.js] ❌ Errore HTTP:', this.status, this.statusText);
                el.textContent = 'Errore di rete: ' + this.statusText;
                return;
            }

            const response = JSON.parse(this.responseText);
            log('[dashboard.js] 📩 Risposta:', url, response.success ? 'OK' : 'nessun dato');
            if (response.error) {
                el.textContent = 'Errore: ' + response.error;
            } else if (response.success) {
                el.textContent = formatter(response);
            } else {
                el.textContent = response.message || 'Nessun evento';
            }
        };
        xhr.onerror = function () {
            const el = $(containerSelector);
            if (el) el.textContent = 'Impossibile connettersi al server.';
        };
        xhr.send();
    }

    function initNews() {
        log('[dashboard.js] 🗞️ initNews avviata');

        loadNews(
            'assets/utils/get_deleted_appointments.php',
            '.app_annullati',
            function (data) {
                return data.appointments.map(function (app) {
                    return [
                        app.corso,
                        new Date(app.data).toLocaleDateString('it-IT', {
                            day: 'numeric', month: 'long',
                        }),
                        app.oraInizio.substring(0, 5),
                        app.oraFine.substring(0, 5),
                        app.descrizione,
                        'ANNULLATO',
                    ].join('\t') + '\n';
                }).join('');
            }
        );

        loadNews(
            'assets/utils/get_modified_appointments.php',
            '.app_modificati',
            function (data) {
                return (data.authors || []).map(function (app) {
                    return [
                        '*' + app.autore,
                        'ha modificato ' + app.titolo,
                        ', descrizione: ' + app.descrizione,
                        ', nuova data: ' +
                            new Date(app.appData).toLocaleDateString('it-IT', {
                                day: 'numeric', month: 'long',
                            }),
                        app.oraInizio.substring(0, 5) + '-' + app.oraFine.substring(0, 5),
                    ].join('') + '\n';
                }).join('');
            }
        );

        loadNews(
            'assets/utils/get_today_created.php',
            '.app_creati',
            function (data) {
                return (data.authors || []).map(function (app) {
                    return [
                        '*' + app.autore,
                        ' ha creato ' + app.titolo,
                        ', descrizione: ' + app.descrizione,
                        ', nuova data: ' +
                            new Date(app.appData).toLocaleDateString('it-IT', {
                                day: 'numeric', month: 'long',
                            }),
                        app.oraInizio.substring(0, 5) + '-' + app.oraFine.substring(0, 5),
                    ].join('') + '\n';
                }).join('');
            }
        );

        log('[dashboard.js] 🗞️ initNews completata');
    }

    // ------------------------------------------------------------------
    // Calendar title
    // ------------------------------------------------------------------
    function updateCalendarTitle() {
        const el = $('#calendar-title');
        if (el) {
            el.innerHTML = CONFIG.months[currentMonth] + ' ' + currentYear;
            log('[dashboard.js] 📅 Titolo calendario:', el.innerHTML);
        } else {
            log('[dashboard.js] ⚠️ #calendar-title non trovato');
        }
    }

    // ------------------------------------------------------------------
    // Calendar rendering
    // ------------------------------------------------------------------
    function isHoliday(dateStr) { return holidayDates.includes(dateStr); }

    function getHolidayName(dateStr) {
        var h = holidays.find(function (x) { return x.date === dateStr; });
        return h ? h.name : 'Festivo';
    }

    function renderCalendar() {
        log('[dashboard.js] 📅 renderCalendar avviata');
        var placeholder = $('#calendar-days-placeholder');
        if (!placeholder) {
            log('[dashboard.js] ⚠️ #calendar-days-placeholder non trovato');
            return;
        }

        placeholder.innerHTML = '';

        var firstDay = new Date(currentYear, currentMonth, 1);
        var lastDay = new Date(currentYear, currentMonth + 1, 0);
        var firstDayIndex = firstDay.getDay();
        var lastDate = lastDay.getDate();

        // Weekday headers
        var headerRow = document.createElement('div');
        headerRow.style.display = 'flex';
        headerRow.style.fontWeight = 'bold';
        headerRow.style.marginBottom = '5px';
        headerRow.style.borderBottom = '1px solid var(--bs-primary)';

        CONFIG.weekDays.forEach(function (day) {
            var d = document.createElement('div');
            d.style.width = '14.28%';
            d.style.textAlign = 'center';
            d.textContent = day;
            headerRow.appendChild(d);
        });
        placeholder.appendChild(headerRow);

        // Grid
        var grid = document.createElement('div');
        grid.style.display = 'flex';
        grid.style.flexWrap = 'wrap';

        // Empty cells before the 1st
        for (var i = 0; i < firstDayIndex; i++) {
            var empty = document.createElement('div');
            empty.style.width = '14.28%';
            empty.style.height = '50px';
            grid.appendChild(empty);
        }

        var todayOnly = new Date(today.getFullYear(), today.getMonth(), today.getDate());

        // Day cells
        for (var day = 1; day <= lastDate; day++) {
            var cell = document.createElement('div');
            cell.style.width = '14.28%';
            cell.style.height = '50px';
            cell.style.textAlign = 'center';
            cell.style.paddingTop = '5px';
            cell.style.cursor = 'pointer';
            cell.classList.add('calendar-day');
            cell.style.borderRadius = '10px';
            cell.style.boxShadow =
                '2px -2px 4px rgba(255,255,255,0.5), '
                + '-2px 2px 4px rgba(0,0,0,0.3)';

            cell.textContent = day;
            cell.dataset.day = day;

            var cellDate = new Date(currentYear, currentMonth, day);
            var dateStr = formatDateLocal(cellDate);
            var hasEvent = allAppointments.some(function (e) {
                return e.date === dateStr;
            });

            cell.addEventListener('click', (function (c, cd, ds, he) {
                return function () {
                    log('[dashboard.js] 🖱️ Giorno cliccato:', day);
                    selectedDate = cd;
                    renderTimeline(ds);
                    showDailyView(cd);
                    resetCalendarColors();
                    c.style.backgroundColor = 'blue';
                };
            })(cell, cellDate, dateStr, hasEvent));

            // Store metadata for color reset
            cell._isHoliday = isHoliday(dateStr);
            cell._isSunday = cellDate.getDay() === 0;
            cell._hasEvent = hasEvent;
            cell._isToday = cellDate.getTime() === todayOnly.getTime();
            cell._dateStr = dateStr;

            // --- Hover effect: rosso al passaggio del mouse ---
            cell.addEventListener('mouseenter', function () {
                this.style.border = '2px solid red';
            });
            cell.addEventListener('mouseleave', function () {
                this.style.border = '';
            });

            grid.appendChild(cell);
        }

        placeholder.appendChild(grid);
        resetCalendarColors();
        log('[dashboard.js] 📅 renderCalendar completata (', lastDate, 'giorni)');
    }

    function resetCalendarColors() {
        $$('.calendar-day').forEach(function (cell) {
            if (cell._isToday) {
                cell.style.fontWeight = 'bold';
                cell.style.backgroundColor = 'green';
            } else if (cell._isHoliday) {
                cell.style.backgroundColor = '#FF6B6B';
                cell.title = getHolidayName(cell._dateStr);
            } else if (cell._isSunday) {
                cell.style.backgroundColor = '#FF6B6B';
                cell.title = 'Domenica';
            } else if (cell._hasEvent) {
                cell.style.backgroundColor = 'rgba(179, 138, 221, 0.3)';
                cell.title = 'Eventi presenti';
            } else {
                cell.style.backgroundColor = 'var(--bs-bg-dark)';
            }
        });
    }

    // ------------------------------------------------------------------
    // Timeline rendering
    // ------------------------------------------------------------------
    function renderTimeline(dateStr) {
        log('[dashboard.js] 📋 renderTimeline per:', dateStr);
        var grid = $('.timeline-container .event-grid');
        if (!grid) {
            log('[dashboard.js] ⚠️ .timeline-container .event-grid non trovato');
            return;
        }

        var events = allAppointments.filter(function (e) { return e.date === dateStr; });
        var dayObj = new Date(dateStr);

        var html = 'Eventi per ' + dayObj.toLocaleDateString('it-IT') + ':<br>';

        if (holidayDates.includes(dateStr)) {
            html += getHolidayName(dateStr) + '<br>';
        } else if (dayObj.getDay() === 0) {
            html += '<i>Domenica.<br></i>';
        } else if (events.length === 0) {
            html += '<i>Nessun evento programmato.<br></i>';
            html += '<a href="prenota-day.php?openModal=true&date=' +
                    dateStr + '" class="btn btn-primary">Aggiungi</a>';
        } else {
            events.sort(function (a, b) {
                return a.startTime.localeCompare(b.startTime);
            });
            events.forEach(function (ev) {
                html += '<div style="border-left:5px solid ' + (ev.color || 'var(--bs-primary)') +
                    ';margin:5px 0;padding:3px 5px;font-size:0.9em;">' +
                    '<b>' + ev.title + '</b> (' +
                    ev.startTime + ' - ' + ev.endTime + ') @ ' +
                    ev.place + ' - ' + ev.descrizione + '</div>';
            });
            html += '<a href="prenota-day.php?openModal=true&date=' +
                    dateStr + '" class="btn btn-primary">Aggiungi</a>';
        }

        grid.innerHTML = html;
        log('[dashboard.js] 📋 renderTimeline completata (', events.length, 'eventi)');
    }

    // ------------------------------------------------------------------
    // Daily view (timeline with location rows)
    // ------------------------------------------------------------------
    function showDailyView(dateToShow) {
        log('[dashboard.js] 🕐 showDailyView per:', dateToShow);
        var container = $('.daily-timeline');
        if (!container) {
            log('[dashboard.js] ⚠️ .daily-timeline non trovato');
            return;
        }

        container.innerHTML = '';

        var dateStr = formatDateLocal(dateToShow);
        var events = allAppointments.filter(function (e) {
            return e.date === dateStr;
        });
        var locations = [...new Set(events.map(function (e) { return e.place; }))];

        log('[dashboard.js] 🕐 Eventi:', events.length, '| Luoghi:', locations.length);

        var wrapper = document.createElement('div');
        wrapper.className = 'timeline-container d-flex flex-row';

        // Left section – location labels
        var left = document.createElement('div');
        left.className = 'left-section col-md-4 p-2 border-end';
        locations.forEach(function (loc) {
            var d = document.createElement('div');
            d.className = 'location text-truncate';
            d.style.height = CONFIG.rowHeight + 'px';
            d.style.lineHeight = CONFIG.rowHeight + 'px';
            d.textContent = loc;
            left.appendChild(d);
        });

        // Right section
        var right = document.createElement('div');
        right.className = 'right-section col-md-8 d-flex flex-column p-2';

        // Appointments container
        var aptContainer = document.createElement('div');
        aptContainer.className =
            'appointments-container position-relative flex-grow-1 overflow-y-auto';

        // Hours scale
        var scale = document.createElement('div');
        scale.className = 'hours-scale d-flex align-items-end justify-content-between p-2 bg-light';

        var totalHours = CONFIG.endHour - CONFIG.startHour;
        var hourWidth = aptContainer.clientWidth / totalHours;

        for (var h = 0; h <= totalHours; h++) {
            var mark = document.createElement('div');
            mark.className = 'hour-mark text-center';
            mark.style.width = hourWidth + 'px';
            mark.style.flex = '0 0 auto';
            mark.textContent = CONFIG.startHour + h;
            scale.appendChild(mark);
        }

        right.appendChild(aptContainer);
        right.appendChild(scale);

        // Render event bars after layout is ready
        requestAnimationFrame(function () {
            var w = aptContainer.clientWidth;
            log('[dashboard.js] 🕐 Container width:', w);
            if (w === 0) return;

            aptContainer.style.minHeight =
                locations.length * CONFIG.rowHeight + 'px';

            var totalMinutes = totalHours * 60;
            var pxPerMin = w / totalMinutes;

            events.forEach(function (ev) {
                var rowIdx = locations.indexOf(ev.place);
                var top = rowIdx * CONFIG.rowHeight;

                var startParts = ev.startTime.split(':').map(Number);
                var endParts = ev.endTime.split(':').map(Number);

                var startMin = startParts[0] * 60 + startParts[1] -
                    CONFIG.startHour * 60;
                var endMin = endParts[0] * 60 + endParts[1] -
                    CONFIG.startHour * 60;
                var duration = endMin - startMin;

                var bar = document.createElement('div');
                bar.className = 'appointment position-absolute text-white p-1 rounded';
                bar.style.top = top + 'px';
                bar.style.left = (startMin * pxPerMin) + 'px';
                bar.style.width = (duration * pxPerMin) + 'px';
                bar.style.borderRadius = '10px';
                bar.style.backgroundColor = ev.color;
                bar.style.boxShadow =
                    '2px -2px 4px rgba(255,255,255,0.5), ' +
                    '-2px 2px 4px rgba(0,0,0,0.3)';

                bar.dataset.appointmentId = ev.id;
                bar.dataset.id = ev.idCorso;
                bar.dataset.projectTitle = ev.title;
                bar.dataset.date = ev.date;
                bar.dataset.startTime = ev.startTime;
                bar.dataset.endTime = ev.endTime;
                bar.dataset.place = ev.place;
                bar.dataset.description = ev.descrizione || '';
                bar.dataset.resourceId = ev.resourceId;

                bar.textContent =
                    (ev.title !== 'prenotazione' && ev.title !== 'orario')
                        ? ev.title
                        : ev.descrizione;

                aptContainer.appendChild(bar);
            });
            log('[dashboard.js] 🕐 showDailyView completata');
        });

        wrapper.appendChild(left);
        wrapper.appendChild(right);
        container.appendChild(wrapper);
    }

    // ------------------------------------------------------------------
    // Modal logic
    // ------------------------------------------------------------------
    function initModal() {
        log('[dashboard.js] 🪟 initModal avviata');
        var modalEl = $('#appointmentDetailsEditModal');
        if (!modalEl) {
            log('[dashboard.js] ⚠️ #appointmentDetailsEditModal non trovato');
            return;
        }

        var modal = new bootstrap.Modal(modalEl);

        // ------------------------------------------------------------------
        // Confirmation modal (sostituisce confirm() nativo)
        // ------------------------------------------------------------------
        var confirmModalHtml =
            '<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">' +
            '  <div class="modal-dialog modal-dialog-centered">' +
            '    <div class="modal-content">' +
            '      <div class="modal-header">' +
            '        <h5 class="modal-title"><i class="fa-solid fa-triangle-exclamation me-2"></i>Conferma Annullamento</h5>' +
            '        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>' +
            '      </div>' +
            '      <div class="modal-body">' +
            '        <p>Sei sicuro di voler annullare questo appuntamento?</p>' +
            '      </div>' +
            '      <div class="modal-footer">' +
            '        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>' +
            '        <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Sì, annulla</button>' +
            '      </div>' +
            '    </div>' +
            '  </div>' +
            '</div>';
        document.body.insertAdjacentHTML('beforeend', confirmModalHtml);
        var confirmDeleteInstance = new bootstrap.Modal($('#confirmDeleteModal'));

        // ------------------------------------------------------------------
        // Success toast (sostituisce alert() nativo)
        // ------------------------------------------------------------------
        var toastHtml =
            '<div class="toast-container position-fixed top-0 end-0 p-3">' +
            '  <div id="successToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">' +
            '    <div class="toast-header">' +
            '      <i class="fa-solid fa-circle-check text-success me-2"></i>' +
            '      <strong class="me-auto">OggiInLab</strong>' +
            '      <button type="button" class="btn-close" data-bs-dismiss="toast"></button>' +
            '    </div>' +
            '    <div class="toast-body">' +
            '      <span id="toastMessage"></span>' +
            '    </div>' +
            '  </div>' +
            '</div>';
        document.body.insertAdjacentHTML('beforeend', toastHtml);

        function showToast(message) {
            $('#toastMessage').textContent = message;
            var toastEl = $('#successToast');
            var toast = new bootstrap.Toast(toastEl, { delay: 3000 });
            toast.show();
        }

        // ------------------------------------------------------------------
        // Variables for delete confirmation flow
        // ------------------------------------------------------------------
        var pendingAptId = null;
        var pendingProjId = null;

        // --- Wire confirm button inside the confirmation modal ---
        var confirmDeleteBtnRef = $('#confirmDeleteBtn');
        if (confirmDeleteBtnRef) {
            confirmDeleteBtnRef.addEventListener('click', function () {
                if (!pendingAptId || !pendingProjId) {
                    alert('ID non validi per l\'eliminazione.');
                    return;
                }

                log('[dashboard.js] 🗑️ Eliminazione appuntamento:', pendingAptId);
                fetch('assets/utils/invalida-appointment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'idCorso=' + encodeURIComponent(pendingProjId) +
                        '&idAppuntamento=' + encodeURIComponent(pendingAptId) +
                        '&_token=' + csrfToken,
                })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.success) {
                        confirmDeleteInstance.hide();
                        modal.hide();
                        showToast('Appuntamento annullato con successo');
                        setTimeout(function () { location.reload(); }, 1500);
                    } else {
                        confirmDeleteInstance.hide();
                        alert('Errore nell\'annullamento: ' + data.message);
                    }
                })
                .catch(function () {
                    confirmDeleteInstance.hide();
                    alert('Si è verificato un errore. Controlla i log.');
                });

                // Reset pending IDs
                pendingAptId = null;
                pendingProjId = null;
            });
        }

        var deleteBtn = $('#deleteAppointmentBtnFooter');
        var saveBtn = $('#saveAppointmentBtn');
        var switchBtn = $('#switch-to-edit-btn');

        var form = $('#editAppointmentForm');
        var projId = $('#editAppointmentProjectId');
        var aptId = $('#editAppointmentId');
        var aptData = $('#editAppointmentData');
        var aptOraInizio = $('#editAppointmentOraInizio');
        var aptOraFine = $('#editAppointmentOraFine');
        var aptLuogo = $('#editAppointmentLuogo');
        var aptDescrizione = $('#editAppointmentDescrizione');

        var dData = $('#detail-data');
        var dOraInizio = $('#detail-oraInizio');
        var dOraFine = $('#detail-oraFine');
        var dLuogo = $('#detail-luogo');
        var dDescrizione = $('#detail-descrizione');

        // --- Footer button visibility ---
        function updateFooter(tabId) {
            if (tabId === 'details') {
                if (deleteBtn) deleteBtn.style.display = 'inline-block';
                if (saveBtn) saveBtn.style.display = 'none';
            } else {
                if (deleteBtn) deleteBtn.style.display = 'none';
                if (saveBtn) saveBtn.style.display = 'inline-block';
            }
        }

        // --- Tab switch ---
        if (switchBtn) {
            switchBtn.addEventListener('click', function () {
                log('[dashboard.js] 🪟 Switch to edit tab');
                var editTab = $('#edit-tab');
                var tab = bootstrap.Tab.getOrCreateInstance(editTab);
                tab.show();
                updateFooter('edit');
            });
        }

        // --- Tab change listener ---
        modalEl.addEventListener('shown.bs.tab', function (event) {
            var activeId = event.target.id;
            if (activeId === 'details-tab') {
                updateFooter('details');
                $('#appointmentDetailsEditModalLabel').textContent =
                    'Dettagli Appuntamento';
            } else if (activeId === 'edit-tab') {
                updateFooter('edit');
                $('#appointmentDetailsEditModalLabel').textContent =
                    'Modifica Appuntamento';
            }
        });

        // --- Click on appointment bars ---
        document.body.addEventListener('click', function (event) {
            if (!event.target.classList.contains('appointment')) return;

            log('[dashboard.js] 🖱️ Appointment cliccato:', event.target.dataset.appointmentId);
            var appt = event.target;

            // Populate details
            dData.textContent = appt.dataset.date;
            dOraInizio.textContent = appt.dataset.startTime;
            dOraFine.textContent = appt.dataset.endTime;
            dLuogo.textContent = appt.dataset.place;
            dDescrizione.textContent = appt.dataset.description;

            // Show/hide "Modifica Progetto" button
            var manageBtn = $('#manageProjectBtn');
            if (manageBtn) {
                var projectTitle = appt.dataset.projectTitle || '';
                if (projectTitle !== 'orario' && projectTitle !== 'prenotazione' && projectTitle !=='Manutenzione') {
                    manageBtn.href = 'manage-project.php?id=' + encodeURIComponent(appt.dataset.id);
                    manageBtn.style.display = 'inline-block';
                } else {
                    manageBtn.style.display = 'none';
                }
            }

            // Populate form
            projId.value = appt.dataset.id;
            aptId.value = appt.dataset.appointmentId;
            aptData.value = appt.dataset.date;
            aptOraInizio.value = appt.dataset.startTime;
            aptOraFine.value = appt.dataset.endTime;
            if (aptLuogo && appt.dataset.resourceId) {
                aptLuogo.value = appt.dataset.resourceId;
            }
            aptDescrizione.value = appt.dataset.description;

            modal.show();

            // Activate details tab
            var detailsTab = $('#details-tab');
            bootstrap.Tab.getOrCreateInstance(detailsTab).show();
            updateFooter('details');
        });

        // --- Save appointment ---
        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                log('[dashboard.js] 💾 Salvataggio appuntamento:', aptId.value);

                fetch('assets/utils/edit-appointment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        idAppuntamento: aptId.value,
                        idCorso: projId.value,
                        data: aptData.value,
                        oraInizio: aptOraInizio.value,
                        oraFine: aptOraFine.value,
                        luogo: aptLuogo.value,
                        descrizione: aptDescrizione.value,
                        _token: csrfToken,
                    }),
                })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.success) {
                        alert('Appuntamento aggiornato con successo');
                        modal.hide();
                        location.reload();
                    } else {
                        alert('Errore nell\'aggiornamento: ' + data.message);
                    }
                })
                .catch(function () {
                    alert('Si è verificato un errore. Controlla i log.');
                });
            });
        }

        // --- Delete appointment ---
        if (deleteBtn) {
            deleteBtn.addEventListener('click', function () {
                if (!aptId.value || !projId.value) {
                    alert('ID non validi per l\'eliminazione.');
                    return;
                }

                // Salva gli ID per il flusso di conferma asincrono
                pendingAptId = aptId.value;
                pendingProjId = projId.value;

                // Apri il modal di conferma Bootstrap invece di confirm()
                confirmDeleteInstance.show();
            });
        }

        log('[dashboard.js] 🪟 initModal completata');
    }

    // ------------------------------------------------------------------
    // Navigation
    // ------------------------------------------------------------------
    function initNavigation() {
        log('[dashboard.js] 🧭 initNavigation avviata');
        var prevBtn = $('#prev');
        var nextBtn = $('#next');
        var todayBtn = $('#today');
        var stampaBtn = $('#stampa');

        if (prevBtn) {
            prevBtn.addEventListener('click', goPrev);
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', goNext);
        }

        // Tastiera: frecce sinistra/destra per navigazione mesi (registrato una sola volta)
        if (!keydownListenerRegistered) {
            keydownListenerRegistered = true;
            document.addEventListener('keydown', function (e) {
                // Ignora se l'utente sta digitando in un input/textarea/select
                var tag = (e.target && e.target.tagName) ? e.target.tagName.toLowerCase() : '';
                if (tag === 'input' || tag === 'textarea' || tag === 'select') return;

                if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    goPrev();
                } else if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    goNext();
                }
            });
            log('[dashboard.js] ⌨️ Listener tastiera registrato');
        }

        if (todayBtn) {
            todayBtn.addEventListener('click', function () {
                var now = new Date();
                currentMonth = now.getMonth();
                currentYear = now.getFullYear();
                selectedDate = now;
                updateCalendarTitle();
                renderCalendar();
                showDailyView(selectedDate);
                renderTimeline(formatDateLocal(selectedDate));
            });
        }

        if (stampaBtn) {
            stampaBtn.addEventListener('click', function () {
                var d = selectedDate;
                var dateStr = d.getFullYear() + '-' +
                    pad(d.getMonth() + 1) + '-' + pad(d.getDate());
                window.open('assets/utils/print_today.php?data=' + dateStr, '_blank');
            });
        }

        log('[dashboard.js] 🧭 initNavigation completata');
    }

    // ------------------------------------------------------------------
    // Resize handler
    // ------------------------------------------------------------------
    var resizeTimer;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
            log('[dashboard.js] 📐 Resize handler');
            showDailyView(selectedDate);
        }, 250);
    });

    // ------------------------------------------------------------------
    // Bootstrap
    // ------------------------------------------------------------------
    log('[dashboard.js] ⏳ Attendo DOMContentLoaded...');

    function bootDashboard() {
        log('[dashboard.js] 🚀 Avvio dashboard...');
        initNews();
        initNavigation();
        initModal();
        updateCalendarTitle();
        renderCalendar();
        showDailyView(selectedDate);
        renderTimeline(formatDateLocal(selectedDate));
        log('[dashboard.js] ✅ Dashboard inizializzata con successo!');
    }

    // Se il DOM è già pronto (script caricato in fondo al body), avvia subito
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootDashboard);
    } else {
        log('[dashboard.js] ℹ️ DOM già pronto, avvio immediato');
        bootDashboard();
    }
})();
