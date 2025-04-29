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

// Dokumente aus der Datenbank abrufen (nur neueste Versionen)
try {
    $db = Database::getInstance()->getConnection();
    // Join mit fw_users, um die E-Mail des Uploaders zu erhalten
    // NEU: Nur Dokumente mit is_latest = 1 abrufen
    // NEU: version_group_id und Anzahl der Versionen pro Gruppe abrufen
    $stmt = $db->query("
        SELECT 
            d.*, 
            u.email as uploader_email,
            (SELECT COUNT(*) FROM verwaltung_dokumente v WHERE v.version_group_id = d.version_group_id) as version_count
        FROM verwaltung_dokumente d
        LEFT JOIN fw_users u ON d.hochgeladen_von_userid = u.id
        WHERE d.is_latest = 1
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
                        Erlaubte Typen: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, JPG, PNG, GIF, WEBP, TXT, CSV, SQL.<br>
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
                            <th>Neueste Version</th>
                            <th>Hochgeladen von</th>
                            <th>Versionen</th> 
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
                                <td></td> 
                            </tr>
                        <?php else: ?>
                            <?php foreach ($dokumente as $doc): ?>
                                <tr>
                                    <td>
                                        <a href="dokumente_download.php?id=<?php echo $doc['id']; ?>" target="_blank" title="Neueste Version ansehen/herunterladen">
                                            <i class="fas fa-file me-1"></i> <?php echo htmlspecialchars($doc['dateiname_original']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($doc['mime_typ']); ?></td>
                                    <td><?php echo formatBytes($doc['groesse']); ?></td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($doc['hochgeladen_am'])); ?></td>
                                    <td><?php echo htmlspecialchars($doc['uploader_email'] ?? 'Unbekannt'); ?></td>
                                    <td class="text-center">
                                        <?php if ($doc['version_count'] > 1): ?>
                                            <button class="btn btn-secondary btn-sm view-versions-btn" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#versionsModal"
                                                    data-group-id="<?php echo $doc['version_group_id']; ?>" 
                                                    data-doc-name="<?php echo htmlspecialchars($doc['dateiname_original']); ?>" 
                                                    title="Ältere Versionen anzeigen">
                                                <i class="fas fa-history"></i> (<?php echo $doc['version_count']; ?>)
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted">1</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="actions-column">
                                        <a href="dokumente_download.php?id=<?php echo $doc['id']; ?>" class="btn btn-success btn-sm me-1" title="Download">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <button class="btn btn-danger btn-sm delete-btn" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#deleteConfirmModal"
                                                data-doc-id="<?php echo $doc['id']; ?>" 
                                                data-doc-name="<?php echo htmlspecialchars($doc['dateiname_original']); ?> (Version vom <?php echo date('d.m.Y H:i', strtotime($doc['hochgeladen_am'])); ?>)" 
                                                title="Diese Version löschen">
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
                <h5 class="modal-title" id="deleteConfirmModalLabel">Version löschen?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Sind Sie sicher, dass Sie die Version "<strong id="docNameToDelete"></strong>" löschen möchten?
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

<!-- NEU: Versionen Anzeigen Modal -->
<div class="modal fade" id="versionsModal" tabindex="-1" aria-labelledby="versionsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="versionsModalLabel">Versionen für: <span id="versionDocName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="versionsTableContainer">
                    <p class="text-center">Lade Versionen...</p>
                    <!-- Tabelle wird hier per JS eingefügt -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
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
    const mainTable = $('#dokumenteTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/de-DE.json"
        },
        "order": [[ 3, "desc" ]], // Sortiere nach "Neueste Version" absteigend
        "columnDefs": [ 
            { "orderable": false, "targets": [5, 6] } // Spalten "Versionen" und "Aktionen" nicht sortierbar machen
        ]
    });

    // Löschen-Modal vorbereiten ( bleibt fast gleich, nur Titel angepasst )
    var deleteModal = document.getElementById('deleteConfirmModal');
    deleteModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var docId = button.getAttribute('data-doc-id');
        var docName = button.getAttribute('data-doc-name');
        deleteModal.querySelector('#docNameToDelete').textContent = docName;
        deleteModal.querySelector('#docIdToDelete').value = docId;
        deleteModal.querySelector('#deleteConfirmModalLabel').textContent = 'Version löschen?'; // Titel anpassen
    });

    // NEU: Versionen-Modal vorbereiten und laden
    var versionsModal = document.getElementById('versionsModal');
    var versionsTableContainer = document.getElementById('versionsTableContainer');
    var versionDocNameSpan = document.getElementById('versionDocName');

    versionsModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var groupId = button.getAttribute('data-group-id');
        var docName = button.getAttribute('data-doc-name');
        
        versionDocNameSpan.textContent = docName;
        versionsTableContainer.innerHTML = '<p class="text-center"><i class="fas fa-spinner fa-spin"></i> Lade Versionen...</p>'; // Ladeindikator

        // AJAX-Aufruf, um Versionen zu laden
        $.ajax({
            url: 'get_document_versions.php',
            type: 'GET',
            data: { group_id: groupId },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.versions.length > 0) {
                    let tableHtml = '<table class="table table-sm table-bordered table-striped"><thead><tr><th>Version vom</th><th>Größe</th><th>Uploader</th><th>Aktionen</th></tr></thead><tbody>';
                    response.versions.forEach(function(version) {
                        tableHtml += `<tr>
                            <td>${new Date(version.hochgeladen_am).toLocaleString('de-DE')} ${version.is_latest ? '<span class="badge bg-success ms-1">Neueste</span>' : ''}</td>
                            <td>${version.groesse_formatiert}</td>
                            <td>${version.uploader_email ? version.uploader_email : 'Unbekannt'}</td>
                            <td>
                                <a href="dokumente_download.php?id=${version.id}" class="btn btn-outline-success btn-sm" title="Download"><i class="fas fa-download"></i></a>
                            </td>
                        </tr>`;
                    });
                    tableHtml += '</tbody></table>';
                    versionsTableContainer.innerHTML = tableHtml;
                } else {
                    versionsTableContainer.innerHTML = '<p class="text-center text-danger">Konnte Versionen nicht laden oder keine vorhanden.</p>';
                }
            },
            error: function() {
                versionsTableContainer.innerHTML = '<p class="text-center text-danger">Fehler beim Laden der Versionen.</p>';
            }
        });
    });

});
</script>

</body>
</html> 