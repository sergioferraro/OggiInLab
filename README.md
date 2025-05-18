# OggiInLab
OggiInLab è un'applicazione web sviluppata in PHP per la gestione di appuntamenti e orari in un laboratorio o ambiente simile (ad esempio, per corsi, scuola secondaria). Il dashboard permette di visualizzare gli eventi su un calendario mensile, modificare o annullarli, e monitorare le notizie relative a modifiche o cancellazioni.

![Anteprima del progetto](/assets/Screenshot.png)

📌 Descrizione
OggiInLab fornisce una dashboard per amministratori o utenti autorizzati, con funzionalità di gestione degli appuntamenti (creazione, modifica, annullamento), visualizzazione su un calendario interattivo e notifiche in tempo reale. Il sistema è stato realizzato utilizzando tecnologie moderne come PHP, MySQL, Bootstrap e JavaScript.

🧱 Tecnologie Utilizzate
Linguaggi: PHP 7+, JavaScript, HTML5, CSS3
Database: MySQL (con PDO per l'accesso)
Framework/Strumenti:
Bootstrap 5 (tema "Cyborg" per il design scuro)
jQuery per la gestione degli eventi DOM
Font Awesome per icone
PHPMailer o funzionalità di logging interno (per errori e notifiche)

🚀 Funzionalità Principali
Calendario Mensile

Visualizzazione giornaliera ed oraria degli appuntamenti.
Colorazione dinamica basata sul progetto associato.
Highlight delle date festive o domeniche.
Gestione Appuntamenti

Creazione, modifica e cancellazione di eventi tramite un'interfaccia modale.
Gestione di luoghi (aule) e progetti associati agli appuntamenti.
Notizie in Tempo Reale

Visualizzazione di:
Appuntamenti annullati, Modifiche recenti, Nuovi appuntamenti creati, Autenticazione

Sessione utente gestita con $_SESSION.
Protezione da accesso non autorizzato (redirect a index.php).
Stampa e Export
Funzione di stampa per il calendario del giorno selezionato.

📦 Installazione
Requisiti
PHP 7+
MySQL/MariaDB
Server web (es: Apache, Nginx)
Composer (opzionale per dipendenze aggiuntive)

Passaggi
Clona il repository:
git clone https://github.com/sergioferraro/oggiinlab.git
Configura il file includes/config.php con le credenziali del database.
Configura il file privacy.php
Importa lo schema SQL : includes/schema.sql;
aggiungi almeno un amministratore con php password_hash

🗄 Struttura del Database
Il progetto utilizza un database MySQL con le seguenti tabelle:
1. admin
id: ID dell'utente amministratore (PK).
nomeCompleto: Nome completo dell'amministratore.
adminEmail: Email dell'amministratore (UNIQUE).
userName: Username (UNIQUE).
password: Password crittografata (password_hash).
updationDate: Data ultima modifica (timestamp).
is_super_admin: Flag per utente super admin (0/1).
2. appuntamento
idCorso: ID del progetto associato (FK a progetto.idProgetto).
data: Data dell'appuntamento (YYYY-MM-DD).
oraInizio, oraFine: Orari di inizio e fine (HH:MM).
luogo: ID dell'aula associata (FK a aula.idAula).
isDeleted: Flag per cancellazione (0 = attivo, 1 = cancellato).
lastModified: Ultima modifica (timestamp).
idAppuntamento: ID univoco dell'appuntamento (PK).
descrizione: Descrizione dell'evento.
autore: ID dell'autore (FK a admin.id).
3. aula
idAula: ID univoco dell'aula (PK).
nAula: Nome o numero dell'aula (UNIQUE).
nPosti: Numero di posti disponibili.
computer, richiedeAt, lim, pcDocente: Flag per caratteristiche specifiche.
4. calendario
idCalendario: ID univoco (PK).
annoScolastico: Anno scolastico associato.
giorno: Data della festività o chiusura (YYYY-MM-DD, UNIQUE).
nomeChiusura: Nome della festività o motivo del blocco.
5. comments
id: ID del commento (PK).
post_id: ID del post associato (FK a posts.id).
user_id: ID dell'utente che ha lasciato il commento (FK a admin.id).
content: Testo del commento.
created_at: Data di creazione (timestamp).
6. docente
idDocente: ID univoco del docente (PK).
cognome, nome: Nome e cognome del docente.
isDeleted: Flag per cancellazione (0 = attivo, 1 = cancellato).
7. likes
id: ID del like (PK).
post_id: ID del post associato (FK a posts.id).
user_id: ID dell'utente che ha messo like (FK a admin.id).
created_at: Data di creazione (timestamp).
8. orario_settimana
idOrarioSettimana: ID univoco dell'orario (PK).
idProgetto: ID del progetto associato (FK a progetto.idProgetto).
idAula: ID dell'aula associata (FK a aula.idAula).
idDocente: ID del docente associato (FK a docente.idDocente).
classe: Nome della classe.
giorno: Giorno della settimana (Lunedì, Martedì, etc.).
ora_inizio, ora_fine: Orari di inizio e fine (HH:MM).
autore: ID dell'autore (FK a admin.id).
9. posts
id: ID del post (PK).
user_id: ID dell'utente che ha creato il post (FK a admin.id).
content: Testo del post.
image_url: URL immagine associata (opzionale).
created_at: Data di creazione (timestamp).
10. progetto
idProgetto: ID univoco del progetto (PK).
nomeProgetto: Nome del progetto.
idTutor, idEsperto: ID del tutor/esperto (FK a docente.idDocente).
descProgetto: Descrizione del progetto.
cnp, cup: Codici specifici (opzionali).
startDate, endDate: Date di inizio e fine (YYYY-MM-DD).
11. servizi
idServizio: ID univoco dei servizi (PK).
idAssistente: ID dell'assistente (FK a admin.id).
serviziData: Data del servizio (YYYY-MM-DD).
serviziOraInizio, serviziOraFine: Orari di inizio e fine (HH:MM).
serviziDescrizione: Descrizione del servizio.
serviziLuogo: ID dell'aula associata (FK a aula.idAula).
serviziProj: ID del progetto associato (FK a progetto.idProgetto).

🔗 Relazioni tra Tabelle
appuntamento.idCorso → progetto.idProgetto
appuntamento.luogo → aula.idAula
orario_settimana.idProgetto → progetto.idProgetto
orario_settimana.idAula → aula.idAula
orario_settimana.idDocente → docente.idDocente
posts.user_id → admin.id
comments.post_id → posts.id
comments.user_id → admin.id
likes.post_id → posts.id
likes.user_id → admin.id

📝 Uso
Login
Accedi a index.php con le credenziali dell'utente autorizzato.

Dashboard: 
Naviga tra i mesi usando i pulsanti "Prec" o "Succ".
Clicca su un appuntamento per visualizzarne i dettagli.
Usa il pulsante "Oggi" per tornare al calendario attuale.
Gestione Appuntamenti

Modifica o cancella appuntamenti tramite la finestra modale.
Crea nuovi appuntamenti cliccando su un giorno vuoto nel calendario.

Notizie
Visualizza gli aggiornamenti in tempo reale nella sezione "News".

🛠 Contribuzione
Se desideri contribuire al progetto:

Fork il repository.
Crea un branch per le modifiche (es: feature/new-feature).
Invia un pull request con un chiaro descrizione delle modifiche.
Linee guida:

Rispetta lo stile di codice esistente.
Aggiungi commenti e documentazione quando necessario.
Testa le funzionalità prima di inviare il PR.

📄 Licenza
Questo progetto è rilasciato sotto la licenza MIT License.
Per maggiori dettagli, consulta il file LICENSE.
