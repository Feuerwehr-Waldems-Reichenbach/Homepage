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
function showNeuigkeiten($itemsPerPage = 5, $customClass = '')
{
    // Always include CSS as the first step
    echo loadNeuigkeitenCSS();
    // Get current page from URL parameter
    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
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
function renderNeuigkeitCard($neuigkeit)
{
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
    $html .= '<div class="neuigkeit-volltext">' . $neuigkeit['Information'] . '</div>';
    // Footer mit Aktionsbuttons
    $html .= '<div class="neuigkeit-footer">';
    // Kalender-Download-Button (immer anzeigen)
    $html .= '<a href="/assets/includes/kalender-download.php?id=' . $neuigkeit['ID'] . '" class="btn-neuigkeit-aktion kalender-btn">';
    $html .= '<i class="far fa-calendar-alt"></i> Kalender';
    $html .= '</a>';
    // Bild-URL oder Veranstaltungsseite-URL für den Teilen-Button festlegen
    $title = htmlspecialchars($neuigkeit['Ueberschrift']);
    if (!empty($neuigkeit['path_to_image'])) {
        $imageUrl = htmlspecialchars($neuigkeit['path_to_image']);
        $shareUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $imageUrl;
    } else {
        $shareUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/Veranstaltungen";
    }
    // Teilen-Button (immer anzeigen)
    $html .= '<button class="btn-neuigkeit-aktion share-btn" data-title="' . $title . '" data-url="' . $shareUrl . '">';
    $html .= '<i class="fas fa-share-alt"></i> Teilen';
    $html .= '</button>';
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
function loadNeuigkeitenCSS()
{
    $version = '1.1.1'; // Increment this when you make CSS changes
    return '<link rel="stylesheet" href="/assets/css/neuigkeiten.css?v=' . $version . '">' .
        '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">' .
        '<style>
                .btn-neuigkeit-aktion {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    padding: 5px 10px;
                    background-color: #f8f9fa;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    font-size: 0.85rem;
                    cursor: pointer;
                    color: #333;
                    margin-right: 5px;
                    text-decoration: none;
                    transition: background-color 0.2s;
                }
                .btn-neuigkeit-aktion:hover {
                    background-color: #e9ecef;
                }
                .btn-neuigkeit-aktion i {
                    margin-right: 5px;
                }
            </style>' .
        '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    initShareButtons();
                    // Initialisierung nach jeder Änderung im Popup-Inhalt
                    const observer = new MutationObserver(function(mutations) {
                        mutations.forEach(function(mutation) {
                            if (mutation.type === "childList" && 
                                (mutation.target.id === "popupContent" || 
                                 document.getElementById("popupContent").contains(mutation.target))) {
                                initShareButtons();
                            }
                        });
                    });
                    // Popup-Inhalt beobachten
                    const popupContent = document.getElementById("popupContent");
                    if (popupContent) {
                        observer.observe(popupContent, { childList: true, subtree: true });
                    }
                    // Alle Share-Buttons initialisieren
                    function initShareButtons() {
                        document.querySelectorAll(".share-btn").forEach(function(button) {
                            button.addEventListener("click", function() {
                                const title = this.getAttribute("data-title");
                                const url = this.getAttribute("data-url");
                                const text = "Schau dir dieses Event an: " + title;
                                // Teilen-Dialog mit Fallback-Optionen
                                const shareOptionsContainer = document.getElementById("shareOptionsContainer");
                                if (!shareOptionsContainer) {
                                    // Erstelle Share-Options-Container
                                    const container = document.createElement("div");
                                    container.id = "shareOptionsContainer";
                                    container.style.position = "fixed";
                                    container.style.bottom = "0";
                                    container.style.left = "0";
                                    container.style.right = "0";
                                    container.style.backgroundColor = "white";
                                    container.style.padding = "15px";
                                    container.style.boxShadow = "0 -2px 10px rgba(0, 0, 0, 0.2)";
                                    container.style.zIndex = "99999";
                                    container.style.borderTopLeftRadius = "15px";
                                    container.style.borderTopRightRadius = "15px";
                                    container.style.transform = "translateY(100%)";
                                    container.style.transition = "transform 0.3s ease-out";
                                    // Titel
                                    const shareTitle = document.createElement("h3");
                                    shareTitle.textContent = "Teilen über";
                                    shareTitle.style.textAlign = "center";
                                    shareTitle.style.marginBottom = "15px";
                                    container.appendChild(shareTitle);
                                    // Schließen-Button
                                    const closeButton = document.createElement("button");
                                    closeButton.innerHTML = "&times;";
                                    closeButton.style.position = "absolute";
                                    closeButton.style.top = "10px";
                                    closeButton.style.right = "10px";
                                    closeButton.style.background = "none";
                                    closeButton.style.border = "none";
                                    closeButton.style.fontSize = "20px";
                                    closeButton.style.cursor = "pointer";
                                    closeButton.onclick = function() {
                                        container.style.transform = "translateY(100%)";
                                        setTimeout(() => {
                                            container.remove();
                                        }, 300);
                                    };
                                    container.appendChild(closeButton);
                                    // Optionen Container
                                    const optionsGrid = document.createElement("div");
                                    optionsGrid.style.display = "grid";
                                    optionsGrid.style.gridTemplateColumns = "repeat(4, 1fr)";
                                    optionsGrid.style.gap = "10px";
                                    optionsGrid.style.justifyItems = "center";
                                    container.appendChild(optionsGrid);
                                    // WhatsApp
                                    addShareOption(optionsGrid, "WhatsApp", "fab fa-whatsapp", "#25D366", function() {
                                        window.open("https://wa.me/?text=" + encodeURIComponent(text + " " + url), "_blank");
                                    });
                                    // Telegram
                                    addShareOption(optionsGrid, "Telegram", "fab fa-telegram-plane", "#0088cc", function() {
                                        window.open("https://t.me/share/url?url=" + encodeURIComponent(url) + "&text=" + encodeURIComponent(text), "_blank");
                                    });
                                    // E-Mail
                                    addShareOption(optionsGrid, "E-Mail", "fas fa-envelope", "#dd4b39", function() {
                                        window.location.href = "mailto:?subject=" + encodeURIComponent(title) + "&body=" + encodeURIComponent(text + " " + url);
                                    });
                                    // SMS
                                    addShareOption(optionsGrid, "SMS", "fas fa-sms", "#5BC236", function() {
                                        window.location.href = "sms:?body=" + encodeURIComponent(text + " " + url);
                                    });
                                    // Facebook
                                    addShareOption(optionsGrid, "Facebook", "fab fa-facebook", "#3b5998", function() {
                                        window.open("https://www.facebook.com/sharer/sharer.php?u=" + encodeURIComponent(url), "_blank");
                                    });
                                    // Twitter/X
                                    addShareOption(optionsGrid, "X", "fab fa-x-twitter", "#000000", function() {
                                        window.open("https://twitter.com/intent/tweet?text=" + encodeURIComponent(text) + "&url=" + encodeURIComponent(url), "_blank");
                                    });
                                    // Link kopieren
                                    addShareOption(optionsGrid, "Link kopieren", "fas fa-link", "#333333", function() {
                                        navigator.clipboard.writeText(url).then(function() {
                                            alert("Link in die Zwischenablage kopiert!");
                                        }).catch(function() {
                                            prompt("Link zum Kopieren:", url);
                                        });
                                    });
                                    // Bild-Download
                                    addShareOption(optionsGrid, "Flyer speichern", "fas fa-download", "#A72920", function() {
                                        const a = document.createElement("a");
                                        a.href = url;
                                        a.download = title + ".jpg";
                                        document.body.appendChild(a);
                                        a.click();
                                        document.body.removeChild(a);
                                    });
                                    // Hinzufügen zum DOM und anzeigen
                                    document.body.appendChild(container);
                                    setTimeout(() => {
                                        container.style.transform = "translateY(0)";
                                    }, 10);
                                } else {
                                    shareOptionsContainer.style.transform = "translateY(0)";
                                }
                                // Helper-Funktion zum Hinzufügen von Teilen-Optionen
                                function addShareOption(parent, name, iconClass, color, clickHandler) {
                                    const option = document.createElement("div");
                                    option.style.display = "flex";
                                    option.style.flexDirection = "column";
                                    option.style.alignItems = "center";
                                    option.style.cursor = "pointer";
                                    const iconContainer = document.createElement("div");
                                    iconContainer.style.width = "50px";
                                    iconContainer.style.height = "50px";
                                    iconContainer.style.borderRadius = "50%";
                                    iconContainer.style.backgroundColor = color;
                                    iconContainer.style.display = "flex";
                                    iconContainer.style.justifyContent = "center";
                                    iconContainer.style.alignItems = "center";
                                    iconContainer.style.marginBottom = "5px";
                                    const icon = document.createElement("i");
                                    icon.className = iconClass;
                                    icon.style.color = "white";
                                    icon.style.fontSize = "24px";
                                    iconContainer.appendChild(icon);
                                    const label = document.createElement("span");
                                    label.textContent = name;
                                    label.style.fontSize = "12px";
                                    label.style.textAlign = "center";
                                    option.appendChild(iconContainer);
                                    option.appendChild(label);
                                    option.addEventListener("click", clickHandler);
                                    parent.appendChild(option);
                                }
                            });
                        });
                    }
                });
            </script>';
}
/**
 * Shows a popup modal if there are active popups within their date range
 * 
 * @return void
 */
function ShowPotentialPopup()
{
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
            /* Scrollbar für Neuigkeiten-Text */
            .neuigkeit-volltext {
                max-height: 250px;
                overflow-y: auto;
                padding-right: 5px;
            }
            /* Customizing scrollbar */
            .neuigkeit-volltext::-webkit-scrollbar {
                width: 6px;
            }
            .neuigkeit-volltext::-webkit-scrollbar-track {
                background: #f1f1f1;
                border-radius: 10px;
            }
            .neuigkeit-volltext::-webkit-scrollbar-thumb {
                background: #A72920;
                border-radius: 10px;
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
                /* Mobile scrolling for news content */
                .neuigkeit-volltext {
                    max-height: 150px;
                    overflow-y: auto;
                    border-top: 1px solid rgba(0,0,0,0.1);
                    border-bottom: 1px solid rgba(0,0,0,0.1);
                    padding: 8px 0;
                    margin: 8px 0;
                }
                /* Make popup content scrollable on mobile */
                #popupContent .neuigkeit-karte {
                    max-height: 80vh;
                    overflow-y: auto;
                }
                /* Limit image size on mobile */
                .neuigkeit-bildbereich {
                    max-height: 200px;
                    overflow: hidden;
                }
                .neuigkeit-bild {
                    width: 100%;
                    height: auto;
                    max-height: 200px;
                    object-fit: contain;
                }
                /* Specific adjustments for popup images */
                #popupContent .neuigkeit-bildbereich {
                    max-height: 180px;
                }
                #popupContent .neuigkeit-bild {
                    max-height: 180px;
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
                const popups = ' . json_encode(array_keys($popups)) . ';
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