<?php
require_once 'includes/header.php';

// Reservation-Objekt für Kalenderdaten
require_once 'includes/Reservation.php';
$reservation = new Reservation();

// Aktueller Monat und Jahr
$currentMonth = date('n');
$currentYear = date('Y');

// Überprüfen, ob ein bestimmter Monat und Jahr in der URL angegeben wurden
if (isset($_GET['month']) && isset($_GET['year'])) {
    $month = intval($_GET['month']);
    $year = intval($_GET['year']);
    
    // Validieren
    if ($month >= 1 && $month <= 12 && $year >= date('Y')) {
        $currentMonth = $month;
        $currentYear = $year;
    }
}

// Alle benötigten Informationen aus der Datenbank abrufen
$infoKeys = [
    'WillkommensText',
    'WillkommensUntertext',
    'UebergabeZeit', 
    'MinBuchungszeitraum', 
    'RueckgabeText',
    'ImPreisEnthaltenWasser',
    'ImPreisEnthaltenStrom',
    'ImPreisEnthaltenBiertische',
    'WichtigerHinweis1',
    'WichtigerHinweis2',
    'WichtigerHinweis3',
    'WichtigerHinweis4',
    'VerwaltungspersonVorname',
    'VerwaltungspersonNachname',
    'VerwaltungspersonEmail',
    'VerwaltungspersonTelefon',
    'SystemEmailProbleme'
];

// Preisdaten und Systeminformationen abrufen
$priceInfo = $reservation->getPriceInformation();
$basePrice = number_format($priceInfo['base_price'], 2, ',', '.');
$depositAmount = number_format($priceInfo['deposit_amount'], 2, ',', '.');
$infoData = $reservation->getSystemInformation($infoKeys);

// Dynamische Informationen nach Kategorien abrufen
$grillhuetteInfos = $reservation->getSystemInformation([], 'grillhuette_info');
$imPreisEnthalten = $reservation->getSystemInformation([], 'im_preis_enthalten');
$wichtigeHinweise = $reservation->getSystemInformation([], 'wichtige_hinweise');
?>

<div class="row mb-4">
    <div class="col-12">
        <!-- Reservierungshilfe Button -->
        <div class="text-center mb-3">
            <button id="reservierungshilfeBtn" class="btn btn-primary">
                <i class="bi bi-question-circle"></i> Reservierungshilfe starten
            </button>
        </div>
       
        <!-- Responsives Layout für mobil und desktop -->
        <div class="row">
            <!-- Kalender Container - volle Breite auf mobil, 2/3 auf Desktop -->
            <div class="col-12 col-lg-8 mb-4">
                <div class="calendar-container">
                    <style>
                        /* Kalender-Styling */
                        .day.public-event {
                            background-color: #b8e0d2; /* Grün-bläuliche Farbe für öffentliche Events */
                            color: #333;
                            position: relative;
                        }
                        
                        .day .event-indicator {
                            position: absolute;
                            bottom: 0;
                            left: 0;
                            right: 0;
                            font-size: 10px;
                            background-color: #5a8c7b;
                            color: white;
                            padding: 2px;
                            text-align: center;
                            white-space: nowrap;
                            overflow: hidden;
                            text-overflow: ellipsis;
                            max-width: 100%;
                        }
                        
                        /* Mobile Legend */
                        @media (max-width: 991.98px) {
                            .mobile-legend .public-event-indicator {
                                width: 15px;
                                height: 15px;
                                background-color: #b8e0d2;
                                border-radius: 3px;
                            }
                        }
                    </style>
                    
                    <script>
                        // Global configuration for JavaScript
                        const APP_CONFIG = {
                            ROOT_PATH: '<?php echo APP_ROOT; ?>'
                        };
                    </script>
                    
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <button id="prevMonth" class="btn btn-outline-secondary">
                            <i class="bi bi-chevron-left"></i> <span class="d-none d-md-inline">Vorheriger Monat</span>
                        </button>
                        <h3 id="monthYear" class="mb-0">
                            <?php 
                            $monthNames = ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];
                            echo $monthNames[$currentMonth - 1] . ' ' . $currentYear; 
                            ?>
                        </h3>
                        <button id="nextMonth" class="btn btn-outline-secondary">
                            <span class="d-none d-md-inline">Nächster Monat</span> <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>
                    
                    <div id="calendar">
                        <!-- Kalender wird durch JavaScript gerendert -->
                    </div>
                    
                    <form id="calendarForm" method="get" class="d-none">
                        <input type="hidden" id="month" name="month" value="<?php echo $currentMonth; ?>">
                        <input type="hidden" id="year" name="year" value="<?php echo $currentYear; ?>">
                    </form>
                </div>
                
                <!-- Mobile-optimierte Legende -->
                <div class="d-lg-none mb-4">
                    <div class="card">
                        <div class="card-body p-3">
                            <h5 class="card-title h6 mb-2">Legende:</h5>
                            <div class="d-flex flex-wrap mobile-legend">
                                <div class="me-3 mb-2">
                                    <div class="d-flex align-items-center">
                                        <div class="me-1" style="width: 15px; height: 15px; background-color: #d4edda; border-radius: 3px;"></div>
                                        <small>Frei</small>
                                    </div>
                                </div>
                                <div class="me-3 mb-2">
                                    <div class="d-flex align-items-center">
                                        <div class="me-1" style="width: 15px; height: 15px; background-color: #fff3cd; border-radius: 3px;"></div>
                                        <small>Angefragt</small>
                                    </div>
                                </div>
                                <div class="me-3 mb-2">
                                    <div class="d-flex align-items-center">
                                        <div class="me-1" style="width: 15px; height: 15px; background-color: #f8d7da; border-radius: 3px;"></div>
                                        <small>Belegt</small>
                                    </div>
                                </div>
                                <div class="me-3 mb-2">
                                    <div class="d-flex align-items-center">
                                        <div class="me-1 public-event-indicator" style="width: 15px; height: 15px; background-color: #b8e0d2; border-radius: 3px;"></div>
                                        <small>Veranstaltung</small>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <div class="d-flex align-items-center">
                                        <div class="me-1" style="width: 15px; height: 15px; background-color: #cce5ff; border-radius: 3px; display: flex; align-items: center; justify-content: center;"><i class="bi bi-key" style="font-size: 8px;"></i></div>
                                        <small>Schlüsselübergabe</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Willkommenskarte unter dem Kalender - nur auf Desktop sichtbar -->
                <div class="card mb-4 d-none d-lg-block">
                    <div class="card-body">
                        <?php
                        ?>
                        <h5 class="card-title"><?php echo $infoData['WillkommensText'] ?? 'Willkommen im Reservierungssystem der Grillhütte Waldems Reichenbach'; ?></h5>
                        <p><?php echo $infoData['WillkommensUntertext'] ?? 'Hier können Sie freie Termine einsehen und eine Reservierung vornehmen.'; ?></p>
                        
                        <div class="row mb-3">
                            <div class="col-md-3 mb-2">
                                <div class="d-flex align-items-center">
                                    <div class="me-2" style="width: 20px; height: 20px; background-color: #d4edda; border-radius: 3px;"></div>
                                    <span>Frei</span>
                                </div>
                            </div>
                            <div class="col-md-3 mb-2">
                                <div class="d-flex align-items-center">
                                    <div class="me-2" style="width: 20px; height: 20px; background-color: #fff3cd; border-radius: 3px;"></div>
                                    <span>Angefragt</span>
                                </div>
                            </div>
                            <div class="col-md-3 mb-2">
                                <div class="d-flex align-items-center">
                                    <div class="me-2" style="width: 20px; height: 20px; background-color: #f8d7da; border-radius: 3px;"></div>
                                    <span>Belegt</span>
                                </div>
                            </div>
                            <div class="col-md-3 mb-2">
                                <div class="d-flex align-items-center">
                                    <div class="me-2" style="width: 20px; height: 20px; background-color: #b8e0d2; border-radius: 3px;"></div>
                                    <span>Veranstaltung</span>
                                </div>
                            </div>
                            <div class="col-md-3 mb-2">
                                <div class="d-flex align-items-center">
                                    <div class="me-2" style="width: 20px; height: 20px; background-color: #cce5ff; border-radius: 3px; display: flex; align-items: center; justify-content: center;"><i class="bi bi-key"></i></div>
                                    <span>Schlüsselübergabe</span>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <h5 class="card-title">Informationen zur Grillhütte</h5>
                        <ul class="list-unstyled">
                            <li><strong>Miete:</strong> <?php echo $basePrice; ?>€ pro Tag (<?php echo $infoData['UebergabeZeit'] ?? '12 - 12 Uhr'; ?>)</li>
                            <li><strong>Kaution:</strong> <?php echo $depositAmount; ?>€</li>
                            <li><strong>Rückgabe:</strong> <?php echo $infoData['RueckgabeText'] ?? 'bis spätestens am nächsten Tag 12:00 Uhr'; ?></li>
                            <li><strong>Min. Buchungszeitraum:</strong> <?php echo $infoData['MinBuchungszeitraum'] ?? '1 Tag'; ?></li>
                            <?php 
                            // Zusätzliche dynamische Informationen zur Grillhütte
                            foreach ($grillhuetteInfos as $title => $content): 
                            ?>
                            <li><?php echo $content; ?></li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <h6>Im Mietzins enthalten:</h6>
                        <ul>
                            <?php 
                            // Dynamische Informationen zum Mietzins
                            if (!empty($imPreisEnthalten)) {
                                foreach ($imPreisEnthalten as $title => $content): 
                                ?>
                                <li><?php echo $content; ?></li>
                                <?php 
                                endforeach;
                            } else {
                                // Fallback für alte Einträge, falls die Kategorisierung nicht funktioniert
                                ?>
                                <li><?php echo $infoData['ImPreisEnthaltenWasser'] ?? '1m³ Wasser'; ?></li>
                                <li><?php echo $infoData['ImPreisEnthaltenStrom'] ?? '5 kW/h Strom'; ?></li>
                                <li><?php echo $infoData['ImPreisEnthaltenBiertische'] ?? '5 Biertisch-Garnituren, jede weitere Garnitur zzgl. 1€'; ?></li>
                            <?php } ?>
                        </ul>
                        
                        <div class="alert alert-info">
                            <p class="mb-1"><strong>Wichtige Hinweise:</strong></p>
                            <ul class="mb-0">
                                <?php 
                                // Dynamische wichtige Hinweise
                                if (!empty($wichtigeHinweise)) {
                                    foreach ($wichtigeHinweise as $title => $content): 
                                    ?>
                                    <li><?php echo $content; ?></li>
                                    <?php 
                                    endforeach;
                                } else {
                                    // Fallback für alte Einträge
                                    ?>
                                    <li><?php echo $infoData['WichtigerHinweis1'] ?? 'Es ist ausschließlich Barzahlung möglich'; ?></li>
                                    <li><?php echo $infoData['WichtigerHinweis2'] ?? 'Bitte beachten Sie, dass in den kälteren Monaten (ca. Oktober bis März) die Toiletten möglicherweise nicht nutzbar sind, da das Wasser abgestellt wird. Der genaue Zeitraum kann variieren.'; ?></li>
                                    <li><?php echo $infoData['WichtigerHinweis3'] ?? 'Die Grillhütte sowie die Toiletten sind sauber zu verlassen'; ?></li>
                                    <li><?php echo $infoData['WichtigerHinweis4'] ?? 'Müll ist selbst zu entsorgen'; ?></li>
                                <?php } ?>
                            </ul>
                        </div>
                        
                        <h6>Schlüsselübergabe und Abnahme:</h6>
                        <p><?php echo $infoData['VerwaltungspersonVorname'] ?? 'Julia'; ?> <?php echo $infoData['VerwaltungspersonNachname'] ?? 'Kitschmann'; ?></p>
                        
                        <div class="mt-3">
                            <p><strong>Kontakt zur Verwalterin:</strong></p>
                            <ul class="list-unstyled">
                                <li><a href="javascript:void(0)" class="email-protect" data-encoded="<?php echo base64_encode($infoData['VerwaltungspersonEmail'] ?? 'julia@kitschmann.de'); ?>">E-Mail anzeigen</a></li>
                                <li><a href="javascript:void(0)" class="phone-protect" data-encoded="<?php echo base64_encode($infoData['VerwaltungspersonTelefon'] ?? '0178/8829055'); ?>">Telefonnummer anzeigen</a></li>
                            </ul>
                        </div>                   
                        
                        <div class="mt-3 alert alert-secondary">
                            <p class="mb-0"><strong>Hinweis:</strong> Bei technischen Problemen mit dem Reservierungssystem wenden Sie sich bitte an: <a href="javascript:void(0)" class="email-protect" data-encoded="<?php echo base64_encode($infoData['SystemEmailProbleme'] ?? 'it@feuerwehr-waldems-reichenbach.de'); ?>">IT-Support</a></p>
                        </div>
                    </div>
                </div>
                
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Base64 decode function
                    function decodeBase64(str) {
                        return decodeURIComponent(atob(str).split('').map(function(c) {
                            return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
                        }).join(''));
                    }
                    
                    // Email protection
                    document.querySelectorAll('.email-protect').forEach(function(element) {
                        element.addEventListener('click', function(e) {
                            e.preventDefault();
                            var encodedEmail = this.getAttribute('data-encoded');
                            var email = decodeBase64(encodedEmail);
                            this.innerHTML = email;
                            this.setAttribute('href', 'mailto:' + email);
                        });
                    });
                    
                    // Phone protection
                    document.querySelectorAll('.phone-protect').forEach(function(element) {
                        element.addEventListener('click', function(e) {
                            e.preventDefault();
                            var encodedPhone = this.getAttribute('data-encoded');
                            var phone = decodeBase64(encodedPhone);
                            this.innerHTML = phone;
                            this.setAttribute('href', 'tel:' + phone.replace(/[^0-9+]/g, ''));
                        });
                    });

                    // Für nicht angemeldete Nutzer - Kalender-Klick-Handler
                    <?php if (!isset($_SESSION['user_id'])): ?>
                    const calendarElement = document.getElementById('calendar');
                    if (calendarElement) {
                        calendarElement.addEventListener('click', function(e) {
                            if (e.target.classList.contains('day') && !e.target.classList.contains('other-month') && !e.target.classList.contains('past')) {
                                // Verhindere Standard-Verhalten
                                e.preventDefault();
                                e.stopPropagation();
                                
                                // Zeige Modal
                                const loginModal = new bootstrap.Modal(document.getElementById('loginRequiredModal'));
                                loginModal.show();
                            }
                        }, true);
                    }
                    <?php endif; ?>

                    // Public event toggle
                    const isPublicCheckbox = document.getElementById('is_public');
                    const publicEventDetails = document.getElementById('public-event-details');
                    
                    if (isPublicCheckbox && publicEventDetails) {
                        isPublicCheckbox.addEventListener('change', function() {
                            publicEventDetails.style.display = this.checked ? 'block' : 'none';
                            
                            // If unchecked, clear the fields
                            if (!this.checked) {
                                document.getElementById('event_name').value = '';
                                document.getElementById('display_start_date').value = '';
                                document.getElementById('display_end_date').value = '';
                            } else {
                                // Initialize with the start and end dates
                                const startDate = document.getElementById('start_date');
                                const endDate = document.getElementById('end_date');
                                
                                if (startDate && startDate.value) {
                                    document.getElementById('display_start_date').value = startDate.value;
                                }
                                if (endDate && endDate.value) {
                                    document.getElementById('display_end_date').value = endDate.value;
                                }
                            }
                        });
                    }
                });
                </script>
            </div>
            
            <!-- Reservierungsformular - volle Breite auf mobil, 1/3 auf Desktop -->
            <div class="col-12 col-lg-4">
                <?php if (isset($_SESSION['user_id']) && $_SESSION['is_verified']): ?>
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0">Neue Reservierung</h4>
                        </div>
                        <div class="card-body">
                            <form id="reservationForm" method="post" action="<?php echo getRelativePath('Erstellen'); ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                
                                <div class="mb-3">
                                    <label for="start_date" class="form-label">Startdatum</label>
                                    <input type="text" class="form-control" id="start_date" name="start_date" readonly required>
                                </div>
                                <div class="mb-3">
                                    <label for="end_date" class="form-label">Enddatum</label>
                                    <input type="text" class="form-control" id="end_date" name="end_date" readonly required>
                                </div>
                                
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="receipt_requested" name="receipt_requested" value="1">
                                    <label class="form-check-label" for="receipt_requested">Quittung für die Reservierung gewünscht</label>
                                </div>
                                
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="is_public" name="is_public" value="1">
                                    <label class="form-check-label" for="is_public">Öffentliche Reservierung (im Kalender sichtbar)</label>
                                </div>
                                
                                <div id="public-event-details" style="display: none;">
                                    <div class="mb-3">
                                        <label for="event_name" class="form-label">Name der Veranstaltung</label>
                                        <input type="text" class="form-control" id="event_name" name="event_name" maxlength="255" placeholder="z.B. Grillfest">
                                    </div>

                                    <div class="mb-3 form-check">
                                        <input type="checkbox" class="form-check-input" id="show_date_range" name="show_date_range">
                                        <label class="form-check-label" for="show_date_range">Veranstaltung geht über mehrere Tage</label>
                                    </div>
                                    
                                    <div id="single-day-field" class="mb-3">
                                        <label for="event_day" class="form-label">Veranstaltungstag</label>
                                        <input type="text" class="form-control date-picker" id="event_day" name="event_day">
                                        <div class="form-text">An diesem Tag wird die Veranstaltung im Kalender angezeigt.</div>
                                    </div>
                                    
                                    <div id="date-range-fields" style="display: none;">
                                        <div class="mb-3">
                                            <label for="display_start_date" class="form-label">Veranstaltung anzeigen von</label>
                                            <input type="text" class="form-control date-picker" id="display_start_date" name="display_start_date">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="display_end_date" class="form-label">Veranstaltung anzeigen bis</label>
                                            <input type="text" class="form-control date-picker" id="display_end_date" name="display_end_date">
                                            <div class="form-text">In diesem Zeitraum wird die Veranstaltung im Kalender angezeigt.</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="message" class="form-label">Nachricht / Anmerkungen (optional)</label>
                                    <textarea class="form-control" id="message" name="message" rows="3"></textarea>
                                </div>
                                
                                <!-- Kostenübersicht -->
                                <div class="mb-3">
                                    <label class="form-label">Kostenübersicht</label>
                                    <div class="card">
                                        <div class="card-body p-3">
                                            <?php
                                            // Preisdaten für den angemeldeten Benutzer abrufen
                                            $userPriceInfo = $reservation->getPriceInformation(isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null);
                                            // Explizit 0€ für Feuerwehr-Mitglieder setzen (Sicherheitsmaßnahme)
                                            if (isset($_SESSION['is_Feuerwehr']) && $_SESSION['is_Feuerwehr']) {
                                                $userPriceInfo['user_rate'] = 0.00;
                                            }
                                            $userRate = number_format($userPriceInfo['user_rate'], 2, ',', '.');
                                            $userRateRaw = $userPriceInfo['user_rate'];
                                            ?>
                                            <ul class="list-unstyled mb-0" id="cost-overview" data-user-rate="<?php echo $userRateRaw; ?>">
                                                <li>Grundpreis: <span id="base-cost"><?php echo $userRate; ?>€</span> pro Tag</li>
                                                <li>Anzahl Tage: <span id="day-count">1</span></li>
                                                <li class="border-top mt-2 pt-2"><strong>Gesamtpreis: <span id="total-cost"><?php echo $userRate; ?>€</span></strong></li>
                                                <?php if (isset($_SESSION['is_Feuerwehr']) && $_SESSION['is_Feuerwehr']): ?>
                                                <li class="special-price-note text-success mt-2"><i class="bi bi-check-circle"></i> Spezialpreis für Feuerwehr (0€)</li>
                                                <?php endif; ?>
                                            </ul>
                                            <div class="form-text mt-2">Kaution (<?php echo $depositAmount; ?>€) nicht im Gesamtpreis enthalten.</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100">Reservierung anfragen</button>
                            </form>
                        </div>
                    </div>
                <?php elseif (isset($_SESSION['user_id']) && !$_SESSION['is_verified']): ?>
                    <div class="alert alert-warning">
                        Bitte bestätigen Sie Ihre E-Mail-Adresse, um eine Reservierung vornehmen zu können.
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0">Reservierung</h4>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info mb-4">
                                <p>Um eine Reservierung vornehmen zu können, müssen Sie angemeldet sein und Ihre E-Mail-Adresse bestätigt haben.</p>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <a href="<?php echo getRelativePath('Benutzer/Anmelden'); ?>" class="btn btn-primary">Anmelden</a>
                                <a href="<?php echo getRelativePath('Benutzer/Registrieren'); ?>" class="btn btn-outline-primary">Registrieren</a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Mobile-optimierte Infokarte - nur auf mobil sichtbar -->
                <div class="d-lg-none mt-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Informationen</h5>
                        </div>
                        <div class="card-body p-3">
                            <div class="accordion" id="mobileInfoAccordion">
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                                            Preise & Konditionen
                                        </button>
                                    </h2>
                                    <div id="collapseOne" class="accordion-collapse collapse" data-bs-parent="#mobileInfoAccordion">
                                        <div class="accordion-body">
                                            <ul class="list-unstyled mb-0">
                                                <li><strong>Miete:</strong> <?php echo $basePrice; ?>€ pro Tag (<?php echo $infoData['UebergabeZeit'] ?? '12 - 12 Uhr'; ?>)</li>
                                                <li><strong>Kaution:</strong> <?php echo $depositAmount; ?>€</li>
                                                <li><strong>Rückgabe:</strong> <?php echo $infoData['RueckgabeText'] ?? 'bis spätestens am nächsten Tag 12:00 Uhr'; ?></li>
                                                <li><strong>Min. Buchungszeitraum:</strong> <?php echo $infoData['MinBuchungszeitraum'] ?? '1 Tag'; ?></li>
                                                <?php foreach ($grillhuetteInfos as $title => $content): ?>
                                                <li><?php echo $content; ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                            Im Preis enthalten
                                        </button>
                                    </h2>
                                    <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#mobileInfoAccordion">
                                        <div class="accordion-body">
                                            <ul class="mb-0">
                                                <?php 
                                                if (!empty($imPreisEnthalten)) {
                                                    foreach ($imPreisEnthalten as $title => $content): 
                                                    ?>
                                                    <li><?php echo $content; ?></li>
                                                    <?php 
                                                    endforeach;
                                                } else {
                                                    ?>
                                                    <li><?php echo $infoData['ImPreisEnthaltenWasser'] ?? '1m³ Wasser'; ?></li>
                                                    <li><?php echo $infoData['ImPreisEnthaltenStrom'] ?? '5 kW/h Strom'; ?></li>
                                                    <li><?php echo $infoData['ImPreisEnthaltenBiertische'] ?? '5 Biertisch-Garnituren, jede weitere Garnitur zzgl. 1€'; ?></li>
                                                <?php } ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                            Wichtige Hinweise
                                        </button>
                                    </h2>
                                    <div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#mobileInfoAccordion">
                                        <div class="accordion-body">
                                            <ul class="mb-0">
                                                <?php 
                                                if (!empty($wichtigeHinweise)) {
                                                    foreach ($wichtigeHinweise as $title => $content): 
                                                    ?>
                                                    <li><?php echo $content; ?></li>
                                                    <?php 
                                                    endforeach;
                                                } else {
                                                    ?>
                                                    <li><?php echo $infoData['WichtigerHinweis1'] ?? 'Es ist ausschließlich Barzahlung möglich'; ?></li>
                                                    <li><?php echo $infoData['WichtigerHinweis2'] ?? 'Bitte beachten Sie, dass in den kälteren Monaten (ca. Oktober bis März) die Toiletten möglicherweise nicht nutzbar sind, da das Wasser abgestellt wird. Der genaue Zeitraum kann variieren.'; ?></li>
                                                    <li><?php echo $infoData['WichtigerHinweis3'] ?? 'Die Grillhütte sowie die Toiletten sind sauber zu verlassen'; ?></li>
                                                    <li><?php echo $infoData['WichtigerHinweis4'] ?? 'Müll ist selbst zu entsorgen'; ?></li>
                                                <?php } ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                            Kontakt
                                        </button>
                                    </h2>
                                    <div id="collapseFour" class="accordion-collapse collapse" data-bs-parent="#mobileInfoAccordion">
                                        <div class="accordion-body">
                                            <p class="mb-2"><strong>Verwalterin:</strong> <?php echo $infoData['VerwaltungspersonVorname'] ?? 'Julia'; ?> <?php echo $infoData['VerwaltungspersonNachname'] ?? 'Kitschmann'; ?></p>
                                            <ul class="list-unstyled mb-0">
                                                <li><a href="javascript:void(0)" class="email-protect" data-encoded="<?php echo base64_encode($infoData['VerwaltungspersonEmail'] ?? 'julia@kitschmann.de'); ?>">E-Mail anzeigen</a></li>
                                                <li><a href="javascript:void(0)" class="phone-protect" data-encoded="<?php echo base64_encode($infoData['VerwaltungspersonTelefon'] ?? '0178/8829055'); ?>">Telefon anzeigen</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Login Required Modal -->
<?php if (!isset($_SESSION['user_id'])): ?>
<div class="modal fade" id="loginRequiredModal" tabindex="-1" aria-labelledby="loginRequiredModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="loginRequiredModalLabel">Anmeldung erforderlich</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <i class="bi bi-lock-fill" style="font-size: 3rem; color: #6c757d;"></i>
                </div>
                <p>Um eine Reservierung vornehmen zu können, müssen Sie angemeldet sein und Ihre E-Mail-Adresse bestätigt haben.</p>
                <p>Bitte melden Sie sich an oder erstellen Sie ein neues Konto, um fortzufahren.</p>
            </div>
            <div class="modal-footer d-flex justify-content-center">
                <a href="<?php echo getRelativePath('Benutzer/Anmelden'); ?>" class="btn btn-primary" style="min-width: 120px;">Anmelden</a>
                <a href="<?php echo getRelativePath('Benutzer/Registrieren'); ?>" class="btn btn-outline-primary" style="min-width: 120px;">Registrieren</a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Schwebende Tooltip-Box für die Reservierungshilfe -->
<div id="guideTip" class="guide-tip">
    <div class="guide-tip-header">
        <span id="guideStepTitle">Schritt 1: Kalender ansehen</span>
        <button id="closeGuideTip" class="close-btn">&times;</button>
    </div>
    <div id="guideStepContent" class="guide-tip-content">
        Schauen Sie sich den Kalender an, um verfügbare Tage zu sehen. Grün markierte Tage sind verfügbar.
    </div>
    <div class="guide-tip-footer">
        <span id="stepCounter">Schritt 1 von 6</span>
        <button id="nextGuideStep" class="btn btn-sm btn-primary">Weiter</button>
    </div>
</div>

<style>
    /* Stil für die Reservierungshilfe */
    .guide-tip {
        position: absolute;
        display: none;
        width: 280px;
        background-color: white;
        border: 1px solid #ddd;
        border-radius: 6px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 1000;
        font-size: 14px;
        animation: fadeIn 0.3s;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    .guide-tip-header {
        padding: 8px 12px;
        background-color: #f8f9fa;
        border-bottom: 1px solid #ddd;
        border-radius: 6px 6px 0 0;
        font-weight: bold;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .guide-tip-content {
        padding: 12px;
        line-height: 1.4;
    }
    
    .guide-tip-footer {
        padding: 8px 12px;
        border-top: 1px solid #ddd;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .close-btn {
        background: none;
        border: none;
        font-size: 20px;
        cursor: pointer;
        padding: 0;
        line-height: 1;
    }
    
    .highlight-element {
        position: relative;
        z-index: 10;
        animation: pulse 2s infinite;
        border: 2px solid #007bff !important;
        border-radius: 5px;
        box-shadow: 0 0 10px rgba(0, 123, 255, 0.5);
    }
    
    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(0, 123, 255, 0.7); }
        70% { box-shadow: 0 0 0 10px rgba(0, 123, 255, 0); }
        100% { box-shadow: 0 0 0 0 rgba(0, 123, 255, 0); }
    }
    
    .overlay-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.3);
        z-index: 5;
        display: none;
    }
    
    /* Mobile Anpassungen */
    @media (max-width: 768px) {
        .guide-tip {
            width: 260px;
            position: fixed;
            bottom: 80px;
            left: 50%;
            transform: translateX(-50%);
        }
    }
</style>

<div id="overlayBackdrop" class="overlay-backdrop"></div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Hilfselemente
        const guideBtn = document.getElementById('reservierungshilfeBtn');
        const guideTip = document.getElementById('guideTip');
        const closeGuideTipBtn = document.getElementById('closeGuideTip');
        const nextGuideStepBtn = document.getElementById('nextGuideStep');
        const guideStepTitle = document.getElementById('guideStepTitle');
        const guideStepContent = document.getElementById('guideStepContent');
        const stepCounter = document.getElementById('stepCounter');
        const overlayBackdrop = document.getElementById('overlayBackdrop');
        
        // Aktueller Schritt
        let currentStep = 0;
        let currentHighlightedElement = null;
        let isGuideActive = false;
        
        // Definiere alle Schritte
        const guideSteps = [
            {
                title: "Schritt 1: Kalender ansehen",
                content: "Schauen Sie sich den Kalender an, um verfügbare Tage zu sehen. Grün markierte Tage sind verfügbar.",
                targetSelector: "#calendar",
                position: "right",
                waitForAction: false
            },
            {
                title: "Schritt 2: Startdatum auswählen",
                content: "Klicken Sie auf einen verfügbaren (grünen) Tag im Kalender, um Ihr Startdatum auszuwählen.",
                targetSelector: ".day:not(.other-month):not(.booked):not(.past)",
                position: "right",
                waitForAction: true,
                actionCheck: function() {
                    return document.getElementById('start_date') && document.getElementById('start_date').value !== '';
                }
            },
            {
                title: "Schritt 3: Enddatum auswählen",
                content: "Wählen Sie nun das Enddatum Ihrer Reservierung aus. Sie können den gleichen Tag oder ein späteres Datum wählen.",
                targetSelector: ".day:not(.other-month):not(.booked):not(.past)",
                position: "right",
                waitForAction: true,
                actionCheck: function() {
                    return document.getElementById('end_date') && document.getElementById('end_date').value !== '';
                }
            },
            {
                title: "Schritt 4: Optionen auswählen",
                content: "Hier können Sie zusätzliche Optionen auswählen, wie eine Quittung anzufordern oder es als öffentliche Reservierung zu markieren.",
                targetSelector: "#receipt_requested",
                position: "right",
                waitForAction: false
            },
            {
                title: "Schritt 5: Nachricht eingeben (optional)",
                content: "Sie können eine optionale Nachricht für den Verwalter hinterlassen, z.B. für spezielle Anfragen.",
                targetSelector: "#message",
                position: "top",
                waitForAction: false
            },
            {
                title: "Schritt 6: Reservierung anfragen",
                content: "Überprüfen Sie die Kostenübersicht und klicken Sie auf 'Reservierung anfragen', um Ihre Buchung abzuschließen.",
                targetSelector: "button[type='submit']",
                position: "top",
                waitForAction: true,
                actionCheck: function() {
                    // Diese Funktion würde nur durch das Absenden des Formulars ausgelöst werden,
                    // aber da wir die Seite nicht verlassen wollen, ist dies nur ein Platzhalter
                    return false;
                }
            }
        ];
        
        // Event-Listener für den Guide-Button
        guideBtn.addEventListener('click', function() {
            startGuide();
        });
        
        // Event-Listener zum Schließen des Guides
        closeGuideTipBtn.addEventListener('click', function() {
            endGuide();
        });
        
        // Event-Listener für "Weiter"-Button
        nextGuideStepBtn.addEventListener('click', function() {
            goToNextStep();
        });
        
        // Funktion zum Starten des Guides
        function startGuide() {
            isGuideActive = true;
            currentStep = 0;
            showOverlay();
            showCurrentStep();
        }
        
        // Funktion zum Beenden des Guides
        function endGuide() {
            isGuideActive = false;
            hideGuideTip();
            removeHighlight();
            hideOverlay();
        }
        
        // Funktion zum Anzeigen des aktuellen Schritts
        function showCurrentStep() {
            if (currentStep >= guideSteps.length) {
                endGuide();
                return;
            }
            
            const step = guideSteps[currentStep];
            
            // Titel und Inhalt aktualisieren
            guideStepTitle.textContent = step.title;
            guideStepContent.textContent = step.content;
            stepCounter.textContent = `Schritt ${currentStep + 1} von ${guideSteps.length}`;
            
            // Element hervorheben
            highlightElement(step.targetSelector);
            
            // Tooltip positionieren
            positionGuideTip(step.targetSelector, step.position);
            
            // Warten auf Aktion oder nicht
            if (step.waitForAction) {
                nextGuideStepBtn.style.display = 'none';
                setupActionCheck(step);
            } else {
                nextGuideStepBtn.style.display = 'block';
            }
        }
        
        // Element hervorheben
        function highlightElement(selector) {
            removeHighlight();
            
            const elements = document.querySelectorAll(selector);
            if (elements.length > 0) {
                // Bei mehreren Elementen nur das erste hervorheben
                currentHighlightedElement = elements[0];
                currentHighlightedElement.classList.add('highlight-element');
                
                // Stelle sicher, dass das Element sichtbar ist
                currentHighlightedElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            }
        }
        
        // Highlight entfernen
        function removeHighlight() {
            if (currentHighlightedElement) {
                currentHighlightedElement.classList.remove('highlight-element');
                currentHighlightedElement = null;
            }
            
            // Falls es mehrere Elemente gibt, die hervorgehoben wurden
            document.querySelectorAll('.highlight-element').forEach(el => {
                el.classList.remove('highlight-element');
            });
        }
        
        // Tooltip positionieren
        function positionGuideTip(selector, position) {
            const element = document.querySelector(selector);
            
            if (!element) {
                guideTip.style.display = 'none';
                return;
            }
            
            const rect = element.getBoundingClientRect();
            const isMobile = window.innerWidth <= 768;
            
            if (isMobile) {
                // Auf mobilen Geräten immer am unteren Bildschirmrand
                guideTip.style.top = 'auto';
                guideTip.style.left = '50%';
                guideTip.style.transform = 'translateX(-50%)';
                guideTip.style.bottom = '20px';
            } else {
                // Auf Desktop je nach Position
                switch (position) {
                    case 'right':
                        guideTip.style.top = (rect.top + window.scrollY) + 'px';
                        guideTip.style.left = (rect.right + 10 + window.scrollX) + 'px';
                        guideTip.style.transform = 'none';
                        break;
                    case 'left':
                        guideTip.style.top = (rect.top + window.scrollY) + 'px';
                        guideTip.style.left = (rect.left - guideTip.offsetWidth - 10 + window.scrollX) + 'px';
                        guideTip.style.transform = 'none';
                        break;
                    case 'top':
                        guideTip.style.top = (rect.top - guideTip.offsetHeight - 10 + window.scrollY) + 'px';
                        guideTip.style.left = (rect.left + rect.width/2 + window.scrollX) + 'px';
                        guideTip.style.transform = 'translateX(-50%)';
                        break;
                    case 'bottom':
                        guideTip.style.top = (rect.bottom + 10 + window.scrollY) + 'px';
                        guideTip.style.left = (rect.left + rect.width/2 + window.scrollX) + 'px';
                        guideTip.style.transform = 'translateX(-50%)';
                        break;
                }
            }
            
            guideTip.style.display = 'block';
        }
        
        // Nächster Schritt
        function goToNextStep() {
            currentStep++;
            showCurrentStep();
        }
        
        // Überwachung der Aktion einrichten
        function setupActionCheck(step) {
            if (!step.actionCheck) return;
            
            // Prüfen wir alle 500ms, ob die Aktion ausgeführt wurde
            const checkInterval = setInterval(function() {
                if (!isGuideActive) {
                    clearInterval(checkInterval);
                    return;
                }
                
                if (step.actionCheck()) {
                    clearInterval(checkInterval);
                    
                    // Kurz warten, bevor wir zum nächsten Schritt gehen
                    setTimeout(function() {
                        if (isGuideActive) goToNextStep();
                    }, 1000);
                }
            }, 500);
        }
        
        // Guide-Tooltip ausblenden
        function hideGuideTip() {
            guideTip.style.display = 'none';
        }
        
        // Overlay anzeigen
        function showOverlay() {
            overlayBackdrop.style.display = 'block';
        }
        
        // Overlay ausblenden
        function hideOverlay() {
            overlayBackdrop.style.display = 'none';
        }
        
        // Event-Listener für Kalender-Klicks (für die automatische Erkennung)
        // Wird nur aktiviert, wenn der Guide aktiv ist
        document.addEventListener('click', function(e) {
            if (!isGuideActive) return;
            
            const dayElement = e.target.closest('.day');
            if (dayElement && (currentStep === 1 || currentStep === 2)) {
                // Wir können hier keine direkte Aktion ausführen, da die Auswahl
                // von der vorhandenen Kalenderlogik verwaltet wird.
                // Die setupActionCheck Funktion wird dies überwachen.
            }
        });
        
        // Anpassung an Fenstergröße
        window.addEventListener('resize', function() {
            if (isGuideActive) {
                const step = guideSteps[currentStep];
                positionGuideTip(step.targetSelector, step.position);
            }
        });
        
        // Anpassung an Scrollen
        window.addEventListener('scroll', function() {
            if (isGuideActive) {
                const step = guideSteps[currentStep];
                positionGuideTip(step.targetSelector, step.position);
            }
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?> 