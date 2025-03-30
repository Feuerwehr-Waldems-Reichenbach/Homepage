<?php
require_once '../includes/config.php';

// Titel für die Seite
$pageTitle = 'Nutzungsbedingungen';

// Header einbinden
require_once '../includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h1 class="h2 mb-0">Nutzungsbedingungen</h1>
                </div>
                <div class="card-body">
                    <h2 class="h4">1. Allgemeines</h2>
                    <p>Die Nutzungsbedingungen regeln die Nutzung des Reservierungssystems für die Grillhütte Waldems Reichenbach und stellen einen verbindlichen Vertrag zwischen Ihnen und dem Betreiber der Grillhütte dar.</p>
                    
                    <h2 class="h4">2. Registrierung und Benutzerkonto</h2>
                    <p>Für die Nutzung des Reservierungssystems ist eine Registrierung erforderlich. Sie sind verpflichtet, bei der Registrierung wahrheitsgemäße Angaben zu machen und diese bei Änderungen zu aktualisieren.</p>
                    <p>Sie sind für die Geheimhaltung Ihrer Zugangsdaten verantwortlich. Jede unter Ihrem Benutzerkonto vorgenommene Handlung wird Ihnen zugerechnet.</p>
                    
                    <h2 class="h4">3. Reservierungen</h2>
                    <p>Eine Reservierung ist erst nach Bestätigung durch den Betreiber verbindlich. Der Betreiber behält sich das Recht vor, Reservierungsanfragen ohne Angabe von Gründen abzulehnen.</p>
                    <p>Die Kosten für die Nutzung der Grillhütte richten sich nach der aktuellen Preisliste. Eine Stornierung ist bis zu 7 Tage vor dem Termin kostenlos möglich. Bei späterer Stornierung können Gebühren anfallen.</p>
                    
                    <h2 class="h4">4. Verhaltensregeln</h2>
                    <p>Die Nutzer sind verpflichtet, mit den Einrichtungen der Grillhütte sorgsam umzugehen. Beschädigungen sind dem Betreiber umgehend zu melden.</p>
                    <p>Die Grillhütte ist nach der Nutzung gereinigt zu übergeben. Müll ist entsprechend der örtlichen Vorschriften zu entsorgen.</p>
                    
                    <h2 class="h4">5. Haftung</h2>
                    <p>Der Betreiber haftet nicht für Schäden, die durch höhere Gewalt oder durch das Verhalten Dritter entstehen.</p>
                    <p>Die Nutzer haften für Schäden, die sie selbst oder ihre Gäste an den Einrichtungen der Grillhütte verursachen.</p>
                    
                    <h2 class="h4">6. Änderungen der Nutzungsbedingungen</h2>
                    <p>Der Betreiber behält sich das Recht vor, diese Nutzungsbedingungen jederzeit zu ändern. Die Nutzer werden über Änderungen per E-Mail informiert.</p>
                    
                    <h2 class="h4">7. Schlussbestimmungen</h2>
                    <p>Sollten einzelne Bestimmungen dieser Nutzungsbedingungen ungültig sein oder werden, bleibt die Gültigkeit der übrigen Bestimmungen unberührt.</p>
                    <p>Es gilt deutsches Recht. Gerichtsstand ist, soweit gesetzlich zulässig, der Sitz des Betreibers.</p>
                    
                    <div class="mt-4">
                        <p><strong>Stand:</strong> <?php echo date('d.m.Y'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 