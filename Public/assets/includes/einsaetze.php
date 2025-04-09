<?php
require_once dirname(__DIR__, 3) . '/Private/Database/Database.php';

/**
 * Einsaetze-Modul - Zeigt Einsätze aus der Datenbank an mit Paginierung
 * 
 * Verwendung:
 * 1. Diese Datei einbinden: include_once "assets/includes/einsaetze.php";
 * 2. Die Funktion aufrufen: showEinsaetze($itemsPerPage, $customClass);
 *    - $itemsPerPage: Anzahl der Einsätze pro Seite (optional, Standard: 5)
 *    - $customClass: Benutzerdefinierte CSS-Klasse für den Container (optional)
 * 3. Für Statistiken: showEinsatzStatistik($jahr, $customClass);
 *    - $jahr: Jahr für die Statistiken (optional, Standard: aktuelles Jahr)
 *    - $customClass: Benutzerdefinierte CSS-Klasse (optional)
 */

/**
 * Zeigt Einsätze mit Paginierung an
 * 
 * @param int $itemsPerPage Anzahl der Einsätze pro Seite
 * @param string $customClass Benutzerdefinierte CSS-Klasse für den Container
 * @return void
 */
function showEinsaetze($itemsPerPage = 5, $customClass = '') {
    // Aktuelle Seite aus der URL abrufen
    $page = isset($_GET['einsatz_page']) ? (int)$_GET['einsatz_page'] : 1;
    if ($page < 1) {
        $page = 1;
    }
    
    // Datenbankobjekt holen
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Gesamtzahl der Einsätze abrufen (nur die, die angezeigt werden sollen)
    $countSql = "SELECT COUNT(*) as total FROM einsatz WHERE Anzeigen = 1";
    $countStmt = $conn->prepare($countSql);
    $countStmt->execute();
    $totalItems = $countStmt->fetchColumn();
    
    // Gesamtzahl der Seiten berechnen
    $totalPages = ceil($totalItems / $itemsPerPage);
    
    // Sicherstellen, dass die aktuelle Seite nicht größer als die Gesamtzahl der Seiten ist
    if ($page > $totalPages && $totalPages > 0) {
        $page = $totalPages;
    }
    
    // Offset für die SQL-Abfrage berechnen
    $offset = ($page - 1) * $itemsPerPage;
    
    // Einsätze abrufen
    $sql = "SELECT * FROM einsatz 
            WHERE Anzeigen = 1 
            ORDER BY Datum DESC 
            LIMIT :limit OFFSET :offset";
            
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':limit', $itemsPerPage, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $einsaetze = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // CSS-Stile für das Modul
    echo '<style>
        .einsaetze-container {
            margin: 2rem 0;
            padding: 1rem;
            background-color: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .einsaetze-title {
            color: #A72920;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #A72920;
            padding-bottom: 0.5rem;
        }
        .einsatz-item {
            margin-bottom: 1.5rem;
            padding: 1rem;
            background-color: #fff;
            border-left: 4px solid #A72920;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            transition: transform 0.2s ease;
        }
        .einsatz-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 3px 6px rgba(0,0,0,0.12);
        }
        .einsatz-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        .einsatz-date {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .einsatz-stichwort {
            background-color: #A72920;
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            display: inline-block;
        }
        .einsatz-kategorie {
            background-color: #585858;
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            margin-left: 0.5rem;
            display: inline-block;
        }
        .einsatz-sachverhalt {
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .einsatz-details {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
        }
        .einsatz-ort, .einsatz-einheit {
            display: flex;
            align-items: center;
        }
        .einsatz-ort i, .einsatz-einheit i {
            margin-right: 0.3rem;
            opacity: 0.7;
        }
        .einsatz-duration {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 0.5rem;
            text-align: right;
        }
        .pagination {
            display: flex;
            justify-content: center;
            list-style: none;
            padding: 0;
            margin-top: 1.5rem;
        }
        .pagination li {
            margin: 0 0.2rem;
        }
        .pagination a, .pagination span {
            display: block;
            padding: 0.5rem 0.8rem;
            border-radius: 4px;
            text-decoration: none;
            color: #414141;
            background-color: #fff;
            border: 1px solid #dee2e6;
            transition: all 0.2s ease;
        }
        .pagination a:hover {
            background-color: #f0f0f0;
            border-color: #ced4da;
        }
        .pagination .active span {
            background-color: #A72920;
            color: white;
            border-color: #A72920;
        }
        .pagination .disabled span {
            color: #adb5bd;
            background-color: #fff;
            cursor: not-allowed;
        }
        .no-einsaetze {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
        }
    </style>';
    
    // Container mit optionaler benutzerdefinierter Klasse
    echo '<div class="einsaetze-container ' . htmlspecialchars($customClass) . '">';
    echo '<h2 class="einsaetze-title">Einsätze</h2>';
    
    if (empty($einsaetze)) {
        echo '<div class="no-einsaetze">Aktuell sind keine Einsätze verfügbar.</div>';
    } else {
        foreach ($einsaetze as $einsatz) {
            // Datum und Uhrzeit formatieren
            $datumObj = new DateTime($einsatz['Datum']);
            $endZeitObj = new DateTime($einsatz['Endzeit']);
            $formattedDatum = $datumObj->format('d.m.Y - H:i');
            
            // Einsatzdauer berechnen
            $dauer = $datumObj->diff($endZeitObj);
            $dauerText = '';
            
            if ($dauer->d > 0) {
                $dauerText .= $dauer->d . ' Tag' . ($dauer->d > 1 ? 'e' : '') . ', ';
            }
            if ($dauer->h > 0) {
                $dauerText .= $dauer->h . ' Stunde' . ($dauer->h > 1 ? 'n' : '') . ', ';
            }
            if ($dauer->i > 0) {
                $dauerText .= $dauer->i . ' Minute' . ($dauer->i > 1 ? 'n' : '');
            }
            if (empty($dauerText)) {
                $dauerText = 'Weniger als eine Minute';
            } else if (substr($dauerText, -2) == ', ') {
                $dauerText = substr($dauerText, 0, -2);
            }
            
            echo '<div class="einsatz-item">';
            echo '<div class="einsatz-header">';
            echo '<div class="einsatz-date">' . $formattedDatum . ' Uhr</div>';
            echo '<div>';
            echo '<span class="einsatz-stichwort">' . htmlspecialchars($einsatz['Stichwort']) . '</span>';
            if (!empty($einsatz['Kategorie'])) {
                echo '<span class="einsatz-kategorie">' . htmlspecialchars($einsatz['Kategorie']) . '</span>';
            }
            echo '</div>';
            echo '</div>';
            
            echo '<div class="einsatz-sachverhalt">' . htmlspecialchars($einsatz['Sachverhalt']) . '</div>';
            
            echo '<div class="einsatz-details">';
            echo '<div class="einsatz-ort"><i class="bi bi-geo-alt"></i> ' . htmlspecialchars($einsatz['Ort']) . '</div>';
            echo '<div class="einsatz-einheit"><i class="bi bi-people"></i> ' . htmlspecialchars($einsatz['Einheit']) . '</div>';
            echo '</div>';
            
            echo '<div class="einsatz-duration">Einsatzdauer: ' . $dauerText . '</div>';
            echo '</div>';
        }
        
        // Paginierung anzeigen, wenn mehr als eine Seite vorhanden ist
        if ($totalPages > 1) {
            // Aktuelle URL und Parameter abrufen
            $urlParts = parse_url($_SERVER['REQUEST_URI']);
            $path = $urlParts['path'] ?? '';
            $query = [];
            
            if (isset($urlParts['query'])) {
                parse_str($urlParts['query'], $query);
            }
            
            echo '<ul class="pagination">';
            
            // Zurück-Button
            if ($page > 1) {
                $query['einsatz_page'] = $page - 1;
                $prevUrl = $path . '?' . http_build_query($query);
                echo '<li><a href="' . htmlspecialchars($prevUrl) . '" aria-label="Zurück">&laquo;</a></li>';
            } else {
                echo '<li class="disabled"><span>&laquo;</span></li>';
            }
            
            // Seitenzahlen
            $startPage = max(1, $page - 2);
            $endPage = min($totalPages, $page + 2);
            
            // Immer Seite 1 anzeigen
            if ($startPage > 1) {
                $query['einsatz_page'] = 1;
                $firstUrl = $path . '?' . http_build_query($query);
                echo '<li><a href="' . htmlspecialchars($firstUrl) . '">1</a></li>';
                
                if ($startPage > 2) {
                    echo '<li class="disabled"><span>...</span></li>';
                }
            }
            
            // Seitennummern anzeigen
            for ($i = $startPage; $i <= $endPage; $i++) {
                if ($i == $page) {
                    echo '<li class="active"><span>' . $i . '</span></li>';
                } else {
                    $query['einsatz_page'] = $i;
                    $pageUrl = $path . '?' . http_build_query($query);
                    echo '<li><a href="' . htmlspecialchars($pageUrl) . '">' . $i . '</a></li>';
                }
            }
            
            // Immer die letzte Seite anzeigen
            if ($endPage < $totalPages) {
                if ($endPage < $totalPages - 1) {
                    echo '<li class="disabled"><span>...</span></li>';
                }
                
                $query['einsatz_page'] = $totalPages;
                $lastUrl = $path . '?' . http_build_query($query);
                echo '<li><a href="' . htmlspecialchars($lastUrl) . '">' . $totalPages . '</a></li>';
            }
            
            // Weiter-Button
            if ($page < $totalPages) {
                $query['einsatz_page'] = $page + 1;
                $nextUrl = $path . '?' . http_build_query($query);
                echo '<li><a href="' . htmlspecialchars($nextUrl) . '" aria-label="Weiter">&raquo;</a></li>';
            } else {
                echo '<li class="disabled"><span>&raquo;</span></li>';
            }
            
            echo '</ul>';
        }
    }
    
    echo '</div>';
}

/**
 * Zeigt Statistiken zu den Einsätzen an
 * 
 * @param int|null $jahr Jahr für die Statistik (null = aktuelles Jahr)
 * @param string $customClass Benutzerdefinierte CSS-Klasse für den Container
 * @return void
 */
function showEinsatzStatistik($jahr = null, $customClass = '') {
    // Wenn kein Jahr angegeben ist, aktuelles Jahr verwenden
    if ($jahr === null) {
        $jahr = date('Y');
    }
    
    // Datenbankobjekt holen
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Statistiken für das angegebene Jahr abrufen
    $startDate = $jahr . '-01-01 00:00:00';
    $endDate = $jahr . '-12-31 23:59:59';
    
    // Abfragen vorbereiten
    $queries = [
        'gesamt' => "SELECT COUNT(*) FROM einsatz WHERE Anzeigen = 1 AND Datum BETWEEN :startDate AND :endDate",
        'kategorien' => "SELECT Kategorie, COUNT(*) as Anzahl FROM einsatz WHERE Anzeigen = 1 AND Datum BETWEEN :startDate AND :endDate GROUP BY Kategorie ORDER BY Anzahl DESC",
        'monate' => "SELECT MONTH(Datum) as Monat, COUNT(*) as Anzahl FROM einsatz WHERE Anzeigen = 1 AND Datum BETWEEN :startDate AND :endDate GROUP BY MONTH(Datum) ORDER BY Monat",
        'stichworte' => "SELECT Stichwort, COUNT(*) as Anzahl FROM einsatz WHERE Anzeigen = 1 AND Datum BETWEEN :startDate AND :endDate GROUP BY Stichwort ORDER BY Anzahl DESC LIMIT 5",
        'einsatzorte' => "SELECT Ort, COUNT(*) as Anzahl FROM einsatz WHERE Anzeigen = 1 AND Datum BETWEEN :startDate AND :endDate GROUP BY Ort ORDER BY Anzahl DESC LIMIT 5",
        'wochentage' => "SELECT WEEKDAY(Datum) as Wochentag, COUNT(*) as Anzahl FROM einsatz WHERE Anzeigen = 1 AND Datum BETWEEN :startDate AND :endDate GROUP BY WEEKDAY(Datum) ORDER BY Wochentag",
        'tageszeit' => "SELECT 
                            CASE 
                                WHEN HOUR(Datum) BETWEEN 6 AND 11 THEN 'Morgen (6-12 Uhr)'
                                WHEN HOUR(Datum) BETWEEN 12 AND 17 THEN 'Nachmittag (12-18 Uhr)'
                                WHEN HOUR(Datum) BETWEEN 18 AND 22 THEN 'Abend (18-23 Uhr)'
                                ELSE 'Nacht (23-6 Uhr)'
                            END as Tageszeit,
                            COUNT(*) as Anzahl
                         FROM einsatz 
                         WHERE Anzeigen = 1 AND Datum BETWEEN :startDate AND :endDate 
                         GROUP BY Tageszeit 
                         ORDER BY Anzahl DESC",
        'durchschnittsdauer' => "SELECT AVG(TIMESTAMPDIFF(MINUTE, Datum, Endzeit)) as Minuten FROM einsatz WHERE Anzeigen = 1 AND Datum BETWEEN :startDate AND :endDate",
        'laengster' => "SELECT Sachverhalt, Datum, Endzeit, TIMESTAMPDIFF(MINUTE, Datum, Endzeit) as Dauer 
                        FROM einsatz 
                        WHERE Anzeigen = 1 AND Datum BETWEEN :startDate AND :endDate 
                        ORDER BY Dauer DESC 
                        LIMIT 1"
    ];
    
    // Ergebnisse sammeln
    global $stats; // Make stats global so individual stat functions can access it
    $stats = [];
    
    foreach ($queries as $key => $query) {
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':startDate', $startDate);
        $stmt->bindParam(':endDate', $endDate);
        $stmt->execute();
        
        if ($key === 'gesamt' || $key === 'durchschnittsdauer') {
            $stats[$key] = $stmt->fetchColumn();
        } elseif ($key === 'laengster') {
            $stats[$key] = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $stats[$key] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    
    // Jahr-Auswahl-Formular erstellen
    $jahreQuery = "SELECT DISTINCT YEAR(Datum) as Jahr FROM einsatz WHERE Anzeigen = 1 ORDER BY Jahr DESC";
    $jahreStmt = $conn->prepare($jahreQuery);
    $jahreStmt->execute();
    $verfuegbareJahre = $jahreStmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Monatsnamen für die Grafik
    global $monate; // Make monate global so individual stat functions can access it
    $monate = [
        1 => 'Januar', 2 => 'Februar', 3 => 'März', 4 => 'April',
        5 => 'Mai', 6 => 'Juni', 7 => 'Juli', 8 => 'August',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember'
    ];
    
    // Wochentagsnamen
    global $wochentage; // Make wochentage global so individual stat functions can access it
    $wochentage = [
        0 => 'Montag', 1 => 'Dienstag', 2 => 'Mittwoch', 3 => 'Donnerstag',
        4 => 'Freitag', 5 => 'Samstag', 6 => 'Sonntag'
    ];
    
    // Monatsdaten für das Diagramm vorbereiten
    global $monatsdaten; // Make monatsdaten global
    $monatsdaten = array_fill(1, 12, 0); // Initialisiere alle Monate mit 0
    
    foreach ($stats['monate'] as $monat) {
        $monatsdaten[$monat['Monat']] = (int)$monat['Anzahl'];
    }
    
    // Wochentagsdaten für das Diagramm vorbereiten
    global $wochentagsdaten; // Make wochentagsdaten global
    $wochentagsdaten = array_fill(0, 7, 0); // Initialisiere alle Wochentage mit 0
    
    foreach ($stats['wochentage'] as $tag) {
        $wochentagsdaten[$tag['Wochentag']] = (int)$tag['Anzahl'];
    }
    
    // Store current year in global variable
    global $aktuellesStatistikJahr;
    $aktuellesStatistikJahr = $jahr;
    
    // Store available years in global variable
    global $verfuegbareStatistikJahre;
    $verfuegbareStatistikJahre = $verfuegbareJahre;
    
    // CSS-Styles für die Statistik-Anzeige
    echo '<style>
        .einsatz-statistik {
            margin: 2rem 0;
            padding: 1rem;
            background-color: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .statistik-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .statistik-title {
            color: #A72920;
            margin-bottom: 0.5rem;
            border-bottom: 2px solid #A72920;
            padding-bottom: 0.5rem;
        }
        .statistik-jahr-form {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .statistik-jahr-select {
            padding: 0.375rem 0.75rem;
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
        }
        .statistik-jahr-button {
            padding: 0.375rem 0.75rem;
            background-color: #A72920;
            color: white;
            border: none;
            border-radius: 0.25rem;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .statistik-jahr-button:hover {
            background-color: #8e2219;
        }
        .statistik-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        .statistik-card {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            cursor: pointer;
        }
        .statistik-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 3px 6px rgba(0,0,0,0.12);
        }
        .statistik-card-title {
            font-size: 1.1rem;
            font-weight: bold;
            margin-bottom: 1rem;
            color: #414141;
            display: flex;
            align-items: center;
        }
        .statistik-card-title i {
            margin-right: 0.5rem;
            color: #A72920;
        }
        .statistik-highlight {
            font-size: 2rem;
            font-weight: bold;
            color: #A72920;
            text-align: center;
            margin: 1rem 0;
        }
        .statistik-chart-container {
            position: relative;
            height: 200px;
            margin-top: 1rem;
        }
        .statistik-bar-chart {
            display: flex;
            align-items: flex-end;
            height: 180px;
            gap: 8px;
        }
        .statistik-bar {
            flex: 1;
            background-color: #A72920;
            min-width: 20px;
            border-radius: 4px 4px 0 0;
            position: relative;
            transition: height 0.3s ease, background-color 0.3s ease;
        }
        .statistik-bar:hover {
            background-color: #8e2219;
            transform: scaleY(1.05);
            transform-origin: bottom;
            box-shadow: 0 0 8px rgba(167, 41, 32, 0.4);
        }
        .statistik-bar-label {
            position: absolute;
            bottom: -25px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 0.75rem;
            text-align: center;
            width: 100%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .statistik-bar-value {
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 0.75rem;
            font-weight: bold;
            color: #414141;
        }
        .statistik-top-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .statistik-top-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e9ecef;
            transition: background-color 0.2s ease, transform 0.2s ease;
        }
        .statistik-top-item:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
            padding-left: 5px;
            border-left: 3px solid #A72920;
        }
        .statistik-top-item:last-child {
            border-bottom: none;
        }
        .statistik-top-label {
            font-weight: 500;
        }
        .statistik-top-value {
            font-weight: bold;
            color: #A72920;
        }
        .statistik-pie-container {
            width: 100%;
            height: 180px;
            position: relative;
            display: flex;
            justify-content: center;
        }
        .statistik-info-text {
            font-size: 0.9rem;
            color: #6c757d;
            margin-top: 1rem;
        }
        
        .statistik-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.7);
            z-index: 1000;
            overflow: auto;
            padding: 0;
            margin: 0;
            align-items: center;
            justify-content: center;
        }
        .statistik-modal-content {
            position: relative;
            background-color: white;
            margin: auto;
            padding: 2.5rem;
            width: 90%;
            max-width: 900px;
            border-radius: 10px;
            box-shadow: 0 5px 30px rgba(0,0,0,0.3);
            max-height: 90vh;
            overflow-y: auto;
            transform: translateY(0);
            animation: modal-slide-in 0.3s ease;
        }
        @keyframes modal-slide-in {
            from {
                transform: translateY(-30px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        .statistik-modal-close {
            position: absolute;
            right: 1.5rem;
            top: 1.5rem;
            font-size: 1.75rem;
            cursor: pointer;
            color: #6c757d;
            transition: color 0.2s ease, transform 0.2s ease;
            z-index: 10;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        .statistik-modal-close:hover {
            color: #A72920;
            transform: rotate(90deg);
            background-color: #f8f9fa;
        }
        .statistik-modal h2 {
            color: #A72920;
            margin-bottom: 2rem;
            font-size: 1.8rem;
            border-bottom: 2px solid #A72920;
            padding-bottom: 0.5rem;
        }
        
        /* Modal content specific styles */
        #statistikModalContent .statistik-card {
            box-shadow: none;
            cursor: default;
            padding: 0;
            background: none;
        }
        #statistikModalContent .statistik-card:hover {
            transform: none;
            box-shadow: none;
        }
        #statistikModalContent .statistik-card-title {
            display: none;
        }
        #statistikModalContent .statistik-chart-container {
            height: 300px;
            margin-top: 2rem;
        }
        #statistikModalContent .statistik-bar-chart {
            height: 280px;
        }
        #statistikModalContent .statistik-bar {
            min-width: 40px;
            border-radius: 6px 6px 0 0;
        }
        #statistikModalContent .statistik-highlight {
            font-size: 3rem;
        }
        #statistikModalContent .statistik-top-list {
            font-size: 1.1rem;
        }
        
        @media (max-width: 768px) {
            .statistik-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            .statistik-grid {
                grid-template-columns: 1fr;
            }
            .statistik-modal-content {
                width: 95%;
                padding: 1.5rem;
            }
        }
    </style>';
    
    // Add modal HTML
    echo '<div id="statistikModal" class="statistik-modal">
        <div class="statistik-modal-content">
            <span class="statistik-modal-close">&times;</span>
            <div id="statistikModalContent"></div>
        </div>
    </div>';
    
    // Add JavaScript for modal functionality
    echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            const modal = document.getElementById("statistikModal");
            const modalContent = document.getElementById("statistikModalContent");
            const closeBtn = document.querySelector(".statistik-modal-close");
            
            // Close modal when clicking outside
            window.onclick = function(event) {
                if (event.target == modal) {
                    closeModal();
                }
            }
            
            // Close modal when clicking close button
            closeBtn.onclick = function() {
                closeModal();
            }
            
            // Add escape key to close modal
            document.addEventListener("keydown", function(event) {
                if (event.key === "Escape" && modal.style.display === "flex") {
                    closeModal();
                }
            });
            
            function closeModal() {
                modal.style.opacity = "0";
                setTimeout(() => {
                    modal.style.display = "none";
                    modal.style.opacity = "1";
                }, 300);
            }
            
            // Add click handlers to all stat cards
            function addCardHandlers() {
                document.querySelectorAll(".statistik-card").forEach(card => {
                    card.onclick = function() {
                        const title = this.querySelector(".statistik-card-title").textContent;
                        const content = this.innerHTML;
                        modalContent.innerHTML = `<h2>${title}</h2>${content}`;
                        modal.style.display = "flex";
                        
                        // Add animation class to bars in modal
                        setTimeout(function() {
                            const modalBars = modalContent.querySelectorAll(".statistik-bar");
                            modalBars.forEach((bar, index) => {
                                setTimeout(() => {
                                    bar.style.transition = "height 0.5s ease";
                                    bar.style.height = bar.style.height;
                                }, index * 50);
                            });
                        }, 100);
                    }
                });
            }
            
            // Initial handler setup
            addCardHandlers();
            
            // Make addCardHandlers available globally
            window.addStatistikCardHandlers = addCardHandlers;
        });
    </script>';
    
    // HTML für die Statistik generieren
    echo '<div class="einsatz-statistik ' . htmlspecialchars($customClass) . '" id="einsatz-statistik-container">';
    
    echo '<div class="statistik-header">';
    echo '<h2 class="statistik-title">Einsatzstatistik ' . htmlspecialchars($jahr) . '</h2>';
    
    // Jahr-Auswahl-Formular
    echo '<form class="statistik-jahr-form" method="get">';
    echo '<label for="statistik_jahr">Jahr auswählen:</label>';
    echo '<select class="statistik-jahr-select" name="statistik_jahr" id="statistik_jahr">';
    
    foreach ($verfuegbareJahre as $verfuegbaresJahr) {
        $selected = ($verfuegbaresJahr == $jahr) ? 'selected' : '';
        echo '<option value="' . $verfuegbaresJahr . '" ' . $selected . '>' . $verfuegbaresJahr . '</option>';
    }
    
    echo '</select>';
    
    // Bestehende GET-Parameter beibehalten
    foreach ($_GET as $key => $value) {
        if ($key !== 'statistik_jahr') {
            echo '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">';
        }
    }
    
    echo '<button type="submit" class="statistik-jahr-button">Anzeigen</button>';
    echo '</form>';
    echo '</div>';
    
    // Überprüfen, ob Einsätze vorhanden sind
    if ($stats['gesamt'] == 0) {
        echo '<div style="text-align: center; padding: 2rem; color: #6c757d;">Keine Einsätze im Jahr ' . htmlspecialchars($jahr) . ' verfügbar.</div>';
    } else {
        // Statistik-Grid starten
        echo '<div class="statistik-grid" id="einsatz-statistik-grid"></div>';
        
        // Add JavaScript to append stats to the grid
        echo '<script>
            function appendToStatistikGrid(html) {
                const grid = document.getElementById("einsatz-statistik-grid");
                const tempDiv = document.createElement("div");
                tempDiv.innerHTML = html;
                
                // Get the statistik-card element from the tempDiv
                const card = tempDiv.querySelector(".statistik-card");
                
                // Append to grid
                if (card) {
                    grid.appendChild(card);
                }
                
                // Re-initialize click handlers
                if (window.addStatistikCardHandlers) {
                    window.addStatistikCardHandlers();
                }
            }
        </script>';
    }
    
    echo '</div>';
}

/**
 * Zeigt eine einzelne Statistik an
 * 
 * @param string $type Typ der Statistik (gesamt, dauer, monate, wochentage, tageszeit, stichworte, einsatzorte, kategorien)
 * @return void
 */
function EinsatzStatistikShow($type) {
    global $stats;
    
    if (empty($stats)) {
        echo '<div style="color: red; margin: 1rem 0;">Fehler: Bitte zuerst showEinsatzStatistik() aufrufen!</div>';
        return;
    }
    
    if ($stats['gesamt'] == 0) {
        return;
    }
    
    $html = '';
    
    // Call the specific function based on type
    switch ($type) {
        case 'gesamt':
            $html = showStatistikGesamt();
            break;
        case 'dauer':
            $html = showStatistikDauer();
            break;
        case 'monate':
            $html = showStatistikMonate();
            break;
        case 'wochentage':
            $html = showStatistikWochentage();
            break;
        case 'tageszeit':
            $html = showStatistikTageszeit();
            break;
        case 'stichworte':
            $html = showStatistikStichworte();
            break;
        case 'einsatzorte':
            $html = showStatistikEinsatzorte();
            break;
        case 'kategorien':
            $html = showStatistikKategorien();
            break;
        default:
            $html = '<div style="color: red; margin: 1rem 0;">Fehler: Unbekannter Statistik-Typ "' . htmlspecialchars($type) . '"</div>';
    }
    
    // Output the JavaScript to append to grid
    echo '<script>appendToStatistikGrid(`' . $html . '`);</script>';
}

/**
 * Zeigt die Gesamt-Statistik an
 * 
 * @return string HTML der Statistik
 */
function showStatistikGesamt() {
    global $stats, $aktuellesStatistikJahr;
    
    $html = '<div class="statistik-card">';
    $html .= '<div class="statistik-card-title"><i class="bi bi-flag"></i> Einsätze gesamt</div>';
    $html .= '<div class="statistik-highlight">' . number_format($stats['gesamt'], 0, ',', '.') . '</div>';
    $html .= '<div class="statistik-info-text">im Jahr ' . htmlspecialchars($aktuellesStatistikJahr) . '</div>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Zeigt die Dauer-Statistik an
 * 
 * @return string HTML der Statistik
 */
function showStatistikDauer() {
    global $stats;
    
    $html = '<div class="statistik-card">';
    $html .= '<div class="statistik-card-title"><i class="bi bi-clock"></i> Durchschnittliche Einsatzdauer</div>';
    
    // Durchschnittsdauer in Stunden und Minuten umrechnen
    $durchschnittMinuten = round($stats['durchschnittsdauer']);
    $durchschnittStunden = floor($durchschnittMinuten / 60);
    $restMinuten = $durchschnittMinuten % 60;
    
    $dauerText = '';
    if ($durchschnittStunden > 0) {
        $dauerText .= $durchschnittStunden . ' Std. ';
    }
    $dauerText .= $restMinuten . ' Min.';
    
    $html .= '<div class="statistik-highlight">' . $dauerText . '</div>';
    
    if (isset($stats['laengster']) && is_array($stats['laengster'])) {
        $laengsterMinuten = $stats['laengster']['Dauer'];
        $laengsterStunden = floor($laengsterMinuten / 60);
        $laengsterRestMinuten = $laengsterMinuten % 60;
        
        $laengsterText = '';
        if ($laengsterStunden > 0) {
            $laengsterText .= $laengsterStunden . ' Std. ';
        }
        $laengsterText .= $laengsterRestMinuten . ' Min.';
        
        $datumObj = new DateTime($stats['laengster']['Datum']);
        $formattedDatum = $datumObj->format('d.m.Y');
        
        $html .= '<div class="statistik-info-text">Längster Einsatz: ' . $laengsterText . ' am ' . $formattedDatum . '</div>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Zeigt die Monats-Statistik an
 * 
 * @return string HTML der Statistik
 */
function showStatistikMonate() {
    global $monate, $monatsdaten;
    
    $html = '<div class="statistik-card">';
    $html .= '<div class="statistik-card-title"><i class="bi bi-calendar3"></i> Einsätze pro Monat</div>';
    
    $html .= '<div class="statistik-chart-container">';
    $html .= '<div class="statistik-bar-chart">';
    
    $maxMonatsWert = max($monatsdaten);
    
    for ($i = 1; $i <= 12; $i++) {
        $anzahl = $monatsdaten[$i];
        $height = ($maxMonatsWert > 0) ? ($anzahl / $maxMonatsWert * 100) : 0;
        
        $html .= '<div class="statistik-bar" style="height: ' . $height . '%;" title="' . $monate[$i] . ': ' . $anzahl . ' Einsätze">';
        
        if ($anzahl > 0) {
            $html .= '<span class="statistik-bar-value">' . $anzahl . '</span>';
        }
        
        $html .= '<span class="statistik-bar-label">' . substr($monate[$i], 0, 3) . '</span>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Zeigt die Wochentags-Statistik an
 * 
 * @return string HTML der Statistik
 */
function showStatistikWochentage() {
    global $wochentage, $wochentagsdaten;
    
    $html = '<div class="statistik-card">';
    $html .= '<div class="statistik-card-title"><i class="bi bi-calendar-week"></i> Einsätze nach Wochentagen</div>';
    
    $html .= '<div class="statistik-chart-container">';
    $html .= '<div class="statistik-bar-chart">';
    
    $maxTagWert = max($wochentagsdaten);
    
    for ($i = 0; $i < 7; $i++) {
        $anzahl = $wochentagsdaten[$i];
        $height = ($maxTagWert > 0) ? ($anzahl / $maxTagWert * 100) : 0;
        
        $html .= '<div class="statistik-bar" style="height: ' . $height . '%;" title="' . $wochentage[$i] . ': ' . $anzahl . ' Einsätze">';
        
        if ($anzahl > 0) {
            $html .= '<span class="statistik-bar-value">' . $anzahl . '</span>';
        }
        
        $html .= '<span class="statistik-bar-label">' . substr($wochentage[$i], 0, 2) . '</span>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Zeigt die Tageszeit-Statistik an
 * 
 * @return string HTML der Statistik
 */
function showStatistikTageszeit() {
    global $stats;
    
    $html = '<div class="statistik-card">';
    $html .= '<div class="statistik-card-title"><i class="bi bi-sun"></i> Einsätze nach Tageszeit</div>';
    
    if (!empty($stats['tageszeit'])) {
        $html .= '<ul class="statistik-top-list">';
        
        foreach ($stats['tageszeit'] as $tageszeit) {
            $html .= '<li class="statistik-top-item">';
            $html .= '<span class="statistik-top-label">' . htmlspecialchars($tageszeit['Tageszeit']) . '</span>';
            $html .= '<span class="statistik-top-value">' . $tageszeit['Anzahl'] . '</span>';
            $html .= '</li>';
        }
        
        $html .= '</ul>';
    } else {
        $html .= '<div class="statistik-info-text">Keine Daten verfügbar</div>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Zeigt die Stichwort-Statistik an
 * 
 * @return string HTML der Statistik
 */
function showStatistikStichworte() {
    global $stats;
    
    $html = '<div class="statistik-card">';
    $html .= '<div class="statistik-card-title"><i class="bi bi-tags"></i> Häufigste Einsatzarten</div>';
    
    if (!empty($stats['stichworte'])) {
        $html .= '<ul class="statistik-top-list">';
        
        foreach ($stats['stichworte'] as $stichwort) {
            $html .= '<li class="statistik-top-item">';
            $html .= '<span class="statistik-top-label">' . htmlspecialchars($stichwort['Stichwort']) . '</span>';
            $html .= '<span class="statistik-top-value">' . $stichwort['Anzahl'] . '</span>';
            $html .= '</li>';
        }
        
        $html .= '</ul>';
    } else {
        $html .= '<div class="statistik-info-text">Keine Daten verfügbar</div>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Zeigt die Einsatzort-Statistik an
 * 
 * @return string HTML der Statistik
 */
function showStatistikEinsatzorte() {
    global $stats;
    
    $html = '<div class="statistik-card">';
    $html .= '<div class="statistik-card-title"><i class="bi bi-geo-alt"></i> Häufigste Einsatzorte</div>';
    
    if (!empty($stats['einsatzorte'])) {
        $html .= '<ul class="statistik-top-list">';
        
        foreach ($stats['einsatzorte'] as $ort) {
            $html .= '<li class="statistik-top-item">';
            $html .= '<span class="statistik-top-label">' . htmlspecialchars($ort['Ort']) . '</span>';
            $html .= '<span class="statistik-top-value">' . $ort['Anzahl'] . '</span>';
            $html .= '</li>';
        }
        
        $html .= '</ul>';
    } else {
        $html .= '<div class="statistik-info-text">Keine Daten verfügbar</div>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Zeigt die Kategorie-Statistik an
 * 
 * @return string HTML der Statistik
 */
function showStatistikKategorien() {
    global $stats;
    
    $html = '<div class="statistik-card">';
    $html .= '<div class="statistik-card-title"><i class="bi bi-list-check"></i> Einsatzkategorien</div>';
    
    if (!empty($stats['kategorien'])) {
        $html .= '<ul class="statistik-top-list">';
        
        foreach ($stats['kategorien'] as $kategorie) {
            $kategorienName = !empty($kategorie['Kategorie']) ? $kategorie['Kategorie'] : 'Ohne Kategorie';
            
            $html .= '<li class="statistik-top-item">';
            $html .= '<span class="statistik-top-label">' . htmlspecialchars($kategorienName) . '</span>';
            $html .= '<span class="statistik-top-value">' . $kategorie['Anzahl'] . '</span>';
            $html .= '</li>';
        }
        
        $html .= '</ul>';
    } else {
        $html .= '<div class="statistik-info-text">Keine Daten verfügbar</div>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Zeigt Gesamtzahl der Einsätze an
 * @return void
 */
function EinsatzStatistikGesamt() {
    EinsatzStatistikShow('gesamt');
}

/**
 * Zeigt durchschnittliche Einsatzdauer an
 * @return void
 */
function EinsatzStatistikDauer() {
    EinsatzStatistikShow('dauer');
}

/**
 * Zeigt Einsätze nach Monaten an
 * @return void
 */
function EinsatzStatistikMonate() {
    EinsatzStatistikShow('monate');
}

/**
 * Zeigt Einsätze nach Wochentagen an
 * @return void
 */
function EinsatzStatistikWochentage() {
    EinsatzStatistikShow('wochentage');
}

/**
 * Zeigt Einsätze nach Tageszeit an
 * @return void
 */
function EinsatzStatistikTageszeit() {
    EinsatzStatistikShow('tageszeit');
}

/**
 * Zeigt häufigste Einsatzarten (Stichworte) an
 * @return void
 */
function EinsatzStatistikStichworte() {
    EinsatzStatistikShow('stichworte');
}

/**
 * Zeigt häufigste Einsatzorte an
 * @return void
 */
function EinsatzStatistikEinsatzorte() {
    EinsatzStatistikShow('einsatzorte');
}

/**
 * Zeigt Einsatzkategorien an
 * @return void
 */
function EinsatzStatistikKategorien() {
    EinsatzStatistikShow('kategorien');
}
?> 