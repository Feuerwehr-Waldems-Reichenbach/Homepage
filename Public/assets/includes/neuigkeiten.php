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
 * @return void
 */
function showNeuigkeiten($itemsPerPage = 5, $customClass = '') {
    // Always include CSS as the first step
    echo loadNeuigkeitenCSS();
    
    // Get current page from URL parameter
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $itemsPerPage;
    
    // Get total count for pagination
    $db = Database::getInstance()->getConnection();
    
    $currentDate = date('Y-m-d');
    $stmt = $db->prepare("SELECT COUNT(*) FROM neuigkeiten WHERE aktiv = 1 AND Datum >= :currentDate");
    $stmt->bindParam(':currentDate', $currentDate);
    $stmt->execute();
    $totalItems = $stmt->fetchColumn();
    $totalPages = ceil($totalItems / $itemsPerPage);
    
    // Get news items for current page
    $stmt = $db->prepare("SELECT * FROM neuigkeiten WHERE aktiv = 1 AND Datum >= :currentDate ORDER BY Datum ASC LIMIT :limit OFFSET :offset");
    $stmt->bindParam(':currentDate', $currentDate);
    $stmt->bindParam(':limit', $itemsPerPage, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $neuigkeiten = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Output the news items
    echo '<div class="neuigkeiten-container ' . $customClass . '">';
    
    if (count($neuigkeiten) > 0) {
        foreach ($neuigkeiten as $neuigkeit) {
            echo renderNeuigkeitCard($neuigkeit);
        }
        
        // Pagination
        if ($totalPages > 1) {
            echo '<div class="pagination">';
            for ($i = 1; $i <= $totalPages; $i++) {
                $activeClass = ($i == $page) ? 'active' : '';
                echo '<a href="?page=' . $i . '" class="page-link ' . $activeClass . '">' . $i . '</a>';
            }
            echo '</div>';
        }
    } else {
        echo '<p>Keine Neuigkeiten vorhanden.</p>';
    }
    
    echo '</div>';
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
    
    $imagePath = !empty($neuigkeit['path_to_image']) ? $neuigkeit['path_to_image'] : '/assets/images/default-news.png';
    
    $html = '<div class="karte neuigkeit-karte">';
    $html .= '<div class="bildbereich neuigkeit-bildbereich">';
    $html .= '<img src="' . htmlspecialchars($imagePath) . '" alt="' . htmlspecialchars($neuigkeit['Ueberschrift']) . '" class="neuigkeit-bild">';
    $html .= '</div>';
    $html .= '<div class="infobereich neuigkeit-infobereich">';
    $html .= '<h2 class="neuigkeit-titel">' . htmlspecialchars($neuigkeit['Ueberschrift']) . '</h2>';
    $html .= '<div class="info-details neuigkeit-details">';
    $html .= '<span class="datum neuigkeit-datum">' . $formatiertesDatum . '</span> | ';
    $html .= '<span class="ort neuigkeit-ort">' . htmlspecialchars($neuigkeit['Ort']) . '</span>';
    $html .= '</div>';
    $html .= '<div class="neuigkeit-volltext">' . nl2br(htmlspecialchars($neuigkeit['Information'])) . '</div>';
    
    // Footer mit Aktionsbuttons
    $html .= '<div class="neuigkeit-footer">';
    
    // Kalender-Download-Button (immer anzeigen)
    $html .= '<a href="/assets/includes/kalender-download.php?id=' . $neuigkeit['ID'] . '" class="btn-neuigkeit-aktion kalender-btn">';
    $html .= '<i class="far fa-calendar-alt"></i> Kalender';
    $html .= '</a>';
    
    // Bild-/Flyer-Buttons nur anzeigen, wenn ein Bild existiert
    if (!empty($neuigkeit['path_to_image'])) {
        $imageUrl = htmlspecialchars($neuigkeit['path_to_image']);
        $absoluteImageUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $imageUrl;
        $title = htmlspecialchars($neuigkeit['Ueberschrift']);
        
        // Bild-Download-Button
        $html .= '<a href="' . $imageUrl . '" class="btn-neuigkeit-aktion" download>';
        $html .= '<i class="fas fa-download"></i> Bild';
        $html .= '</a>';
        
        // WhatsApp Teilen
        $whatsappText = urlencode("Schau dir dieses Event an: " . $absoluteImageUrl);
        $html .= '<a href="https://wa.me/?text=' . $whatsappText . '" target="_blank" class="btn-neuigkeit-aktion">';
        $html .= '<i class="fab fa-whatsapp"></i> WhatsApp';
        $html .= '</a>';
    }
    
    $html .= '</div>';
    
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Helper function to load the Neuigkeiten CSS
 * Uses a version number to prevent caching issues
 * 
 * @return string The link tag for the CSS
 */
function loadNeuigkeitenCSS() {
    $version = '1.0.8'; // Increment this when you make CSS changes
    return '<link rel="stylesheet" href="/assets/css/neuigkeiten.css?v=' . $version . '">' . 
           '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">';
}

/**
 * Example of usage in other files:
 * 
 * <?php
 * require_once $_SERVER['DOCUMENT_ROOT'] . '/assets/includes/neuigkeiten.php';
 * 
 * // Show all news items with pagination
 * showNeuigkeiten();
 * 
 * ?>
 */
?>
