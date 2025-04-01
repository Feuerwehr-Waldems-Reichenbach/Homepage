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
                            bottom: 2px;
                            right: 2px;
                            font-size: 10px;
                            background-color: #5a8c7b;
                            color: white;
                            width: 16px;
                            height: 16px;
                            border-radius: 50%;
                            display: flex;
                            align-items: center;
                            justify-content: center;
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
                                <div class="mb-2">
                                    <div class="d-flex align-items-center">
                                        <div class="me-1 public-event-indicator" style="width: 15px; height: 15px; background-color: #b8e0d2; border-radius: 3px;"></div>
                                        <small>Veranstaltung</small>
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
                                
                                <div class="mb-3">
                                    <label for="start_time" class="form-label">Startzeit</label>
                                    <input type="time" class="form-control" id="start_time" name="start_time" value="12:00" step="1800" required>
                                </div>
                                <div class="mb-3">
                                    <label for="end_time" class="form-label">Endzeit</label>
                                    <input type="time" class="form-control" id="end_time" name="end_time" value="12:00" step="1800" required>
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
                                        <label for="event_name" class="form-label">Veranstaltungsname</label>
                                        <input type="text" class="form-control" id="event_name" name="event_name" maxlength="255">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="display_start_date" class="form-label">Anzeige im Kalender von</label>
                                        <input type="text" class="form-control" id="display_start_date" name="display_start_date" readonly>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="display_end_date" class="form-label">Anzeige im Kalender bis</label>
                                        <input type="text" class="form-control" id="display_end_date" name="display_end_date" readonly>
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

<?php require_once 'includes/footer.php'; ?> 