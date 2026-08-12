document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    // ── Toggle new-fields visibility ──
    function setupToggle(checkboxId, fieldsId) {
        var cb = document.getElementById(checkboxId);
        var fields = document.getElementById(fieldsId);
        if (!cb || !fields) return;

        function update() {
            fields.style.display = cb.checked ? 'block' : 'none';
        }
        cb.addEventListener('change', update);
        update();
    }

    setupToggle('newTutorToggle', 'newTutorFields');
    setupToggle('newEspertoToggle', 'newEspertoFields');

    // ── AJAX: add docente ──
    function addDocente(type) {
        var nomeInput    = document.querySelector('[name="' + type + '_nome"]');
        var cognomeInput = document.querySelector('[name="' + type + '_cognome"]');

        if (!nomeInput || !cognomeInput) return;

        var nome    = nomeInput.value.trim();
        var cognome = cognomeInput.value.trim();

        if (!nome || !cognome) {
            alert('Nome e cognome sono obbligatori.');
            return;
        }

        var formData = new FormData();
        formData.append('nome', nome);
        formData.append('cognome', cognome);

        fetch('assets/utils/add_docente.php', {
            method: 'POST',
            body: formData
        })
        .then(function (response) { return response.json(); })
        .then(function (data) {
            if (data.success) {
                var selectId = type === 'tutor' ? 'tutorSelect' : 'espertoSelect';
                var select = document.getElementById(selectId);

                if (data.duplicate) {
                    // Docente già esistente: cerca e seleziona l'opzione nel dropdown
                    var found = false;
                    for (var i = 0; i < select.options.length; i++) {
                        if (select.options[i].value === String(data.docente.idDocente)) {
                            select.selectedIndex = i;
                            found = true;
                            break;
                        }
                    }
                    if (!found) {
                        var option = new Option(data.docente.cognome, data.docente.idDocente, true, true);
                        select.add(option);
                    }
                    alert('Docente già presente in elenco: ' + data.docente.nome + ' ' + data.docente.cognome);
                } else {
                    var option = new Option(data.docente.cognome, data.docente.idDocente, true, true);
                    select.add(option);
                    select.value = data.docente.idDocente;
                }

                // Pulisci i campi
                nomeInput.value    = '';
                cognomeInput.value = '';
            } else {
                alert('Errore: ' + data.message);
            }
        })
        .catch(function () {
            alert("Errore durante l'invio dei dati.");
        });
    }

    // ── Bind AJAX buttons ──
    var addTutorBtn = document.getElementById('addTutorBtn');
    if (addTutorBtn) {
        addTutorBtn.addEventListener('click', function () { addDocente('tutor'); });
    }

    var addEspertoBtn = document.getElementById('addEspertoBtn');
    if (addEspertoBtn) {
        addEspertoBtn.addEventListener('click', function () { addDocente('esperto'); });
    }
});