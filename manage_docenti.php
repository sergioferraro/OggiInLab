<?php
/**
 * manage_docenti.php – OggiInLab: Gestione Docenti
 *
 * OggiInLab
 * Copyright (c) 2026 Sergio Ferraro
 * Licensed under the MIT License
 */
declare(strict_types=1);
require_once __DIR__ . '/includes/session.php';
error_reporting(E_ALL);
ini_set('display_errors', defined('APP_DEBUG') && APP_DEBUG ? '1' : '0');

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/Logger.php';

// -------------------------------------------------------------------
// Auth guard
// -------------------------------------------------------------------
if (empty($_SESSION['alogin'])) {
    header('Location: index.php');
    exit;
}

// -------------------------------------------------------------------
// CSRF token
// -------------------------------------------------------------------
$_SESSION['csrf_token'] = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));

// -------------------------------------------------------------------
// Fetch docenti counts (active + total)
// -------------------------------------------------------------------
try {
    $stmt = $dbh->query('SELECT COUNT(*) FROM docente WHERE isDeleted <> 1');
    $activeCount = (int) $stmt->fetchColumn();
    $stmt = $dbh->query('SELECT COUNT(*) FROM docente WHERE isDeleted = 1');
    $deletedCount = (int) $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log('Count docenti: ' . $e->getMessage());
    $activeCount = 0;
    $deletedCount = 0;
}

// -------------------------------------------------------------------
// Fetch docenti list (active + deactivated)
// -------------------------------------------------------------------
try {
    $stmt = $dbh->query(
        'SELECT idDocente, nome, cognome, isDeleted '
        . 'FROM docente ORDER BY isDeleted ASC, cognome, nome'
    );
    $docenti = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Fetch docenti: ' . $e->getMessage());
    $docenti = [];
}

$pageTitle  = 'OggiInLab | Gestione Docenti';
$pageCsrf   = true;
$pageStyles = '
.row-deactivated {
    opacity: 0.45;
    text-decoration: line-through;
}
';
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="container mt-4">
    <h4 class="mb-4 text-center"><i class="fa-solid fa-users-gear me-2"></i>Gestione Docenti</h4>

    <div class="row">
        <!-- ── Card: Conteggio docenti ── -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Docenti attivi</span>
                    <button type="button"
                            class="btn btn-primary btn-sm"
                            data-bs-toggle="collapse"
                            data-bs-target="#docentiList"
                            aria-expanded="false"
                            aria-controls="docentiList">
                        <i class="fa-solid fa-list me-1"></i>Visualizza
                    </button>
                </div>
                <div class="card-body text-center">
                    <i class="fa-solid fa-users fa-3x mb-2 text-primary"></i>
                    <h3 id="docentiCount"><?= $activeCount ?></h3>
                    <small class="text-muted">attivi / <?= $activeCount + $deletedCount ?> totali (<?= $deletedCount ?> disattivati)</small>
                </div>
            </div>
        </div>

        <!-- ── Card: Aggiungi nuovo docente ── -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <i class="fa-solid fa-user-plus me-2"></i>Aggiungi un nuovo docente
                </div>
                <div class="card-body">
                    <form id="addDocenteForm">
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome</label>
                            <input type="text"
                                   class="form-control"
                                   id="nome"
                                   name="nome"
                                   required>
                        </div>
                        <div class="mb-3">
                            <label for="cognome" class="form-label">Cognome</label>
                            <input type="text"
                                   class="form-control"
                                   id="cognome"
                                   name="cognome"
                                   required>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-plus me-2"></i>Aggiungi Docente
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- ── Lista docenti (collapsible) ── -->
        <div id="docentiList" class="col-md-12 collapse">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fa-solid fa-list me-2"></i>Lista Docenti</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="collapse" aria-label="Chiudi"></button>
                </div>
                <div class="card-body p-3">
                    <?php if (empty($docenti)): ?>
                        <div class="alert alert-warning">Nessun docente trovato.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th style="width:60px;">ID</th>
                                        <th>Nome</th>
                                        <th>Cognome</th>
                                        <th style="width:180px;">Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($docenti as $docente): ?>
                                        <?php $isDeleted = (int) $docente['isDeleted']; ?>
                                        <tr class="docente-row <?= $isDeleted ? 'row-deactivated' : '' ?>" data-id="<?= (int) $docente['idDocente'] ?>">
                                            <td><?= (int) $docente['idDocente'] ?></td>
                                            <td><?= htmlspecialchars($docente['nome']) ?></td>
                                            <td><?= htmlspecialchars($docente['cognome']) ?></td>
                                            <td>
                                                <?php if ($isDeleted): ?>
                                                    <button type="button"
                                                            class="btn btn-sm btn-success btn-toggle"
                                                            data-id="<?= (int) $docente['idDocente'] ?>"
                                                            title="Riattiva docente">
                                                        <i class="fa-solid fa-lock-open me-1"></i>Riattiva
                                                    </button>
                                                <?php else: ?>
                                                    <button type="button"
                                                            class="btn btn-sm btn-warning btn-toggle"
                                                            data-id="<?= (int) $docente['idDocente'] ?>"
                                                            title="Disattiva docente">
                                                        <i class="fa-solid fa-lock me-1"></i>Disattiva
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // ── Toggle docente (attiva/disattiva) ──
    document.querySelectorAll('.btn-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id    = this.getAttribute('data-id');
            var row   = this.closest('.docente-row');

            if (!confirm('Confermi il cambiamento di stato del docente?')) return;

            var originalHTML = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>...';

            var wasDeleted = row.classList.contains('row-deactivated');

            var formData = new FormData();
            formData.append('delete_id', id);
            formData.append('_token', csrfToken);

            fetch('assets/utils/delete-docente.php', {
                method: 'POST',
                body: formData
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    if (wasDeleted) {
                        // Riattivato: rimuovi evidenziazione
                        row.classList.remove('row-deactivated');
                        btn.innerHTML = '<i class="fa-solid fa-lock me-1"></i>Disattiva';
                        btn.className = 'btn btn-sm btn-warning btn-toggle';
                        btn.setAttribute('title', 'Disattiva docente');
                    } else {
                        // Disattivato: aggiungi evidenziazione
                        row.classList.add('row-deactivated');
                        btn.innerHTML = '<i class="fa-solid fa-lock-open me-1"></i>Riattiva';
                        btn.className = 'btn btn-sm btn-success btn-toggle';
                        btn.setAttribute('title', 'Riattiva docente');
                    }

                    // Aggiorna il conteggio
                    var countEl = document.getElementById('docentiCount');
                    var current = parseInt(countEl.textContent, 10);
                    countEl.textContent = wasDeleted ? (current + 1) : Math.max(0, current - 1);
                } else {
                    alert('Errore: ' + data.message);
                    btn.innerHTML = originalHTML;
                }
                btn.disabled = false;
            })
            .catch(function () {
                alert('Errore durante la richiesta.');
                btn.innerHTML = originalHTML;
                btn.disabled = false;
            });
        });
    });

    // ── Aggiungi nuovo docente ──
    var addForm = document.getElementById('addDocenteForm');
    if (addForm) {
        addForm.addEventListener('submit', function (e) {
            e.preventDefault();

            var nomeInput    = document.getElementById('nome');
            var cognomeInput = document.getElementById('cognome');
            var submitBtn    = addForm.querySelector('button[type="submit"]');

            var formData = new FormData();
            formData.append('nome', nomeInput.value.trim());
            formData.append('cognome', cognomeInput.value.trim());
            formData.append('_token', csrfToken);

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Aggiungendo...';

            fetch('assets/utils/add_docente.php', {
                method: 'POST',
                body: formData
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    // Aggiorna il conteggio
                    var countEl = document.getElementById('docentiCount');
                    var current = parseInt(countEl.textContent, 10);
                    countEl.textContent = current + 1;

                    // Reset form
                    addForm.reset();
                } else {
                    alert('Errore: ' + data.message);
                }
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fa-solid fa-plus me-2"></i>Aggiungi Docente';
            })
            .catch(function () {
                alert('Errore durante la richiesta.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fa-solid fa-plus me-2"></i>Aggiungi Docente';
            });
        });
    }
});
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
