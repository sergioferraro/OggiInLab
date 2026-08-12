# OggiInLab

Piattaforma di gestione per laboratori scolastici, sviluppata in PHP e MySQL.

## Funzionalità

### Gestione utenti
- Autenticazione e sessione sicura
- Modifica profilo e cambio password
- Gestione account amministrativi con ruolo Super Admin

### Gestione progetti
- Creazione, modifica e visualizzazione progetti
- Assegnazione tutor ed esperto
- Filtra progetti attivi e terminati

### Gestione docenti
- Elenco docenti attivi e disattivati
- Aggiunta, disattivazione ed eliminazione docenti

### Gestione aule
- Elenco aule con dotazioni (posti, computer, LIM, PC docente)
- Creazione e modifica aule

### Prenotazioni
- Visualizzazione e creazione prenotazioni con validazione conflitti
- Gestione prenotazioni annullate con eliminazione batch
- Stampa delle prenotazioni

### Calendario scolastico
- Calendario annuale con festività e giorni di chiusura
- Calcolo automatico di Pasqua e Pasquetta

### Orario settimanale
- Visualizzazione orario aule per giorno e fascia oraria

### Servizi
- Registrazione servizi assistenti con luogo e progetto di riferimento

### Bacheca
- Pubblicazione messaggi con immagini
- Visualizzazione pubblica

### Prestiti
- Registrazione prestiti attrezzature con tracciamento restituzione

### Stampa
- Report di prenotazioni, progetti, servizi e dati Gantt

### Sicurezza
- Validazione CSRF, password crittografate, logging azioni amministrative

## Requisiti

- PHP 8.0+
- MySQL o MariaDB
- Server web (Apache o Nginx)

## Installazione

1. Copiare `includes/config.example.php` in `includes/config.php`
2. Inserire le credenziali del database in `includes/config.php`
3. Importare lo schema di base: `mysql -u root -p [nomedatabase] < includes/schema.sql`
4. (Opzionale) Importare i dati personali da un backup esistente
5. Imporre permessi di scrittura sulla cartella `logs/`
6. Accedere con utente `admin` / password `admin` e modificare la password

## Licenza

MIT. Vedere il file LICENSE.

## Autore

Sergio Ferraro
