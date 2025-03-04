<?php
require_once '../backend/includes/auth.php';
require_once '../backend/includes/user.php';

$auth = new Auth();

// Redirect if not logged in or not admin
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ../login.php');
    exit();
}

$isAdmin = true;
$user = new User();
$allUsers = $user->getAllUsers();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Benutzerverwaltung - Grillhütte Rechenbach</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../frontend/css/styles.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/navigation.php'; ?>

    <main class="container my-5">
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2 class="card-title mb-0">Benutzerverwaltung</h2>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newUserModal">
                                <i class="bi bi-person-plus me-2"></i>Neuen Benutzer anlegen
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>E-Mail</th>
                                        <th>Status</th>
                                        <th>Registriert am</th>
                                        <th>Aktionen</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($allUsers['success']): ?>
                                        <?php foreach ($allUsers['users'] as $user): ?>
                                            <tr data-user-id="<?php echo $user['id']; ?>">
                                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td>
                                                    <span class="badge <?php echo $user['is_admin'] ? 'bg-danger' : 'bg-success'; ?>">
                                                        <?php echo $user['is_admin'] ? 'Administrator' : 'Benutzer'; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo (new DateTime($user['created_at']))->format('d.m.Y H:i'); ?></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <button type="button" class="btn btn-sm btn-outline-primary edit-user"
                                                                data-bs-toggle="modal" data-bs-target="#editUserModal"
                                                                data-user='<?php echo json_encode($user); ?>'>
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-danger delete-user"
                                                                    data-user-id="<?php echo $user['id']; ?>">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Benutzer bearbeiten</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editUserForm">
                        <input type="hidden" id="edit_user_id" name="user_id">
                        <div class="mb-3">
                            <label for="edit_user_name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="edit_user_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_user_email" class="form-label">E-Mail-Adresse</label>
                            <input type="email" class="form-control" id="edit_user_email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_user_password" class="form-label">Neues Passwort (optional)</label>
                            <input type="password" class="form-control" id="edit_user_password" name="password">
                            <div class="form-text">Leer lassen, um das Passwort nicht zu ändern.</div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="edit_user_is_admin" name="is_admin">
                                <label class="form-check-label" for="edit_user_is_admin">Administrator-Rechte</label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" form="editUserForm" class="btn btn-primary">Speichern</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../frontend/js/main.js"></script>
    <script>
        // Handle user editing
        document.querySelectorAll('.edit-user').forEach(button => {
            button.addEventListener('click', (e) => {
                const user = JSON.parse(e.target.closest('button').dataset.user);
                document.getElementById('edit_user_id').value = user.id;
                document.getElementById('edit_user_name').value = user.name;
                document.getElementById('edit_user_email').value = user.email;
                document.getElementById('edit_user_is_admin').checked = user.is_admin == 1;
            });
        });

        // Handle user deletion
        document.querySelectorAll('.delete-user').forEach(button => {
            button.addEventListener('click', async (e) => {
                if (!confirm('Möchten Sie diesen Benutzer wirklich löschen?')) return;
                
                const userId = e.target.closest('button').dataset.userId;
                
                try {
                    const response = await window.app.makeRequest('../backend/process_user_delete.php', {
                        method: 'POST',
                        body: JSON.stringify({ user_id: userId })
                    });

                    if (response.success) {
                        window.app.showAlert('Benutzer erfolgreich gelöscht!');
                        e.target.closest('tr').remove();
                    }
                } catch (error) {
                    console.error('Error:', error);
                }
            });
        });

        // Handle user editing form submission
        document.getElementById('editUserForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const userData = {
                user_id: formData.get('user_id'),
                name: formData.get('name'),
                email: formData.get('email'),
                password: formData.get('password'),
                is_admin: formData.get('is_admin') === 'on'
            };

            try {
                const response = await window.app.makeRequest('../backend/process_user_update.php', {
                    method: 'POST',
                    body: JSON.stringify(userData)
                });

                if (response.success) {
                    window.app.showAlert('Benutzer erfolgreich aktualisiert!');
                    window.location.reload();
                }
            } catch (error) {
                console.error('Error:', error);
            }
        });
    </script>
</body>
</html> 