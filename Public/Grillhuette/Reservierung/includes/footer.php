</main>
<footer class="bg-dark text-white py-4 mt-5">
    <div class="container">
        <div class="row mb-4">
            <div class="col-12">
                <div class="map-wrapper">
                    <div class="map-header d-flex justify-content-between align-items-center mb-2">
                        <h5 class="m-0"><i class="bi bi-geo-alt-fill me-2"></i>Standort Grillhütte Reichenbach</h5>
                        <a href="https://maps.app.goo.gl/B33Pk2xZeHWrdPQL9" target="_blank"
                            class="btn btn-sm btn-outline-light">
                            <i class="bi bi-box-arrow-up-right me-1"></i>Google Maps öffnen
                        </a>
                    </div>
                    <div class="map-container rounded shadow-sm overflow-hidden">
                        <iframe
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d5132.282750261533!2d8.371175454928999!3d50.268477518526375!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x47bdb1bf3444aa6f%3A0x53abe310515b94df!2sGrillh%C3%BCtte%20Reichenbach!5e1!3m2!1sde!2sde!4v1743380028454!5m2!1sde!2sde"
                            width="100%" height="250" style="border:0; display:block;" allowfullscreen="" loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            title="Standort Grillhütte Reichenbach"></iframe>
                    </div>
                </div>
            </div>
        </div>
        <div class="row pt-3 border-top border-secondary">
            <div class="col-md-6 mb-3 mb-md-0">
                <div class="d-flex flex-column h-100">
                    <div class="contact-info mt-3">
                        <div class="d-flex align-items-start mb-2">
                            <i class="bi bi-geo-alt-fill me-2 mt-1 text-danger"></i>
                            <span>Am Dorfgemeinschaftshaus 1<br>65529 Waldems</span>
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-envelope-fill me-2 text-danger"></i>
                            <a href="mailto:info@feuerwehr-waldems-reichenbach.de"
                                class="text-white text-decoration-none hover-underline">info@feuerwehr-waldems-reichenbach.de</a>
                        </div>
                    </div>
                    <div class="social-links mt-3">
                        <a href="https://www.facebook.com/groups/163127135137/" target="_blank" class="text-white me-3"
                            title="Facebook">
                            <i class="bi bi-facebook fs-5"></i>
                        </a>
                        <a href="https://www.instagram.com/feuerwehrreichenbach/" target="_blank"
                            class="text-white me-3" title="Instagram">
                            <i class="bi bi-instagram fs-5"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 text-md-end">
                <p class="mb-2 mb-md-0">&copy; <?php echo date('Y'); ?> Förderverein der Freiwilligen Feuerwehr
                    Waldems-Reichenbach e.V. im Auftrag für die Freiwillige Feuerwehr Waldems Reichenbach - Alle Rechte
                    vorbehalten</p>
                <!-- Footer links -->
                <div class="d-flex flex-wrap justify-content-start justify-content-md-end mt-2 footer-links">
                    <a href="<?php echo getRelativePath('Nutzungsbedingungen'); ?>" class="text-white me-3 mb-2">
                        <i class="bi bi-file-text me-1"></i>Nutzungsbedingungen
                    </a>
                    <a href="<?php echo getRelativePath('Anleitung'); ?>" class="text-white me-3 mb-2">
                        <i class="bi bi-question-circle me-1"></i>Anleitung
                    </a>
                    <a href="/Datenschutz" class="text-white me-3 mb-2">
                        <i class="bi bi-shield-lock me-1"></i>Datenschutz
                    </a>
                    <a href="/Impressum" class="text-white mb-2">
                        <i class="bi bi-info-circle me-1"></i>Impressum
                    </a>
                </div>
            </div>
        </div>
    </div>
</footer>
<!-- Mobile Action Button -->
<?php if (!isset($_SESSION['user_id'])): ?>
    <div class="d-md-none" style="position: fixed; bottom: 20px; right: 20px; z-index: 1000;">
        <a href="<?php echo getRelativePath('Benutzer/Anmelden'); ?>" class="btn btn-primary rounded-circle shadow"
            style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
            <i class="bi bi-box-arrow-in-right" style="font-size: 1.5rem;"></i>
        </a>
    </div>
<?php endif; ?>
<!-- Cookie Consent Modal -->
<div class="modal fade" id="cookieConsentModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="cookieConsentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cookieConsentModalLabel">Cookie-Zustimmung</h5>
            </div>
            <div class="modal-body">
                <p>Diese Website verwendet notwendige Cookies, um die Funktionalität zu gewährleisten. Diese Cookies
                    sind für den Betrieb der Website unerlässlich und können nicht deaktiviert werden.</p>
                <p>Durch die Nutzung dieser Website stimmen Sie der Verwendung dieser notwendigen Cookies zu.</p>
            </div>
            <div class="modal-footer flex-column flex-sm-row">
                <button type="button" class="btn btn-secondary w-100 w-sm-auto mb-2 mb-sm-0"
                    id="declineCookies">Ablehnen</button>
                <button type="button" class="btn btn-primary w-100 w-sm-auto" id="acceptCookies">Zustimmen</button>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    nonce="<?php echo $cspNonce; ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr" nonce="<?php echo $cspNonce; ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/de.js" nonce="<?php echo $cspNonce; ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js" nonce="<?php echo $cspNonce; ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/locale/de.js" nonce="<?php echo $cspNonce; ?>"></script>
<script src="<?php echo str_repeat('../', substr_count($_SERVER['REQUEST_URI'], '/') - 3); ?>assets/js/main.js"
    nonce="<?php echo $cspNonce; ?>"></script>
<script nonce="<?php echo $cspNonce; ?>">
    document.addEventListener('DOMContentLoaded', function () {
        // Check if consent was already given
        if (!localStorage.getItem('cookieConsent')) {
            // Show the modal if consent was not given yet
            var cookieModal = new bootstrap.Modal(document.getElementById('cookieConsentModal'));
            cookieModal.show();
        }
        // Accept cookies
        document.getElementById('acceptCookies').addEventListener('click', function () {
            localStorage.setItem('cookieConsent', 'accepted');
            var cookieModal = bootstrap.Modal.getInstance(document.getElementById('cookieConsentModal'));
            cookieModal.hide();
        });
        // Decline cookies
        document.getElementById('declineCookies').addEventListener('click', function () {
            // Show message and redirect to blank page or reload
            alert('Um diese Website nutzen zu können, müssen Sie der Verwendung von notwendigen Cookies zustimmen.');
            // You could redirect to a "blocked" page or just reload the page which will show the modal again
            window.location.reload();
        });
        // Add iOS safe area padding for iPhone X and newer
        function updateSafeAreaPadding() {
            if (CSS.supports('padding: max(0px)')) {
                document.body.style.paddingBottom = 'max(30px, env(safe-area-inset-bottom))';
            }
        }
        // Call initially and on resize
        updateSafeAreaPadding();
        window.addEventListener('resize', updateSafeAreaPadding);
    });
</script>
</body>

</html>
<?php
// Flush the output buffer
ob_end_flush();
?>