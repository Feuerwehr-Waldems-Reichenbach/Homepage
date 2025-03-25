    </main>

    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Grillhütte Reichenbach</h5>
                    <p>Ein Service der Feuerwehr Waldems-Reichenbach</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p>&copy; <?php echo date('Y'); ?> Feuerwehr Waldems-Reichenbach</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Cookie Consent Modal -->
    <div class="modal fade" id="cookieConsentModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="cookieConsentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cookieConsentModalLabel">Cookie-Zustimmung</h5>
                </div>
                <div class="modal-body">
                    <p>Diese Website verwendet notwendige Cookies, um die Funktionalität zu gewährleisten. Diese Cookies sind für den Betrieb der Website unerlässlich und können nicht deaktiviert werden.</p>
                    <p>Durch die Nutzung dieser Website stimmen Sie der Verwendung dieser notwendigen Cookies zu.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="declineCookies">Ablehnen</button>
                    <button type="button" class="btn btn-primary" id="acceptCookies">Zustimmen</button>
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
        });
    </script>
</body>
</html> 