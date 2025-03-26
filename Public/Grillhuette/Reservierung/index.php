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
?>

<div class="row mb-4">
    <div class="col-12">       
        <!-- Responsives Layout für mobil und desktop -->
        <div class="row">
            <!-- Kalender Container - volle Breite auf mobil, 2/3 auf Desktop -->
            <div class="col-12 col-lg-8 mb-4">
                <div class="calendar-container">
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
                <div class="d-md-none mb-4">
                    <div class="card">
                        <div class="card-body p-3">
                            <h5 class="card-title h6 mb-2">Legende:</h5>
                            <div class="d-flex flex-wrap">
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
                                <div class="mb-2">
                                    <div class="d-flex align-items-center">
                                        <div class="me-1" style="width: 15px; height: 15px; background-color: #f8d7da; border-radius: 3px;"></div>
                                        <small>Belegt</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Willkommenskarte unter dem Kalender - nur auf Desktop sichtbar -->
                <div class="card mb-4 d-none d-md-block">
                    <div class="card-body">
                        <h5 class="card-title">Willkommen im Reservierungssystem der Grillhütte Waldems Reichenbach</h5>
                        <p>Hier können Sie freie Termine einsehen und eine Reservierung vornehmen.</p>
                        
                        <div class="row mb-3">
                            <div class="col-md-4 mb-2">
                                <div class="d-flex align-items-center">
                                    <div class="me-2" style="width: 20px; height: 20px; background-color: #d4edda; border-radius: 3px;"></div>
                                    <span>Frei</span>
                                </div>
                            </div>
                            <div class="col-md-4 mb-2">
                                <div class="d-flex align-items-center">
                                    <div class="me-2" style="width: 20px; height: 20px; background-color: #fff3cd; border-radius: 3px;"></div>
                                    <span>Anfrage in Bearbeitung</span>
                                </div>
                            </div>
                            <div class="col-md-4 mb-2">
                                <div class="d-flex align-items-center">
                                    <div class="me-2" style="width: 20px; height: 20px; background-color: #f8d7da; border-radius: 3px;"></div>
                                    <span>Belegt</span>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <h5 class="card-title">Informationen zur Grillhütte</h5>
                        <ul class="list-unstyled">
                            <li><strong>Miete:</strong> 100€ pro Tag (12 - 12 Uhr)</li>
                            <li><strong>Kaution:</strong> 100€</li>
                            <li><strong>Rückgabe:</strong> bis spätestens am nächsten Tag 12:00 Uhr</li>
                        </ul>
                        
                        <h6>Im Mietzins enthalten:</h6>
                        <ul>
                            <li>1m³ Wasser</li>
                            <li>5 kW/h Strom</li>
                            <li>5 Biertisch-Garnituren, jede weitere Garnitur zzgl. 1€</li>
                        </ul>
                        
                        <div class="alert alert-info">
                            <p class="mb-1"><strong>Wichtige Hinweise:</strong></p>
                            <ul class="mb-0">
                                <li>Die Grillhütte sowie die Toiletten sind sauber zu verlassen</li>
                                <li>Müll ist selbst zu entsorgen</li>
                            </ul>
                        </div>
                        
                        <h6>Schlüsselübergabe und Abnahme:</h6>
                        <p>Julia Kitschmann</p>
                        
                        <div class="mt-3">
                            <p><strong>Kontakt zur Verwalterin:</strong></p>
                            <ul class="list-unstyled">
                                <li><a href="javascript:void(0)" class="email-protect" data-encoded="<?php echo base64_encode('julia@kitschmann.de'); ?>">E-Mail anzeigen</a></li>
                                <li><a href="javascript:void(0)" class="phone-protect" data-encoded="<?php echo base64_encode('0178/8829055'); ?>">Telefonnummer anzeigen</a></li>
                            </ul>
                        </div>                   
                        
                        <div class="mt-3 alert alert-secondary">
                            <p class="mb-0"><strong>Hinweis:</strong> Bei technischen Problemen mit dem Reservierungssystem wenden Sie sich bitte an: <a href="javascript:void(0)" class="email-protect" data-encoded="<?php echo base64_encode('it@feuerwehr-waldems-reichenbach.de'); ?>">IT-Support</a></p>
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
                                    <input type="text" class="form-control time-picker" id="start_time" name="start_time" value="12:00" required>
                                </div>
                                <div class="mb-3">
                                    <label for="end_time" class="form-label">Endzeit</label>
                                    <input type="text" class="form-control time-picker" id="end_time" name="end_time" value="12:00" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="message" class="form-label">Nachricht / Anmerkungen (optional)</label>
                                    <textarea class="form-control" id="message" name="message" rows="3"></textarea>
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
                <div class="d-md-none mt-4">
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
                                                <li><strong>Miete:</strong> 100€ pro Tag (12 - 12 Uhr)</li>
                                                <li><strong>Kaution:</strong> 100€</li>
                                                <li><strong>Rückgabe:</strong> bis spätestens am nächsten Tag 12:00 Uhr</li>
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
                                                <li>1m³ Wasser</li>
                                                <li>5 kW/h Strom</li>
                                                <li>5 Biertisch-Garnituren, jede weitere Garnitur zzgl. 1€</li>
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
                                                <li>Die Grillhütte sowie die Toiletten sind sauber zu verlassen</li>
                                                <li>Müll ist selbst zu entsorgen</li>
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
                                            <p class="mb-2"><strong>Verwalterin:</strong> Julia Kitschmann</p>
                                            <ul class="list-unstyled mb-0">
                                                <li><a href="javascript:void(0)" class="email-protect" data-encoded="<?php echo base64_encode('julia@kitschmann.de'); ?>">E-Mail anzeigen</a></li>
                                                <li><a href="javascript:void(0)" class="phone-protect" data-encoded="<?php echo base64_encode('0178/8829055'); ?>">Telefon anzeigen</a></li>
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

<?php require_once 'includes/footer.php'; ?> 