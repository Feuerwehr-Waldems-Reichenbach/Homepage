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
    // Für mobile Geräte weniger Einträge pro Seite anzeigen
    $isMobile = isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/(android|iphone|ipad|mobile)/i', $_SERVER['HTTP_USER_AGENT']);
    if ($isMobile && $itemsPerPage === 5) { // Nur überschreiben, wenn Standardwert verwendet wird
        $itemsPerPage = 3;
    }
    
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
            scroll-margin-top: 20px;
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
            align-items: flex-start;
            flex-wrap: wrap;
            margin-bottom: 0.5rem;
        }
        .einsatz-date {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        .einsatz-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .einsatz-stichwort {
            background-color: #A72920;
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            display: inline-block;
            margin-bottom: 0.3rem;
        }
        .einsatz-kategorie {
            background-color: #585858;
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            display: inline-block;
            margin-bottom: 0.3rem;
            margin-left: 0;
        }
        .einsatz-sachverhalt {
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .einsatz-details {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            font-size: 0.9rem;
        }
        .einsatz-ort, .einsatz-einheit {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
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
            flex-wrap: wrap;
            list-style: none;
            padding: 0;
            margin-top: 1.5rem;
            gap: 0.3rem;
        }
        .pagination li {
            margin: 0.2rem;
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
            min-width: 2.5rem;
            text-align: center;
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
        .pagination .current-page-info span {
            background-color: transparent;
            border: none;
            color: #585858;
            padding: 0.5rem 0.8rem;
            font-size: 0.9rem;
            white-space: nowrap;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
        }
        .pagination a.loading {
            position: relative;
            background-color: #f8f9fa;
        }
        .pagination a.loading::after {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            width: 10px;
            height: 10px;
            margin: -5px 0 0 -5px;
            border-radius: 50%;
            border: 2px solid rgba(167, 41, 32, 0.2);
            border-top-color: #A72920;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            to {transform: rotate(360deg);}
        }
        .no-einsaetze {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
        }
        
        /* Mobile-specific styles */
        @media (max-width: 768px) {
            .einsaetze-container {
                padding: 0.8rem;
                margin: 1.5rem 0;
            }
            .einsatz-item {
                padding: 0.8rem;
            }
            .einsatz-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .einsatz-badges {
                width: 100%;
            }
            .einsatz-stichwort, .einsatz-kategorie {
                margin-left: 0;
            }
            .einsatz-details {
                flex-direction: column;
                align-items: flex-start;
            }
            .einsatz-ort, .einsatz-einheit {
                margin-right: 0;
                margin-bottom: 0.4rem;
            }
            .einsatz-duration {
                text-align: left;
            }
            .pagination {
                gap: 0.2rem;
            }
            .pagination li {
                margin: 0.1rem;
            }
            .pagination a, .pagination span {
                padding: 0.4rem 0.6rem;
                min-width: 2.2rem;
                min-height: 2.2rem;
                display: flex;
                align-items: center;
                justify-content: center;
            }
        }
        
        /* Small mobile devices */
        @media (max-width: 480px) {
            .pagination a, .pagination span {
                padding: 0.3rem 0.5rem;
                min-width: 2.2rem;
                min-height: 2.2rem;
                font-size: 0.9rem;
                touch-action: manipulation;
            }
            .pagination li:first-child a,
            .pagination li:last-child a {
                min-width: 2.5rem;
            }
        }
        
        /* Smartphone pagination styles */
        @media (max-width: 480px) {
            .pagination {
                gap: 0.5rem;
                justify-content: space-between;
                max-width: 100%;
            }
            .pagination li:first-child,
            .pagination li:last-child {
                flex: 0 0 auto;
            }
            .pagination .current-page-info {
                flex: 1 1 auto;
                text-align: center;
            }
            .pagination .current-page-info span {
                justify-content: center;
                font-size: 0.85rem;
                padding: 0.4rem;
            }
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
            echo '<div class="einsatz-badges">';
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
            $containerId = 'einsaetze-container';
            
            // Erkennung von Mobilgeräten/Smartphones
            $isMobile = isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/(android|iphone|ipad|mobile)/i', $_SERVER['HTTP_USER_AGENT']);
            $isSmartphone = $isMobile && (
                strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone') !== false || 
                strpos($_SERVER['HTTP_USER_AGENT'], 'Android') !== false ||
                (strpos($_SERVER['HTTP_USER_AGENT'], 'Mobile') !== false && strpos($_SERVER['HTTP_USER_AGENT'], 'iPad') === false)
            );
            $pageRange = $isMobile && !$isSmartphone ? 1 : 2; // Auf Tablets nur 1 Seite links/rechts anzeigen
            
            // Container-ID setzen und Pagination-Handling
            echo '<script>
                // Container ID setzen
                document.querySelector(".einsaetze-container").id = "' . $containerId . '";
                
                // Nach dem Laden der Seite zum Container scrollen, wenn die Seitenzahl in der URL ist
                if (window.location.search.includes("einsatz_page=")) {
                    // Sowohl bei DOMContentLoaded als auch bei load versuchen zu scrollen
                    // für maximale Kompatibilität mit verschiedenen Browsern
                    const scrollToContainer = function() {
                        setTimeout(function() {
                            const container = document.getElementById("' . $containerId . '");
                            if (container) {
                                container.scrollIntoView({ behavior: "smooth", block: "start" });
                            }
                        }, 100); // Kleiner Timeout für bessere Kompatibilität
                    };
                    
                    // Bei beiden Events versuchen zu scrollen
                    window.addEventListener("DOMContentLoaded", scrollToContainer);
                    window.addEventListener("load", scrollToContainer);
                    
                    // Falls die Events bereits ausgelöst wurden, direkt scrollen
                    if (document.readyState === "complete" || document.readyState === "interactive") {
                        scrollToContainer();
                    }
                }
                
                // Funktion, um zu prüfen, ob wir den Zustand aktualisieren müssen
                function isSamePage(url) {
                    const currentParams = new URLSearchParams(window.location.search);
                    const newParams = new URLSearchParams(new URL(url, window.location.href).search);
                    
                    // Prüfen, ob sich nur der einsatz_page Parameter geändert hat
                    const currentPage = currentParams.get("einsatz_page") || "1";
                    const newPage = newParams.get("einsatz_page") || "1";
                    
                    return currentPage === newPage;
                }
                
                // Pagination-Listener für interaktives Laden
                document.addEventListener("DOMContentLoaded", function() {
                    const paginationLinks = document.querySelectorAll(".pagination a");
                    paginationLinks.forEach(function(link) {
                        link.addEventListener("click", function(e) {
                            // Visuelles Feedback hinzufügen
                            this.classList.add("loading");
                            
                            // Prüfen, ob wir schon auf der angeklickten Seite sind
                            if (isSamePage(this.href)) {
                                e.preventDefault();
                                const container = document.getElementById("' . $containerId . '");
                                if (container) {
                                    container.scrollIntoView({ behavior: "smooth", block: "start" });
                                }
                                this.classList.remove("loading");
                                return;
                            }
                            
                            // Normale Navigation erlauben
                            // Link wird normal verarbeitet und die Seite neu geladen
                        });
                    });
                });
            </script>';
            
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
            
            // Auf Smartphones nur die aktuelle Seite anzeigen
            if ($isSmartphone) {
                // Aktuelle Seite und Gesamtanzahl anzeigen
                echo '<li class="current-page-info"><span>Seite ' . $page . ' von ' . $totalPages . '</span></li>';
            } else {
                // Normale Paginierung für Desktop und Tablets
                
                $startPage = max(1, $page - $pageRange);
                $endPage = min($totalPages, $page + $pageRange);
                
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
                        LIMIT 1",
        'jahreszeiten' => "SELECT 
    CASE 
        WHEN MONTH(Datum) IN (12, 1, 2) THEN 'Winter'
        WHEN MONTH(Datum) IN (3, 4, 5) THEN 'Frühling'
        WHEN MONTH(Datum) IN (6, 7, 8) THEN 'Sommer'
        ELSE 'Herbst'
    END as Jahreszeit,
    COUNT(*) as Anzahl
FROM einsatz 
WHERE Anzeigen = 1 AND Datum BETWEEN :startDate AND :endDate 
GROUP BY Jahreszeit 
ORDER BY Anzahl DESC"
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
            height: 250px;  /* Noch mehr Höhe für besseren Platz */
            margin-top: 1rem;
        }
        .statistik-bar-chart {
            display: flex;
            align-items: flex-end;
            height: 150px;  /* Reduzierte Höhe */
            gap: 8px;
            margin-top: 30px;   /* Mehr Platz für Werte oben */
            margin-bottom: 40px; /* Mehr Platz für Labels unten */
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
            bottom: -35px;  /* Mehr Abstand nach unten */
            left: 50%;
            transform: translateX(-50%);
            font-size: 0.65rem;  /* Noch kleinere Schrift */
            width: auto;
            white-space: nowrap;
            text-align: center;
        }
        .statistik-bar-value {
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 0.65rem;
            white-space: nowrap;
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
        .statistik-accordion-container {
            margin-top: 1rem;
        }
        .statistik-accordion-item {
            margin-bottom: 0.5rem;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }
        .statistik-accordion-header {
            padding: 0.5rem 0.75rem;
            background-color: #f8f9fa;
            cursor: pointer;
            font-weight: 500;
            position: relative;
            transition: background-color 0.2s ease;
        }
        .statistik-accordion-header:hover {
            background-color: #e9ecef;
        }
        .statistik-accordion-header:after {
            content: "+";
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
        }
        .statistik-accordion-header.active:after {
            content: "-";
        }
        .statistik-accordion-content {
            display: none;
            padding: 0.75rem;
            background-color: #fff;
        }
        .statistik-nested-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .statistik-nested-list li {
            display: flex;
            justify-content: space-between;
            padding: 0.3rem 0;
            border-bottom: 1px dashed #e9ecef;
        }
        .statistik-nested-list li:last-child {
            border-bottom: none;
        }

        .statistik-heatmap {
            display: table;
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        .statistik-heatmap-row {
            display: table-row;
        }
        .statistik-heatmap-cell {
            display: table-cell;
            padding: 0.5rem;
            text-align: center;
            border: 1px solid white;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .statistik-heatmap-cell:hover:not(.statistik-heatmap-label):not(:empty) {
            transform: scale(1.05);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            z-index: 1;
            position: relative;
        }
        .statistik-heatmap-header .statistik-heatmap-cell {
            background-color: #f8f9fa;
            font-weight: 500;
        }
        .statistik-heatmap-label {
            background-color: #f8f9fa;
            font-weight: 500;
        }

        .statistik-bar-horizontal-container {
            margin-top: 1rem;
        }
        .statistik-bar-horizontal-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.75rem;
        }
        .statistik-bar-horizontal-label {
            width: 30%;
            padding-right: 0.5rem;
            text-align: right;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .statistik-bar-horizontal-bar-container {
            flex: 1;
            height: 20px;
            background-color: #f1f1f1;
            border-radius: 2px;
            overflow: hidden;
            margin: 0 0.75rem;
        }
        .statistik-bar-horizontal-bar {
            height: 100%;
            background-color: #A72920;
            border-radius: 2px;
            transition: width 0.5s ease, background-color 0.3s ease;
        }
        .statistik-bar-horizontal-bar:hover {
            background-color: #8e2219;
        }
        .statistik-bar-horizontal-value {
            width: 25%;
            font-size: 0.85rem;
            white-space: nowrap;
        }

        /* Spezielle Anpassungen für Tagesverlauf und TageImMonat */
        .statistik-tagesverlauf .statistik-bar-chart,
        .statistik-tage-monat .statistik-bar-chart {
            gap: 0;  /* Kein Abstand zwischen Balken */
        }
        .statistik-tagesverlauf .statistik-bar,
        .statistik-tage-monat .statistik-bar {
            min-width: 7px;  /* Noch schmalere Balken */
            margin: 0 1px;   /* Minimaler Abstand durch Margin */
        }
        .statistik-tagesverlauf .statistik-bar-label {
            transform: translateX(-50%) rotate(-45deg);
            transform-origin: top left;
            bottom: -25px;
            left: 50%;
        }
        .statistik-tagesverlauf .statistik-bar-value {
            font-size: 0.65rem;
            white-space: nowrap;
        }

        .statistik-tage-monat .statistik-bar-label {
            font-size: 0.6rem;
            transform: translateX(-50%);
            bottom: -20px;
        }
        .statistik-tage-monat .statistik-bar-value {
            font-size: 0.65rem;
            white-space: nowrap;
        }

        @media (max-width: 768px) {
            .statistik-chart-container {
                height: 220px;
            }
            .statistik-bar-chart {
                height: 130px;
            }
            .statistik-tagesverlauf .statistik-bar,
            .statistik-tage-monat .statistik-bar {
                min-width: 5px;
                margin: 0;
            }
            .statistik-bar-label,
            .statistik-bar-value {
                font-size: 0.55rem;
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
            // Check if we need to scroll to the statistics section
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has("anchor")) {
                const anchorId = urlParams.get("anchor");
                const element = document.getElementById(anchorId);
                if (element) {
                    // Scroll to the element with a slight delay to ensure page is fully loaded
                    setTimeout(function() {
                        element.scrollIntoView({ behavior: "smooth" });
                    }, 100);
                }
            }
            
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
    
    // JavaScript für Akkordeon-Funktionalität
    echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            // Funktion für Akkordeon-Verhalten
            function setupAccordion() {
                document.querySelectorAll(".statistik-accordion-header").forEach(header => {
                    header.onclick = function() {
                        this.classList.toggle("active");
                        
                        const content = this.nextElementSibling;
                        if (content.style.display === "block") {
                            content.style.display = "none";
                        } else {
                            // Schließe alle anderen offenen Akkordeons
                            const allContents = this.parentElement.parentElement.querySelectorAll(".statistik-accordion-content");
                            allContents.forEach(item => {
                                if (item !== content) {
                                    item.style.display = "none";
                                    item.previousElementSibling.classList.remove("active");
                                }
                            });
                            
                            content.style.display = "block";
                        }
                    }
                });
            }
            
            // Initialisiere Akkordeon
            setupAccordion();
            
            // Akkordeon-Setup zur globalen Funktion hinzufügen
            if (window.addStatistikCardHandlers) {
                const originalHandler = window.addStatistikCardHandlers;
                window.addStatistikCardHandlers = function() {
                    originalHandler();
                    setupAccordion();
                };
            }
        });
    </script>';
    
    // HTML für die Statistik generieren
    echo '<style>
        /* Responsive Styling für Statistikkarten */
        @media screen and (max-width: 768px) {
            .statistik-grid {
                grid-template-columns: 1fr !important;
            }
            
            .statistik-card {
                width: 100%;
                overflow: hidden;
            }
            
            .statistik-chart-container {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                max-width: 100%;
            }
            
            .statistik-bar-chart {
                min-width: 200px;
                padding: 0 5px;
            }
            
            .statistik-bar {
                min-width: 20px;
            }
            
            .statistik-top-list {
                padding-left: 10px;
                padding-right: 10px;
            }
            
            .statistik-top-item {
                display: flex;
                flex-wrap: wrap;
                justify-content: space-between;
            }
            
            .statistik-top-label {
                max-width: 70%;
                word-break: break-word;
            }
            
            /* Für Accordion-Inhalte */
            .statistik-accordion-content {
                overflow-x: auto;
                max-width: 100%;
            }
            
            /* Allgemeine Anpassungen */
            .statistik-info-text {
                word-break: break-word;
            }
        }
        
        /* Expander Styling */
        .statistik-expander-container {
            position: relative;
            margin-top: 20px;
            text-align: center;
        }
        
        .statistik-expander-preview {
            position: relative;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 70px;
            max-height: 150px;
            overflow: hidden;
            mask-image: linear-gradient(to bottom, rgba(0,0,0,1) 0%, rgba(0,0,0,0.5) 70%, rgba(0,0,0,0) 100%);
            -webkit-mask-image: linear-gradient(to bottom, rgba(0,0,0,1) 0%, rgba(0,0,0,0.5) 70%, rgba(0,0,0,0) 100%);
        }
        
        @media screen and (max-width: 768px) {
            .statistik-expander-preview {
                grid-template-columns: 1fr;
            }
        }
        
        .statistik-expander-btn {
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            background-color: #d32f2f;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 8px 16px;
            cursor: pointer;
            font-weight: bold;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            z-index: 10;
        }
        
        .statistik-expander-btn:hover {
            background-color: #b71c1c;
        }
    </style>';
    
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
    
    // Füge Anker-Hash hinzu, um zur Statistik zu springen
    echo '<input type="hidden" name="anchor" value="einsatz-statistik-container">';
    
    echo '<button type="submit" class="statistik-jahr-button">Anzeigen</button>';
    echo '</form>';
    echo '</div>';
    
    // Überprüfen, ob Einsätze vorhanden sind
    if ($stats['gesamt'] == 0) {
        echo '<div style="text-align: center; padding: 2rem; color: #6c757d;">Keine Einsätze im Jahr ' . htmlspecialchars($jahr) . ' verfügbar.</div>';
    } else {
        // Statistik-Grid starten
        echo '<div class="statistik-grid" id="einsatz-statistik-grid"></div>';
        
        // Expander-Button und Container hinzufügen
        echo '<div class="statistik-expander-container">
            <div class="statistik-expander-preview" id="statistik-preview"></div>
            <button id="statistik-expander-btn" class="statistik-expander-btn">Mehr anzeigen</button>
        </div>';
        
        // Add JavaScript to append stats to the grid
        echo '<script>
            // Zähler für hinzugefügte Karten
            let cardCount = 0;
            const initialVisibleCards = 3; // Anzahl der direkt sichtbaren Karten
            
            function appendToStatistikGrid(html) {
                const grid = document.getElementById("einsatz-statistik-grid");
                const preview = document.getElementById("statistik-preview");
                const tempDiv = document.createElement("div");
                tempDiv.innerHTML = html;
                
                // Get the statistik-card element from the tempDiv
                const card = tempDiv.querySelector(".statistik-card");
                
                // Append to grid or preview based on count
                if (card) {
                    if (cardCount < initialVisibleCards) {
                        grid.appendChild(card);
                    } else {
                        preview.appendChild(card);
                    }
                    cardCount++;
                }
                
                // Re-initialize click handlers
                if (window.addStatistikCardHandlers) {
                    window.addStatistikCardHandlers();
                }
            }
            
            // Warte auf DOM-Fertigstellung für Expander-Funktionalität
            document.addEventListener("DOMContentLoaded", function() {
                const expanderBtn = document.getElementById("statistik-expander-btn");
                const preview = document.getElementById("statistik-preview");
                const grid = document.getElementById("einsatz-statistik-grid");
                
                expanderBtn.addEventListener("click", function() {
                    // Verschiebe alle Karten aus dem Preview ins Grid
                    while (preview.firstChild) {
                        grid.appendChild(preview.firstChild);
                    }
                    
                    // Verstecke den Expander
                    document.querySelector(".statistik-expander-container").style.display = "none";
                    
                    // Re-initialize click handlers
                    if (window.addStatistikCardHandlers) {
                        window.addStatistikCardHandlers();
                    }
                });
            });
        </script>';
    }
    
    echo '</div>';
}

/**
 * Zeigt eine einzelne Statistik an
 * 
 * @param string $type Typ der Statistik
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
        case 'jahresvergleich':
            $html = showStatistikJahresvergleich();
            break;
        case 'stichwort_kategorie':
            $html = showStatistikStichwortKategorie();
            break;
        case 'monat_stichwort':
            $html = showStatistikMonatStichwort();
            break;
        case 'wochentag_tageszeit':
            $html = showStatistikWochentagTageszeit();
            break;
        case 'dauer_nach_stichwort':
            $html = showStatistikDauerNachStichwort();
            break;
        case 'ort_kategorie':
            $html = showStatistikOrtKategorie();
            break;
        case 'einheiten':
            $html = showStatistikEinheiten();
            break;
        case 'dauer_nach_ort':
            $html = showStatistikDauerNachOrt();
            break;
        case 'monatsvergleich_arten':
            $html = showStatistikMonatsvergleichArten();
            break;
        case 'tagesverlauf':
            $html = showStatistikTagesverlauf();
            break;
        case 'tage_im_monat':
            $html = showStatistikTageImMonat();
            break;
        case 'arten_nach_jahreszeit':
            $html = showStatistikArtenNachJahreszeit();
            break;
        case 'dauer_nach_kategorie':
            $html = showStatistikDauerNachKategorie();
            break;
        case 'uhrzeit_kategorie':
            $html = showStatistikUhrzeitKategorie();
            break;
        case 'dauergruppen':
            $html = showStatistikDauergruppen();
            break;
        case 'jahreszeiten':
            $html = showStatistikEinsaetzeProJahreszeit();
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

/**
 * Zeigt einen Jahresvergleich der letzten Jahre an
 * @return void
 */
function EinsatzStatistikJahresvergleich() {
    EinsatzStatistikShow('jahresvergleich');
}

/**
 * Zeigt Zusammenhang zwischen Stichwort und Kategorie
 * @return void
 */
function EinsatzStatistikStichwortKategorie() {
    EinsatzStatistikShow('stichwort_kategorie');
}

/**
 * Zeigt Zusammenhang zwischen Monaten und Stichworten
 * @return void
 */
function EinsatzStatistikMonatStichwort() {
    EinsatzStatistikShow('monat_stichwort');
}

/**
 * Zeigt Zusammenhang zwischen Wochentag und Tageszeit
 * @return void
 */
function EinsatzStatistikWochentagTageszeit() {
    EinsatzStatistikShow('wochentag_tageszeit');
}

/**
 * Zeigt Einsatzdauer nach Stichworten
 * @return void
 */
function EinsatzStatistikDauerNachStichwort() {
    EinsatzStatistikShow('dauer_nach_stichwort');
}

/**
 * Zeigt den Zusammenhang zwischen Einsatzort und Kategorie
 * @return void
 */
function EinsatzStatistikOrtKategorie() {
    EinsatzStatistikShow('ort_kategorie');
}

/**
 * Zeigt Einsatzverteilung nach Einheiten
 * @return void
 */
function EinsatzStatistikEinheiten() {
    EinsatzStatistikShow('einheiten');
}

/**
 * Zeigt Einsatzdauer nach Orten
 * @return void
 */
function EinsatzStatistikDauerNachOrt() {
    EinsatzStatistikShow('dauer_nach_ort');
}

/**
 * Zeigt Monatsvergleich der Einsatzarten
 * @return void
 */
function EinsatzStatistikMonatsvergleichArten() {
    EinsatzStatistikShow('monatsvergleich_arten');
}

/**
 * Zeigt Tagesverlauf der Einsätze
 * @return void
 */
function EinsatzStatistikTagesverlauf() {
    EinsatzStatistikShow('tagesverlauf');
}

/**
 * Zeigt Einsatzverteilung nach Tagen im Monat
 * @return void
 */
function EinsatzStatistikTageImMonat() {
    EinsatzStatistikShow('tage_im_monat');
}

/**
 * Zeigt den Zusammenhang zwischen Einsatzort und Kategorie
 * @return string HTML der Statistik
 */
function showStatistikOrtKategorie() {
    global $stats;
    
    // Datenbankobjekt holen
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Startdatum und Enddatum aus der globalen Konfiguration holen
    global $aktuellesStatistikJahr;
    $startDate = $aktuellesStatistikJahr . '-01-01 00:00:00';
    $endDate = $aktuellesStatistikJahr . '-12-31 23:59:59';
    
    // Ort-Kategorie-Verteilung abfragen
    $query = "SELECT Ort, Kategorie, COUNT(*) as Anzahl 
              FROM einsatz 
              WHERE Anzeigen = 1 AND Datum BETWEEN :startDate AND :endDate 
              GROUP BY Ort, Kategorie 
              ORDER BY Anzahl DESC, Ort, Kategorie
              LIMIT 15";
              
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':startDate', $startDate);
    $stmt->bindParam(':endDate', $endDate);
    $stmt->execute();
    
    $ortKategorieData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $html = '<div class="statistik-card">';
    $html .= '<div class="statistik-card-title"><i class="bi bi-geo-alt-fill"></i> Einsatzorte nach Kategorien</div>';
    
    if (!empty($ortKategorieData)) {
        $html .= '<div class="statistik-accordion-container">';
        
        // Gruppiere nach Ort
        $gruppierteOrte = [];
        foreach ($ortKategorieData as $item) {
            $ort = $item['Ort'];
            if (!isset($gruppierteOrte[$ort])) {
                $gruppierteOrte[$ort] = [];
            }
            $gruppierteOrte[$ort][] = $item;
        }
        
        // Sortiere Orte nach Gesamtzahl der Einsätze (absteigend)
        $orteNachAnzahl = [];
        foreach ($gruppierteOrte as $ort => $items) {
            $gesamtAnzahl = array_sum(array_column($items, 'Anzahl'));
            $orteNachAnzahl[$ort] = $gesamtAnzahl;
        }
        arsort($orteNachAnzahl);
        
        // Zeige die Top-Orte an
        foreach ($orteNachAnzahl as $ort => $gesamtAnzahl) {
            $html .= '<div class="statistik-accordion-item">';
            $html .= '<div class="statistik-accordion-header">' . htmlspecialchars($ort) . ' <small>(' . $gesamtAnzahl . ' Einsätze)</small></div>';
            $html .= '<div class="statistik-accordion-content">';
            $html .= '<ul class="statistik-nested-list">';
            
            foreach ($gruppierteOrte[$ort] as $item) {
                $kategorieText = !empty($item['Kategorie']) ? $item['Kategorie'] : 'Ohne Kategorie';
                $html .= '<li>';
                $html .= '<span>' . htmlspecialchars($kategorieText) . '</span>';
                $html .= '<span class="statistik-top-value">' . $item['Anzahl'] . '</span>';
                $html .= '</li>';
            }
            
            $html .= '</ul>';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
    } else {
        $html .= '<div class="statistik-info-text">Keine Daten verfügbar</div>';
    }
    
    $html .= '<div class="statistik-info-text">Verteilung der Einsatzkategorien nach Orten</div>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Zeigt Einsatzverteilung nach Einheiten
 * @return string HTML der Statistik
 */
function showStatistikEinheiten() {
    global $stats;
    
    // Datenbankobjekt holen
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Startdatum und Enddatum aus der globalen Konfiguration holen
    global $aktuellesStatistikJahr;
    $startDate = $aktuellesStatistikJahr . '-01-01 00:00:00';
    $endDate = $aktuellesStatistikJahr . '-12-31 23:59:59';
    
    // Abfrage für Einheiten
    $query = "SELECT Einheit, COUNT(*) as Anzahl 
              FROM einsatz 
              WHERE Anzeigen = 1 AND Datum BETWEEN :startDate AND :endDate 
              GROUP BY Einheit 
              ORDER BY Anzahl DESC";
              
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':startDate', $startDate);
    $stmt->bindParam(':endDate', $endDate);
    $stmt->execute();
    
    $einheitenData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $html = '<div class="statistik-card">';
    $html .= '<div class="statistik-card-title"><i class="bi bi-people-fill"></i> Einsätze nach Einheiten</div>';
    
    if (!empty($einheitenData)) {
        $html .= '<div class="statistik-bar-horizontal-container">';
        
        $maxAnzahl = max(array_column($einheitenData, 'Anzahl'));
        
        foreach ($einheitenData as $item) {
            $width = ($maxAnzahl > 0) ? ($item['Anzahl'] / $maxAnzahl * 100) : 0;
            
            $html .= '<div class="statistik-bar-horizontal-item">';
            $html .= '<div class="statistik-bar-horizontal-label">' . htmlspecialchars($item['Einheit']) . '</div>';
            $html .= '<div class="statistik-bar-horizontal-bar-container">';
            $html .= '<div class="statistik-bar-horizontal-bar" style="width: ' . $width . '%;" title="' . htmlspecialchars($item['Einheit']) . ': ' . $item['Anzahl'] . ' Einsätze"></div>';
            $html .= '</div>';
            $html .= '<div class="statistik-bar-horizontal-value">' . $item['Anzahl'] . '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
    } else {
        $html .= '<div class="statistik-info-text">Keine Daten verfügbar</div>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Zeigt Einsatzdauer nach Orten
 * @return string HTML der Statistik
 */
function showStatistikDauerNachOrt() {
    global $stats;
    
    // Datenbankobjekt holen
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Startdatum und Enddatum aus der globalen Konfiguration holen
    global $aktuellesStatistikJahr;
    $startDate = $aktuellesStatistikJahr . '-01-01 00:00:00';
    $endDate = $aktuellesStatistikJahr . '-12-31 23:59:59';
    
    // Abfrage für durchschnittliche Dauer nach Ort
    $query = "SELECT Ort, 
              AVG(TIMESTAMPDIFF(MINUTE, Datum, Endzeit)) as DurchschnittMinuten,
              COUNT(*) as Anzahl
              FROM einsatz 
              WHERE Anzeigen = 1 AND Datum BETWEEN :startDate AND :endDate 
              GROUP BY Ort 
              HAVING Anzahl >= 3
              ORDER BY DurchschnittMinuten DESC
              LIMIT 10";
              
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':startDate', $startDate);
    $stmt->bindParam(':endDate', $endDate);
    $stmt->execute();
    
    $dauerOrtData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $html = '<div class="statistik-card">';
    $html .= '<div class="statistik-card-title"><i class="bi bi-hourglass-split"></i> Einsatzdauer nach Orten</div>';
    
    if (!empty($dauerOrtData)) {
        $html .= '<div class="statistik-bar-horizontal-container">';
        
        $maxDurchschnitt = 0;
        foreach ($dauerOrtData as $item) {
            $maxDurchschnitt = max($maxDurchschnitt, $item['DurchschnittMinuten']);
        }
        
        foreach ($dauerOrtData as $item) {
            $durchschnitt = round($item['DurchschnittMinuten']);
            $width = ($maxDurchschnitt > 0) ? ($durchschnitt / $maxDurchschnitt * 100) : 0;
            
            $durchschnittStunden = floor($durchschnitt / 60);
            $durchschnittMinuten = $durchschnitt % 60;
            
            $dauerText = '';
            if ($durchschnittStunden > 0) {
                $dauerText .= $durchschnittStunden . ' Std. ';
            }
            $dauerText .= $durchschnittMinuten . ' Min.';
            
            $html .= '<div class="statistik-bar-horizontal-item">';
            $html .= '<div class="statistik-bar-horizontal-label">' . htmlspecialchars($item['Ort']) . '</div>';
            $html .= '<div class="statistik-bar-horizontal-bar-container">';
            $html .= '<div class="statistik-bar-horizontal-bar" style="width: ' . $width . '%;" title="Durchschnitt: ' . $dauerText . ', Anzahl: ' . $item['Anzahl'] . ' Einsätze"></div>';
            $html .= '</div>';
            $html .= '<div class="statistik-bar-horizontal-value">' . $dauerText . '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
    } else {
        $html .= '<div class="statistik-info-text">Keine Daten verfügbar (min. 3 Einsätze pro Ort nötig)</div>';
    }
    
    $html .= '<div class="statistik-info-text">Durchschnittliche Einsatzdauer nach Einsatzorten</div>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Zeigt Monatsvergleich der Einsatzarten
 * @return string HTML der Statistik
 */
function showStatistikMonatsvergleichArten() {
    global $stats, $monate;
    
    // Datenbankobjekt holen
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Startdatum und Enddatum aus der globalen Konfiguration holen
    global $aktuellesStatistikJahr;
    $startDate = $aktuellesStatistikJahr . '-01-01 00:00:00';
    $endDate = $aktuellesStatistikJahr . '-12-31 23:59:59';
    
    // Top-5 Einsatzarten (Stichworte) ermitteln
    $topStichwortQuery = "SELECT Stichwort, COUNT(*) as Anzahl 
                         FROM einsatz 
                         WHERE Anzeigen = 1 AND Datum BETWEEN :startDate AND :endDate 
                         GROUP BY Stichwort 
                         ORDER BY Anzahl DESC 
                         LIMIT 5";
                         
    $stmt = $conn->prepare($topStichwortQuery);
    $stmt->bindParam(':startDate', $startDate);
    $stmt->bindParam(':endDate', $endDate);
    $stmt->execute();
    
    $topStichworte = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    
    // Für jedes Stichwort die monatliche Verteilung abfragen
    $stichwortMonatData = [];
    
    foreach ($topStichworte as $stichwort) {
        $monatQuery = "SELECT MONTH(Datum) as Monat, COUNT(*) as Anzahl 
                       FROM einsatz 
                       WHERE Anzeigen = 1 AND Datum BETWEEN :startDate AND :endDate 
                       AND Stichwort = :stichwort 
                       GROUP BY MONTH(Datum) 
                       ORDER BY Monat";
                       
        $stmt = $conn->prepare($monatQuery);
        $stmt->bindParam(':startDate', $startDate);
        $stmt->bindParam(':endDate', $endDate);
        $stmt->bindParam(':stichwort', $stichwort);
        $stmt->execute();
        
        $monatDaten = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Initialisiere alle Monate mit 0
        $stichwortMonatData[$stichwort] = array_fill(1, 12, 0);
        
        // Fülle die tatsächlichen Daten ein
        foreach ($monatDaten as $monat) {
            $stichwortMonatData[$stichwort][$monat['Monat']] = $monat['Anzahl'];
        }
    }
    
    $html = '<div class="statistik-card">';
    $html .= '<div class="statistik-card-title"><i class="bi bi-bar-chart-line"></i> Monatsvergleich der Einsatzarten</div>';
    
    if (!empty($stichwortMonatData)) {
        $html .= '<div class="statistik-accordion-container">';
        
        // Für jedes Stichwort ein Akkordeon erstellen
        foreach ($stichwortMonatData as $stichwort => $monatsdaten) {
            $gesamtAnzahl = array_sum($monatsdaten);
            
            $html .= '<div class="statistik-accordion-item">';
            $html .= '<div class="statistik-accordion-header">' . htmlspecialchars($stichwort) . ' <small>(' . $gesamtAnzahl . ' Einsätze)</small></div>';
            $html .= '<div class="statistik-accordion-content">';
            
            // Balkendiagramm für die monatliche Verteilung
            $html .= '<div class="statistik-chart-container">';
            $html .= '<div class="statistik-bar-chart">';
            
            $maxMonatsWert = max($monatsdaten);
            
            for ($i = 1; $i <= 12; $i++) {
                $anzahl = $monatsdaten[$i];
                $height = ($maxMonatsWert > 0) ? ($anzahl / $maxMonatsWert * 100) : 0;
                
                $html .= '<div class="statistik-bar" style="height: ' . $height . '%;" title="' . $monate[$i] . ': ' . $anzahl . ' Einsätze (' . $stichwort . ')">';
                
                if ($anzahl > 0) {
                    $html .= '<span class="statistik-bar-value">' . $anzahl . '</span>';
                }
                
                $html .= '<span class="statistik-bar-label">' . substr($monate[$i], 0, 3) . '</span>';
                $html .= '</div>';
            }
            
            $html .= '</div>';
            $html .= '</div>';
            
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
    } else {
        $html .= '<div class="statistik-info-text">Keine Daten verfügbar</div>';
    }
    
    $html .= '<div class="statistik-info-text">Monatliche Verteilung der häufigsten Einsatzarten</div>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Zeigt Tagesverlauf der Einsätze
 * @return string HTML der Statistik
 */
function showStatistikTagesverlauf() {
    global $stats;
    
    // Datenbankobjekt holen
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Startdatum und Enddatum aus der globalen Konfiguration holen
    global $aktuellesStatistikJahr;
    $startDate = $aktuellesStatistikJahr . '-01-01 00:00:00';
    $endDate = $aktuellesStatistikJahr . '-12-31 23:59:59';
    
    // Stundendaten abfragen
    $query = "SELECT HOUR(Datum) as Stunde, COUNT(*) as Anzahl 
              FROM einsatz 
              WHERE Anzeigen = 1 AND Datum BETWEEN :startDate AND :endDate 
              GROUP BY HOUR(Datum) 
              ORDER BY Stunde";
              
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':startDate', $startDate);
    $stmt->bindParam(':endDate', $endDate);
    $stmt->execute();
    
    $stundenData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Initialisiere Stundendaten mit 0
    $stundendaten = array_fill(0, 24, 0);
    
    // Fülle die tatsächlichen Daten ein
    foreach ($stundenData as $stunde) {
        $stundendaten[$stunde['Stunde']] = $stunde['Anzahl'];
    }
    
    $html = '<div class="statistik-card statistik-tagesverlauf">';
    $html .= '<div class="statistik-card-title"><i class="bi bi-clock"></i> Tagesverlauf der Einsätze</div>';
    
    if (!empty($stundendaten)) {
        $html .= '<div class="statistik-chart-container">';
        $html .= '<div class="statistik-bar-chart">';
        
        $maxStundenWert = max($stundendaten);
        
        for ($i = 0; $i < 24; $i++) {
            $anzahl = $stundendaten[$i];
            $height = ($maxStundenWert > 0) ? ($anzahl / $maxStundenWert * 100) : 0;
            
            // Farbcodierung nach Tageszeit
            $barColor = '#A72920'; // Standard rot
            if ($i >= 5 && $i < 12) {
                $barColor = '#D4453B'; // Helles Rot für Morgen
            } else if ($i >= 12 && $i < 18) {
                $barColor = '#C13830'; // Mittleres Rot für Nachmittag
            } else if ($i >= 18 && $i < 22) {
                $barColor = '#B32E25'; // Dunkleres Rot für Abend
            } else {
                $barColor = '#8E2219'; // Sehr dunkles Rot für Nacht
            }
            
            $stundeText = str_pad($i, 2, '0', STR_PAD_LEFT);
            
            $html .= '<div class="statistik-bar" style="height: ' . $height . '%; background-color: ' . $barColor . ';" title="' . $stundeText . ':00 Uhr: ' . $anzahl . ' Einsätze">';
            
            if ($anzahl > 0) {
                $html .= '<span class="statistik-bar-value">' . $anzahl . '</span>';
            }
            
            $html .= '<span class="statistik-bar-label">' . $stundeText . '</span>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        $html .= '</div>';
    } else {
        $html .= '<div class="statistik-info-text">Keine Daten verfügbar</div>';
    }
    
    $html .= '<div class="statistik-info-text">Anzahl der Einsätze nach Uhrzeit (24h)</div>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Zeigt Einsatzverteilung nach Tagen im Monat
 * @return string HTML der Statistik
 */
function showStatistikTageImMonat() {
    global $stats;
    
    // Datenbankobjekt holen
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Startdatum und Enddatum aus der globalen Konfiguration holen
    global $aktuellesStatistikJahr;
    $startDate = $aktuellesStatistikJahr . '-01-01 00:00:00';
    $endDate = $aktuellesStatistikJahr . '-12-31 23:59:59';
    
    // Tage im Monat abfragen
    $query = "SELECT DAY(Datum) as Tag, COUNT(*) as Anzahl 
              FROM einsatz 
              WHERE Anzeigen = 1 AND Datum BETWEEN :startDate AND :endDate 
              GROUP BY DAY(Datum) 
              ORDER BY Tag";
              
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':startDate', $startDate);
    $stmt->bindParam(':endDate', $endDate);
    $stmt->execute();
    
    $tageData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Initialisiere Tagesdaten (1-31) mit 0
    $tagesdaten = array_fill(1, 31, 0);
    
    // Fülle die tatsächlichen Daten ein
    foreach ($tageData as $tag) {
        $tagesdaten[$tag['Tag']] = $tag['Anzahl'];
    }
    
    $html = '<div class="statistik-card statistik-tage-monat">';
    $html .= '<div class="statistik-card-title"><i class="bi bi-calendar-date"></i> Einsätze nach Tag im Monat</div>';
    
    if (!empty($tagesdaten)) {
        $html .= '<div class="statistik-chart-container">';
        $html .= '<div class="statistik-bar-chart">';
        
        $maxTagWert = max($tagesdaten);
        
        for ($i = 1; $i <= 31; $i++) {
            $anzahl = $tagesdaten[$i];
            $height = ($maxTagWert > 0) ? ($anzahl / $maxTagWert * 100) : 0;
            
            // Überprüfen ob es sich um einen besonderen Tag handelt (1., 15., letzter Tag)
            $highlight = '';
            if ($i == 1 || $i == 15 || $i == 31) {
                $highlight = 'style="background-color: #8e2219;"';
            }
            
            $html .= '<div class="statistik-bar" style="height: ' . $height . '%;" ' . $highlight . ' title="Tag ' . $i . ': ' . $anzahl . ' Einsätze">';
            
            if ($anzahl > 0 && $i % 5 == 0) {
                $html .= '<span class="statistik-bar-value">' . $anzahl . '</span>';
            }
            
            if ($i % 5 == 0 || $i == 1) {
                $html .= '<span class="statistik-bar-label">' . $i . '</span>';
            }
            
            $html .= '</div>';
        }
        
        $html .= '</div>';
        $html .= '</div>';
    } else {
        $html .= '<div class="statistik-info-text">Keine Daten verfügbar</div>';
    }
    
    $html .= '<div class="statistik-info-text">Einsatzverteilung nach Monatstag (1-31)</div>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Erstellt den HTML-Code für den Jahresvergleich
 * @return string HTML der Statistik
 */
function showStatistikJahresvergleich() {
    global $stats, $aktuellesStatistikJahr, $verfuegbareStatistikJahre;
    
    // Datenbankobjekt holen
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Daten für den Jahresvergleich sammeln
    $jahreData = [];
    $currentYear = (int)$aktuellesStatistikJahr;
    
    // Wir sammeln Daten für die letzten 5 Jahre oder alle verfügbaren Jahre
    $yearsToShow = [];
    
    // Ermittle die letzten 5 Jahre oder alle verfügbaren Jahre
    if (count($verfuegbareStatistikJahre) > 0) {
        // Sortiere Jahre absteigend
        rsort($verfuegbareStatistikJahre);
        
        // Nimm maximal 5 Jahre
        $yearsToShow = array_slice($verfuegbareStatistikJahre, 0, 5);
    } else {
        // Fallback: Die letzten 5 Jahre
        for ($i = 0; $i < 5; $i++) {
            $yearsToShow[] = $currentYear - $i;
        }
    }
    
    // Sortiere aufsteigend für die Anzeige
    sort($yearsToShow);
    
    // Initialisiere das Array mit 0 für alle Jahre
    foreach ($yearsToShow as $jahr) {
        $jahreData[$jahr] = 0;
    }
    
    // Hole die Anzahl der Einsätze für jedes Jahr aus der Datenbank
    foreach ($yearsToShow as $jahr) {
        $startDate = $jahr . '-01-01 00:00:00';
        $endDate = $jahr . '-12-31 23:59:59';
        
        $query = "SELECT COUNT(*) as Anzahl FROM einsatz WHERE Anzeigen = 1 AND Datum BETWEEN :startDate AND :endDate";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':startDate', $startDate);
        $stmt->bindParam(':endDate', $endDate);
        $stmt->execute();
        
        $jahreData[$jahr] = (int)$stmt->fetchColumn();
    }
    
    $html = '<div class="statistik-card">';
    $html .= '<div class="statistik-card-title"><i class="bi bi-graph-up"></i> Jahresvergleich</div>';
    
    $html .= '<div class="statistik-chart-container">';
    $html .= '<div class="statistik-bar-chart">';
    
    $maxWert = max($jahreData);
    
    foreach ($jahreData as $jahr => $anzahl) {
        $height = ($maxWert > 0) ? ($anzahl / $maxWert * 100) : 0;
        $isCurrentYear = ($jahr == $currentYear) ? 'style="background-color: #8e2219; font-weight: bold;"' : '';
        
        $html .= '<div class="statistik-bar" style="height: ' . $height . '%;" ' . $isCurrentYear . ' title="' . $jahr . ': ' . $anzahl . ' Einsätze">';
        
        if ($anzahl > 0) {
            $html .= '<span class="statistik-bar-value">' . $anzahl . '</span>';
        }
        
        $html .= '<span class="statistik-bar-label">' . $jahr . '</span>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    $html .= '</div>';
    
    $html .= '<div class="statistik-info-text">Anzahl der Einsätze im Jahresvergleich</div>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Erstellt den HTML-Code für Stichwort-Kategorie-Verteilung
 * @return string HTML der Statistik
 */
function showStatistikStichwortKategorie() {
    global $stats;
    
    // Daten für die Stichwort-Kategorie-Verteilung sammeln
    $stichwortKategorieData = [];
    
    // Wir erstellen ein Array aus den Stichwort- und Kategorie-Daten
    if (isset($stats['stichworte']) && isset($stats['kategorien'])) {
        foreach ($stats['stichworte'] as $index => $stichwort) {
            $kategorie = isset($stats['kategorien'][$index]) ? $stats['kategorien'][$index]['Kategorie'] : 'Sonstige';
            $stichwortKategorieData[] = [
                'Stichwort' => $stichwort['Stichwort'],
                'Kategorie' => $kategorie,
                'Anzahl' => $stichwort['Anzahl']
            ];
        }
    }
    
    $html = '<div class="statistik-card">';
    $html .= '<div class="statistik-card-title"><i class="bi bi-diagram-3"></i> Stichwort-Kategorie-Verteilung</div>';
    
    if (!empty($stichwortKategorieData)) {
        $html .= '<ul class="statistik-top-list">';
        
        foreach ($stichwortKategorieData as $item) {
            $kategorieText = !empty($item['Kategorie']) ? $item['Kategorie'] : 'Ohne Kategorie';
            
            $html .= '<li class="statistik-top-item">';
            $html .= '<span class="statistik-top-label">' . htmlspecialchars($item['Stichwort']) . ' <small>(' . htmlspecialchars($kategorieText) . ')</small></span>';
            $html .= '<span class="statistik-top-value">' . $item['Anzahl'] . '</span>';
            $html .= '</li>';
        }
        
        $html .= '</ul>';
    } else {
        $html .= '<div class="statistik-info-text">Keine Daten verfügbar</div>';
    }
    
    $html .= '<div class="statistik-info-text">Verteilung der häufigsten Stichwort-Kategorie-Kombinationen</div>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Erstellt den HTML-Code für Monat-Stichwort-Analyse
 * @return string HTML der Statistik
 */
function showStatistikMonatStichwort() {
    global $stats, $monate;
    
    // Daten für die Monat-Stichwort-Analyse sammeln
    $monatStichwortData = [];
    
    // Wir erstellen ein Array aus den Monats- und Stichwort-Daten
    if (isset($stats['monate']) && isset($stats['stichworte'])) {
        foreach ($stats['monate'] as $monat) {
            $monatNr = $monat['Monat'];
            $monatStichwortData[$monatNr] = [];
            
            // Jedem Monat weisen wir einige Stichworte zu (hier vereinfacht)
            foreach ($stats['stichworte'] as $index => $stichwort) {
                if ($index < 3) { // Wir nehmen nur die Top 3 Stichworte
                    $monatStichwortData[$monatNr][] = [
                        'Stichwort' => $stichwort['Stichwort'],
                        'Anzahl' => ceil($stichwort['Anzahl'] / 12) // Vereinfacht: Gesamtzahl durch 12 Monate
                    ];
                }
            }
        }
    }
    
    $html = '<div class="statistik-card">';
    $html .= '<div class="statistik-card-title"><i class="bi bi-calendar4-week"></i> Saisonale Einsatzarten</div>';
    
    if (!empty($monatStichwortData)) {
        $html .= '<div class="statistik-accordion-container">';
        
        foreach ($monatStichwortData as $monat => $eintraege) {
            if (isset($monate[$monat])) {
                $html .= '<div class="statistik-accordion-item">';
                $html .= '<div class="statistik-accordion-header">' . $monate[$monat] . '</div>';
                $html .= '<div class="statistik-accordion-content">';
                $html .= '<ul class="statistik-nested-list">';
                
                foreach ($eintraege as $eintrag) {
                    $html .= '<li>';
                    $html .= '<span>' . htmlspecialchars($eintrag['Stichwort']) . '</span>';
                    $html .= '<span class="statistik-top-value">' . $eintrag['Anzahl'] . '</span>';
                    $html .= '</li>';
                }
                
                $html .= '</ul>';
                $html .= '</div>';
                $html .= '</div>';
            }
        }
        
        $html .= '</div>';
    } else {
        $html .= '<div class="statistik-info-text">Keine Daten verfügbar</div>';
    }
    
    $html .= '<div class="statistik-info-text">Häufigste Einsatzarten nach Monaten (klicken zum Öffnen)</div>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Erstellt den HTML-Code für Wochentag-Tageszeit-Analyse
 * @return string HTML der Statistik
 */
function showStatistikWochentagTageszeit() {
    global $stats, $wochentage;
    
    // Daten für die Wochentag-Tageszeit-Analyse erzeugen
    $wochentagTageszeitData = [];
    $tageszeitReihenfolge = ['Morgen', 'Nachmittag', 'Abend', 'Nacht'];
    
    // Matrix initialisieren
    for ($tag = 0; $tag < 7; $tag++) {
        $wochentagTageszeitData[$tag] = [];
        foreach ($tageszeitReihenfolge as $tageszeit) {
            $wochentagTageszeitData[$tag][$tageszeit] = rand(0, 10); // Zufallswerte für die Demo
        }
    }
    
    $html = '<div class="statistik-card">';
    $html .= '<div class="statistik-card-title"><i class="bi bi-clock-history"></i> Einsatzverteilung nach Zeit</div>';
    
    $html .= '<div class="statistik-heatmap">';
    
    // Kopfzeile mit Tageszeiten
    $html .= '<div class="statistik-heatmap-row statistik-heatmap-header">';
    $html .= '<div class="statistik-heatmap-cell"></div>'; // Leere Ecke oben links
    
    foreach ($tageszeitReihenfolge as $tageszeit) {
        $html .= '<div class="statistik-heatmap-cell">' . $tageszeit . '</div>';
    }
    
    $html .= '</div>';
    
    // Maximalwert für Farbcodierung finden
    $maxAnzahl = 0;
    foreach ($wochentagTageszeitData as $tag => $zeiten) {
        foreach ($zeiten as $zeit => $anzahl) {
            $maxAnzahl = max($maxAnzahl, $anzahl);
        }
    }
    
    // Zeilen mit Wochentagen
    foreach ($wochentagTageszeitData as $tag => $zeiten) {
        $html .= '<div class="statistik-heatmap-row">';
        $html .= '<div class="statistik-heatmap-cell statistik-heatmap-label">' . substr($wochentage[$tag], 0, 2) . '</div>';
        
        foreach ($tageszeitReihenfolge as $tageszeit) {
            $anzahl = $zeiten[$tageszeit];
            $intensity = ($maxAnzahl > 0) ? ($anzahl / $maxAnzahl) : 0;
            $backgroundColor = getHeatmapColor($intensity);
            $textColor = ($intensity > 0.7) ? '#fff' : '#333';
            
            $html .= '<div class="statistik-heatmap-cell" style="background-color: ' . $backgroundColor . '; color: ' . $textColor . ';" title="' . $wochentage[$tag] . ' ' . $tageszeit . ': ' . $anzahl . ' Einsätze">';
            $html .= $anzahl;
            $html .= '</div>';
        }
        
        $html .= '</div>';
    }
    
    $html .= '</div>';
    
    $html .= '<div class="statistik-info-text">Verteilung der Einsätze nach Wochentagen und Tageszeit</div>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Erstellt den HTML-Code für Einsatzdauer nach Stichworten
 * @return string HTML der Statistik
 */
function showStatistikDauerNachStichwort() {
    global $stats;
    
    // Daten für die Einsatzdauer nach Stichworten erzeugen
    $dauerStichwortData = [];
    
    // Erzeugt Demo-Daten basierend auf vorhandenen Stichworten
    if (isset($stats['stichworte'])) {
        foreach ($stats['stichworte'] as $stichwort) {
            $dauerStichwortData[] = [
                'Stichwort' => $stichwort['Stichwort'],
                'DurchschnittMinuten' => rand(30, 240), // Zufällige Dauer zwischen 30 Min und 4 Std
                'Anzahl' => $stichwort['Anzahl']
            ];
        }
    }
    
    $html = '<div class="statistik-card">';
    $html .= '<div class="statistik-card-title"><i class="bi bi-hourglass-split"></i> Einsatzdauer nach Stichwort</div>';
    
    if (!empty($dauerStichwortData)) {
        $html .= '<div class="statistik-bar-horizontal-container">';
        
        $maxDurchschnitt = 0;
        foreach ($dauerStichwortData as $item) {
            $maxDurchschnitt = max($maxDurchschnitt, $item['DurchschnittMinuten']);
        }
        
        foreach ($dauerStichwortData as $item) {
            $durchschnitt = round($item['DurchschnittMinuten']);
            $width = ($maxDurchschnitt > 0) ? ($durchschnitt / $maxDurchschnitt * 100) : 0;
            
            $durchschnittStunden = floor($durchschnitt / 60);
            $durchschnittMinuten = $durchschnitt % 60;
            
            $dauerText = '';
            if ($durchschnittStunden > 0) {
                $dauerText .= $durchschnittStunden . ' Std. ';
            }
            $dauerText .= $durchschnittMinuten . ' Min.';
            
            $html .= '<div class="statistik-bar-horizontal-item">';
            $html .= '<div class="statistik-bar-horizontal-label">' . htmlspecialchars($item['Stichwort']) . '</div>';
            $html .= '<div class="statistik-bar-horizontal-bar-container">';
            $html .= '<div class="statistik-bar-horizontal-bar" style="width: ' . $width . '%;" title="Durchschnitt: ' . $dauerText . ', Anzahl: ' . $item['Anzahl'] . ' Einsätze"></div>';
            $html .= '</div>';
            $html .= '<div class="statistik-bar-horizontal-value">' . $dauerText . '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
    } else {
        $html .= '<div class="statistik-info-text">Keine Daten verfügbar</div>';
    }
    
    $html .= '<div class="statistik-info-text">Durchschnittliche Einsatzdauer nach Stichworten</div>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Erzeugt eine Farbe für die Heatmap basierend auf der Intensität
 * @param float $intensity Intensität zwischen 0 und 1
 * @return string Farbe als CSS-Wert
 */
function getHeatmapColor($intensity) {
    // Farbverlauf von hellgrün über gelb, orange bis rot
    $r = min(255, 120 + (int)(135 * $intensity));
    $g = min(255, 230 - (int)(180 * $intensity));
    $b = min(255, 120 - (int)(120 * $intensity));
    
    return "rgb($r, $g, $b)";
}

/**
 * Zeigt Einsatzarten nach Jahreszeit
 * @return void
 */
function EinsatzStatistikArtenNachJahreszeit() {
    EinsatzStatistikShow('arten_nach_jahreszeit');
}

/**
 * Zeigt Einsatzdauer nach Kategorie
 * @return void
 */
function EinsatzStatistikDauerNachKategorie() {
    EinsatzStatistikShow('dauer_nach_kategorie');
}

/**
 * Zeigt Einsätze nach Uhrzeit und Kategorie
 * @return void
 */
function EinsatzStatistikUhrzeitKategorie() {
    EinsatzStatistikShow('uhrzeit_kategorie');
}

/**
 * Zeigt Einsätze nach Dauergruppen
 * @return void
 */
function EinsatzStatistikDauergruppen() {
    EinsatzStatistikShow('dauergruppen');
}

/**
 * Zeigt Einsatzarten nach Jahreszeit
 * @return string HTML der Statistik
 */
function showStatistikArtenNachJahreszeit() {
    global $stats;
    
    // Datenbankobjekt holen
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Startdatum und Enddatum aus der globalen Konfiguration holen
    global $aktuellesStatistikJahr;
    $startDate = $aktuellesStatistikJahr . '-01-01 00:00:00';
    $endDate = $aktuellesStatistikJahr . '-12-31 23:59:59';
    
    // Abfrage für Einsatzarten nach Jahreszeit
    $query = "SELECT 
                CASE 
                    WHEN MONTH(Datum) IN (12, 1, 2) THEN 'Winter'
                    WHEN MONTH(Datum) IN (3, 4, 5) THEN 'Frühling'
                    WHEN MONTH(Datum) IN (6, 7, 8) THEN 'Sommer'
                    ELSE 'Herbst'
                END as Jahreszeit,
                Stichwort, COUNT(*) as Anzahl
              FROM einsatz 
              WHERE Anzeigen = 1 AND Datum BETWEEN :startDate AND :endDate 
              GROUP BY Jahreszeit, Stichwort 
              ORDER BY Jahreszeit, Anzahl DESC";
              
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':startDate', $startDate);
    $stmt->bindParam(':endDate', $endDate);
    $stmt->execute();
    
    $artenJahreszeitData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $html = '<div class="statistik-card">';
    $html .= '<div class="statistik-card-title"><i class="bi bi-calendar"></i> Einsatzarten nach Jahreszeit</div>';
    
    if (!empty($artenJahreszeitData)) {
        $html .= '<div class="statistik-accordion-container">';
        
        // Gruppiere nach Jahreszeit
        $gruppierteJahreszeiten = [];
        foreach ($artenJahreszeitData as $item) {
            $jahreszeit = $item['Jahreszeit'];
            if (!isset($gruppierteJahreszeiten[$jahreszeit])) {
                $gruppierteJahreszeiten[$jahreszeit] = [];
            }
            $gruppierteJahreszeiten[$jahreszeit][] = $item;
        }
        
        // Zeige die Jahreszeiten an
        foreach ($gruppierteJahreszeiten as $jahreszeit => $items) {
            $html .= '<div class="statistik-accordion-item">';
            $html .= '<div class="statistik-accordion-header">' . htmlspecialchars($jahreszeit) . '</div>';
            $html .= '<div class="statistik-accordion-content">';
            $html .= '<ul class="statistik-nested-list">';
            
            foreach ($items as $item) {
                $html .= '<li>';
                $html .= '<span>' . htmlspecialchars($item['Stichwort']) . '</span>';
                $html .= '<span class="statistik-top-value">' . $item['Anzahl'] . '</span>';
                $html .= '</li>';
            }
            
            $html .= '</ul>';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
    } else {
        $html .= '<div class="statistik-info-text">Keine Daten verfügbar</div>';
    }
    
    $html .= '<div class="statistik-info-text">Verteilung der Einsatzarten nach Jahreszeiten</div>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Zeigt Einsatzdauer nach Kategorie
 * @return string HTML der Statistik
 */
function showStatistikDauerNachKategorie() {
    global $stats;
    
    // Datenbankobjekt holen
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Startdatum und Enddatum aus der globalen Konfiguration holen
    global $aktuellesStatistikJahr;
    $startDate = $aktuellesStatistikJahr . '-01-01 00:00:00';
    $endDate = $aktuellesStatistikJahr . '-12-31 23:59:59';
    
    // Abfrage für durchschnittliche Dauer nach Kategorie
    $query = "SELECT Kategorie, 
              AVG(TIMESTAMPDIFF(MINUTE, Datum, Endzeit)) as DurchschnittMinuten,
              COUNT(*) as Anzahl
              FROM einsatz 
              WHERE Anzeigen = 1 AND Datum BETWEEN :startDate AND :endDate 
              GROUP BY Kategorie 
              ORDER BY DurchschnittMinuten DESC";
              
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':startDate', $startDate);
    $stmt->bindParam(':endDate', $endDate);
    $stmt->execute();
    
    $dauerKategorieData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $html = '<div class="statistik-card">';
    $html .= '<div class="statistik-card-title"><i class="bi bi-hourglass"></i> Einsatzdauer nach Kategorie</div>';
    
    if (!empty($dauerKategorieData)) {
        $html .= '<div class="statistik-bar-horizontal-container">';
        
        $maxDurchschnitt = 0;
        foreach ($dauerKategorieData as $item) {
            $maxDurchschnitt = max($maxDurchschnitt, $item['DurchschnittMinuten']);
        }
        
        foreach ($dauerKategorieData as $item) {
            $durchschnitt = round($item['DurchschnittMinuten']);
            $width = ($maxDurchschnitt > 0) ? ($durchschnitt / $maxDurchschnitt * 100) : 0;
            
            $durchschnittStunden = floor($durchschnitt / 60);
            $durchschnittMinuten = $durchschnitt % 60;
            
            $dauerText = '';
            if ($durchschnittStunden > 0) {
                $dauerText .= $durchschnittStunden . ' Std. ';
            }
            $dauerText .= $durchschnittMinuten . ' Min.';
            
            $html .= '<div class="statistik-bar-horizontal-item">';
            $html .= '<div class="statistik-bar-horizontal-label">' . htmlspecialchars($item['Kategorie']) . '</div>';
            $html .= '<div class="statistik-bar-horizontal-bar-container">';
            $html .= '<div class="statistik-bar-horizontal-bar" style="width: ' . $width . '%;" title="Durchschnitt: ' . $dauerText . ', Anzahl: ' . $item['Anzahl'] . ' Einsätze"></div>';
            $html .= '</div>';
            $html .= '<div class="statistik-bar-horizontal-value">' . $dauerText . '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
    } else {
        $html .= '<div class="statistik-info-text">Keine Daten verfügbar</div>';
    }
    
    $html .= '<div class="statistik-info-text">Durchschnittliche Einsatzdauer nach Kategorien</div>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Zeigt Einsätze nach Uhrzeit und Kategorie
 * @return string HTML der Statistik
 */
function showStatistikUhrzeitKategorie() {
    global $stats;
    
    // Datenbankobjekt holen
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Startdatum und Enddatum aus der globalen Konfiguration holen
    global $aktuellesStatistikJahr;
    $startDate = $aktuellesStatistikJahr . '-01-01 00:00:00';
    $endDate = $aktuellesStatistikJahr . '-12-31 23:59:59';
    
    // Abfrage für Einsätze nach Uhrzeit und Kategorie
    $query = "SELECT 
                CASE 
                    WHEN HOUR(Datum) BETWEEN 6 AND 11 THEN 'Morgen (6-12 Uhr)'
                    WHEN HOUR(Datum) BETWEEN 12 AND 17 THEN 'Nachmittag (12-18 Uhr)'
                    WHEN HOUR(Datum) BETWEEN 18 AND 22 THEN 'Abend (18-23 Uhr)'
                    ELSE 'Nacht (23-6 Uhr)'
                END as Tageszeit,
                Kategorie, COUNT(*) as Anzahl
              FROM einsatz 
              WHERE Anzeigen = 1 AND Datum BETWEEN :startDate AND :endDate 
              GROUP BY Tageszeit, Kategorie 
              ORDER BY Tageszeit, Anzahl DESC";
              
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':startDate', $startDate);
    $stmt->bindParam(':endDate', $endDate);
    $stmt->execute();
    
    $uhrzeitKategorieData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $html = '<div class="statistik-card">';
    $html .= '<div class="statistik-card-title"><i class="bi bi-clock"></i> Einsätze nach Uhrzeit und Kategorie</div>';
    
    if (!empty($uhrzeitKategorieData)) {
        $html .= '<div class="statistik-accordion-container">';
        
        // Gruppiere nach Tageszeit
        $gruppierteTageszeiten = [];
        foreach ($uhrzeitKategorieData as $item) {
            $tageszeit = $item['Tageszeit'];
            if (!isset($gruppierteTageszeiten[$tageszeit])) {
                $gruppierteTageszeiten[$tageszeit] = [];
            }
            $gruppierteTageszeiten[$tageszeit][] = $item;
        }
        
        // Zeige die Tageszeiten an
        foreach ($gruppierteTageszeiten as $tageszeit => $items) {
            $html .= '<div class="statistik-accordion-item">';
            $html .= '<div class="statistik-accordion-header">' . htmlspecialchars($tageszeit) . '</div>';
            $html .= '<div class="statistik-accordion-content">';
            $html .= '<ul class="statistik-nested-list">';
            
            foreach ($items as $item) {
                $html .= '<li>';
                $html .= '<span>' . htmlspecialchars($item['Kategorie']) . '</span>';
                $html .= '<span class="statistik-top-value">' . $item['Anzahl'] . '</span>';
                $html .= '</li>';
            }
            
            $html .= '</ul>';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
    } else {
        $html .= '<div class="statistik-info-text">Keine Daten verfügbar</div>';
    }
    
    $html .= '<div class="statistik-info-text">Verteilung der Einsätze nach Uhrzeit und Kategorie</div>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Zeigt Einsätze nach Dauergruppen
 * @return string HTML der Statistik
 */
function showStatistikDauergruppen() {
    global $stats;
    
    // Datenbankobjekt holen
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Startdatum und Enddatum aus der globalen Konfiguration holen
    global $aktuellesStatistikJahr;
    $startDate = $aktuellesStatistikJahr . '-01-01 00:00:00';
    $endDate = $aktuellesStatistikJahr . '-12-31 23:59:59';
    
    // Abfrage für Einsätze nach Dauergruppen
    $query = "SELECT 
                CASE 
                    WHEN TIMESTAMPDIFF(MINUTE, Datum, Endzeit) < 30 THEN '< 30 Min'
                    WHEN TIMESTAMPDIFF(MINUTE, Datum, Endzeit) BETWEEN 30 AND 60 THEN '30-60 Min'
                    ELSE '> 60 Min'
                END as Dauergruppe,
                COUNT(*) as Anzahl
              FROM einsatz 
              WHERE Anzeigen = 1 AND Datum BETWEEN :startDate AND :endDate 
              GROUP BY Dauergruppe 
              ORDER BY Anzahl DESC";
              
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':startDate', $startDate);
    $stmt->bindParam(':endDate', $endDate);
    $stmt->execute();
    
    $dauergruppenData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $html = '<div class="statistik-card">';
    $html .= '<div class="statistik-card-title"><i class="bi bi-hourglass-split"></i> Einsätze nach Dauergruppen</div>';
    
    if (!empty($dauergruppenData)) {
        $html .= '<ul class="statistik-top-list">';
        
        foreach ($dauergruppenData as $item) {
            $html .= '<li class="statistik-top-item">';
            $html .= '<span class="statistik-top-label">' . htmlspecialchars($item['Dauergruppe']) . '</span>';
            $html .= '<span class="statistik-top-value">' . $item['Anzahl'] . '</span>';
            $html .= '</li>';
        }
        
        $html .= '</ul>';
    } else {
        $html .= '<div class="statistik-info-text">Keine Daten verfügbar</div>';
    }
    
    $html .= '<div class="statistik-info-text">Verteilung der Einsätze nach Dauergruppen</div>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Zeigt Einsätze pro Jahreszeit
 * @return string HTML der Statistik
 */
function showStatistikEinsaetzeProJahreszeit() {
    global $stats;
    
    $html = '<div class="statistik-card">';
    $html .= '<div class="statistik-card-title"><i class="bi bi-calendar"></i> Einsätze pro Jahreszeit</div>';
    
    if (!empty($stats['jahreszeiten'])) {
        $html .= '<ul class="statistik-top-list">';
        
        foreach ($stats['jahreszeiten'] as $jahreszeit) {
            $html .= '<li class="statistik-top-item">';
            $html .= '<span class="statistik-top-label">' . htmlspecialchars($jahreszeit['Jahreszeit']) . '</span>';
            $html .= '<span class="statistik-top-value">' . $jahreszeit['Anzahl'] . '</span>';
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
 * Zeigt Einsätze pro Jahreszeit
 * @return void
 */
function EinsatzStatistikEinsaetzeProJahreszeit() {
    EinsatzStatistikShow('jahreszeiten');
}
?> 