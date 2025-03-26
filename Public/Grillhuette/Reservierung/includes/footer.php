    </main>

    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-12 col-md-6 mb-3 mb-md-0">
                    <h5 class="mb-2">Grillhütte Reichenbach</h5>
                    <p class="mb-0">Ein Service der Feuerwehr Waldems-Reichenbach</p>
                </div>
                <div class="col-12 col-md-6 text-md-end">
                    <p class="mb-2 mb-md-0">&copy; <?php echo date('Y'); ?> Feuerwehr Waldems-Reichenbach</p>
                    <!-- Mobile-optimized footer links -->
                    <div class="d-flex flex-wrap justify-content-center justify-content-md-end mt-2">
                        <a href="/Grillhuette" class="text-white me-3 mb-2">Informationen</a>
                        <a href="<?php echo getRelativePath('home'); ?>" class="text-white me-3 mb-2">Startseite</a>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="<?php echo getRelativePath('Benutzer/Meine-Reservierungen'); ?>" class="text-white me-3 mb-2">Meine Reservierungen</a>
                        <?php else: ?>
                            <a href="<?php echo getRelativePath('Benutzer/Anmelden'); ?>" class="text-white me-3 mb-2">Anmelden</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Mobile Action Button -->
    <?php if (!isset($_SESSION['user_id'])): ?>
    <div class="d-md-none" style="position: fixed; bottom: 20px; right: 20px; z-index: 1000;">
        <a href="<?php echo getRelativePath('Benutzer/Anmelden'); ?>" class="btn btn-primary rounded-circle shadow" style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
            <i class="bi bi-box-arrow-in-right" style="font-size: 1.5rem;"></i>
        </a>
    </div>
    <?php endif; ?>

    <!-- Cookie Consent Modal -->
    <div class="modal fade" id="cookieConsentModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="cookieConsentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cookieConsentModalLabel">Cookie-Zustimmung</h5>
                </div>
                <div class="modal-body">
                    <p>Diese Website verwendet notwendige Cookies, um die Funktionalität zu gewährleisten. Diese Cookies sind für den Betrieb der Website unerlässlich und können nicht deaktiviert werden.</p>
                    <p>Durch die Nutzung dieser Website stimmen Sie der Verwendung dieser notwendigen Cookies zu.</p>
                </div>
                <div class="modal-footer flex-column flex-sm-row">
                    <button type="button" class="btn btn-secondary w-100 w-sm-auto mb-2 mb-sm-0" id="declineCookies">Ablehnen</button>
                    <button type="button" class="btn btn-primary w-100 w-sm-auto" id="acceptCookies">Zustimmen</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/de.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/locale/de.js"></script>
    <script src="<?php echo str_repeat('../', substr_count($_SERVER['REQUEST_URI'], '/') - 3); ?>assets/js/main.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Check if consent was already given
            if (!localStorage.getItem('cookieConsent')) {
                // Show the modal if consent was not given yet
                var cookieModal = new bootstrap.Modal(document.getElementById('cookieConsentModal'));
                cookieModal.show();
            }

            // Accept cookies
            document.getElementById('acceptCookies').addEventListener('click', function() {
                localStorage.setItem('cookieConsent', 'accepted');
                var cookieModal = bootstrap.Modal.getInstance(document.getElementById('cookieConsentModal'));
                cookieModal.hide();
            });

            // Decline cookies
            document.getElementById('declineCookies').addEventListener('click', function() {
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