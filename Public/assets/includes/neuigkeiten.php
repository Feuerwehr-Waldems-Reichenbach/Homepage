<?php
require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/Private/Database/Database.php';

/**
 * Zeigt Neuigkeiten in Kartenform an.
 * 
 * @param int $limit Optional. Begrenzt die Anzahl der angezeigten Einträge
 * @param string $customClass Optional. Zusätzliche CSS-Klassen für den Container
 * @return string HTML-Output
 */
function showNeuigkeiten($limit = null, $customClass = '') {
    // CSS-Styles einfügen
    $output = getNeuigkeitenStyles();
    
    // Neuigkeiten abrufen und anzeigen
    $db = Database::getInstance()->getConnection();
    
    $sql = "SELECT * FROM neuigkeiten WHERE aktiv = 1 ORDER BY Datum DESC";
    
    if ($limit !== null && is_numeric($limit)) {
        $sql .= " LIMIT :limit";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    } else {
        $stmt = $db->prepare($sql);
    }
    
    $stmt->execute();
    $neuigkeiten = $stmt->fetchAll();
    
    $output .= '<div class="row ffr-neuigkeiten-container ' . $customClass . '">';
    $modals = '';
    
    foreach ($neuigkeiten as $neuigkeit) {
        $bildPfad = $neuigkeit['path_to_image'] ?? '/Veranstaltungen/Flyer/default.jpg';
        $datum = date('d.m.Y', strtotime($neuigkeit['Datum']));
        $id = $neuigkeit['ID'];
        
        $output .= '
        <div class="col-md-4 col-sm-6 mb-4">
            <div class="ffr-neuigkeit-card">
                <div class="ffr-neuigkeit-img-wrapper">
                    <img src="' . htmlspecialchars($bildPfad) . '" class="ffr-neuigkeit-img" alt="' . htmlspecialchars($neuigkeit['Ueberschrift']) . '">
                </div>
                <div class="ffr-neuigkeit-body">
                    <h5 class="ffr-neuigkeit-title">' . htmlspecialchars($neuigkeit['Ueberschrift']) . '</h5>
                    <h6 class="ffr-neuigkeit-subtitle">' . $datum . ' | ' . htmlspecialchars($neuigkeit['Ort']) . '</h6>
                    <p class="ffr-neuigkeit-text">' . htmlspecialchars($neuigkeit['kurzinfo']) . '</p>
                </div>
                <div class="ffr-neuigkeit-footer">
                    <button type="button" class="ffr-neuigkeit-btn" data-bs-toggle="modal" data-bs-target="#ffr-neuigkeitModal' . $id . '">
                        Mehr erfahren
                    </button>
                </div>
            </div>
        </div>';
        
        // Modal für jede Neuigkeit
        $modals .= erstelleNeuigkeitModal($neuigkeit);
    }
    
    $output .= '</div>';
    
    // Modals am Ende anhängen
    $output .= $modals;
    
    if (empty($neuigkeiten)) {
        $output = getNeuigkeitenStyles() . '<div class="alert alert-info">Aktuell sind keine Neuigkeiten vorhanden.</div>';
    }
    
    return $output;
}

/**
 * Zeigt Neuigkeiten in einer Listenansicht an.
 * 
 * @param int $limit Optional. Begrenzt die Anzahl der angezeigten Einträge
 * @param string $customClass Optional. Zusätzliche CSS-Klassen für den Container
 * @return string HTML-Output
 */
function showNeuigkeitenListe($limit = null, $customClass = '') {
    // CSS-Styles einfügen
    $output = getNeuigkeitenStyles();
    
    // Neuigkeiten abrufen und anzeigen
    $db = Database::getInstance()->getConnection();
    
    $sql = "SELECT * FROM neuigkeiten WHERE aktiv = 1 ORDER BY Datum DESC";
    
    if ($limit !== null && is_numeric($limit)) {
        $sql .= " LIMIT :limit";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    } else {
        $stmt = $db->prepare($sql);
    }
    
    $stmt->execute();
    $neuigkeiten = $stmt->fetchAll();
    
    $output .= '<div class="ffr-neuigkeiten-liste ' . $customClass . '">';
    $modals = '';
    
    foreach ($neuigkeiten as $neuigkeit) {
        $datum = date('d.m.Y', strtotime($neuigkeit['Datum']));
        $id = $neuigkeit['ID'];
        
        $output .= '
        <div class="ffr-neuigkeit-list-item">
            <div class="ffr-neuigkeit-list-header">
                <h5 class="ffr-neuigkeit-list-title">' . htmlspecialchars($neuigkeit['Ueberschrift']) . '</h5>
                <small class="ffr-neuigkeit-list-date">' . $datum . '</small>
            </div>
            <p class="ffr-neuigkeit-list-text">' . htmlspecialchars($neuigkeit['kurzinfo']) . '</p>
            <small class="ffr-neuigkeit-list-location">' . htmlspecialchars($neuigkeit['Ort']) . '</small>
            <div class="mt-2">
                <button type="button" class="ffr-neuigkeit-btn-sm" data-bs-toggle="modal" data-bs-target="#ffr-neuigkeitModal' . $id . '">
                    Mehr erfahren
                </button>
            </div>
        </div>';
        
        // Modal für jede Neuigkeit
        $modals .= erstelleNeuigkeitModal($neuigkeit);
    }
    
    $output .= '</div>';
    
    // Modals am Ende anhängen
    $output .= $modals;
    
    if (empty($neuigkeiten)) {
        $output = getNeuigkeitenStyles() . '<div class="alert alert-info">Aktuell sind keine Neuigkeiten vorhanden.</div>';
    }
    
    return $output;
}

/**
 * Zeigt eine einzelne Neuigkeit detailliert an
 * 
 * @param int $id ID der Neuigkeit
 * @param string $customClass Optional. Zusätzliche CSS-Klassen für den Container
 * @return string HTML-Output
 */
function showNeuigkeitDetail($id, $customClass = '') {
    // CSS-Styles einfügen
    $output = getNeuigkeitenStyles();
    
    // Neuigkeit abrufen
    $db = Database::getInstance()->getConnection();
    
    $sql = "SELECT * FROM neuigkeiten WHERE ID = :id";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    
    $neuigkeit = $stmt->fetch();
    
    if (!$neuigkeit) {
        return getNeuigkeitenStyles() . '<div class="alert alert-danger">Die angeforderte Neuigkeit wurde nicht gefunden.</div>';
    }
    
    $bildPfad = $neuigkeit['path_to_image'] ?? '/Veranstaltungen/Flyer/default.jpg';
    $datum = date('d.m.Y', strtotime($neuigkeit['Datum']));
    
    $output .= '
    <div class="ffr-neuigkeit-detail ' . $customClass . '">
        <div class="row">
            <div class="col-md-8">
                <h1 class="ffr-neuigkeit-detail-heading">' . htmlspecialchars($neuigkeit['Ueberschrift']) . '</h1>
                <p class="ffr-neuigkeit-detail-meta">' . $datum . ' | ' . htmlspecialchars($neuigkeit['Ort']) . '</p>
                <div class="ffr-neuigkeit-detail-text mt-4">
                    ' . nl2br(htmlspecialchars($neuigkeit['Information'])) . '
                </div>
            </div>
            <div class="col-md-4">
                <img src="' . htmlspecialchars($bildPfad) . '" class="ffr-neuigkeit-detail-image" alt="' . htmlspecialchars($neuigkeit['Ueberschrift']) . '">
            </div>
        </div>
    </div>';
    
    return $output;
}

/**
 * Erstellt ein Modal für eine einzelne Neuigkeit
 * 
 * @param array $neuigkeit Die Neuigkeit als Array
 * @return string HTML-Output für das Modal
 */
function erstelleNeuigkeitModal($neuigkeit) {
    $bildPfad = $neuigkeit['path_to_image'] ?? '/Veranstaltungen/Flyer/default.jpg';
    $datum = date('d.m.Y', strtotime($neuigkeit['Datum']));
    $id = $neuigkeit['ID'];
    
    return '
    <div class="modal fade" id="ffr-neuigkeitModal' . $id . '" tabindex="-1" aria-labelledby="ffr-neuigkeitModalLabel' . $id . '" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="ffr-neuigkeit-modal-content">
                <div class="ffr-neuigkeit-modal-header">
                    <h5 class="ffr-neuigkeit-modal-title" id="ffr-neuigkeitModalLabel' . $id . '">' . htmlspecialchars($neuigkeit['Ueberschrift']) . '</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>
                <div class="ffr-neuigkeit-modal-body">
                    <div class="row">
                        <div class="col-md-7 mb-3 mb-md-0">
                            <div class="ffr-neuigkeit-detail-content">
                                <p class="ffr-neuigkeit-detail-meta">' . $datum . ' | ' . htmlspecialchars($neuigkeit['Ort']) . '</p>
                                <div class="ffr-neuigkeit-detail-text mt-3">
                                    ' . nl2br(htmlspecialchars($neuigkeit['Information'])) . '
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="ffr-neuigkeit-flyer-container">
                                <img src="' . htmlspecialchars($bildPfad) . '" class="ffr-neuigkeit-flyer-image" alt="' . htmlspecialchars($neuigkeit['Ueberschrift']) . '">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="ffr-neuigkeit-modal-footer">
                    <button type="button" class="ffr-neuigkeit-btn-secondary" data-bs-dismiss="modal">Schließen</button>
                </div>
            </div>
        </div>
    </div>';
}

/**
 * Gibt die CSS-Styles für die Neuigkeiten zurück
 * 
 * @return string CSS-Styles in einem style-Tag
 */
function getNeuigkeitenStyles() {
    return '<style>
    /* Container und Karten-Styling */
    .ffr-neuigkeiten-container {
      margin: 2rem 0;
    }
    .ffr-neuigkeit-card {
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      border: none;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      height: 100%;
      display: flex;
      flex-direction: column;
      background-color: #fff;
    }
    .ffr-neuigkeit-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 10px 20px rgba(0,0,0,0.15);
    }
    .ffr-neuigkeit-img-wrapper {
      height: 300px;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
      background-color: #f8f9fa;
    }
    .ffr-neuigkeit-img {
      object-fit: contain;
      max-height: 100%;
      width: auto;
      max-width: 100%;
    }
    .ffr-neuigkeit-body {
      padding: 1.25rem;
      flex: 1 1 auto;
    }
    .ffr-neuigkeit-title {
      color: #A72920;
      font-weight: 600;
      margin-bottom: 0.5rem;
    }
    .ffr-neuigkeit-subtitle {
      color: #6c757d;
      font-size: 0.9rem;
      margin-bottom: 1rem;
    }
    .ffr-neuigkeit-text {
      color: #212529;
      margin-bottom: 0;
    }
    .ffr-neuigkeit-footer {
      padding: 0.75rem 1.25rem;
      background-color: transparent;
      border-top: 1px solid rgba(0,0,0,0.05);
    }
    .ffr-neuigkeit-btn {
      display: inline-block;
      font-weight: 400;
      text-align: center;
      white-space: nowrap;
      vertical-align: middle;
      user-select: none;
      border: 1px solid #A72920;
      padding: 0.375rem 0.75rem;
      font-size: 1rem;
      line-height: 1.5;
      border-radius: 0.25rem;
      transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
      color: #fff;
      background-color: #A72920;
      cursor: pointer;
    }
    .ffr-neuigkeit-btn:hover {
      background-color: #8a231c;
      border-color: #8a231c;
    }
    .ffr-neuigkeit-btn-secondary {
      display: inline-block;
      font-weight: 400;
      text-align: center;
      white-space: nowrap;
      vertical-align: middle;
      user-select: none;
      border: 1px solid #6c757d;
      padding: 0.375rem 0.75rem;
      font-size: 1rem;
      line-height: 1.5;
      border-radius: 0.25rem;
      transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
      color: #fff;
      background-color: #6c757d;
      cursor: pointer;
    }
    .ffr-neuigkeit-btn-secondary:hover {
      background-color: #5a6268;
      border-color: #545b62;
    }
    .ffr-neuigkeit-btn-sm {
      padding: 0.25rem 0.5rem;
      font-size: 0.875rem;
      line-height: 1.5;
      border-radius: 0.2rem;
      color: #fff;
      background-color: #A72920;
      border: 1px solid #A72920;
      display: inline-block;
      font-weight: 400;
      text-align: center;
      white-space: nowrap;
      vertical-align: middle;
      user-select: none;
      transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
      cursor: pointer;
    }
    .ffr-neuigkeit-btn-sm:hover {
      background-color: #8a231c;
      border-color: #8a231c;
    }
    
    /* Modal-Styles */
    .ffr-neuigkeit-modal-content {
      position: relative;
      display: flex;
      flex-direction: column;
      width: 100%;
      pointer-events: auto;
      background-color: #fff;
      background-clip: padding-box;
      border: none;
      border-radius: 8px;
      outline: 0;
    }
    .ffr-neuigkeit-modal-header {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      padding: 1rem;
      border-bottom: 1px solid rgba(0,0,0,0.05);
      border-top-left-radius: 8px;
      border-top-right-radius: 8px;
      background-color: #f8f9fa;
    }
    .ffr-neuigkeit-modal-title {
      margin-bottom: 0;
      line-height: 1.5;
      color: #A72920;
      font-weight: 600;
    }
    .ffr-neuigkeit-modal-body {
      position: relative;
      flex: 1 1 auto;
      padding: 1.5rem;
    }
    .ffr-neuigkeit-modal-footer {
      display: flex;
      align-items: center;
      justify-content: flex-end;
      padding: 1rem;
      border-top: 1px solid rgba(0,0,0,0.05);
      border-bottom-right-radius: 8px;
      border-bottom-left-radius: 8px;
    }
    .ffr-neuigkeit-flyer-container {
      display: flex;
      justify-content: center;
      align-items: flex-start;
      height: 100%;
    }
    .ffr-neuigkeit-flyer-image {
      max-height: 70vh;
      width: auto;
      max-width: 100%;
      object-fit: contain;
      box-shadow: 0 5px 15px rgba(0,0,0,0.15);
      transition: transform 0.3s ease;
      border-radius: 0.25rem;
    }
    .ffr-neuigkeit-flyer-image:hover {
      transform: scale(1.02);
    }
    .ffr-neuigkeit-detail-content {
      line-height: 1.7;
    }
    .ffr-neuigkeit-detail-meta {
      color: #6c757d;
      margin-bottom: 1rem;
    }
    .ffr-neuigkeit-detail-text {
      color: #212529;
    }
    
    /* Listen-Styling */
    .ffr-neuigkeiten-liste {
      display: flex;
      flex-direction: column;
    }
    .ffr-neuigkeit-list-item {
      position: relative;
      display: block;
      padding: 1.25rem;
      margin-bottom: -1px;
      background-color: #fff;
      border: 1px solid rgba(0,0,0,.125);
    }
    .ffr-neuigkeit-list-item:first-child {
      border-top-left-radius: 0.25rem;
      border-top-right-radius: 0.25rem;
    }
    .ffr-neuigkeit-list-item:last-child {
      margin-bottom: 0;
      border-bottom-right-radius: 0.25rem;
      border-bottom-left-radius: 0.25rem;
    }
    .ffr-neuigkeit-list-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 0.5rem;
    }
    .ffr-neuigkeit-list-title {
      margin-bottom: 0;
      color: #A72920;
    }
    .ffr-neuigkeit-list-date {
      color: #6c757d;
    }
    .ffr-neuigkeit-list-text {
      margin-bottom: 0.5rem;
    }
    .ffr-neuigkeit-list-location {
      color: #6c757d;
      display: block;
      margin-bottom: 0.5rem;
    }
    
    /* Detailansicht-Styling */
    .ffr-neuigkeit-detail {
      margin: 2rem 0;
    }
    .ffr-neuigkeit-detail-heading {
      margin-bottom: 1rem;
      color: #A72920;
    }
    .ffr-neuigkeit-detail-image {
      max-width: 100%;
      height: auto;
      border-radius: 0.25rem;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
      transition: transform 0.3s ease;
    }
    .ffr-neuigkeit-detail-image:hover {
      transform: scale(1.02);
    }
    
    /* Responsive Anpassungen */
    @media (max-width: 767px) {
      .ffr-neuigkeit-flyer-image {
        max-height: 50vh;
        margin-bottom: 1rem;
      }
      .modal-dialog {
        margin: 0.5rem;
      }
    }
    </style>';
}

/**
 * Beispiel für die Verwendung in anderen Dateien:
 * 
 * <?php
 * require_once $_SERVER['DOCUMENT_ROOT'] . '/assets/includes/neuigkeiten.php';
 * 
 * // Alle Neuigkeiten als Karten anzeigen
 * echo showNeuigkeiten();
 * 
 * // Die neuesten 3 Einträge anzeigen
 * // echo showNeuigkeiten(3);
 * 
 * // Mit zusätzlicher CSS-Klasse
 * // echo showNeuigkeiten(null, 'meine-zusaetzliche-klasse');
 * 
 * // Alternativ als Liste anzeigen
 * // echo showNeuigkeitenListe();
 * 
 * // Einzelne Neuigkeit anzeigen
 * // echo showNeuigkeitDetail($_GET['id']);
 * ?>
 */
?>
