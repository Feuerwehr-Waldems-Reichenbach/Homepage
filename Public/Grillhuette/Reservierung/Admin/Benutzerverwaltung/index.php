<?php
require_once '../../includes/config.php';
require_once '../../includes/User.php';

// Nur für angemeldete Administratoren zugänglich
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    $_SESSION['flash_message'] = 'Sie haben keine Berechtigung, auf diese Seite zuzugreifen.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ' . getRelativePath('home'));
    exit;
}

// User-Objekt initialisieren
$user = new User();

// Benutzer abrufen für die Anzeige
$allUsers = $user->getAllUsers();

// Preisdaten aus der Datenbank abrufen
require_once '../../includes/Reservation.php';
$reservation = new Reservation();
$priceInfo = $reservation->getPriceInformation();
$basePrice = number_format($priceInfo['base_price'], 2, ',', '.');

// Werte für Aktives Mitglied und Feuerwehr aus dem PriceInfo-Array holen
$aktivesPrice = "80,00"; // Standardwert
$feuerwehrPrice = "60,00"; // Standardwert

// Aus den Systemeinstellungen holen (falls vorhanden)
$infoQuery = $reservation->getSystemInformation(['MietpreisAktivesMitglied', 'MietpreisFeuerwehr']);

if (isset($infoQuery['MietpreisAktivesMitglied'])) {
    $aktivesPrice = $infoQuery['MietpreisAktivesMitglied'];
    // € Zeichen entfernen falls vorhanden
    $aktivesPrice = str_replace('€', '', $aktivesPrice);
}

if (isset($infoQuery['MietpreisFeuerwehr'])) {
    $feuerwehrPrice = $infoQuery['MietpreisFeuerwehr'];
    // € Zeichen entfernen falls vorhanden
    $feuerwehrPrice = str_replace('€', '', $feuerwehrPrice);
}

// Alle POST-Anfragen abfangen und PRG-Muster anwenden
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF-Token überprüfen
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['flash_message'] = 'Ungültige Anfrage. Bitte versuchen Sie es erneut.';
        $_SESSION['flash_type'] = 'danger';
    } else {
        // Admin-Status ändern
        if (isset($_POST['toggle_admin'])) {
            $userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
            
            // Verhindern, dass ein Admin sich selbst die Rechte entzieht
            if ($userId === $_SESSION['user_id']) {
                $_SESSION['flash_message'] = 'Sie können Ihren eigenen Administrator-Status nicht ändern.';
                $_SESSION['flash_type'] = 'danger';
            } else {
                $result = $user->toggleAdmin($userId);
                
                $_SESSION['flash_message'] = $result['message'];
                $_SESSION['flash_type'] = $result['success'] ? 'success' : 'danger';
            }
        }
        
        // Verifikationsstatus ändern
        else if (isset($_POST['toggle_verification'])) {
            $userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
            
            $result = $user->toggleVerification($userId);
            
            $_SESSION['flash_message'] = $result['message'];
            $_SESSION['flash_type'] = $result['success'] ? 'success' : 'danger';
        }
        
        // Neuen Benutzer erstellen
        else if (isset($_POST['create_user'])) {
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            $firstName = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
            $lastName = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
            $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
            $password = isset($_POST['password']) ? $_POST['password'] : '';
            $isAdmin = isset($_POST['is_admin']) ? 1 : 0;
            $isVerified = isset($_POST['is_verified']) ? 1 : 0;
            $isAktivesMitglied = isset($_POST['is_AktivesMitglied']) ? 1 : 0;
            $isFeuerwehr = isset($_POST['is_Feuerwehr']) ? 1 : 0;
            
            // Validierung
            $errors = [];
            
            if (empty($email)) {
                $errors[] = 'Bitte geben Sie eine E-Mail-Adresse ein.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Bitte geben Sie eine gültige E-Mail-Adresse ein.';
            }
            
            if (empty($firstName)) {
                $errors[] = 'Bitte geben Sie einen Vornamen ein.';
            }
            
            if (empty($lastName)) {
                $errors[] = 'Bitte geben Sie einen Nachnamen ein.';
            }
            
            if (empty($password)) {
                $errors[] = 'Bitte geben Sie ein Passwort ein.';
            } elseif (strlen($password) < 8) {
                $errors[] = 'Das Passwort muss mindestens 8 Zeichen lang sein.';
            }
            
            if (empty($errors)) {
                $result = $user->createUserByAdmin($email, $password, $firstName, $lastName, $phone, $isAdmin, $isVerified, $isAktivesMitglied, $isFeuerwehr);
                
                $_SESSION['flash_message'] = $result['message'];
                $_SESSION['flash_type'] = $result['success'] ? 'success' : 'danger';
            } else {
                $_SESSION['flash_message'] = implode('<br>', $errors);
                $_SESSION['flash_type'] = 'danger';
            }
        }
        
        // Benutzer bearbeiten
        else if (isset($_POST['edit_user'])) {
            $userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            $firstName = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
            $lastName = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
            $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
            $isAdmin = isset($_POST['is_admin']) ? 1 : 0;
            $isVerified = isset($_POST['is_verified']) ? 1 : 0;
            $isAktivesMitglied = isset($_POST['is_AktivesMitglied']) ? 1 : 0;
            $isFeuerwehr = isset($_POST['is_Feuerwehr']) ? 1 : 0;
            $newPassword = isset($_POST['new_password']) ? $_POST['new_password'] : '';
            
            // Validierung
            $errors = [];
            
            if (empty($email)) {
                $errors[] = 'Bitte geben Sie eine E-Mail-Adresse ein.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Bitte geben Sie eine gültige E-Mail-Adresse ein.';
            }
            
            if (empty($firstName)) {
                $errors[] = 'Bitte geben Sie einen Vornamen ein.';
            }
            
            if (empty($lastName)) {
                $errors[] = 'Bitte geben Sie einen Nachnamen ein.';
            }
            
            // Passwort nur überprüfen, wenn es geändert werden soll
            if (!empty($newPassword) && strlen($newPassword) < 8) {
                $errors[] = 'Das neue Passwort muss mindestens 8 Zeichen lang sein.';
            }
            
            // Verhindern, dass ein Admin sich selbst die Rechte entzieht
            if ($userId === $_SESSION['user_id'] && $_SESSION['is_admin'] && !$isAdmin) {
                $errors[] = 'Sie können Ihren eigenen Administrator-Status nicht ändern.';
            }
            
            if (empty($errors)) {
                $result = $user->updateUser($userId, $email, $firstName, $lastName, $phone, $isAdmin, $newPassword, $isVerified, $isAktivesMitglied, $isFeuerwehr);
                
                $_SESSION['flash_message'] = $result['message'];
                $_SESSION['flash_type'] = $result['success'] ? 'success' : 'danger';
                
                // Session aktualisieren, wenn der eigene Benutzer aktualisiert wurde
                if ($result['success'] && $userId === $_SESSION['user_id']) {
                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_name'] = $firstName . ' ' . $lastName;
                    $_SESSION['is_admin'] = $isAdmin;
                    $_SESSION['is_AktivesMitglied'] = $isAktivesMitglied;
                    $_SESSION['is_Feuerwehr'] = $isFeuerwehr;
                }
            } else {
                $_SESSION['flash_message'] = implode('<br>', $errors);
                $_SESSION['flash_type'] = 'danger';
            }
        }
        
        // Benutzer löschen
        else if (isset($_POST['delete_user'])) {
            $userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
            
            // Verhindern, dass ein Admin sich selbst löscht
            if ($userId === $_SESSION['user_id']) {
                $_SESSION['flash_message'] = 'Sie können Ihr eigenes Konto nicht löschen.';
                $_SESSION['flash_type'] = 'danger';
            } else {
                $result = $user->deleteUser($userId);
                
                $_SESSION['flash_message'] = $result['message'];
                $_SESSION['flash_type'] = $result['success'] ? 'success' : 'danger';
            }
        }
    }
    
    // PRG-Muster: Nach POST-Anfrage zurück zur selben Seite weiterleiten, um erneutes Absenden zu verhindern
    header('Location: ' . getRelativePath('Admin/Benutzerverwaltung'));
    exit;
}

// Titel für die Seite
$pageTitle = 'Benutzer verwalten';

// Header einbinden
require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <h1 class="mb-4">Benutzer verwalten</h1>
        
        <!-- Button zum Öffnen des Modals -->
        <div class="mb-4">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newUserModal">
                <i class="bi bi-plus-circle"></i> Neuen Benutzer erstellen
            </button>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3>Benutzer</h3>
            </div>
            <div class="card-body">
                <?php if (empty($allUsers)): ?>
                    <div class="alert alert-info">
                        Keine Benutzer gefunden.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>E-Mail</th>
                                    <th>Telefon</th>
                                    <th>Status</th>
                                    <th>Registriert am</th>
                                    <th>Aktionen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allUsers as $userItem): ?>
                                    <tr>
                                        <td><?php echo $userItem['id']; ?></td>
                                        <td><?php echo escape($userItem['first_name'] . ' ' . $userItem['last_name']); ?></td>
                                        <td><?php echo escape($userItem['email']); ?></td>
                                        <td><?php echo escape($userItem['phone'] ?: '-'); ?></td>
                                        <td>
                                            <?php if ($userItem['is_verified']): ?>
                                                <span class="badge bg-success">Verifiziert</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Nicht verifiziert</span>
                                            <?php endif; ?>
                                            
                                            <?php if ($userItem['is_admin']): ?>
                                                <span class="badge bg-primary">Administrator</span>
                                            <?php endif; ?>
                                            
                                            <?php if (isset($userItem['is_Feuerwehr']) && $userItem['is_Feuerwehr']): ?>
                                                <span class="badge bg-danger">Feuerwehr</span>
                                            <?php endif; ?>
                                            
                                            <?php if (isset($userItem['is_AktivesMitglied']) && $userItem['is_AktivesMitglied']): ?>
                                                <span class="badge bg-info">Aktives Mitglied</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('d.m.Y H:i', strtotime($userItem['created_at'])); ?></td>
                                        <td>
                                            <button type="button" 
                                                    class="btn btn-info btn-sm" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editUserModal"
                                                    data-id="<?php echo $userItem['id']; ?>"
                                                    data-email="<?php echo escape($userItem['email']); ?>"
                                                    data-first-name="<?php echo escape($userItem['first_name']); ?>"
                                                    data-last-name="<?php echo escape($userItem['last_name']); ?>"
                                                    data-phone="<?php echo escape($userItem['phone']); ?>"
                                                    data-is-admin="<?php echo $userItem['is_admin']; ?>"
                                                    data-is-verified="<?php echo $userItem['is_verified']; ?>"
                                                    data-is-aktivesmitglied="<?php echo isset($userItem['is_AktivesMitglied']) && $userItem['is_AktivesMitglied'] ? 1 : 0; ?>"
                                                    data-is-feuerwehr="<?php echo isset($userItem['is_Feuerwehr']) && $userItem['is_Feuerwehr'] ? 1 : 0; ?>"
                                                    onclick="prepareEditUserModal(this)">
                                                <i class="bi bi-pencil"></i> Bearbeiten
                                            </button>
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

<?php require_once '../../includes/footer.php'; ?>

<!-- Modal für neuen Benutzer -->
<div class="modal fade" id="newUserModal" tabindex="-1" aria-labelledby="newUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newUserModalLabel">Neuen Benutzer erstellen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <div class="modal-body">
                <form method="post" id="createUserForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="create_user" value="1">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">Vorname</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Nachname</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">E-Mail-Adresse</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Telefonnummer (optional)</label>
                            <input type="tel" class="form-control" id="phone" name="phone">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Passwort</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="form-text">Mindestens 8 Zeichen.</div>
                        </div>
                        
                        <div class="col-md-6 mb-3 d-flex align-items-end">
                            <div class="form-check mt-4">
                                <input type="checkbox" class="form-check-input" id="is_admin" name="is_admin">
                                <label class="form-check-label" for="is_admin">Administrator-Berechtigungen</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3 d-flex align-items-start">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="is_verified" name="is_verified" checked>
                                <label class="form-check-label" for="is_verified">Benutzer verifiziert</label>
                                <div class="form-text">Wenn aktiviert, kann sich der Benutzer sofort einloggen ohne E-Mail-Bestätigung.</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3 d-flex flex-column">
                            <div class="form-check mt-1">
                                <input type="checkbox" class="form-check-input" id="is_aktivesmitglied" name="is_AktivesMitglied">
                                <label class="form-check-label" for="is_aktivesmitglied">Aktives Mitglied</label>
                                <div class="form-text">Ermöglicht vergünstigte Buchungen (<?php echo $aktivesPrice; ?>€ pro Tag).</div>
                            </div>
                            
                            <div class="form-check mt-2">
                                <input type="checkbox" class="form-check-input" id="is_feuerwehr" name="is_Feuerwehr">
                                <label class="form-check-label" for="is_feuerwehr">Feuerwehr</label>
                                <div class="form-text">Ermöglicht vergünstigte Buchungen (<?php echo $feuerwehrPrice; ?>€ pro Tag).</div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <button type="button" class="btn btn-primary" onclick="document.getElementById('createUserForm').submit();">Benutzer erstellen</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal zum Bearbeiten eines Benutzers -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel">Benutzer bearbeiten</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <div class="modal-body">
                <form method="post" id="editUserForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="edit_user" value="1">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_first_name" class="form-label">Vorname</label>
                            <input type="text" class="form-control" id="edit_first_name" name="first_name" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="edit_last_name" class="form-label">Nachname</label>
                            <input type="text" class="form-control" id="edit_last_name" name="last_name" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_email" class="form-label">E-Mail-Adresse</label>
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="edit_phone" class="form-label">Telefonnummer (optional)</label>
                            <input type="tel" class="form-control" id="edit_phone" name="phone">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_new_password" class="form-label">Neues Passwort (optional)</label>
                            <input type="password" class="form-control" id="edit_new_password" name="new_password">
                            <div class="form-text">Lassen Sie dieses Feld leer, um das Passwort nicht zu ändern. Andernfalls mindestens 8 Zeichen.</div>
                        </div>
                        
                        <div class="col-md-6 mb-3 d-flex flex-column justify-content-around">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="edit_is_admin" name="is_admin">
                                <label class="form-check-label" for="edit_is_admin">Administrator-Berechtigungen</label>
                            </div>
                            <div class="form-check mt-2">
                                <input type="checkbox" class="form-check-input" id="edit_is_verified" name="is_verified">
                                <label class="form-check-label" for="edit_is_verified">Benutzer verifiziert</label>
                                <div class="form-text">Wenn aktiviert, kann sich der Benutzer einloggen ohne E-Mail-Bestätigung.</div>
                            </div>
                            
                            <div class="form-check mt-2">
                                <input type="checkbox" class="form-check-input" id="edit_is_aktivesmitglied" name="is_AktivesMitglied">
                                <label class="form-check-label" for="edit_is_aktivesmitglied">Aktives Mitglied</label>
                                <div class="form-text">Ermöglicht vergünstigte Buchungen (<?php echo $aktivesPrice; ?>€ pro Tag).</div>
                            </div>
                            
                            <div class="form-check mt-2">
                                <input type="checkbox" class="form-check-input" id="edit_is_feuerwehr" name="is_Feuerwehr">
                                <label class="form-check-label" for="edit_is_feuerwehr">Feuerwehr</label>
                                <div class="form-text">Ermöglicht vergünstigte Buchungen (<?php echo $feuerwehrPrice; ?>€ pro Tag).</div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <div class="d-flex justify-content-between w-100">
                    <div>
                        <button type="button" id="deleteUserBtn" class="btn btn-danger" onclick="confirmDelete()">Benutzer löschen</button>
                    </div>
                    <div>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                        <button type="button" class="btn btn-primary" onclick="document.getElementById('editUserForm').submit();">Änderungen speichern</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Formular zum Löschen eines Benutzers (wird via JavaScript abgesendet) -->
<form method="post" id="deleteUserForm" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
    <input type="hidden" name="delete_user" value="1">
    <input type="hidden" name="user_id" id="delete_user_id">
</form>

<!-- JavaScript für die Formularvorausfüllung und das Löschen -->
<script>
function prepareEditUserModal(button) {
    // Daten aus dem Button-Attributen auslesen
    const userId = button.getAttribute('data-id');
    const email = button.getAttribute('data-email');
    const firstName = button.getAttribute('data-first-name');
    const lastName = button.getAttribute('data-last-name');
    const phone = button.getAttribute('data-phone');
    const isAdmin = button.getAttribute('data-is-admin') === '1';
    const isVerified = button.getAttribute('data-is-verified') === '1';
    const isAktivesMitglied = button.getAttribute('data-is-aktivesmitglied') === '1';
    const isFeuerwehr = button.getAttribute('data-is-feuerwehr') === '1';
    
    // Formularfelder ausfüllen
    document.getElementById('edit_user_id').value = userId;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_first_name').value = firstName;
    document.getElementById('edit_last_name').value = lastName;
    document.getElementById('edit_phone').value = phone || '';
    document.getElementById('edit_is_admin').checked = isAdmin;
    document.getElementById('edit_is_verified').checked = isVerified;
    document.getElementById('edit_is_aktivesmitglied').checked = isAktivesMitglied;
    document.getElementById('edit_is_feuerwehr').checked = isFeuerwehr;
    document.getElementById('edit_new_password').value = '';
    document.getElementById('delete_user_id').value = userId;
    
    // Eigenes Konto kann nicht gelöscht werden
    const sessionUserId = <?php echo $_SESSION['user_id']; ?>;
    if (userId == sessionUserId) {
        document.getElementById('deleteUserBtn').disabled = true;
        document.getElementById('deleteUserBtn').title = 'Sie können Ihr eigenes Konto nicht löschen';
    } else {
        document.getElementById('deleteUserBtn').disabled = false;
        document.getElementById('deleteUserBtn').title = '';
    }
}

function confirmDelete() {
    if (confirm('Sind Sie sicher, dass Sie diesen Benutzer löschen möchten? Alle Daten und Reservierungen dieses Benutzers werden unwiderruflich gelöscht!')) {
        document.getElementById('deleteUserForm').submit();
    }
}
</script> 