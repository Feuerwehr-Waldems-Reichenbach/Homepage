<?php
require_once '../../includes/config.php';
require_once '../../includes/Reservation.php';
// Generate CSRF token for forms
$_SESSION['csrf_token'] = generate_csrf_token();
// Verfolgen der aktiven Tab
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'system';
// Nur für angemeldete Administratoren zugänglich
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    $_SESSION['flash_message'] = 'Sie haben keine Berechtigung, auf diese Seite zuzugreifen.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ' . getRelativePath('home'));
    exit;
}
// Reservation-Objekt für Datenbankzugriff initialisieren
$reservation = new Reservation();
// Kategorien und ihre Anzeigenamen
$categoriesMap = [
    'system' => 'Systemeinstellungen',
    'grillhuette_info' => 'Informationen zur Grillhütte',
    'im_preis_enthalten' => 'Im Preis enthalten',
    'wichtige_hinweise' => 'Wichtige Hinweise'
];
// Validierung der aktiven Tab gegen verfügbare Kategorien
if (!isset($categoriesMap[$activeTab])) {
    $activeTab = 'system'; // Fallback auf system, wenn ungültige Tab
}
// Bearbeitbare vs. nicht bearbeitbare Kategorien
$editableCategories = ['grillhuette_info', 'im_preis_enthalten', 'wichtige_hinweise'];
$systemCategory = 'system';
try {
    // Datenbankverbindung testen
    $db = Database::getInstance()->getConnection();
    // Anzahl der Datensätze pro Kategorie zählen
    foreach (array_keys($categoriesMap) as $cat) {
        // Anzahl der Datensätze pro Kategorie zählen
        $stmt = $db->prepare("SELECT COUNT(*) FROM gh_informations WHERE category = ?");
        $stmt->execute([$cat]);
    }
    // Gesamtzahl der Datensätze
    $stmt = $db->prepare("SELECT COUNT(*) FROM gh_informations");
    $stmt->execute();
} catch (Exception $e) {
}
// Alle Informationen abrufen (mit allen Feldern)
$allInformations = $reservation->getSystemInformationByCreationTime();
// Informationen nach Kategorien gruppieren
$groupedInfo = [];
foreach ($allInformations as $info) {
    $category = $info['category'] ?? 'system';
    if (!isset($groupedInfo[$category])) {
        $groupedInfo[$category] = [];
    }
    $groupedInfo[$category][] = $info;
}
// Alle POST-Anfragen abfangen und PRG-Muster anwenden
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF-Token überprüfen
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['flash_message'] = 'Ungültige Anfrage. Bitte versuchen Sie es erneut.';
        $_SESSION['flash_type'] = 'danger';
    } else {
        // Information aktualisieren
        if (isset($_POST['update_info'])) {
            $infoId = isset($_POST['info_id']) ? intval($_POST['info_id']) : 0;
            $infoContent = isset($_POST['info_content']) ? trim($_POST['info_content']) : '';
            $sortOrder = isset($_POST['info_sort_order']) ? intval($_POST['info_sort_order']) : null;
            // Aktive Tab aus der Form abrufen
            $activeTab = isset($_POST['active_tab']) ? $_POST['active_tab'] : 'system';
            if (empty($infoId)) {
                $_SESSION['flash_message'] = 'Keine gültige Information ausgewählt.';
                $_SESSION['flash_type'] = 'danger';
            } else {
                // Sortierreihenfolge nur für nicht-System-Einträge aktualisieren
                $result = $reservation->updateInformation($infoId, $infoContent, null, $sortOrder);
                $_SESSION['flash_message'] = $result['message'];
                $_SESSION['flash_type'] = $result['success'] ? 'success' : 'danger';
            }
        }
        // Neue Information hinzufügen
        else if (isset($_POST['add_info'])) {
            $infoTitle = isset($_POST['info_title']) ? trim($_POST['info_title']) : '';
            $infoContent = isset($_POST['info_content']) ? trim($_POST['info_content']) : '';
            $infoCategory = isset($_POST['info_category']) ? trim($_POST['info_category']) : '';
            $infoSortOrder = isset($_POST['info_sort_order']) ? intval($_POST['info_sort_order']) : 10;
            // Aktive Tab aus der Form abrufen
            $activeTab = isset($_POST['active_tab']) ? $_POST['active_tab'] : $infoCategory;
            if (empty($infoTitle) || empty($infoContent) || empty($infoCategory)) {
                $_SESSION['flash_message'] = 'Bitte füllen Sie alle Pflichtfelder aus.';
                $_SESSION['flash_type'] = 'danger';
            } elseif (!in_array($infoCategory, $editableCategories)) {
                $_SESSION['flash_message'] = 'Die ausgewählte Kategorie kann nicht bearbeitet werden.';
                $_SESSION['flash_type'] = 'danger';
            } else {
                $result = $reservation->addInformation($infoTitle, $infoContent, $infoCategory, $infoSortOrder);
                $_SESSION['flash_message'] = $result['message'];
                $_SESSION['flash_type'] = $result['success'] ? 'success' : 'danger';
            }
        }
        // Information löschen
        else if (isset($_POST['delete_info'])) {
            $infoId = isset($_POST['info_id']) ? intval($_POST['info_id']) : 0;
            // Aktive Tab aus der Form abrufen
            $activeTab = isset($_POST['active_tab']) ? $_POST['active_tab'] : 'system';
            if (empty($infoId)) {
                $_SESSION['flash_message'] = 'Keine gültige Information ausgewählt.';
                $_SESSION['flash_type'] = 'danger';
            } else {
                $result = $reservation->deleteInformation($infoId);
                $_SESSION['flash_message'] = $result['message'];
                $_SESSION['flash_type'] = $result['success'] ? 'success' : 'danger';
            }
        }
    }
    // PRG-Muster: Nach POST-Anfrage zurück zur selben Seite weiterleiten, um erneutes Absenden zu verhindern
    header('Location: ' . getRelativePath('Admin/Informationsverwaltung') . '?tab=' . $activeTab);
    exit;
}
// Titel für die Seite
$pageTitle = 'Informationen verwalten';
// Header einbinden
require_once '../../includes/header.php';
?>
<div class="row">
    <div class="col-md-12">
        <h1 class="mb-4">Informationen verwalten</h1>
        <div class="alert alert-info mb-4">
            <p class="mb-0">Hier können Sie alle Informationen bearbeiten, die auf der Website angezeigt werden,
                insbesondere im Willkommensbereich auf der Startseite. Sie können dynamisch weitere Informationen zu den
                Kategorien "Informationen zur Grillhütte", "Im Preis enthalten" und "Wichtige Hinweise" hinzufügen oder
                entfernen.</p>
        </div>
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="mb-0">Informationen</h3>
            </div>
            <div class="card-body">
                <ul class="nav nav-tabs" id="infoTabs" role="tablist">
                    <?php
                    $first = true;
                    foreach ($categoriesMap as $category => $displayName):
                        $isActive = ($category === $activeTab);
                        ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?php echo $isActive ? 'active' : ''; ?>"
                                id="<?php echo str_replace('_', '-', $category); ?>-tab" data-bs-toggle="tab"
                                data-bs-target="#<?php echo str_replace('_', '-', $category); ?>" type="button" role="tab"
                                aria-controls="<?php echo str_replace('_', '-', $category); ?>"
                                aria-selected="<?php echo $isActive ? 'true' : 'false'; ?>">
                                <?php echo $displayName; ?>
                            </button>
                        </li>
                    <?php
                    endforeach;
                    ?>
                </ul>
                <div class="tab-content" id="infoTabsContent">
                    <?php
                    foreach ($categoriesMap as $category => $displayName):
                        $infos = $groupedInfo[$category] ?? [];
                        $isActive = ($category === $activeTab);
                        ?>
                        <div class="tab-pane fade <?php echo $isActive ? 'show active' : ''; ?>"
                            id="<?php echo str_replace('_', '-', $category); ?>" role="tabpanel"
                            aria-labelledby="<?php echo str_replace('_', '-', $category); ?>-tab">
                            <?php if (in_array($category, $editableCategories)): ?>
                                <div class="mt-3 mb-4">
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                        data-bs-target="#addInfoModal<?php echo $category; ?>">
                                        <i class="bi bi-plus-circle"></i> Neue Information hinzufügen
                                    </button>
                                </div>
                            <?php endif; ?>
                            <div class="row mt-3">
                                <?php if (empty($infos)): ?>
                                    <div class="col-12">
                                        <div class="alert alert-info">
                                            Keine Informationen in dieser Kategorie gefunden.
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <?php
                                    // Array mit benutzerfreundlichen Titeln für Systemeinstellungen
                                    $friendlyTitles = [
                                        // Preisdaten
                                        'MietpreisNormal' => 'Mietpreis (Normal)',
                                        'MietpreisAktivesMitglied' => 'Mietpreis (Aktives Mitglied)',
                                        'MietpreisFeuerwehr' => 'Mietpreis (Feuerwehr)',
                                        'Kautionspreis' => 'Kautionspreis',
                                        // Verwaltungsperson
                                        'VerwaltungspersonVorname' => 'Vorname der Verwaltungsperson',
                                        'VerwaltungspersonNachname' => 'Nachname der Verwaltungsperson',
                                        'VerwaltungspersonTelefon' => 'Telefon der Verwaltungsperson',
                                        'VerwaltungspersonEmail' => 'E-Mail der Verwaltungsperson',
                                        // System E-Mails
                                        'SystemEmail' => 'System E-Mail-Adresse',
                                        'SystemEmailProbleme' => 'Support E-Mail-Adresse',
                                        // Buchungsdetails
                                        'UebergabeZeit' => 'Übergabezeit',
                                        'MinBuchungszeitraum' => 'Minimaler Buchungszeitraum',
                                        'RueckgabeText' => 'Rückgabetext',
                                        // Willkommenstexte
                                        'WillkommensText' => 'Willkommenstext (Überschrift)',
                                        'WillkommensUntertext' => 'Willkommenstext (Untertitel)',
                                        // Generischer Titel für andere Kategorien
                                        '_default' => 'Information'
                                    ];
                                    foreach ($infos as $info):
                                        // Bestimme den anzuzeigenden Titel
                                        if ($category === 'system') {
                                            $displayTitle = $friendlyTitles[$info['title']] ?? $info['title'];
                                        } else {
                                            // Kategorie-spezifische statische Titel
                                            switch ($category) {
                                                case 'grillhuette_info':
                                                    $displayTitle = 'Information zur Grillhütte';
                                                    break;
                                                case 'im_preis_enthalten':
                                                    $displayTitle = 'Im Preis enthalten';
                                                    break;
                                                case 'wichtige_hinweise':
                                                    $displayTitle = 'Wichtiger Hinweis';
                                                    break;
                                                default:
                                                    $displayTitle = 'Information';
                                            }
                                            // Einfaches Nummerierungssystem für mehrere Einträge
                                            if (count($groupedInfo[$category]) > 1) {
                                                // Finde die Position in der sortierten Liste
                                                $position = array_search($info, $infos) + 1;
                                                $displayTitle .= ' #' . $position;
                                            }
                                        }
                                        ?>
                                        <div class="col-md-6 mb-4">
                                            <div class="card h-100">
                                                <div class="card-header d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h5 class="mb-0"><?php echo $displayTitle; ?></h5>
                                                        <?php if ($category !== 'system'): ?>
                                                            <small class="text-muted">Sortierung: <span
                                                                    class="badge bg-light text-dark"><?php echo $info['sort_order']; ?></span></small>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if (in_array($category, $editableCategories)): ?>
                                                        <form method="post" class="d-inline"
                                                            onsubmit="return confirm('Sind Sie sicher, dass Sie diese Information löschen möchten?');">
                                                            <input type="hidden" name="csrf_token"
                                                                value="<?php echo $_SESSION['csrf_token']; ?>">
                                                            <input type="hidden" name="info_id" value="<?php echo $info['id']; ?>">
                                                            <input type="hidden" name="active_tab" value="<?php echo $category; ?>">
                                                            <button type="submit" name="delete_info"
                                                                class="btn btn-sm btn-outline-danger">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="card-body">
                                                    <form method="post">
                                                        <input type="hidden" name="csrf_token"
                                                            value="<?php echo $_SESSION['csrf_token']; ?>">
                                                        <input type="hidden" name="info_id" value="<?php echo $info['id']; ?>">
                                                        <input type="hidden" name="active_tab" value="<?php echo $category; ?>">
                                                        <!-- Wenn kein System-Eintrag, zeige den Datenbankschlüssel als Hinweis -->
                                                        <?php if ($category !== 'system'): ?>
                                                            <div class="mb-2">
                                                                <small class="text-muted">Datenbankschlüssel:
                                                                    <?php echo $info['title']; ?></small>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div class="mb-3">
                                                            <label for="info_content_<?php echo $info['id']; ?>"
                                                                class="form-label">Inhalt</label>
                                                            <textarea class="form-control"
                                                                id="info_content_<?php echo $info['id']; ?>" name="info_content"
                                                                rows="3" <?php echo $category === $systemCategory ? '' : ''; ?>><?php echo htmlspecialchars($info['content']); ?></textarea>
                                                            <?php if (in_array($info['title'], ['MietpreisNormal', 'MietpreisAktivesMitglied', 'MietpreisFeuerwehr', 'Kautionspreis'])): ?>
                                                                <div class="form-text">Bitte mit €-Zeichen eingeben, z. B. "100€"</div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <?php if (in_array($category, $editableCategories)): ?>
                                                            <div class="mb-3">
                                                                <label for="info_sort_order_<?php echo $info['id']; ?>"
                                                                    class="form-label">Sortierreihenfolge</label>
                                                                <input type="number" class="form-control"
                                                                    id="info_sort_order_<?php echo $info['id']; ?>"
                                                                    name="info_sort_order" value="<?php echo $info['sort_order']; ?>"
                                                                    min="1">
                                                                <div class="form-text">Kleinere Zahlen werden weiter oben angezeigt.
                                                                    <strong>Hinweis:</strong> Die Sortierreihenfolge bestimmt nur die
                                                                    Anzeigereihenfolge auf der Website, nicht in der Verwaltung.</div>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div class="d-grid">
                                                            <button type="submit" name="update_info"
                                                                class="btn btn-primary">Aktualisieren</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php
                    endforeach;
                    ?>
                </div>
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">
                <h3 class="mb-0">Vorschau</h3>
            </div>
            <div class="card-body">
                <?php
                // Systeminformationen für die Vorschau abrufen
                $systemInfos = $reservation->getSystemInformation();
                $grillhuetteInfos = $reservation->getSystemInformation([], 'grillhuette_info');
                $imPreisEnthalten = $reservation->getSystemInformation([], 'im_preis_enthalten');
                $wichtigeHinweise = $reservation->getSystemInformation([], 'wichtige_hinweise');
                ?>
                <h5 class="card-title">
                    <?php echo $systemInfos['WillkommensText'] ?? 'Willkommen im Reservierungssystem der Grillhütte Waldems Reichenbach'; ?>
                </h5>
                <p><?php echo $systemInfos['WillkommensUntertext'] ?? 'Hier können Sie freie Termine einsehen und eine Reservierung vornehmen.'; ?>
                </p>
                <hr>
                <h5 class="card-title">Informationen zur Grillhütte</h5>
                <ul class="list-unstyled">
                    <li><strong>Miete:</strong> <?php echo $systemInfos['MietpreisNormal'] ?? '100€'; ?> pro Tag
                        (<?php echo $systemInfos['UebergabeZeit'] ?? '12 - 12 Uhr'; ?>)</li>
                    <li><strong>Kaution:</strong> <?php echo $systemInfos['Kautionspreis'] ?? '100€'; ?></li>
                    <li><strong>Rückgabe:</strong>
                        <?php echo $systemInfos['RueckgabeText'] ?? 'bis spätestens am nächsten Tag 12:00 Uhr'; ?></li>
                    <li><strong>Min. Buchungszeitraum:</strong>
                        <?php echo $systemInfos['MinBuchungszeitraum'] ?? '1 Tag'; ?></li>
                    <?php
                    // Zusätzliche Informationen zur Grillhütte
                    $counter = 1;
                    foreach ($grillhuetteInfos as $title => $content):
                        ?>
                        <li><?php echo $content; ?></li>
                        <?php
                        $counter++;
                    endforeach;
                    ?>
                </ul>
                <h6>Im Mietzins enthalten:</h6>
                <ul>
                    <?php if (!empty($imPreisEnthalten)): ?>
                        <?php
                        $counter = 1;
                        foreach ($imPreisEnthalten as $title => $content):
                            ?>
                            <li><?php echo $content; ?></li>
                            <?php
                            $counter++;
                        endforeach;
                        ?>
                    <?php else: ?>
                        <li>1m³ Wasser</li>
                        <li>5 kW/h Strom</li>
                        <li>5 Biertisch-Garnituren, jede weitere Garnitur zzgl. 1€</li>
                    <?php endif; ?>
                </ul>
                <div class="alert alert-info">
                    <p class="mb-1"><strong>Wichtige Hinweise:</strong></p>
                    <ul class="mb-0">
                        <?php if (!empty($wichtigeHinweise)): ?>
                            <?php
                            $counter = 1;
                            foreach ($wichtigeHinweise as $title => $content):
                                ?>
                                <li><?php echo $content; ?></li>
                                <?php
                                $counter++;
                            endforeach;
                            ?>
                        <?php else: ?>
                            <li>Die Grillhütte sowie die Toiletten sind sauber zu verlassen</li>
                            <li>Müll ist selbst zu entsorgen</li>
                        <?php endif; ?>
                    </ul>
                </div>
                <h6>Schlüsselübergabe und Abnahme:</h6>
                <p><?php echo $systemInfos['VerwaltungspersonVorname'] ?? 'Julia'; ?>
                    <?php echo $systemInfos['VerwaltungspersonNachname'] ?? 'Kitschmann'; ?></p>
                <div class="mt-3">
                    <p><strong>Kontakt zur Verwalterin:</strong></p>
                    <ul class="list-unstyled">
                        <li><?php echo $systemInfos['VerwaltungspersonEmail'] ?? 'julia@kitschmann.de'; ?></li>
                        <li><?php echo $systemInfos['VerwaltungspersonTelefon'] ?? '0178/8829055'; ?></li>
                    </ul>
                </div>
                <div class="mt-3 alert alert-secondary">
                    <p class="mb-0"><strong>Hinweis:</strong> Bei technischen Problemen mit dem Reservierungssystem
                        wenden Sie sich bitte an:
                        <?php echo $systemInfos['SystemEmailProbleme'] ?? 'hilfe@feuerwehr-waldems-reichenbach.de'; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modals zum Hinzufügen von Informationen für jede bearbeitbare Kategorie -->
<?php foreach ($editableCategories as $category): ?>
    <div class="modal fade" id="addInfoModal<?php echo $category; ?>" tabindex="-1"
        aria-labelledby="addInfoModal<?php echo $category; ?>Label" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addInfoModal<?php echo $category; ?>Label">
                        <?php
                        $modalTitle = '';
                        switch ($category) {
                            case 'grillhuette_info':
                                $modalTitle = 'Neue Information zur Grillhütte hinzufügen';
                                break;
                            case 'im_preis_enthalten':
                                $modalTitle = 'Neue Position "Im Preis enthalten" hinzufügen';
                                break;
                            case 'wichtige_hinweise':
                                $modalTitle = 'Neuen wichtigen Hinweis hinzufügen';
                                break;
                            default:
                                $modalTitle = 'Neue Information hinzufügen';
                        }
                        echo $modalTitle;
                        ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>
                <div class="modal-body">
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="info_category" value="<?php echo $category; ?>">
                        <input type="hidden" name="active_tab" value="<?php echo $category; ?>">
                        <div class="mb-3">
                            <label for="info_title_<?php echo $category; ?>" class="form-label">Schlüssel/ID</label>
                            <input type="text" class="form-control" id="info_title_<?php echo $category; ?>"
                                name="info_title" required>
                            <div class="form-text">
                                <?php
                                $titleHelp = '';
                                $categoryPrefix = '';
                                switch ($category) {
                                    case 'grillhuette_info':
                                        $categoryPrefix = 'GrillhuetteInfo';
                                        $titleHelp = 'Eindeutiger Schlüssel für eine Information über die Grillhütte.';
                                        break;
                                    case 'im_preis_enthalten':
                                        $categoryPrefix = 'ImPreisEnthalten';
                                        $titleHelp = 'Eindeutiger Schlüssel für eine im Preis enthaltene Leistung.';
                                        break;
                                    case 'wichtige_hinweise':
                                        $categoryPrefix = 'WichtigerHinweis';
                                        $titleHelp = 'Eindeutiger Schlüssel für einen wichtigen Hinweis.';
                                        break;
                                    default:
                                        $titleHelp = 'Eindeutiger Schlüssel für die Information.';
                                }
                                $nextNumber = count($groupedInfo[$category] ?? []) + 1;
                                $exampleKey = $categoryPrefix . $nextNumber;
                                ?>
                                <?php echo $titleHelp; ?> Beispiel: "<strong><?php echo $exampleKey; ?></strong>"
                                <br><strong>Hinweis:</strong> Der Schlüssel muss eindeutig sein und darf nicht bereits
                                existieren.
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="info_content_<?php echo $category; ?>" class="form-label">
                                <?php
                                switch ($category) {
                                    case 'grillhuette_info':
                                        echo 'Information zur Grillhütte';
                                        break;
                                    case 'im_preis_enthalten':
                                        echo 'Enthaltene Leistung';
                                        break;
                                    case 'wichtige_hinweise':
                                        echo 'Hinweistext';
                                        break;
                                    default:
                                        echo 'Inhalt';
                                }
                                ?>
                            </label>
                            <textarea class="form-control" id="info_content_<?php echo $category; ?>" name="info_content"
                                rows="3" required placeholder="<?php
                                switch ($category) {
                                    case 'grillhuette_info':
                                        echo 'z. B. "Die Grillhütte verfügt über eine moderne Ausstattung."';
                                        break;
                                    case 'im_preis_enthalten':
                                        echo 'z. B. "1m³ Wasser"';
                                        break;
                                    case 'wichtige_hinweise':
                                        echo 'z. B. "Die Grillhütte ist sauber zu hinterlassen."';
                                        break;
                                    default:
                                        echo '';
                                }
                                ?>"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="info_sort_order_<?php echo $category; ?>"
                                class="form-label">Sortierreihenfolge</label>
                            <input type="number" class="form-control" id="info_sort_order_<?php echo $category; ?>"
                                name="info_sort_order" value="<?php echo count($groupedInfo[$category] ?? []) + 1; ?>"
                                min="1">
                            <div class="form-text">Kleinere Zahlen werden weiter oben angezeigt. <strong>Hinweis:</strong>
                                Die Sortierreihenfolge bestimmt nur die Anzeigereihenfolge auf der Website, nicht in der
                                Verwaltung.</div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" name="add_info" class="btn btn-primary">Hinzufügen</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>
<!-- JavaScript für Tab-Aktivierung basierend auf URL-Parameter -->
<script nonce="<?php echo $cspNonce; ?>">
    document.addEventListener('DOMContentLoaded', function () {
        // Aktive Tab bei Seitenaufruf auswählen
        const urlParams = new URLSearchParams(window.location.search);
        const activeTab = urlParams.get('tab');
        if (activeTab) {
            // Tab-Button aktivieren
            const tabId = activeTab.replace(/_/g, '-');
            const tabElement = document.getElementById(tabId + '-tab');
            if (tabElement) {
                const tab = new bootstrap.Tab(tabElement);
                tab.show();
            }
        }
    });
</script>
<?php require_once '../../includes/footer.php'; ?>