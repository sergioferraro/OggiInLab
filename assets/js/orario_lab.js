// Refresh Page on Room Change
$('#idAula').on('change', function() {
    const id = $(this).val();
    const txt = this.options[this.selectedIndex].text;
    const caption = document.getElementById('captionAula');
    if (caption) {
        caption.innerHTML = 'Aula: <strong>' + txt + '</strong>';
    }
    window.location.href = 'orario_lab.php' + (id ? '?idAula=' + id : '');
});

function fillOrarioSett() {
    fetch('assets/utils/fillOrarioSett2.php')
    .then(response => {
            if (!response.ok) {
                throw new Error('Risposta del server non valida');
            }
            return response.json();
        })
        .then(data => {
            // Show Message (if Present)
            if (data.message) {
                alert(data.message);
                document.getElementById('message').innerText = data.message;
            }

            // Handle
            if (data.status === 'success') {
                console.log('Dati inseriti:', data.inserted);
            } else {
                console.error('Errore:', data.message || 'Stato non riconosciuto');
            }
        })
        .catch(error => {
            console.error('Errore durante la chiamata AJAX:', error);
            alert('Si è verificato un errore. Riprova.');
        });
}