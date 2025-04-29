<?php
require_once dirname(__DIR__, 2) . '/Private/Database/Database.php';
require_once __DIR__ . '/includes/config.php'; // Stellt sicher, dass Session gestartet und Benutzer geprüft wird

// Überprüfen, ob der Benutzer ein Admin ist
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    $_SESSION['error'] = 'Zugriff verweigert.';
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// CSRF-Token generieren, falls noch nicht vorhanden
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

$pageTitle = "Dokumentenverwaltung";
include __DIR__ . '/templates/header.php';

// Dokumente aus der Datenbank abrufen
try {
    $db = Database::getInstance()->getConnection();
    // Join mit fw_users, um die E-Mail des Uploaders zu erhalten (ersetzt u.username)
    $stmt = $db->query("
        SELECT d.*, u.email as uploader_email 
        FROM verwaltung_dokumente d
        LEFT JOIN fw_users u ON d.hochgeladen_von_userid = u.id
        ORDER BY d.hochgeladen_am DESC
    ");
    $dokumente = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Fehler beim Laden der Dokumente: " . $e->getMessage();
    $dokumente = [];
    // Fehler loggen
    error_log("Fehler beim Abrufen von Dokumenten: " . $e->getMessage());
}

// Funktion zum Formatieren der Dateigröße
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, $precision) . ' ' . $units[$pow];
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Dokumentenverwaltung</h1>

    <?php include __DIR__ . '/templates/alert_messages.php'; ?>

    <!-- Dokument hochladen Card -->
    <div class="card glass-card mb-4">
        <div class="card-header">
            <i class="fas fa-upload me-1"></i>
            Neues Dokument hochladen
        </div>
        <div class="card-body">
            <form action="dokumente_upload.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <div class="mb-3">
                    <label for="dokument" class="form-label">Datei auswählen</label>
                    <input class="form-control" type="file" id="dokument" name="dokument" required>
                    <div class="form-text">
                        Erlaubte Typen: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, JPG, PNG, GIF, WEBP, TXT.<br>
                        Maximale Größe: 20 MB.
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-cloud-upload-alt me-2"></i>Hochladen
                </button>
            </form>
        </div>
    </div>

    <!-- Dokumentenliste Card -->
    <div class="card glass-card mb-4">
        <div class="card-header">
            <i class="fas fa-file-alt me-1"></i>
            Hochgeladene Dokumente
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover" id="dokumenteTable">
                    <thead class="table-light">
                        <tr>
                            <th>Dateiname</th>
                            <th>Typ</th>
                            <th>Größe</th>
                            <th>Hochgeladen am</th>
                            <th>Hochgeladen von</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dokumente)): ?>
                            <tr>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td>Keine Dokumente vorhanden.</td>
                                <td></td>
                                <td></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($dokumente as $doc): ?>
                                <tr>
                                    <td>
                                        <a href="dokumente_download.php?id=<?php echo $doc['id']; ?>" target="_blank" title="Dokument ansehen/herunterladen">
                                            <i class="fas fa-file me-1"></i> <?php echo htmlspecialchars($doc['dateiname_original']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($doc['mime_typ']); ?></td>
                                    <td><?php echo formatBytes($doc['groesse']); ?></td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($doc['hochgeladen_am'])); ?></td>
                                    <td><?php echo htmlspecialchars($doc['uploader_email'] ?? 'Unbekannt'); ?></td>
                                    <td class="actions-column">
                                        <a href="dokumente_download.php?id=<?php echo $doc['id']; ?>" class="btn btn-success btn-sm me-1" title="Download">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <button class="btn btn-danger btn-sm delete-btn" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#deleteConfirmModal"
                                                data-doc-id="<?php echo $doc['id']; ?>" 
                                                data-doc-name="<?php echo htmlspecialchars($doc['dateiname_original']); ?>" 
                                                title="Löschen">
                                            <i class="fas fa-trash"></i>
                                        </button>
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

<!-- Löschen Bestätigungsmodal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteConfirmModalLabel">Löschen bestätigen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Sind Sie sicher, dass Sie das Dokument "<strong id="docNameToDelete"></strong>" löschen möchten?
                 Diese Aktion kann nicht rückgängig gemacht werden.
            </div>
            <div class="modal-footer">
                <form id="deleteForm" action="dokumente_delete.php" method="post" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="id" id="docIdToDelete">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-danger">Löschen</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Leere die Variable, da das Skript jetzt direkt eingebunden wird
$additionalScripts = ''; 

// Füge CSS für DataTables hinzu (kann im Header oder hier sein)
?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<?php
include __DIR__ . '/templates/footer.php'; // Footer einbinden
?>

<!-- JavaScript für DataTables und Löschbestätigung (nach dem Footer/jQuery etc.) -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    $('#dokumenteTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/de-DE.json"
        },
        "order": [[ 3, "desc" ]] // Sortiere nach "Hochgeladen am" absteigend (Spalte Index 3)
    });

    // Löschen-Modal vorbereiten
    var deleteModal = document.getElementById('deleteConfirmModal');
    deleteModal.addEventListener('show.bs.modal', function (event) {
        // Button that triggered the modal
        var button = event.relatedTarget;
        // Extract info from data-* attributes
        var docId = button.getAttribute('data-doc-id');
        var docName = button.getAttribute('data-doc-name');
        
        // Update the modal's content.
        var modalTitle = deleteModal.querySelector('.modal-title');
        var modalBodyStrong = deleteModal.querySelector('#docNameToDelete');
        var deleteFormInput = deleteModal.querySelector('#docIdToDelete');

        //modalTitle.textContent = 'Dokument löschen'; // Optional: Titel anpassen
        modalBodyStrong.textContent = docName;
        deleteFormInput.value = docId;
    });
});
</script>

</body>
</html> 