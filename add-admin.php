<?php
// add-admin.php
/*
 * OggiInLab
 * Copyright (c) 2026 Sergio Ferraro
 * Licensed under the MIT License
 */
require_once __DIR__ . '/includes/session.php';
include('includes/config.php');
require_once __DIR__ . '/includes/Logger.php';
error_reporting(0);

if (strlen($_SESSION['alogin']) == 0) {
    header('location:index.php');
} else {
            // Management of Super Admin status change
            if (isset($_POST['toggle_super_admin'])) {
                // --- Role guard: solo Super Admin può modificare i privilegi ---
                if (empty($_SESSION['is_super_admin']) || $_SESSION['is_super_admin'] != 1) {
                    Logger::warning('admin_toggle_superuser_no_privileges', [
                        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                        'target_admin_id' => $_POST['admin_id'] ?? null
                    ]);
                    $error = "Permesso negato: operazione riservata ai Super Admin.";
                }
                // --- CSRF validation ---
                elseif (empty($_POST['_token']) || !is_string($_POST['_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['_token'])) {
                    $error = "Token di sicurezza non valido. Riprova.";
                } else {
                    $adminId = intval($_POST['admin_id']);
                    $newStatus = intval($_POST['new_status']);

                    // Verify if the user is attempting to deactivate a Super Admin
                    if ($newStatus == 0) {
                        // Check the current status of the Super Admin
                        $stmt = $dbh->prepare("SELECT is_super_admin FROM admin WHERE id = :adminId");
                        $stmt->bindParam(':adminId', $adminId, PDO::PARAM_INT);
                        $stmt->execute();
                        $currentSuperAdminStatus = $stmt->fetchColumn();

                        //  If the user was a Super Admin
                        if ($currentSuperAdminStatus == 1) {
                            // Count the current number of Super Admins
                            $superAdminCount = $dbh->query("SELECT COUNT(*) FROM admin WHERE is_super_admin = 1 AND isActive = 1")->fetchColumn();

                            // Verify if at least two Super Admins would remain after the change
                            if ($superAdminCount <= 2) {
                                Logger::warning('admin_toggle_superuser_restricted', [
                                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                                    'admin_id' => $adminId,
                                    'new_status' => $newStatus
                                ]);
                                $error = "Impossibile disattivare il toggle: devono esserci almeno due super admin.";
                            } else {
                                // Perform the role update
                                $sql = "UPDATE admin SET is_super_admin = :newStatus WHERE id = :adminId";
                                $query = $dbh->prepare($sql);
                                $query->bindParam(':newStatus', $newStatus, PDO::PARAM_INT);
                                $query->bindParam(':adminId', $adminId, PDO::PARAM_INT);
                                $query->execute();
                                Logger::success('admin_toggle_superuser', [
                                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                                    'admin_id' => $adminId,
                                    'new_status' => $newStatus
                                ]);
                                $msg = "Privilegi Super Admin aggiornati.";
                            }
                        } else {
                            // Perform the role update
                            $sql = "UPDATE admin SET is_super_admin = :newStatus WHERE id = :adminId";
                            $query = $dbh->prepare($sql);
                            $query->bindParam(':newStatus', $newStatus, PDO::PARAM_INT);
                            $query->bindParam(':adminId', $adminId, PDO::PARAM_INT);
                            $query->execute();
                            Logger::success('admin_toggle_superuser', [
                                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                                'admin_id' => $adminId,
                                'new_status' => $newStatus
                            ]);
                            $msg = "Privilegi Super Admin aggiornati.";
                        }
                    } else {
                        // Activating a Super Admin (no restriction)
                        $sql = "UPDATE admin SET is_super_admin = :newStatus WHERE id = :adminId";
                        $query = $dbh->prepare($sql);
                        $query->bindParam(':newStatus', $newStatus, PDO::PARAM_INT);
                        $query->bindParam(':adminId', $adminId, PDO::PARAM_INT);
                        $query->execute();
                        Logger::success('admin_toggle_superuser', [
                            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                            'admin_id' => $adminId,
                            'new_status' => $newStatus
                        ]);
                        $msg = "Privilegi Super Admin aggiornati.";
                    }
                }
            }
            // Management of isActive status toggle
            if (isset($_POST['toggle_is_active'])) {
                // --- Role guard: solo Super Admin può disattivare/riattivare admin ---
                if (empty($_SESSION['is_super_admin']) || $_SESSION['is_super_admin'] != 1) {
                    Logger::warning('admin_toggle_isactive_no_privileges', [
                        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                        'target_admin_id' => $_POST['admin_id'] ?? null
                    ]);
                    $error = "Permesso negato: operazione riservata ai Super Admin.";
                }
                // --- CSRF validation ---
                elseif (empty($_POST['_token']) || !is_string($_POST['_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['_token'])) {
                    Logger::warning('admin_toggle_isactive_csrf_error', [
                        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                    ]);
                    $error = "Token di sicurezza non valido. Riprova.";
                } else {
                    $adminId = intval($_POST['admin_id']);

                    // Non puoi disattivare te stesso
                    if ($adminId == $_SESSION['id']) {
                        Logger::warning('admin_toggle_isactive_self', [
                            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                            'admin_id' => $adminId
                        ]);
                        $error = "Non puoi disattivare il tuo account.";
                    } else {
                        // Recupera lo stato corrente
                        $stmt = $dbh->prepare("SELECT isActive FROM admin WHERE id = :adminId");
                        $stmt->bindParam(':adminId', $adminId, PDO::PARAM_INT);
                        $stmt->execute();
                        $currentStatus = $stmt->fetchColumn();

                        if ($currentStatus === false) {
                            Logger::warning('admin_toggle_isactive_not_found', [
                                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                                'admin_id' => $adminId
                            ]);
                            $error = "Amministratore non trovato.";
                        } else {
                            // Se stai disattivando, verifica che rimanga almeno un admin attivo
                            if ($currentStatus == 1) {
                                $activeCount = $dbh->query("SELECT COUNT(*) FROM admin WHERE isActive = 1")->fetchColumn();
                                if ($activeCount <= 1) {
                                    Logger::warning('admin_toggle_isactive_min_active', [
                                        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                                        'admin_id' => $adminId,
                                        'current_active_count' => $activeCount
                                    ]);
                                    $error = "Deve rimanere almeno un amministratore attivo.";
                                } else {
                                    $newStatus = 0;
                                    $sql = "UPDATE admin SET isActive = :newStatus WHERE id = :adminId";
                                    $query = $dbh->prepare($sql);
                                    $query->bindParam(':newStatus', $newStatus, PDO::PARAM_INT);
                                    $query->bindParam(':adminId', $adminId, PDO::PARAM_INT);
                                    $query->execute();
                                    Logger::success('admin_toggle_isactive', [
                                        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                                        'admin_id' => $adminId,
                                        'new_status' => $newStatus
                                    ]);
                                    $msg = "Amministratore disattivato.";
                                }
                            } else {
                                $newStatus = 1;
                                $sql = "UPDATE admin SET isActive = :newStatus WHERE id = :adminId";
                                $query = $dbh->prepare($sql);
                                $query->bindParam(':newStatus', $newStatus, PDO::PARAM_INT);
                                $query->bindParam(':adminId', $adminId, PDO::PARAM_INT);
                                $query->execute();
                                Logger::success('admin_toggle_isactive', [
                                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                                    'admin_id' => $adminId,
                                    'new_status' => $newStatus
                                ]);
                                $msg = "Amministratore riattivato.";
                            }
                        }
                    }
                }
            }
            if (isset($_POST['delete_admin'])) {
                // --- Role guard: solo Super Admin può eliminare admin ---
                if (empty($_SESSION['is_super_admin']) || $_SESSION['is_super_admin'] != 1) {
                    Logger::warning('admin_delete_no_privileges', [
                        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                        'target_admin_id' => $_POST['admin_id'] ?? null
                    ]);
                    $error = "Permesso negato: operazione riservata ai Super Admin.";
                }
                // --- CSRF validation ---
                elseif (empty($_POST['_token']) || !is_string($_POST['_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['_token'])) {
                    Logger::warning('admin_delete_csrf_error', [
                        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                    ]);
                    $error = "Token di sicurezza non valido. Riprova.";
                } else {
                    $adminId = intval($_POST['admin_id']);

                    // Verify if the user being attempted to be deleted is a Super Admin
                    $stmt = $dbh->prepare("SELECT is_super_admin FROM admin WHERE id = :adminId");
                    $stmt->bindParam(':adminId', $adminId, PDO::PARAM_INT);
                    $stmt->execute();
                    $isSuperAdmin = $stmt->fetchColumn();

                    if ($isSuperAdmin === false) {
                        Logger::warning('admin_delete_not_found', [
                            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                            'admin_id' => $adminId
                        ]);
                        $error = "Amministratore non trovato.";
                    } elseif ($isSuperAdmin == 1) {
                        Logger::warning('admin_delete_superuser', [
                            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                            'admin_id' => $adminId
                        ]);
                        $error = "Impossibile eliminare un Super Admin.";
                    } else {
                        // Verifica se ci sono appuntamenti registrati a suo nome
                        $stmtCheck = $dbh->prepare("SELECT COUNT(*) FROM appuntamento WHERE autore = :adminId");
                        $stmtCheck->bindParam(':adminId', $adminId, PDO::PARAM_INT);
                        $stmtCheck->execute();
                        $appuntamentoCount = $stmtCheck->fetchColumn();

                        if ($appuntamentoCount > 0) {
                            Logger::warning('admin_delete_appointments_exist', [
                                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                                'admin_id' => $adminId,
                                'appointment_count' => $appuntamentoCount
                            ]);
                            $error = "Impossibile eliminare: ci sono " . $appuntamentoCount . " appuntamenti registrati a nome di questo amministratore.";
                        } else {
                            // Execute the deletion
                            $stmt = $dbh->prepare("DELETE FROM admin WHERE id = :adminId");
                            $stmt->bindParam(':adminId', $adminId, PDO::PARAM_INT);
                            $stmt->execute();
                            Logger::success('admin_delete', [
                                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                                'admin_id' => $adminId
                            ]);
                            $msg = "Amministratore eliminato con successo.";
                        }
                    }
                }
            }


    if (isset($_POST['submit'])) {
        // --- CSRF validation ---
        if (empty($_POST['_token']) || !is_string($_POST['_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['_token'])) {
            Logger::warning('admin_add_csrf_error', [
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            $error = "Token di sicurezza non valido. Riprova.";
        } else {
        $nomeCompleto = $_POST['nomeCompleto'];
        $adminEmail = $_POST['adminEmail'];
        $username = $_POST['userName'];
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirmPassword'];

        // Validate password match in plaintext
        if ($password != $confirmPassword) {
            Logger::warning('admin_add_password_mismatch', [
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            $error = "Le password non corrispondono";
        } else {
            // Check if email or username already exists
            $stmt = $dbh->prepare("SELECT * FROM admin WHERE adminEmail=:email OR userName=:userName");
            $stmt->bindParam(':email', $adminEmail);
            $stmt->bindParam(':userName', $username); // Fixed placeholder name
            $stmt->execute();
            $result = $stmt->fetchAll();

            if ($stmt->rowCount() > 0) {
                Logger::warning('admin_add_duplicate', [
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'email' => $adminEmail,
                    'username' => $username
                ]);
                $error = "Email o username già utilizzati";
            } else {
                // Hash password securely with bcrypt
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                // Insert new admin with hashed password
                $sql = "INSERT INTO admin (nomeCompleto, adminEmail, userName, Password) VALUES (:nome, :email, :username, :password)";
                $query = $dbh->prepare($sql);
                $query->bindParam(':nome', $nomeCompleto);
                $query->bindParam(':email', $adminEmail);
                $query->bindParam(':username', $username);
                $query->bindParam(':password', $hashedPassword);
                $query->execute();

                if ($query->rowCount() > 0) {
                    $msg = "Nuovo amministratore aggiunto con successo";
                } else {
                    $error = "Errore nell'aggiunta dell'utente";
                }
            }
        }
        } // fine CSRF else

    }
}

$pageTitle = 'OggiInLab | Gestione amministratori';
$pageStyles = '
.admin-inactive {
    opacity: 0.5;
}
.admin-inactive > div:first-child {
    text-decoration: line-through;
}
';
$pageCsrf = true;
$pageHeadScripts = '<script type="text/javascript">
    function validateForm() {
        if (document.addadmin.nomeCompleto.value == "") {
            alert("Inserisci il nome completo");
            return false;
        }
        if (document.addadmin.adminEmail.value == "") {
            alert("Inserisci l\'email");
            return false;
        }
        if (document.addadmin.userName.value == "") {
            alert("Inserisci lo username");
            return false;
        }
        if (document.addadmin.password.value != document.addadmin.confirmPassword.value) {
            alert("Le password non corrispondono");
            return false;
        }
        return true;
    }
</script>';
?>
<?php include('includes/header.php'); ?>
    <div class="content-wrapper">
        <div class="container">
            <?php if (isset($error)) { ?><div class="alert alert-danger"><strong>Errore:</strong> <?= htmlspecialchars($error) ?></div><?php } ?>
            <?php if (isset($msg)) { ?><div class="alert alert-success"><strong>Successo:</strong> <?= htmlspecialchars($msg) ?></div><?php } ?>
        <div class="row">
    <!-- Add Administrator Form -->
    <div class="col-md-6">
        <div class="panel panel-info">
            <div class="panel-heading">
                Inserisci dati dell'amministratore
            </div>
            <div class="panel-body">
                <form role="form" name="addadmin" method="post" onSubmit="return validateForm();">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>" />
                    <div class="form-group">
                        <label>Nome completo</label>
                        <input class="form-control" type="text" name="nomeCompleto" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input class="form-control" type="email" name="adminEmail" required>
                    </div>
                    <div class="form-group">
                        <label>Username</label>
                        <input class="form-control" type="text" name="userName" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input class="form-control" type="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label>Conferma password</label>
                        <input class="form-control" type="password" name="confirmPassword" required>
                    </div>
                    <button type="submit" name="submit" class="btn btn-info">Aggiungi</button>
                </form>
            </div>
        </div>
    </div>

    <!-- List of Administrators -->
    <div class="col-md-6">
        <div class="panel panel-info">
            <div class="panel-heading">
                Amministratori presenti
            </div>
            <div class="panel-body">
                <?php
                $sql = "SELECT id, nomeCompleto, adminEmail, userName, is_super_admin, lastLogin, isActive FROM admin ORDER BY isActive DESC, nomeCompleto ASC";
                $query = $dbh->prepare($sql);
                $query->execute();
                $results = $query->fetchAll(PDO::FETCH_OBJ);
                if ($query->rowCount() > 0) {
                    echo '<ul class="list-group">';
                    // Calcola i conteggi
                    $totalActive = 0;
                    $totalInactive = 0;
                    foreach ($results as $r) {
                        if ((int)$r->isActive == 1) $totalActive++;
                        else $totalInactive++;
                    }
                    foreach ($results as $result) {
                        $isActive = (int)$result->isActive;
                        $isSelf = ($result->id == $_SESSION['id']);
                        echo '<li class="list-group-item d-flex justify-content-between align-items-center' . ($isActive == 0 ? ' admin-inactive' : '') . '">';
                        echo '<div>';
                        echo htmlentities($result->nomeCompleto) . ' (' . htmlentities($result->userName) . ')';
                        // Badge stato
                        if ($isActive == 1) {
                            echo ' <span class="badge bg-success rounded-pill ms-1">Attivo</span>';
                        } else {
                            echo ' <span class="badge bg-secondary rounded-pill ms-1">Disattivato</span>';
                        }
                        // Badge super admin
                        if ($result->is_super_admin == 1) {
                            echo ' <span class="badge bg-warning rounded-pill text-dark ms-1">Super Admin</span>';
                        }
                        if ($result->lastLogin !== null) {
                            echo '<br><small class="text-muted">Ultimo login: ' . date('d/m/Y H:i:s', strtotime($result->lastLogin)) . '</small>';
                        } else {
                            echo '<br><small class="text-muted">Mai connesso</small>';
                        }
                        echo '</div>';
                        echo '<div class="d-flex align-items-center gap-2 flex-wrap">';

                        // Show email as a badge
                        echo '<span class="badge bg-primary rounded-pill">' . htmlentities($result->adminEmail) . '</span>';

                        // If the logged-in user is a Super Admin, show the toggles
                        if ($_SESSION['is_super_admin'] == 1) {
                            // ── Toggle isActive (attiva/disattiva) ──
                            if (!$isSelf) {
                                echo '<form method="post" style="margin:0">';
                                echo '<input type="hidden" name="_token" value="' . htmlspecialchars($_SESSION['csrf_token'] ?? '') . '">';
                                echo '<input type="hidden" name="admin_id" value="' . htmlspecialchars($result->id) . '">';
                                if ($isActive == 1) {
                                    echo '<button type="submit" name="toggle_is_active" value="1" class="btn btn-warning btn-sm" title="Disattiva amministratore">';
                                    echo '<i class="fas fa-lock me-1"></i>Disattiva';
                                } else {
                                    echo '<button type="submit" name="toggle_is_active" value="1" class="btn btn-success btn-sm" title="Riattiva amministratore">';
                                    echo '<i class="fas fa-lock-open me-1"></i>Riattiva';
                                }
                                echo '</button>';
                                echo '</form>';
                            } else {
                                echo '<span class="badge bg-info rounded-pill" title="Questo è il tuo account">(tu)</span>';
                            }

                            // Super Admin toggle (switch)
                            echo '<form method="post" style="margin:0">';
                            echo '<input type="hidden" name="_token" value="' . htmlspecialchars($_SESSION['csrf_token'] ?? '') . '">';
                            echo '<input type="hidden" name="admin_id" value="' . htmlspecialchars($result->id) . '">';
                            echo '<input type="hidden" name="new_status" value="' . ($result->is_super_admin ? 0 : 1) . '">';
                            echo '<div class="form-check form-switch">';
                            echo '<input class="form-check-input" type="checkbox" onChange="this.form.submit()" ' . ($result->is_super_admin ? 'checked' : '') . ' title="Super Admin">';
                            echo '</div>';
                            echo '<input type="hidden" name="toggle_super_admin" value="1">';
                            echo '</form>';
                        
                        // Reset Password Button
                        echo '<button 
                            type="button" 
                            class="btn btn-warning btn-sm d-flex align-items-center gap-2"
                            data-bs-toggle="modal" 
                            data-bs-target="#resetPasswordModal" 
                            data-admin-id="' . $result->id . '">
                        <i class="fas fa-key"></i> Reset
                        </button>';
                    
                        // Delete Admin Button
                        echo '<form method="post" style="display:inline; margin-left:5px">';
                        echo '<input type="hidden" name="_token" value="' . htmlspecialchars($_SESSION['csrf_token'] ?? '') . '">';
                        echo '<input type="hidden" name="admin_id" value="' . htmlspecialchars($result->id) . '">';
                        echo '<button 
                            type="submit"
                            name="delete_admin"
                            value="1"
                            class="btn btn-danger btn-sm d-flex align-items-center gap-2"
                            onclick="return confirm(\'Sei sicuro di voler eliminare questo amministratore?\')">
                        <i class="fas fa-trash-alt"></i> Elimina
                        </button>';
                        echo '</form>';
                    }

                        echo '</div>';
                        echo '</li>';
                    }
                    echo '</ul>';
                    echo '<small class="text-muted d-block mt-2">' . $totalActive . ' attivi / ' . count($results) . ' totali (' . $totalInactive . ' disattivati)</small>';
                } else {
                    echo '<div class="alert alert-warning">Nessun amministratore registrato.</div>';
                }
                ?>
            </div>
        </div>
    </div>
</div>
<!--  Modal for resetting the password -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" role="dialog" aria-labelledby="resetPasswordLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header border-bottom border-secondary">
                <h5 class="modal-title" id="resetPasswordLabel">Reset Password</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="assets/utils/reset_admin.php">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>" />
                    <input type="hidden" name="admin_id" id="adminId" />
                    <div class="mb-3">
                        <label for="newPassword" class="form-label">Nuova Password</label>
                        <input type="password" class="form-control bg-dark text-white border-secondary" id="newPassword" name="new_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirmPassword" class="form-label">Conferma Password</label>
                        <input type="password" class="form-control bg-dark text-white border-secondary" id="confirmPassword" name="confirm_password" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Resetta</button>
                </form>
            </div>
        </div>
    </div>
</div>
        </div>
    </div>
    

    <!-- Admin Actions Log (solo Super Admin) -->
    <?php if (isset($_SESSION['is_super_admin']) && $_SESSION['is_super_admin'] == 1): ?>
    <div class="content-wrapper">
        <div class="container">
            <div class="panel panel-info">
                <div class="panel-heading">
                    <i class="fas fa-history me-2"></i>Log Azioni Amministratori
                </div>
                <div class="panel-body">
                    <?php
                    $logEntries = Logger::read(200);

                    // Mappa action_type -> descrizione leggibile
                    $actionLabels = [
                        'csrf_token_generated'              => 'Token CSRF generato',
                        'project_management_request'        => 'Richiesta gestione progetto',
                        'project_update'                    => 'Aggiornamento progetto',
                        'project_add'                       => 'Aggiunta progetto',
                        'project_delete'                    => 'Eliminazione progetto',
                        'appointment_add'                   => 'Aggiunta appuntamento',
                        'appointment_edit'                  => 'Modifica appuntamento',
                        'appointment_invalidate'            => 'Invalidazione appuntamento',
                        'appointment_hard_delete'           => 'Eliminazione appuntamento',
                        'appointment_invalidate_no_privileges_past' => 'Invalidazione negata (privilegi)',
                        'admin_add_csrf_error'              => 'Errore CSRF aggiunta admin',
                        'admin_add_password_mismatch'       => 'Password non corrispondono',
                        'admin_add_duplicate'               => 'Admin duplicato',
                        'admin_toggle_superuser'            => 'Toggle Super Admin',
                        'admin_toggle_superuser_restricted' => 'Toggle Super Admin (bloccato)',
                        'admin_toggle_isactive'             => 'Toggle stato admin',
                        'admin_toggle_isactive_csrf_error'  => 'Errore CSRF toggle admin',
                        'admin_toggle_isactive_self'        => 'Toggle su sé stesso',
                        'admin_toggle_isactive_not_found'   => 'Admin non trovato',
                        'admin_toggle_isactive_min_active'  => 'Minimo admin attivi',
                        'admin_delete'                      => 'Eliminazione admin',
                        'admin_delete_csrf_error'           => 'Errore CSRF eliminazione admin',
                        'admin_delete_not_found'            => 'Admin non trovato',
                        'admin_delete_superuser'            => 'Eliminazione Super Admin negata',
                        'admin_delete_appointments_exist'   => 'Admin con appuntamenti',
                        'admin_reset_password'              => 'Reset password admin',
                        'docente_add_duplicate'             => 'Docente duplicato',
                    ];

                    // Badge per livello
                    $levelBadges = [
                        'DEBUG'    => 'bg-secondary',
                        'INFO'     => 'bg-info',
                        'SUCCESS'  => 'bg-success',
                        'WARNING'  => 'bg-warning text-dark',
                        'ERROR'    => 'bg-danger',
                        'CRITICAL' => 'bg-dark text-danger border border-danger',
                    ];

                    if (empty($logEntries)) {
                        echo '<p class="text-muted">Nessuna entry nel log.</p>';
                    } else {
                        echo '<div class="table-responsive">';
                        echo '<table class="table table-dark table-hover table-striped table-sm mb-0">';
                        echo '<thead><tr>';
                        echo '<th>Data/Ora</th>';
                        echo '<th>Livello</th>';
                        echo '<th>Admin</th>';
                        echo '<th>Azione</th>';
                        echo '<th>Dettagli</th>';
                        echo '<th>IP</th>';
                        echo '</tr></thead><tbody>';

                        foreach ($logEntries as $entry) {
                            // Timestamp
                            $ts = isset($entry['timestamp']) ? date('d/m/Y H:i:s', strtotime($entry['timestamp'])) : '-';

                            // Livello
                            $level = $entry['level'] ?? 'UNKNOWN';
                            $badgeClass = $levelBadges[$level] ?? 'bg-secondary';

                            // Admin
                            $adminInfo = '';
                            if (isset($entry['admin']) && is_array($entry['admin'])) {
                                $name = $entry['admin']['name'] ?? $entry['admin']['username'] ?? null;
                                $id = $entry['admin']['id'] ?? null;
                                if ($name) {
                                    $adminInfo = htmlspecialchars($name);
                                    if ($id) $adminInfo .= ' <small class="text-muted">(id:' . $id . ')</small>';
                                } else {
                                    $adminInfo = '<span class="text-muted">non identificato</span>';
                                }
                            } else {
                                $adminInfo = '<span class="text-muted">non identificato</span>';
                            }

                            // Azione
                            $actionType = $entry['action_type'] ?? 'unknown';
                            $actionLabel = $actionLabels[$actionType] ?? htmlspecialchars($actionType);

                            // Dettagli (campi extra contestuali)
                            $details = '';
                            $skipKeys = ['timestamp', 'level', 'level_code', 'admin', 'action_type', 'client_info', 'ip_address'];
                            $extra = array_diff_key($entry, array_flip($skipKeys));
                            if (!empty($extra)) {
                                $detailParts = [];
                                foreach ($extra as $k => $v) {
                                    if ($k === 'changes' && is_array($v)) {
                                        $changesStr = [];
                                        foreach ($v as $ck => $cv) {
                                            $changesStr[] = htmlspecialchars($ck) . ': ' . htmlspecialchars((string)$cv);
                                        }
                                        $detailParts[] = '<strong>modifiche:</strong> ' . implode(', ', $changesStr);
                                    } elseif (is_array($v)) {
                                        $detailParts[] = htmlspecialchars($k) . ': [array]';
                                    } else {
                                        $detailParts[] = htmlspecialchars($k) . ': ' . htmlspecialchars((string)$v);
                                    }
                                }
                                $details = implode('<br>', $detailParts);
                            }

                            // IP
                            $ip = 'unknown';
                            if (isset($entry['client_info']['ip_address'])) {
                                $ip = $entry['client_info']['ip_address'];
                            } elseif (isset($entry['ip_address'])) {
                                $ip = $entry['ip_address'];
                            }

                            echo '<tr>';
                            echo '<td>' . $ts . '</td>';
                            echo '<td><span class="badge ' . $badgeClass . ' rounded-pill">' . $level . '</span></td>';
                            echo '<td>' . $adminInfo . '</td>';
                            echo '<td>' . $actionLabel . '</td>';
                            echo '<td>' . $details . '</td>';
                            echo '<td><code>' . htmlspecialchars($ip) . '</code></td>';
                            echo '</tr>';
                        }

                        echo '</tbody></table>';
                        echo '</div>';
                        echo '<small class="text-muted d-block mt-2">Ultime ' . count($logEntries) . ' entry</small>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php endif; ?>
    <?php include('includes/footer.php'); ?>
