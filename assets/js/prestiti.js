document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var tbody     = document.getElementById('prestitiBody');
    var emptyMsg  = document.getElementById('prestitiEmpty');

    // ── Fetch open prestiti ──
    function fetchOpenPrestiti() {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'assets/utils/prestiti.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        renderPrestiti(response.prestiti);
                    } else {
                        console.error('Errore recupero prestiti:', response.message);
                    }
                } catch (e) {
                    console.error('Errore parsing risposta:', e);
                }
            }
        };
        xhr.send('action=list_open&_token=' + encodeURIComponent(csrfToken));
    }

    // ── Render table rows ──
    function renderPrestiti(items) {
        tbody.innerHTML = '';
        if (!items || items.length === 0) {
            emptyMsg.style.display = 'block';
            document.getElementById('prestitiTable').style.display = 'none';
            return;
        }
        emptyMsg.style.display = 'none';
        document.getElementById('prestitiTable').style.display = '';

        items.forEach(function (p) {
            var tr = document.createElement('tr');
            tr.id = 'prestito-row-' + p.id;

            // Check if overdue
            var oggi = new Date();
            oggi.setHours(0,0,0,0);
            var prevista = new Date(p.data_consegna_prevista);
            prevista.setHours(0,0,0,0);
            var overdue = prevista < oggi;

            var dataPrevistaCell = overdue
                ? '<span class="badge badge-overdue">' + p.data_consegna_prevista + ' ⚠️</span>'
                : '<span class="badge badge-ok">' + p.data_consegna_prevista + '</span>';

            tr.innerHTML =
                '<td>' + p.id + '</td>' +
                '<td>' + escapeHtml(p.beneficiario) + '</td>' +
                '<td>' + (p.classe ? escapeHtml(p.classe) : '<span class="text-muted">—</span>') + '</td>' +
                '<td>' + p.data_prestito + '</td>' +
                '<td>' + dataPrevistaCell + '</td>' +
                '<td>' + escapeHtml(p.descrizione_bene) + '</td>' +
                '<td>' +
                    '<button class="btn btn-sm btn-success" onclick="segnalaRiconsegna(' + p.id + ')" title="Segna come riconsegnato">' +
                        '<i class="fa-solid fa-check"></i>' +
                    '</button>' +
                '</td>';

            tbody.appendChild(tr);
        });
    }

    // ── Segna riconsegna ──
    window.segnalaRiconsegna = function (id) {
        if (!confirm('Confermi la riconsegna del prestito #' + id + '?')) return;

        var formData = 'action=return&_token=' + encodeURIComponent(csrfToken) + '&prestito_id=' + id;
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'assets/utils/prestiti.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        // Remove row from table
                        var row = document.getElementById('prestito-row-' + id);
                        if (row) row.remove();
                        // Check if table is now empty
                        if (tbody.children.length === 0) {
                            emptyMsg.style.display = 'block';
                            document.getElementById('prestitiTable').style.display = 'none';
                        }
                    } else {
                        alert('Errore: ' + response.message);
                    }
                } catch (e) {
                    alert('Errore di comunicazione con il server.');
                }
            }
        };
        xhr.send(formData);
    };

    // ── Utility: escape HTML ──
    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    // ── Initial load ──
    fetchOpenPrestiti();
});