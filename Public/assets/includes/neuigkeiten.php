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
    // Always include CSS as the first step
    $output = loadNeuigkeitenCSS();
    
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
    $html .= '<div class="neuigkeit-footer">';
    $html .= '<a href="/assets/includes/kalender-download.php?id=' . $neuigkeit['ID'] . '" class="kalender-download-btn" title="In Kalender eintragen"><i class="fas fa-calendar-plus"></i> Kalendereintrag</a>';
    $html .= '</div>';
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
    $output = loadNeuigkeitenCSS();
    
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("SELECT * FROM neuigkeiten WHERE ID = :id AND aktiv = 1");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $neuigkeit = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($neuigkeit) {
        $output .= '<div class="neuigkeit-detail ' . $customClass . '">';
        $output .= renderNeuigkeitCard($neuigkeit);
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
    $output = loadNeuigkeitenCSS();
    
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
 * Helper function to load the Neuigkeiten CSS
 * Uses a version number to prevent caching issues
 * 
 * @return string The link tag for the CSS
 */
function loadNeuigkeitenCSS() {
    $version = '1.0.6'; // Increment this when you make CSS changes
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
 * echo showNeuigkeiten();
 * 
 * // Show latest 3 news items
 * // echo showLatestNeuigkeiten(3);
 * 
 * // Show with additional CSS class
 * // echo showNeuigkeiten(5, 'my-custom-class');
 * 
 * // Show a single news item by ID
 * // echo showNeuigkeitById($_GET['id']);
 * ?>
 */
?>
