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
 * Shows a popup modal if there are active popups within their date range
 * 
 * @return void
 */
function ShowPotentialPopup() {
    $db = Database::getInstance()->getConnection();
    $currentDate = date('Y-m-d H:i:s');
    
    // Get all active popups within their date range
    $stmt = $db->prepare("SELECT * FROM neuigkeiten WHERE aktiv = 1 AND is_popup = 1 AND popup_start <= :currentDate1 AND (popup_end >= :currentDate2 OR popup_end IS NULL) ORDER BY popup_start ASC");
    $stmt->bindParam(':currentDate1', $currentDate);
    $stmt->bindParam(':currentDate2', $currentDate);
    $stmt->execute();
    $popups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($popups) > 0) {
        // Load the existing CSS
        echo loadNeuigkeitenCSS();
        
        // Add additional CSS for the popup modal
        echo '<style>
            .popup-modal {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0,0,0,0.7);
                z-index: 9999;
                justify-content: center;
                align-items: center;
                padding: 20px;
            }
            
            .popup-container {
                position: relative;
                max-width: 100%;
                max-height: 90vh;
                overflow: visible;
            }
            
            .popup-headline {
                text-align: center;
                margin-bottom: 15px;
                color: white;
                font-size: 1.5rem;
                font-weight: bold;
                text-shadow: 0px 0px 5px rgba(0,0,0,0.7);
                animation: pulse 2s infinite;
                position: relative;
                padding-bottom: 10px;
            }
            
            .popup-headline:after {
                content: "";
                position: absolute;
                bottom: 0;
                left: 50%;
                transform: translateX(-50%);
                width: 80px;
                height: 3px;
                background-color: #A72920;
                border-radius: 3px;
            }
            
            @keyframes pulse {
                0% {
                    transform: scale(1);
                }
                50% {
                    transform: scale(1.05);
                }
                100% {
                    transform: scale(1);
                }
            }
            
            .popup-navigation {
                position: absolute;
                top: 50%;
                transform: translateY(-50%);
                background: rgba(167, 41, 32, 0.8);
                color: white;
                border: none;
                width: 40px;
                height: 40px;
                border-radius: 50%;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 20px;
                transition: background-color 0.3s;
                z-index: 10000;
            }
            
            .popup-navigation:hover {
                background: rgba(167, 41, 32, 1);
            }
            
            .popup-prev {
                left: -20px;
            }
            
            .popup-next {
                right: -20px;
            }
            
            .popup-close {
                position: absolute;
                top: -15px;
                right: -15px;
                background: rgba(0,0,0,0.5);
                border: none;
                width: 30px;
                height: 30px;
                border-radius: 50%;
                color: white;
                font-size: 20px;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10000;
            }
            
            /* Popup-spezifische Anpassungen */
            #popupContent .neuigkeiten-container {
                margin: 0 !important;
                gap: 0 !important;
            }
            
            #popupCardsContainer {
                display: none;
            }
            
            @media (max-width: 767px) {
                .popup-headline {
                    font-size: 1.2rem;
                }
                
                .popup-navigation {
                    width: 30px;
                    height: 30px;
                    font-size: 16px;
                }
                
                .popup-prev {
                    left: 10px;
                }
                
                .popup-next {
                    right: 10px;
                }
            }
        </style>';
        
        // Add the popup modal HTML with a default headline (will be updated by JavaScript)
        echo '<div id="popupModal" class="popup-modal">
            <div class="popup-container">
                <h2 class="popup-headline">🔥 Nicht verpassen! 🔥</h2>
                <button class="popup-close">&times;</button>
                <div id="popupContent"></div>
            </div>
            <button class="popup-navigation popup-prev" id="popupPrev">&lt;</button>
            <button class="popup-navigation popup-next" id="popupNext">&gt;</button>
        </div>';
        
        // Render cards for each popup (initially hidden)
        echo '<div id="popupCardsContainer" style="display: none;">';
        foreach ($popups as $index => $popup) {
            echo '<div class="popup-card" data-index="' . $index . '" data-title="' . htmlspecialchars($popup['Ueberschrift']) . '">';
            echo '<div class="neuigkeiten-container">';
            echo renderNeuigkeitCard($popup);
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
        
        // Add JavaScript for popup functionality
        echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                const popups = ' . json_encode(array_keys($popups)) .';
                let currentPopupIndex = 0;
                const modal = document.getElementById("popupModal");
                const content = document.getElementById("popupContent");
                const headline = document.querySelector(".popup-headline");
                const cardsContainer = document.getElementById("popupCardsContainer");
                const cards = document.querySelectorAll(".popup-card");
                const prevBtn = document.getElementById("popupPrev");
                const nextBtn = document.getElementById("popupNext");
                const closeBtn = document.querySelector(".popup-close");
                
                function showPopup(index) {
                    // Get the rendered card HTML
                    const cardToShow = document.querySelector(`.popup-card[data-index="${index}"]`);
                    if (cardToShow) {
                        // Set the content
                        content.innerHTML = cardToShow.innerHTML;
                        
                        // Update headline with the event title
                        const eventTitle = cardToShow.getAttribute("data-title");
                        headline.textContent = `🔥 Nicht verpassen: ${eventTitle} 🔥`;
                        
                        modal.style.display = "flex";
                        
                        // Update navigation buttons
                        prevBtn.style.display = popups.length > 1 ? "flex" : "none";
                        nextBtn.style.display = popups.length > 1 ? "flex" : "none";
                    }
                }
                
                function showNextPopup() {
                    currentPopupIndex = (currentPopupIndex + 1) % popups.length;
                    showPopup(currentPopupIndex);
                }
                
                function showPrevPopup() {
                    currentPopupIndex = (currentPopupIndex - 1 + popups.length) % popups.length;
                    showPopup(currentPopupIndex);
                }
                
                // Event listeners
                prevBtn.addEventListener("click", showPrevPopup);
                nextBtn.addEventListener("click", showNextPopup);
                closeBtn.addEventListener("click", function() {
                    modal.style.display = "none";
                });
                
                // Close when clicking outside
                modal.addEventListener("click", function(e) {
                    if (e.target === modal) {
                        modal.style.display = "none";
                    }
                });
                
                // Handle keyboard navigation
                document.addEventListener("keydown", function(e) {
                    if (modal.style.display === "flex") {
                        if (e.key === "Escape") {
                            modal.style.display = "none";
                        } else if (e.key === "ArrowRight") {
                            showNextPopup();
                        } else if (e.key === "ArrowLeft") {
                            showPrevPopup();
                        }
                    }
                });
                
                // Show first popup
                showPopup(0);
            });
        </script>';
    }
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
