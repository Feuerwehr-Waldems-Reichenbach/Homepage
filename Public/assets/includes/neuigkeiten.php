<?php
require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/Private/Database/Database.php';

/**
 * Functions to display news ("Neuigkeiten") for the website
 */

/**
 * Shows a list of news items with pagination
 * 
 * @param int $itemsPerPage Number of items to display per page
 * @param string $customClass Additional CSS class for styling
 * @return string HTML output
 */
function showNeuigkeiten($itemsPerPage = 5, $customClass = '') {
    // Include CSS
    $output = '<link rel="stylesheet" href="/assets/css/neuigkeiten.css">';
    
    // Get current page from URL parameter
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $itemsPerPage;
    
    // Get total count for pagination
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM neuigkeiten WHERE aktiv = 1");
    $stmt->execute();
    $totalItems = $stmt->fetchColumn();
    $totalPages = ceil($totalItems / $itemsPerPage);
    
    // Get news items for current page
    $stmt = $db->prepare("SELECT * FROM neuigkeiten WHERE aktiv = 1 ORDER BY Datum DESC LIMIT :limit OFFSET :offset");
    $stmt->bindParam(':limit', $itemsPerPage, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $neuigkeiten = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Output the news items
    $output .= '<div class="neuigkeiten-container ' . $customClass . '">';
    
    if (count($neuigkeiten) > 0) {
        foreach ($neuigkeiten as $neuigkeit) {
            $output .= renderNeuigkeitCard($neuigkeit);
        }
        
        // Pagination
        if ($totalPages > 1) {
            $output .= '<div class="pagination">';
            for ($i = 1; $i <= $totalPages; $i++) {
                $activeClass = ($i == $page) ? 'active' : '';
                $output .= '<a href="?page=' . $i . '" class="page-link ' . $activeClass . '">' . $i . '</a>';
            }
            $output .= '</div>';
        }
    } else {
        $output .= '<p>Keine Neuigkeiten vorhanden.</p>';
    }
    
    $output .= '</div>';
    
    return $output;
}

/**
 * Renders a single news item card
 * 
 * @param array $neuigkeit News item data
 * @return string HTML for the news card
 */
function renderNeuigkeitCard($neuigkeit) {
    $datum = new DateTime($neuigkeit['Datum']);
    $formatiertesDatum = $datum->format('d.m.Y');
    
    $imagePath = !empty($neuigkeit['path_to_image']) ? $neuigkeit['path_to_image'] : '/assets/images/default-news.jpg';
    
    $html = '<div class="karte">';
    $html .= '<div class="bildbereich">';
    $html .= '<img src="' . htmlspecialchars($imagePath) . '" alt="' . htmlspecialchars($neuigkeit['Ueberschrift']) . '">';
    $html .= '</div>';
    $html .= '<div class="infobereich">';
    $html .= '<h2>' . htmlspecialchars($neuigkeit['Ueberschrift']) . '</h2>';
    $html .= '<div class="info-details">';
    $html .= '<span class="datum">' . $formatiertesDatum . '</span> | ';
    $html .= '<span class="ort">' . htmlspecialchars($neuigkeit['Ort']) . '</span>';
    $html .= '</div>';
    $html .= '<div class="kurzinfo">' . htmlspecialchars($neuigkeit['kurzinfo']) . '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Shows a single news item by ID
 * 
 * @param int $id ID of the news item to display
 * @param string $customClass Additional CSS class for styling
 * @return string HTML output
 */
function showNeuigkeitById($id, $customClass = '') {
    // Include CSS
    $output = '<link rel="stylesheet" href="/assets/css/neuigkeiten.css">';
    
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("SELECT * FROM neuigkeiten WHERE ID = :id AND aktiv = 1");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $neuigkeit = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($neuigkeit) {
        $output .= '<div class="neuigkeit-detail ' . $customClass . '">';
        $output .= renderNeuigkeitCard($neuigkeit);
        
        // Show full content below the card
        $output .= '<div class="neuigkeit-volltext">';
        $output .= nl2br(htmlspecialchars($neuigkeit['Information']));
        $output .= '</div>';
        $output .= '</div>';
    } else {
        $output .= '<p>Neuigkeit nicht gefunden.</p>';
    }
    
    return $output;
}

/**
 * Shows the latest news items
 * 
 * @param int $count Number of latest news to display
 * @param string $customClass Additional CSS class for styling
 * @return string HTML output
 */
function showLatestNeuigkeiten($count = 3, $customClass = '') {
    // Include CSS
    $output = '<link rel="stylesheet" href="/assets/css/neuigkeiten.css">';
    
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("SELECT * FROM neuigkeiten WHERE aktiv = 1 ORDER BY Datum DESC LIMIT :limit");
    $stmt->bindParam(':limit', $count, PDO::PARAM_INT);
    $stmt->execute();
    $neuigkeiten = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $output .= '<div class="neuigkeiten-latest ' . $customClass . '">';
    
    if (count($neuigkeiten) > 0) {
        foreach ($neuigkeiten as $neuigkeit) {
            $output .= renderNeuigkeitCard($neuigkeit);
        }
    } else {
        $output .= '<p>Keine Neuigkeiten vorhanden.</p>';
    }
    
    $output .= '</div>';
    
    return $output;
}

/**
 * Shows news popup if there is an active popup
 * 
 * @return string HTML output for the popup
 */
function checkAndShowNeuigkeitenPopup() {
    $db = Database::getInstance()->getConnection();
    
    $now = date('Y-m-d H:i:s');
    $stmt = $db->prepare("SELECT * FROM neuigkeiten WHERE aktiv = 1 AND is_popup = 1 
                          AND popup_start <= :now AND popup_end >= :now 
                          ORDER BY Datum DESC LIMIT 1");
    $stmt->bindParam(':now', $now);
    $stmt->execute();
    $popup = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $output = '';
    
    if ($popup) {
        $output .= '<link rel="stylesheet" href="/assets/css/neuigkeiten.css">';
        $output .= '<div id="neuigkeit-popup-overlay" class="popup-overlay">';
        $output .= '<div class="popup-container">';
        $output .= '<div class="popup-close" onclick="closeNeuigkeitenPopup()">×</div>';
        $output .= renderNeuigkeitCard($popup);
        $output .= '</div>';
        $output .= '</div>';
        $output .= '<script>
            function closeNeuigkeitenPopup() {
                document.getElementById("neuigkeit-popup-overlay").style.display = "none";
                // Save in session that this popup was closed
                sessionStorage.setItem("popup_' . $popup['ID'] . '_closed", "true");
            }
            
            // Check if popup was already closed in this session
            if (sessionStorage.getItem("popup_' . $popup['ID'] . '_closed") !== "true") {
                document.getElementById("neuigkeit-popup-overlay").style.display = "flex";
            }
        </script>';
    }
    
    return $output;
}
