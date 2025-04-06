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
                            <!-- Reservierungshilfe Button - nur für angemeldete Benutzer -->
                            <div class="text-center mb-3">
                                <button id="reservierungshilfeBtn" class="btn btn-outline-primary btn-sm w-100">
                                    <i class="bi bi-question-circle"></i> Reservierungshilfe starten
                                </button>
                            </div>
                            
                            <form id="reservationForm" method="post" action="<?php echo getRelativePath('Erstellen'); ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                
                                <div class="mb-3">
                                    <label for="start_date" class="form-label"><strong>Ab wann?</strong> (Erster Tag)</label>
                                    <input type="text" class="form-control" id="start_date" name="start_date" readonly required>
                                    <div class="form-text">Dies ist der erste Tag Ihrer Nutzung der Grillhütte.</div>
                                </div>
                                <div class="mb-3">
                                    <label for="end_date" class="form-label"><strong>Bis wann?</strong> (Letzter Tag)</label>
                                    <input type="text" class="form-control" id="end_date" name="end_date" readonly required>
                                    <div class="form-text">Dies ist der letzte Tag Ihrer Nutzung. Für nur einen Tag wählen Sie den gleichen Tag wie oben.</div>
                                </div>
                                
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="receipt_requested" name="receipt_requested" value="1">
                                    <label class="form-check-label" for="receipt_requested"><strong>Quittung gewünscht?</strong></label>
                                    <div class="form-text">Setzen Sie hier einen Haken, wenn Sie eine Quittung für Ihre Zahlung benötigen.</div>
                                </div>
                                
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="is_public" name="is_public" value="1">
                                    <label class="form-check-label" for="is_public"><strong>Öffentliche Veranstaltung?</strong></label>
                                    <div class="form-text">Setzen Sie hier einen Haken, wenn Ihre Veranstaltung öffentlich ist und im Kalender für alle sichtbar sein soll.</div>
                                </div>
                                
                                <div id="public-event-details" style="display: none;">
                                    <div class="mb-3">
                                        <label for="event_name" class="form-label"><strong>Name Ihrer Veranstaltung</strong></label>
                                        <input type="text" class="form-control" id="event_name" name="event_name" maxlength="255" placeholder="z.B. Familienfest, Vereinsfest">
                                        <div class="form-text">Dieser Name wird für alle sichtbar im Kalender angezeigt.</div>
                                    </div>

                                    <div class="mb-3 form-check">
                                        <input type="checkbox" class="form-check-input" id="show_date_range" name="show_date_range">
                                        <label class="form-check-label" for="show_date_range"><strong>Veranstaltung länger als einen Tag?</strong></label>
                                        <div class="form-text">Setzen Sie hier einen Haken, wenn Ihre Veranstaltung an mehreren Tagen stattfindet.</div>
                                    </div>
                                    
                                    <div id="single-day-field" class="mb-3">
                                        <label for="event_day" class="form-label"><strong>An welchem Tag findet die Veranstaltung statt?</strong></label>
                                        <input type="text" class="form-control date-picker" id="event_day" name="event_day">
                                        <div class="form-text">Wählen Sie den Tag, an dem Ihre Veranstaltung im Kalender angezeigt werden soll.</div>
                                    </div>
                                    
                                    <div id="date-range-fields" style="display: none;">
                                        <div class="mb-3">
                                            <label for="display_start_date" class="form-label"><strong>Erster Veranstaltungstag</strong></label>
                                            <input type="text" class="form-control date-picker" id="display_start_date" name="display_start_date">
                                            <div class="form-text">Ab diesem Tag wird Ihre Veranstaltung im Kalender angezeigt.</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="display_end_date" class="form-label"><strong>Letzter Veranstaltungstag</strong></label>
                                            <input type="text" class="form-control date-picker" id="display_end_date" name="display_end_date">
                                            <div class="form-text">Bis zu diesem Tag wird Ihre Veranstaltung im Kalender angezeigt.</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="message" class="form-label"><strong>Haben Sie besondere Wünsche oder Anmerkungen?</strong> (freiwillig)</label>
                                    <textarea class="form-control" id="message" name="message" rows="3"></textarea>
                                    <div class="form-text">Hier können Sie zusätzliche Informationen für die Verwaltung angeben.</div>
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
        <div class="guide-tip-actions">
            <button id="minimizeGuideTip" class="minimize-btn" title="Minimieren"><i class="bi bi-dash"></i></button>
            <button id="closeGuideTip" class="close-btn" title="Schließen">&times;</button>
        </div>
    </div>
    <div id="guideStepContent" class="guide-tip-content">
        Schauen Sie sich den Kalender an, um verfügbare Tage zu sehen. Grün markierte Tage sind verfügbar. Sie können mit den Pfeilen zwischen den Monaten wechseln.
    </div>
    <div id="guideStepHint" class="guide-tip-hint">
        <div class="hint-toggle">
            <i class="bi bi-chevron-down"></i> <span>Wichtiger Hinweis</span>
        </div>
        <div class="hint-content">
            <i class="bi bi-info-circle"></i> <span id="hintText">Wichtiger Hinweis</span>
        </div>
    </div>
    <div id="guideStepMultiHints" class="guide-tip-multi-hints" style="display: none;">
        <div class="guide-hint-header">
            <span><i class="bi bi-info-circle"></i> Wichtige Hinweise:</span>
            <i class="bi bi-chevron-down toggle-icon"></i>
        </div>
        <ul id="hintsList" class="guide-hints-list">
            <!-- Hints will be inserted here dynamically -->
        </ul>
    </div>
    <div class="guide-tip-footer">
        <span id="stepCounter">Schritt 1 von 6</span>
        <div class="guide-buttons">
            <button id="prevGuideStep" class="btn btn-sm btn-outline-secondary" style="display: none;">Zurück</button>
            <button id="nextGuideStep" class="btn btn-sm btn-primary">Weiter</button>
        </div>
    </div>
</div>

<!-- Minimierter Guide-Button (wird eingeblendet, wenn Guide minimiert ist) -->
<div id="minimizedGuide" class="minimized-guide">
    <button id="expandGuide" class="btn btn-primary rounded-circle">
        <i class="bi bi-question-lg"></i>
    </button>
</div>

<style>
    /* Stil für die Reservierungshilfe */
    .guide-tip {
        position: absolute;
        display: none;
        width: 350px; /* Erhöhte Breite für bessere Lesbarkeit */
        background-color: white;
        border: 2px solid #007bff; /* Dickerer Rahmen mit deutlicherer Farbe */
        border-radius: 8px;
        box-shadow: 0 6px 16px rgba(0,0,0,0.2);
        z-index: 1000;
        font-size: 16px; /* Größere Standardschrift */
        animation: fadeIn 0.3s;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    .guide-tip-header {
        padding: 12px 16px; /* Größere Padding */
        background-color: #007bff; /* Deutlichere Hintergrundfarbe */
        border-bottom: 1px solid #ddd;
        border-radius: 6px 6px 0 0;
        font-weight: bold;
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: white; /* Weißer Text für besseren Kontrast */
        font-size: 18px; /* Größerer Titel */
    }
    
    .guide-tip-actions {
        display: flex;
        align-items: center;
    }
    
    .minimize-btn {
        background: none;
        border: none;
        font-size: 24px;
        color: white;
        cursor: pointer;
        padding: 0;
        margin-right: 10px;
        line-height: 1;
    }
    
    /* Minimierter Zustand des Guides */
    .minimized-guide {
        position: fixed;
        bottom: 80px;
        right: 20px;
        z-index: 1000;
        display: none; /* Initial ausgeblendet */
    }
    
    .minimized-guide button {
        width: 50px;
        height: 50px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.2);
        font-size: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .guide-tip-content {
        padding: 16px; /* Größerer Innenabstand */
        line-height: 1.6; /* Erhöhter Zeilenabstand für bessere Lesbarkeit */
    }
    
    .guide-tip-hint {
        padding: 12px 16px; /* Größerer Innenabstand */
        background-color: #fff8e1;
        border-top: 1px solid #ffe0b2;
        border-bottom: 1px solid #ffe0b2;
        color: #856404;
        font-size: 15px; /* Größere Hinweisschrift */
        line-height: 1.5; /* Erhöhter Zeilenabstand */
    }
    
    .guide-tip-hint i {
        margin-right: 8px;
    }
    
    /* Toggle für Hinweise auf Mobilgeräten */
    .hint-toggle {
        display: none; /* Standard: nicht anzeigen */
        cursor: pointer;
        font-weight: bold;
        user-select: none;
    }
    
    .hint-toggle i {
        transition: transform 0.3s ease;
    }
    
    .hint-toggle.collapsed i {
        transform: rotate(-90deg);
    }
    
    .hint-content {
        overflow: hidden;
        transition: max-height 0.3s ease;
    }
    
    /* Styling for multiple hints */
    .guide-tip-multi-hints {
        padding: 0;
        background-color: #fff8e1;
        border-top: 1px solid #ffe0b2;
        border-bottom: 1px solid #ffe0b2;
        color: #856404;
        font-size: 15px; /* Größere Schrift */
        line-height: 1.5; /* Erhöhter Zeilenabstand */
    }
    
    .guide-hint-header {
        padding: 12px 16px 8px;
        font-weight: bold;
        cursor: pointer;
        user-select: none;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .guide-hint-header i.toggle-icon {
        transition: transform 0.3s ease;
    }
    
    .guide-hint-header.collapsed i.toggle-icon {
        transform: rotate(-90deg);
    }
    
    .guide-hints-list {
        margin: 0;
        padding: 0 16px 12px 36px;
        overflow: hidden;
        transition: max-height 0.3s ease;
    }
    
    .guide-hints-list li {
        margin-bottom: 8px; /* Mehr Abstand zwischen Listenpunkten */
    }
    
    .guide-tip-footer {
        padding: 12px 16px; /* Größerer Innenabstand */
        border-top: 1px solid #ddd;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .guide-buttons {
        display: flex;
        gap: 12px; /* Mehr Abstand zwischen Buttons */
    }
    
    /* Größere, besser klickbare Buttons */
    .guide-buttons button {
        padding: 8px 16px; /* Größerer Button */
        font-size: 16px; /* Größere Schrift */
        border-radius: 6px;
    }
    
    .close-btn {
        background: none;
        border: none;
        font-size: 24px; /* Größeres Schließen-Symbol */
        color: white; /* Weißes Symbol für besseren Kontrast */
        cursor: pointer;
        padding: 0;
        line-height: 1;
    }
    
    .highlight-element {
        position: relative;
        z-index: 10;
        animation: pulse 2s infinite;
        border: 3px solid #007bff !important; /* Dickerer Rahmen */
        border-radius: 5px;
        box-shadow: 0 0 15px rgba(0, 123, 255, 0.6); /* Stärkere Hervorhebung */
        pointer-events: auto !important; /* Sicherstellen, dass Mausinteraktionen funktionieren */
    }
    
    /* Hervorhebung für Formularelemente verbessern */
    .highlight-label {
        color: #007bff !important;
        font-weight: bold !important;
        font-size: 1.1em !important; /* Größere Schrift für Labels */
        pointer-events: auto !important;
    }
    
    .highlight-input {
        border-color: #007bff !important;
        border-width: 2px !important; /* Dickerer Rahmen */
        pointer-events: auto !important;
    }
    
    /* Wichtig: Hover-Zustand nicht beeinträchtigen */
    .highlight-element:hover,
    .highlight-label:hover,
    .highlight-input:hover,
    .form-check:hover,
    .form-check-input:hover,
    .form-check-label:hover {
        opacity: 1 !important;
        color: inherit !important;
        background-color: transparent !important;
    }
    
    /* Verhindern dass interaktive Elemente deaktiviert erscheinen */
    .highlight-element input:hover,
    .highlight-element label:hover,
    .highlight-element .form-check-input:hover,
    .highlight-element .form-check-label:hover {
        opacity: 1 !important;
        cursor: pointer !important;
    }
    
    /* Speziell für Checkboxen - sicherstellen, dass Häkchen beim Hover sichtbar bleibt */
    .form-check-input:checked:hover {
        background-color: #0d6efd !important;
        border-color: #0d6efd !important;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3e%3cpath fill='none' stroke='%23fff' stroke-linecap='round' stroke-linejoin='round' stroke-width='3' d='M6 10l3 3l6-6'/%3e%3c/svg%3e") !important;
    }
    
    /* Größere, besser sichtbare Checkboxen */
    .form-check-input {
        width: 1.25em !important;
        height: 1.25em !important;
        margin-top: 0.25em !important;
    }
    
    /* Form-Elemente innerhalb von highlight-element sollten immer anklickbar sein */
    .highlight-element input,
    .highlight-element textarea,
    .highlight-element select,
    .highlight-element label,
    .highlight-element button {
        pointer-events: auto !important;
        position: relative;
        z-index: 20;
    }
    
    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(0, 123, 255, 0.8); }
        70% { box-shadow: 0 0 0 15px rgba(0, 123, 255, 0); }
        100% { box-shadow: 0 0 0 0 rgba(0, 123, 255, 0); }
    }
    
    .overlay-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.4); /* Dunkleres Overlay für besseren Kontrast */
        z-index: 5;
        display: none;
        pointer-events: none; /* Permits clicks through the overlay */
    }
    
    /* Temporär Animation und Hover-Effekt deaktivieren */
    .guide-active .card,
    .guide-active .card:hover {
        transform: none !important;
        box-shadow: none !important;
        transition: none !important;
        animation: none !important;
    }
    
    /* Highlight-Element überschreibt Hover-Effekte */
    .highlight-element {
        transition: none !important;
        transform: none !important;
        animation: pulse 2s infinite !important;
        z-index: 100 !important;
    }
    
    /* Mobile Anpassungen */
    @media (max-width: 768px) {
        .guide-tip {
            width: 320px; /* Größer aber angepasst für Mobilgeräte */
            position: fixed;
            bottom: 80px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 16px; /* Größere Schrift auch auf Mobilgeräten */
        }
        
        .guide-buttons button {
            padding: 10px 16px; /* Noch größere Touch-Targets auf Mobilgeräten */
        }
    }
    
    /* Extra kleine Geräte wie kleine Smartphones */
    @media (max-width: 375px) {
        .guide-tip {
            width: 90%; /* Relative Breite statt fester Breite */
            bottom: 60px; /* Höher positionieren, damit mehr Inhalte sichtbar sind */
            max-height: 60vh; /* Maximal 60% der Bildschirmhöhe */
            overflow-y: auto; /* Scrollbar, wenn der Inhalt nicht passt */
            font-size: 14px; /* Etwas kleinere Schrift */
        }
        
        .guide-tip-header {
            padding: 10px 12px; /* Kleineres Padding für Header */
            font-size: 16px; /* Kleinerer Titel */
        }
        
        .guide-tip-content,
        .guide-tip-hint,
        .guide-tip-multi-hints {
            padding: 12px; /* Kleineres Padding für Inhalt */
        }
        
        .guide-tip-footer {
            padding: 10px; /* Kleineres Padding für Footer */
        }
        
        /* Kompaktere Hinweise */
        .guide-hints-list {
            padding: 0 12px 10px 30px;
        }
        
        .guide-buttons button {
            padding: 8px 12px; /* Kompaktere Buttons */
            font-size: 14px; /* Kleinere Schrift für Buttons */
        }
        
        /* Reduzierte Schrittnummer für mehr Platz */
        #stepCounter {
            font-size: 13px;
        }
        
        /* Leichtere Animation für bessere Performance auf mobilen Geräten */
        .highlight-element {
            animation: mobile-pulse 2s infinite !important;
            border-width: 2px !important; /* Etwas dünnerer Rahmen für mobil */
            box-shadow: 0 0 8px rgba(0, 123, 255, 0.5) !important; /* Weniger intensive Schatten */
        }
        
        @keyframes mobile-pulse {
            0% { box-shadow: 0 0 0 0 rgba(0, 123, 255, 0.5); }
            70% { box-shadow: 0 0 0 7px rgba(0, 123, 255, 0); }
            100% { box-shadow: 0 0 0 0 rgba(0, 123, 255, 0); }
        }
    }
    
    /* Tablet/mittlere Mobilgeräte */
    @media (min-width: 376px) and (max-width: 768px) {
        /* Optimierte Animation für Tablets */
        .highlight-element {
            animation: tablet-pulse 2s infinite !important;
        }
        
        @keyframes tablet-pulse {
            0% { box-shadow: 0 0 0 0 rgba(0, 123, 255, 0.6); }
            70% { box-shadow: 0 0 0 10px rgba(0, 123, 255, 0); }
            100% { box-shadow: 0 0 0 0 rgba(0, 123, 255, 0); }
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
        const minimizeGuideTipBtn = document.getElementById('minimizeGuideTip');
        const expandGuideBtn = document.getElementById('expandGuide');
        const minimizedGuide = document.getElementById('minimizedGuide');
        const nextGuideStepBtn = document.getElementById('nextGuideStep');
        const prevGuideStepBtn = document.getElementById('prevGuideStep');
        const guideStepTitle = document.getElementById('guideStepTitle');
        const guideStepContent = document.getElementById('guideStepContent');
        const guideStepHint = document.getElementById('guideStepHint');
        const hintText = document.getElementById('hintText');
        const stepCounter = document.getElementById('stepCounter');
        const overlayBackdrop = document.getElementById('overlayBackdrop');
        const hintToggle = document.querySelector('.hint-toggle');
        const hintContent = document.querySelector('.hint-content');
        const multiHintsHeader = document.querySelector('.guide-hint-header');
        const multiHintsList = document.querySelector('.guide-hints-list');
        
        // Variablen für den Zustand der Hinweise
        let isHintCollapsed = window.innerWidth <= 768; // Auf Mobilgeräten standardmäßig eingeklappt
        let isMultiHintsCollapsed = window.innerWidth <= 768; // Auf Mobilgeräten standardmäßig eingeklappt
        
        // Event-Listener für Hint-Toggle
        if (hintToggle) {
            hintToggle.addEventListener('click', function() {
                toggleHint();
            });
        }
        
        // Event-Listener für Multi-Hints-Toggle
        if (multiHintsHeader) {
            multiHintsHeader.addEventListener('click', function() {
                toggleMultiHints();
            });
        }
        
        // Funktion zum Umschalten des Hinweis-Zustands
        function toggleHint() {
            isHintCollapsed = !isHintCollapsed;
            updateHintVisibility();
        }
        
        // Funktion zum Umschalten der Multi-Hinweise
        function toggleMultiHints() {
            isMultiHintsCollapsed = !isMultiHintsCollapsed;
            updateMultiHintsVisibility();
        }
        
        // Aktualisieren der Sichtbarkeit des Hinweises
        function updateHintVisibility() {
            if (isHintCollapsed) {
                hintContent.style.maxHeight = '0';
                hintToggle.classList.add('collapsed');
            } else {
                hintContent.style.maxHeight = hintContent.scrollHeight + 'px';
                hintToggle.classList.remove('collapsed');
            }
        }
        
        // Aktualisieren der Sichtbarkeit der Multi-Hinweise
        function updateMultiHintsVisibility() {
            if (isMultiHintsCollapsed) {
                multiHintsList.style.maxHeight = '0';
                multiHintsHeader.classList.add('collapsed');
            } else {
                multiHintsList.style.maxHeight = multiHintsList.scrollHeight + 'px';
                multiHintsHeader.classList.remove('collapsed');
            }
        }
        
        // Funktion zum Initialisieren der Ansicht basierend auf der Gerätebreite
        function initializeHintsView() {
            const isMobile = window.innerWidth <= 768;
            
            // Anzeigen oder Ausblenden der Toggle-Elemente basierend auf der Bildschirmgröße
            if (isMobile) {
                if (hintToggle) hintToggle.style.display = 'block';
                isHintCollapsed = true;
                isMultiHintsCollapsed = true;
            } else {
                if (hintToggle) hintToggle.style.display = 'none';
                isHintCollapsed = false;
                isMultiHintsCollapsed = false;
            }
            
            // Anfangszustand aktualisieren
            if (hintContent) updateHintVisibility();
            if (multiHintsList) updateMultiHintsVisibility();
        }
        
        // Beim Fenstergrößenwechsel den Zustand aktualisieren
        window.addEventListener('resize', function() {
            initializeHintsView();
        });
        
        // Aktueller Schritt
        let currentStep = 0;
        let currentHighlightedElement = null;
        let isGuideActive = false;
        let isGuideMinimized = false;
        
        // Definiere alle Schritte
        const guideSteps = [
            {
                title: "Schritt 1: Kalender ansehen",
                content: "Schauen Sie sich den Kalender an, um verfügbare Tage zu sehen. Grün markierte Tage sind verfügbar. Sie können mit den Pfeilen zwischen den Monaten wechseln.",
                targetSelector: "#calendar",
                position: "right",
                waitForAction: false
            },
            {
                title: "Schritt 2: Wann möchten Sie die Grillhütte nutzen?",
                content: "Bitte wählen Sie den ersten Tag Ihrer Reservierung aus. Klicken Sie einfach auf einen grünen (freien) Tag im Kalender. Dies ist der erste Tag an dem sie die Hütte nutzen möchten.",
                hints: ["Nur grüne Tage (frei) und blaue Tage (Schlüsselübergabe) können ausgewählt werden.", 
                "Blaue Tage (Schlüsselübergabe) haben eingeschränkte Nutzungszeiten - bewegen Sie Ihre Maus über den Tag, um die verfügbare Zeit zu sehen.",
                "Sie können mit den Pfeilen über dem Kalender zwischen den Monaten wechseln, um andere Termine zu sehen."],
                targetSelector: ".day.free:not(.other-month):not(.past), .day.key-handover:not(.other-month):not(.past)",
                position: "right",
                waitForAction: true,
                validationField: "start_date"
            },
            {
                title: "Schritt 3: Wie lange möchten Sie die Grillhütte nutzen?",
                content: "Bitte wählen Sie den letzten Tag Ihrer Reservierung aus. Möchten Sie die Hütte nur für einen Tag? Dann klicken Sie nochmals auf denselben Tag. Für mehrere Tage wählen Sie einen späteren Tag aus.",
                hints: ["Nur grüne Tage (frei) und blaue Tage (Schlüsselübergabe) können ausgewählt werden.", 
                "Blaue Tage (Schlüsselübergabe) haben eingeschränkte Nutzungszeiten - bewegen Sie Ihre Maus über den Tag, um die verfügbare Zeit zu sehen.",
                "Beispiel: Für ein Wochenende wählen Sie zuerst den Freitag (Schritt 2) und dann den Sonntag (Schritt 3).",
                "Für nur einen Tag (z.B. Samstag) wählen Sie denselben Tag in Schritt 2 und 3."],
                targetSelector: ".day.free:not(.other-month):not(.past), .day.key-handover:not(.other-month):not(.past)",
                position: "right",
                waitForAction: true,
                validationField: "end_date"
            },
            {
                title: "Schritt 4: Quittung anfordern",
                content: "Aktivieren Sie diese Option, wenn Sie eine Quittung für die Reservierung benötigen. Klicken Sie auf das Kästchen, um die Option zu aktivieren oder deaktivieren.",
                hints: ["Eine Quittung kann später nicht mehr nachträglich online angefordert werden.",
                "Wenn nachträglich eine Quittung benötigt wird, kann diese bei der Verwaltenden Person per E-Mail angefordert werden."],
                targetSelector: "label[for='receipt_requested'], #receipt_requested",
                position: "left",
                waitForAction: false
            },
            {
                title: "Schritt 5: Öffentliche Veranstaltung",
                content: "Entscheiden Sie, ob ihre Veranstaltung öffentlich sein soll. Im fall, dass sie eine öffentliche Veranstaltung planen, klicken sie auf das Kästchen.",
                hints: ["Bei öffentlichen Veranstaltungen wird der Veranstaltungsname im Kalender angezeigt.",
                "Es kann ausgewählt werden an welchen tagen die Veranstaltung im Kalender angezeigt werden soll."],
                targetSelector: "label[for='is_public'], #is_public",
                position: "left",
                waitForAction: false,
                customAction: function() {
                    // Wir fügen einen Event-Listener für die is_public Checkbox hinzu
                    const isPublicCheckbox = document.getElementById('is_public');
                    if (isPublicCheckbox) {
                        isPublicCheckbox.addEventListener('change', function() {
                            // Je nach Zustand der Checkbox dynamisch Schritte hinzufügen oder entfernen
                            updateGuideSteps(this.checked);
                        });
                        
                        // Wenn die Checkbox bereits aktiviert ist
                        if (isPublicCheckbox.checked) {
                            updateGuideSteps(true);
                        }
                    }
                }
            },
            // Die Public-Event-Schritte werden dynamisch je nach Auswahl eingefügt
            {
                title: "Schritt 6: Nachricht eingeben (optional)",
                content: "Sie können eine optionale Nachricht für den Verwalter hinterlassen, z.B. für spezielle Anfragen. Klicken Sie auf 'Weiter', wenn Sie bereit sind.",
                targetSelector: "#message",
                position: "left",
                waitForAction: false
            },
            {
                title: "Schritt 7: Reservierung anfragen",
                content: "Überprüfen Sie die Kostenübersicht und klicken Sie auf 'Reservierung anfragen', um Ihre Buchung abzuschließen.",
                hints: ["Wichtiger Hinweis: Nach dem Absenden erhalten Sie eine Eingangsbestätigung per E-Mail."],
                targetSelector: "button[type='submit']",
                position: "left",
                waitForAction: false
            }
        ];
        
        // Öffentliche Reservierungsschritte - werden nur angezeigt, wenn die Checkbox aktiviert ist
        const publicEventSteps = [
            {
                title: "Zusatz 1: Name der Veranstaltung",
                content: "Geben Sie einen Namen für Ihre öffentliche Veranstaltung ein, der im Kalender angezeigt wird (z.B. 'Feuerwehr Sommerfest').",
                hint: "Wichtiger Hinweis: Der Name sollte kurz und aussagekräftig sein.",
                targetSelector: "#event_name",
                position: "left",
                waitForAction: false
            },
            {
                title: "Zusatz 2: Veranstaltungsdauer",
                content: "Wählen Sie, ob Ihre Veranstaltung an einem bestimmten Tag oder über mehrere Tage stattfindet.",
                hint: "Wichtiger Hinweis: Diese Einstellung bestimmt, wie die Veranstaltung im Kalender dargestellt wird.",
                targetSelector: "label[for='show_date_range'], #show_date_range",
                position: "left",
                waitForAction: false,
                customAction: function() {
                    // Event-Listener für die show_date_range Checkbox
                    const showDateRangeCheckbox = document.getElementById('show_date_range');
                    if (showDateRangeCheckbox) {
                        showDateRangeCheckbox.addEventListener('change', function() {
                            // Aktualisiere die Veranstaltungszeitraum-Selektoren basierend auf der Auswahl
                            updateEventDatesSelector(this.checked);
                        });
                        
                        // Initialisieren: Wenn die Checkbox bereits aktiviert ist
                        updateEventDatesSelector(showDateRangeCheckbox.checked);
                    }
                }
            },
            {
                title: "Zusatz 3: Veranstaltungszeitraum",
                content: "Wählen Sie aus, an welchen Tagen Ihre Veranstaltung im Kalender angezeigt werden soll.",
                hint: "Wichtiger Hinweis: Der Zeitraum muss innerhalb Ihrer Reservierungszeit liegen.",
                // Der Selektor wird dynamisch aktualisiert basierend auf dem Zustand der Checkbox
                targetSelector: "#single-day-field",
                position: "left",
                waitForAction: false
            }
        ];
        
        // Funktionen und Variablen zur dynamischen Steuerung der Schritte
        let originalStepsLength = guideSteps.length;
        let hasPublicEventSteps = false;
        
        // Funktion zum Aktualisieren der Schritte je nach Checkbox-Zustand
        function updateGuideSteps(isPublicChecked) {
            // Wenn die Checkbox aktiviert ist und wir noch keine Public-Event-Schritte haben
            if (isPublicChecked && !hasPublicEventSteps) {
                // Öffentliche Event-Schritte einfügen (vor dem Nachricht-Schritt)
                const insertPosition = originalStepsLength - 2; // Position vor "Nachricht eingeben"
                
                // Schritte einfügen
                guideSteps.splice(insertPosition, 0, ...publicEventSteps);
                hasPublicEventSteps = true;
                
                // Schrittzähler aktualisieren
                updateStepNumbers();
            } 
            // Wenn die Checkbox deaktiviert ist und wir bereits Public-Event-Schritte haben
            else if (!isPublicChecked && hasPublicEventSteps) {
                // Öffentliche Event-Schritte entfernen
                const startIndex = originalStepsLength - 2; // Position vor "Nachricht eingeben"
                guideSteps.splice(startIndex, publicEventSteps.length);
                hasPublicEventSteps = false;
                
                // Schrittzähler aktualisieren
                updateStepNumbers();
            }
        }
        
        // Schrittnummerierung aktualisieren
        function updateStepNumbers() {
            // Titel der Schritte mit aktualisierten Nummern versehen
            for (let i = 0; i < guideSteps.length; i++) {
                // Schritt-Titel aktualisieren, aber nur die Nummer
                if (guideSteps[i].title.includes("Schritt")) {
                    guideSteps[i].title = guideSteps[i].title.replace(/Schritt \d+:/, `Schritt ${i + 1}:`);
                }
            }
            
            // Aktualisiere auch die Anzeige, wenn der aktuelle Schritt sichtbar ist
            if (isGuideActive) {
                stepCounter.textContent = `Schritt ${currentStep + 1} von ${guideSteps.length}`;
            }
        }

        // Event-Listener für den Guide-Button
        guideBtn.addEventListener('click', function() {
            startGuide();
        });
        
        // Event-Listener zum Schließen des Guides
        closeGuideTipBtn.addEventListener('click', function() {
            endGuide();
        });
        
        // Event-Listener zum Minimieren des Guides
        minimizeGuideTipBtn.addEventListener('click', function() {
            minimizeGuide();
        });
        
        // Event-Listener zum Wiederherstellen des minimierten Guides
        expandGuideBtn.addEventListener('click', function() {
            expandGuide();
        });
        
        // Event-Listener für "Weiter"-Button
        nextGuideStepBtn.addEventListener('click', function() {
            goToNextStep();
        });
        
        // Event-Listener für "Zurück"-Button
        prevGuideStepBtn.addEventListener('click', function() {
            goToPrevStep();
        });
        
        // Funktion zum Starten des Guides
        function startGuide() {
            isGuideActive = true;
            currentStep = 0;
            showOverlay();
            initializeHintsView(); // Initialisiere den Zustand der Hinweise
            showCurrentStep();
            
            // Füge eine Klasse zum body hinzu, um Hover-Effekte zu deaktivieren
            document.body.classList.add('guide-active');
        }
        
        // Funktion zum Beenden des Guides
        function endGuide() {
            isGuideActive = false;
            isGuideMinimized = false;
            hideGuideTip();
            minimizedGuide.style.display = 'none';
            removeHighlight();
            hideOverlay();
            
            // Entferne die Klasse vom body, um Hover-Effekte wiederherzustellen
            document.body.classList.remove('guide-active');
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
            
            // Hinweis aktualisieren und anzeigen/ausblenden
            if (step.hint && step.hint.trim() !== '') {
                hintText.textContent = step.hint.replace("Wichtiger Hinweis: ", "");
                guideStepHint.style.display = 'block';
                document.getElementById('guideStepMultiHints').style.display = 'none';
                
                // Update toggle text for single hint
                if (hintToggle) {
                    hintToggle.querySelector('span').textContent = 'Wichtiger Hinweis';
                }
                
                // Ensure hint visibility is correct
                setTimeout(updateHintVisibility, 0);
            } else if (step.hints && Array.isArray(step.hints) && step.hints.length > 0) {
                // Multi-hints handling
                const hintsList = document.getElementById('hintsList');
                hintsList.innerHTML = ''; // Clear existing hints
                
                // Create list items for each hint
                step.hints.forEach(hint => {
                    const li = document.createElement('li');
                    li.textContent = hint.replace("Wichtiger Hinweis: ", "");
                    hintsList.appendChild(li);
                });
                
                // Show multi-hints, hide single hint
                document.getElementById('guideStepMultiHints').style.display = 'block';
                guideStepHint.style.display = 'none';
                
                // Ensure multi-hints visibility is correct
                setTimeout(updateMultiHintsVisibility, 0);
            } else {
                // No hints at all
                guideStepHint.style.display = 'none';
                document.getElementById('guideStepMultiHints').style.display = 'none';
            }
            
            stepCounter.textContent = `Schritt ${currentStep + 1} von ${guideSteps.length}`;
            
            // Element hervorheben
            highlightElement(step.targetSelector);
            
            // Tooltip positionieren
            positionGuideTip(step.targetSelector, step.position);
            
            // Zurück-Button anzeigen/ausblenden
            if (currentStep > 0) {
                prevGuideStepBtn.style.display = 'block';
            } else {
                prevGuideStepBtn.style.display = 'none';
            }
            
            // Weiter-Button immer anzeigen
            nextGuideStepBtn.style.display = 'block';
            nextGuideStepBtn.textContent = currentStep === guideSteps.length - 1 ? 'Fertig' : 'Weiter';
            
            // Prüfen, ob der Schritt eine benutzerdefinierte Aktion hat
            if (step.customAction) {
                step.customAction();
            }
        }
        
        // Element hervorheben
        function highlightElement(selector) {
            removeHighlight();
            
            const elements = document.querySelectorAll(selector);
            const isMobile = window.innerWidth <= 768;
            const isSmallMobile = window.innerWidth <= 375;
            
            if (elements.length > 0) {
                // Bei den Schritten zur Datumsauswahl alle verfügbaren Tage hervorheben
                if (currentStep === 1 || currentStep === 2) {
                    elements.forEach(element => {
                        element.classList.add('highlight-element');
                    });
                    
                    // Auch die Monatsnavigation hervorheben
                    const prevMonthBtn = document.getElementById('prevMonth');
                    const nextMonthBtn = document.getElementById('nextMonth');
                    if (prevMonthBtn) prevMonthBtn.classList.add('highlight-element');
                    if (nextMonthBtn) nextMonthBtn.classList.add('highlight-element');
                    
                    // Angepasstes Scrollverhalten für mobile Geräte
                    if (isMobile) {
                        // Scrolle etwas höher auf mobilen Geräten, um Platz für das Popup zu lassen
                        const scrollOptions = {
                            behavior: 'smooth',
                            block: isSmallMobile ? 'start' : 'center' // Kleiner Bildschirm: mehr nach oben scrollen
                        };
                        
                        // Zum Kalender-Container scrollen statt zum ersten Element
                        const calendarContainer = document.querySelector('.calendar-container');
                        if (calendarContainer) {
                            calendarContainer.scrollIntoView(scrollOptions);
                        } else {
                            // Fallback: Zum ersten Element scrollen
                            elements[0].scrollIntoView(scrollOptions);
                        }
                    } else {
                        // Desktop-Verhalten: Scrollen zum ersten Element
                        elements[0].scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                    }
                } 
                // Bei Formularfeldern das gesamte Label mit dem Kästchen hervorheben
                else if (selector.includes('label[for=') || selector.includes('#receipt_requested') || selector.includes('#is_public') || selector.includes('#show_date_range')) {
                    // Verbesserte Hervorhebung für Checkboxen und Labels - den gesamten Form-Check hervorheben
                    elements.forEach(element => {
                        // Finde die parent form-check für eine konsistente Hervorhebung
                        const formCheckParent = element.closest('.form-check');
                        if (formCheckParent) {
                            formCheckParent.classList.add('highlight-element');
                            
                            // Auch das Label und die Checkbox innerhalb des form-check hervorheben
                            const checkboxInput = formCheckParent.querySelector('input[type="checkbox"]');
                            const label = formCheckParent.querySelector('label');
                            
                            if (checkboxInput) {
                                checkboxInput.classList.add('highlight-input');
                            }
                            
                            if (label) {
                                label.classList.add('highlight-label');
                            }
                        } else {
                            // Fallback, falls kein .form-check gefunden wurde
                            element.classList.add('highlight-element');
                            
                            // Wenn es sich um ein Input-Element handelt, finde das zugehörige Label
                            if (element.tagName === 'INPUT' && element.id) {
                                const associatedLabel = document.querySelector(`label[for="${element.id}"]`);
                                if (associatedLabel) {
                                    associatedLabel.classList.add('highlight-label');
                                }
                            }
                            
                            // Wenn es sich um ein Label handelt, finde das zugehörige Input
                            if (element.tagName === 'LABEL' && element.getAttribute('for')) {
                                const associatedInput = document.getElementById(element.getAttribute('for'));
                                if (associatedInput) {
                                    associatedInput.classList.add('highlight-input');
                                }
                            }
                        }
                    });
                    
                    // Angepasstes Scrollverhalten für mobile Geräte
                    if (isMobile) {
                        const scrollTarget = elements[0];
                        
                        // Position berechnen, um ausreichend Abstand für das Popup zu lassen
                        if (isSmallMobile) {
                            // Auf kleinen Geräten weiter nach oben scrollen
                            const rect = scrollTarget.getBoundingClientRect();
                            const targetTop = rect.top + window.scrollY;
                            
                            // Sanft zu einer Position scrollen, die Abstand zum Popup lässt
                            window.scrollTo({
                                top: targetTop - 120, // Mehr Abstand nach oben
                                behavior: 'smooth'
                            });
                        } else {
                            // Normales mobiles Scrollverhalten
                            scrollTarget.scrollIntoView({
                                behavior: 'smooth',
                                block: 'center'
                            });
                        }
                    } else {
                        // Desktop-Verhalten
                        elements[0].scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                    }
                }
                else {
                    // Bei anderen Schritten nur das erste Element hervorheben
                    currentHighlightedElement = elements[0];
                    currentHighlightedElement.classList.add('highlight-element');
                    
                    // Angepasstes Scrollverhalten für mobile Geräte
                    if (isMobile) {
                        // Position berechnen, um ausreichend Abstand für das Popup zu lassen
                        if (isSmallMobile) {
                            const rect = currentHighlightedElement.getBoundingClientRect();
                            const targetTop = rect.top + window.scrollY;
                            
                            // Sanft zu einer Position scrollen, die Abstand zum Popup lässt
                            window.scrollTo({
                                top: targetTop - 120, // Mehr Abstand nach oben
                                behavior: 'smooth'
                            });
                        } else {
                            // Normales mobiles Scrollverhalten
                            currentHighlightedElement.scrollIntoView({
                                behavior: 'smooth',
                                block: 'center'
                            });
                        }
                    } else {
                        // Desktop-Verhalten
                        currentHighlightedElement.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                    }
                }
            }
        }
        
        // Highlight entfernen
        function removeHighlight() {
            // Alle hervorgehobenen Elemente zurücksetzen
            document.querySelectorAll('.highlight-element').forEach(el => {
                el.classList.remove('highlight-element');
            });
            
            // Auch den Referenzwert zurücksetzen
            currentHighlightedElement = null;
        }
        
        // Tooltip positionieren
        function positionGuideTip(selector, position) {
            const elements = document.querySelectorAll(selector);
            
            if (elements.length === 0) {
                guideTip.style.display = 'none';
                return;
            }
            
            const isMobile = window.innerWidth <= 768;
            const isSmallMobile = window.innerWidth <= 375;
            
            // Element für die Positionierung auswählen
            let element;
            
            if (currentStep === 1 || currentStep === 2) {
                // Bei der Datumsauswahl den Tooltip in der Nähe des Kalenders platzieren
                const calendarContainer = document.querySelector('.calendar-container');
                if (calendarContainer) {
                    element = calendarContainer;
                } else {
                    // Fallback auf das erste Element
                    element = elements[0];
                }
            } else {
                // Bei anderen Schritten das erste Element verwenden
                element = elements[0];
            }
            
            const rect = element.getBoundingClientRect();
            
            if (isMobile) {
                // Auf mobilen Geräten immer am unteren Bildschirmrand
                guideTip.style.top = 'auto';
                guideTip.style.left = '50%';
                guideTip.style.transform = 'translateX(-50%)';
                
                // Anpassung der Position basierend auf Bildschirmgröße
                if (isSmallMobile) {
                    // Auf sehr kleinen Bildschirmen höher positionieren, um mehr Platz für Inhalte zu lassen
                    guideTip.style.bottom = '60px';
                    
                    // Stellen wir sicher, dass das hervorgehobene Element sichtbar ist
                    // und genug Abstand zum Popup-Dialog besteht
                    const viewportHeight = window.innerHeight;
                    const elementBottom = rect.bottom;
                    
                    // Wenn das Element zu weit unten ist, scrolle es höher in die Mitte
                    if (elementBottom > viewportHeight - 150) {
                        const scrollTarget = window.scrollY + (elementBottom - (viewportHeight - 200));
                        window.scrollTo({
                            top: scrollTarget,
                            behavior: 'smooth'
                        });
                    }
                } else {
                    // Auf normalen Mobilgeräten weiter unten positionieren
                    guideTip.style.bottom = '20px';
                }
            } else {
                // Auf Desktop je nach Position
                switch (position) {
                    case 'right':
                        guideTip.style.top = (rect.top + window.scrollY) + 'px';
                        guideTip.style.left = (rect.right + 20 + window.scrollX) + 'px'; // Größerer Abstand (20px statt 10px)
                        guideTip.style.transform = 'none';
                        break;
                    case 'left':
                        guideTip.style.top = (rect.top + window.scrollY) + 'px';
                        guideTip.style.left = (rect.left - guideTip.offsetWidth - 20 + window.scrollX) + 'px'; // Größerer Abstand
                        guideTip.style.transform = 'none';
                        break;
                    case 'top':
                        guideTip.style.top = (rect.top - guideTip.offsetHeight - 20 + window.scrollY) + 'px'; // Größerer Abstand
                        guideTip.style.left = (rect.left + rect.width/2 + window.scrollX) + 'px';
                        guideTip.style.transform = 'translateX(-50%)';
                        break;
                    case 'bottom':
                        guideTip.style.top = (rect.bottom + 20 + window.scrollY) + 'px'; // Größerer Abstand
                        guideTip.style.left = (rect.left + rect.width/2 + window.scrollX) + 'px';
                        guideTip.style.transform = 'translateX(-50%)';
                        break;
                }
            }
            
            guideTip.style.display = 'block';
        }
        
        // Nächster Schritt
        function goToNextStep() {
            // Validierung für bestimmte Schritte
            if (currentStep === 1 || currentStep === 2) { // Schritt 2 oder 3
                const step = guideSteps[currentStep];
                
                // Prüfen, ob das entsprechende Feld ausgefüllt ist
                if (step.validationField) {
                    const fieldValue = document.getElementById(step.validationField).value;
                    
                    // Wenn das Feld leer ist, Hinweis anzeigen und nicht weitergehen
                    if (!fieldValue) {
                        // Temporäre Nachricht einblenden
                        const originalContent = guideStepContent.textContent;
                        guideStepContent.innerHTML = `<span class="text-danger"><i class="bi bi-exclamation-triangle"></i> Bitte wählen Sie zuerst ein Datum aus, bevor Sie fortfahren.</span>`;
                        
                        // Nach 3 Sekunden zurücksetzen
                        setTimeout(() => {
                            guideStepContent.textContent = originalContent;
                        }, 3000);
                        
                        return; // Nicht zum nächsten Schritt gehen
                    }
                }
            }
            
            // Zum nächsten Schritt gehen, wenn die Validierung bestanden wurde
            if (currentStep < guideSteps.length - 1) {
                currentStep++;
                showCurrentStep();
            } else {
                // Beim letzten Schritt den Guide beenden
                endGuide();
            }
        }
        
        // Vorheriger Schritt
        function goToPrevStep() {
            if (currentStep > 0) {
                currentStep--;
                showCurrentStep();
            }
        }
        
        // Überwachung der Aktion einrichten - wird jetzt nicht mehr benötigt, da alle Schritte einen Weiter-Button haben
        function setupActionCheck(step) {
            // Diese Funktion wird für die aktualisierte Version nicht mehr benötigt,
            // bleibt aber als leere Funktion für Kompatibilität bestehen
        }
        
        // Guide-Tooltip ausblenden
        function hideGuideTip() {
            guideTip.style.display = 'none';
        }
        
        // Guide minimieren
        function minimizeGuide() {
            isGuideMinimized = true;
            guideTip.style.display = 'none';
            minimizedGuide.style.display = 'block';
            
            // Wenn auf mobilen Geräten
            if (window.innerWidth <= 768) {
                // Overlay leicht transparent machen
                overlayBackdrop.style.opacity = '0.2';
            }
        }
        
        // Guide wiederherstellen
        function expandGuide() {
            isGuideMinimized = false;
            guideTip.style.display = 'block';
            minimizedGuide.style.display = 'none';
            
            // Overlay wieder normal
            overlayBackdrop.style.opacity = '0.4';
            
            // Aktuellen Schritt erneut anzeigen (um die Position zu aktualisieren)
            const step = guideSteps[currentStep];
            positionGuideTip(step.targetSelector, step.position);
        }
        
        // Overlay anzeigen
        function showOverlay() {
            overlayBackdrop.style.display = 'block';
        }
        
        // Overlay ausblenden
        function hideOverlay() {
            overlayBackdrop.style.display = 'none';
        }
        
        // Event-Listener für Kalender-Klicks und Monatswechsel
        document.addEventListener('click', function(e) {
            if (!isGuideActive) return;
            
            const dayElement = e.target.closest('.day');
            const isPrevMonthBtn = e.target.closest('#prevMonth');
            const isNextMonthBtn = e.target.closest('#nextMonth');
            
            // Behandle Tag-Auswahl
            if (dayElement && (currentStep === 1 || currentStep === 2)) { // Schritt 2 oder 3 (Datumsauswahl)
                // Überprüfen, ob der aktuelle Schritt darauf wartet
                const step = guideSteps[currentStep];
                
                if (step.waitForAction && step.validationField) {
                    // Nach kurzer Verzögerung prüfen, ob das Feld jetzt ausgefüllt ist
                    setTimeout(function() {
                        const fieldValue = document.getElementById(step.validationField).value;
                        
                        // Wenn das Feld ausgefüllt wurde, automatisch zum nächsten Schritt gehen
                        if (fieldValue) {
                            goToNextStep();
                        }
                    }, 500); // Kurze Verzögerung, um sicherzustellen, dass das Datum gesetzt wurde
                }
            }
            
            // Behandle Monatsumschaltung
            if (isPrevMonthBtn || isNextMonthBtn) {
                // Wir müssen warten, bis der Kalender neu gerendert wurde
                setTimeout(function() {
                    // Hervorheben der Elemente aktualisieren
                    const step = guideSteps[currentStep];
                    highlightElement(step.targetSelector);
                    positionGuideTip(step.targetSelector, step.position);
                }, 300);
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
        
        // Funktion zum Aktualisieren des Selektors für Veranstaltungsdaten
        function updateEventDatesSelector(isDateRange) {
            // Finde den Veranstaltungszeitraum-Schritt
            const eventDateStep = guideSteps.find(step => step.title.includes("Zusatz 3"));
            if (!eventDateStep) return;
            
            if (isDateRange) {
                // Wenn es ein Datumsbereich ist, aktualisiere den Selektor und Inhalt
                eventDateStep.targetSelector = "#date-range-fields";
                eventDateStep.content = "Wählen Sie den Zeitraum aus, in dem Ihre Veranstaltung im Kalender angezeigt werden soll. Der Zeitraum muss innerhalb Ihrer Reservierung liegen.";
            } else {
                // Wenn es ein einzelner Tag ist
                eventDateStep.targetSelector = "#single-day-field";
                eventDateStep.content = "Wählen Sie den Tag aus, an dem Ihre Veranstaltung im Kalender angezeigt werden soll. Der Tag muss innerhalb Ihrer Reservierung liegen.";
            }
            
            // Aktualisiere die Anzeige, falls dieser Schritt gerade aktiv ist
            if (isGuideActive && guideSteps[currentStep] === eventDateStep) {
                highlightElement(eventDateStep.targetSelector);
                positionGuideTip(eventDateStep.targetSelector, eventDateStep.position);
                guideStepContent.textContent = eventDateStep.content;
            }
        }
    });
</script>

<?php require_once 'includes/footer.php'; ?> 