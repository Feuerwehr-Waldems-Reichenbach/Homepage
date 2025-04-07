<?php
require_once '../includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-3">
            <!-- Inhaltsverzeichnis als Seitennavigation -->
            <div class="card mb-4 sticky-top" style="top: 20px; z-index: 100;">
                <div class="card-header">
                    <h5 class="mb-0">Inhaltsverzeichnis</h5>
                </div>
                <div class="card-body p-0">
                    <nav id="toc" class="nav flex-column">
                        <a class="nav-link" href="#einfuehrung">Einführung</a>
                        <a class="nav-link" href="#benutzer-registrierung">Benutzer-Registrierung</a>
                        <a class="nav-link" href="#benutzer-anmeldung">Benutzer-Anmeldung</a>
                        <a class="nav-link" href="#profil-verwalten">Profil verwalten</a>
                        <a class="nav-link" href="#passwort-zuruecksetzen">Passwort zurücksetzen</a>
                        <a class="nav-link" href="#reservierung-uebersicht">Reservierung - Übersicht</a>
                        <a class="nav-link" href="#reservierung-erstellen">Reservierung erstellen</a>
                        <a class="nav-link" href="#meine-reservierungen">Meine Reservierungen</a>
                        <a class="nav-link" href="#oeffentliche-veranstaltungen">Öffentliche Veranstaltungen</a>
                        <a class="nav-link" href="#kontakt">Kontakt und Hilfe</a>
                        <a class="nav-link" href="#faq">Häufig gestellte Fragen</a>
                    </nav>
                </div>
            </div>
        </div>
        
        <div class="col-lg-9">
            <!-- Hauptinhalt der Anleitung -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h2 class="mb-0">Benutzeranleitung für das Reservierungssystem der Grillhütte Waldems Reichenbach</h2>
                </div>
                <div class="card-body">
                    <div id="einfuehrung" class="mb-5">
                        <h3>Einführung</h3>
                        <hr>
                        <p>
                            Willkommen zur Benutzeranleitung des Reservierungssystems der Grillhütte Waldems Reichenbach. 
                            Dieses System ermöglicht Ihnen die einfache Reservierung der Grillhütte für private oder 
                            öffentliche Veranstaltungen. In dieser Anleitung erfahren Sie Schritt für Schritt, wie Sie 
                            das System nutzen können.
                        </p>
                        
                        <div class="alert alert-info">
                            <strong>Wichtiger Hinweis:</strong> Um das Reservierungssystem nutzen zu können, müssen Sie 
                            sich registrieren und Ihre E-Mail-Adresse bestätigen. Die Anmelde- und Registrierungsschaltflächen 
                            finden Sie oben rechts in der Navigationsleiste.
                        </div>
                        
                        <h4 class="mt-4">Systemanforderungen</h4>
                        <p>
                            Das Reservierungssystem ist mit allen modernen Webbrowsern kompatibel (Chrome, Firefox, Safari, Edge).
                            Es ist sowohl für Desktop-Computer als auch für Mobilgeräte optimiert.
                        </p>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-start mt-4">
                            <a href="<?php echo getRelativePath('home'); ?>" class="btn btn-primary">
                                <i class="bi bi-house-fill"></i> Zur Startseite
                            </a>
                        </div>
                    </div>
                    
                    <div id="benutzer-registrierung" class="mb-5">
                        <h3>Benutzer-Registrierung</h3>
                        <hr>
                        <p>Um das Reservierungssystem nutzen zu können, müssen Sie sich zunächst registrieren:</p>
                        
                        <ol class="mb-4">
                            <li>Klicken Sie auf den Button <strong>"Registrieren"</strong> in der oberen Navigationsleiste.</li>
                            <li>Geben Sie Ihre persönlichen Daten in das Registrierungsformular ein:
                                <ul>
                                    <li>Vorname und Nachname</li>
                                    <li>E-Mail-Adresse</li>
                                    <li>Telefonnummer</li>
                                    <li>Passwort (mindestens 8 Zeichen)</li>
                                </ul>
                            </li>
                            <li>Akzeptieren Sie die Nutzungsbedingungen und Datenschutzbestimmungen.</li>
                            <li>Klicken Sie auf <strong>"Registrieren"</strong>, um den Registrierungsprozess abzuschließen.</li>
                            <li>Nach der Registrierung erhalten Sie eine Bestätigungs-E-Mail mit einem Aktivierungslink.</li>
                            <li>Klicken Sie auf den Aktivierungslink in der E-Mail, um Ihr Konto zu verifizieren.</li>
                        </ol>
                        
                        <div class="alert alert-warning">
                            <strong>Hinweis:</strong> Sie können erst nach erfolgreicher Verifizierung Ihrer E-Mail-Adresse 
                            eine Reservierung vornehmen.
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-start mt-4">
                            <a href="<?php echo getRelativePath('Benutzer/Registrieren'); ?>" class="btn btn-primary">
                                <i class="bi bi-person-plus-fill"></i> Jetzt registrieren
                            </a>
                        </div>
                    </div>
                    
                    <div id="benutzer-anmeldung" class="mb-5">
                        <h3>Benutzer-Anmeldung</h3>
                        <hr>
                        <p>Nach erfolgreicher Registrierung können Sie sich mit Ihrer E-Mail-Adresse und Ihrem Passwort anmelden:</p>
                        
                        <ol>
                            <li>Klicken Sie in der Navigationsleiste auf den Button <strong>"Anmelden"</strong>.</li>
                            <li>Geben Sie Ihre E-Mail-Adresse und Ihr Passwort ein.</li>
                            <li>Klicken Sie auf <strong>"Anmelden"</strong>.</li>
                        </ol>
                        
                        <p>
                            Nach erfolgreicher Anmeldung gelangen Sie zur Übersichtsseite des Reservierungssystems.
                            Von dort aus können Sie alle Funktionen des Systems nutzen.
                        </p>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-start mt-4">
                            <a href="<?php echo getRelativePath('Benutzer/Anmelden'); ?>" class="btn btn-primary">
                                <i class="bi bi-box-arrow-in-right"></i> Zur Anmeldung
                            </a>
                        </div>
                    </div>
                    
                    <div id="profil-verwalten" class="mb-5">
                        <h3>Profil verwalten</h3>
                        <hr>
                        <p>
                            Nach der Anmeldung können Sie Ihr Benutzerprofil verwalten, indem Sie:
                        </p>
                        
                        <ol>
                            <li>Im Dropdown-Menü <strong>"Mein Bereich"</strong> auf <strong>"Mein Profil"</strong> klicken.</li>
                            <li>Auf der Profilseite können Sie:
                                <ul>
                                    <li>Persönliche Daten (Vorname, Nachname, Telefonnummer) ändern</li>
                                    <li>Ihr Passwort ändern</li>
                                    <li>Ihre E-Mail-Adresse ändern (erfordert neue Verifizierung)</li>
                                    <li>Ihre gespeicherten Daten per E-Mail anfordern</li>
                                    <li>Ihr Profil löschen (inkl. aller zugehörigen Reservierungen)</li>
                                </ul>
                            </li>
                            <li>Nach Änderungen auf <strong>"Aktualisieren"</strong> bzw. <strong>"Speichern"</strong> klicken, um die Änderungen zu übernehmen.</li>
                        </ol>
                        
                        <div class="alert alert-info">
                            <strong>Hinweis:</strong> Bei Änderung Ihrer E-Mail-Adresse müssen Sie diese erneut verifizieren, 
                            bevor Sie weitere Reservierungen vornehmen können. Nach der Änderung werden Sie automatisch abgemeldet.
                        </div>
                        
                        <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="d-grid gap-2 d-md-flex justify-content-md-start mt-4">
                            <a href="<?php echo getRelativePath('Benutzer/Profil'); ?>" class="btn btn-primary">
                                <i class="bi bi-person-fill-gear"></i> Mein Profil verwalten
                            </a>
                        </div>
                        <?php else: ?>
                        <div class="d-grid gap-2 d-md-flex justify-content-md-start mt-4">
                            <a href="<?php echo getRelativePath('Benutzer/Anmelden'); ?>" class="btn btn-primary">
                                <i class="bi bi-box-arrow-in-right"></i> Anmelden, um Profil zu verwalten
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div id="passwort-zuruecksetzen" class="mb-5">
                        <h3>Passwort zurücksetzen</h3>
                        <hr>
                        <p>Wenn Sie Ihr Passwort vergessen haben, können Sie es zurücksetzen:</p>
                        
                        <ol>
                            <li>Klicken Sie auf der Anmeldeseite auf <strong>"Passwort vergessen?"</strong>.</li>
                            <li>Geben Sie Ihre E-Mail-Adresse ein und klicken Sie auf <strong>"Passwort zurücksetzen"</strong>.</li>
                            <li>Sie erhalten eine E-Mail mit einem Link zum Zurücksetzen Ihres Passworts.</li>
                            <li>Klicken Sie auf den Link und geben Sie Ihr neues Passwort ein.</li>
                            <li>Bestätigen Sie das neue Passwort, indem Sie es erneut eingeben.</li>
                            <li>Klicken Sie auf <strong>"Passwort ändern"</strong>, um den Vorgang abzuschließen.</li>
                        </ol>
                        
                        <div class="alert alert-warning">
                            <strong>Achtung:</strong> Der Link zum Zurücksetzen des Passworts ist nur 24 Stunden gültig.
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-start mt-4">
                            <a href="<?php echo getRelativePath('Benutzer/Passwort-vergessen'); ?>" class="btn btn-primary">
                                <i class="bi bi-key-fill"></i> Passwort zurücksetzen
                            </a>
                        </div>
                    </div>
                    
                    <div id="reservierung-uebersicht" class="mb-5">
                        <h3>Reservierung - Übersicht</h3>
                        <hr>
                        <p>
                            Die Startseite des Reservierungssystems zeigt einen Kalender mit den verfügbaren und bereits 
                            reservierten Terminen für die Grillhütte. Sie erreichen diese Seite über den Menüpunkt <strong>"Startseite"</strong> 
                            im Dropdown-Menü <strong>"Allgemein"</strong>.
                        </p>
                        
                        <h5 class="mt-4">Kalenderlegende:</h5>
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
                        
                        <h5 class="mt-4">Informationen zur Grillhütte:</h5>
                        <p>
                            Auf der Startseite finden Sie auch alle wichtigen Informationen zur Grillhütte:
                        </p>
                        <ul>
                            <li>Mietpreis pro Tag</li>
                            <li>Kaution</li>
                            <li>Übergabe- und Rückgabezeiten</li>
                            <li>Im Mietzins enthaltene Leistungen</li>
                            <li>Wichtige Hinweise zur Nutzung</li>
                            <li>Kontaktdaten der Verwalterin</li>
                        </ul>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-start mt-4">
                            <a href="<?php echo getRelativePath('home'); ?>" class="btn btn-primary">
                                <i class="bi bi-calendar3"></i> Zum Kalendersystem
                            </a>
                        </div>
                    </div>
                    
                    <div id="reservierung-erstellen" class="mb-5">
                        <h3>Reservierung erstellen</h3>
                        <hr>
                        <p>Um eine neue Reservierung zu erstellen, gehen Sie wie folgt vor:</p>
                        
                        <ol>
                            <li>Melden Sie sich im System an (Ihre E-Mail-Adresse muss verifiziert sein).</li>
                            <li>Auf der Startseite finden Sie rechts neben dem Kalender das Reservierungsformular.</li>
                            <li>Wählen Sie im Kalender den gewünschten Starttermin aus (erster Tag Ihrer Nutzung).</li>
                            <li>Wählen Sie im Kalender den gewünschten Endtermin aus (letzter Tag Ihrer Nutzung).</li>
                            <li>Optional: Setzen Sie einen Haken bei "Quittung gewünscht", wenn Sie eine Quittung benötigen.</li>
                            <li>Optional: Für öffentliche Veranstaltungen:
                                <ul>
                                    <li>Setzen Sie einen Haken bei "Öffentliche Veranstaltung?"</li>
                                    <li>Geben Sie den Namen Ihrer Veranstaltung ein</li>                    
                                    <li>Legen Sie den Tag bzw. Zeitraum fest, an dem die Veranstaltung angezeigt werden soll</li>
                                </ul>
                            </li>
                            <li>Optional: Hinterlassen Sie eine Nachricht mit besonderen Wünschen oder Anmerkungen.</li>
                            <li>Überprüfen Sie die Kostenübersicht.</li>
                            <li>Klicken Sie auf "Reservierung anfragen", um die Reservierung abzuschließen.</li>
                        </ol>
                        
                        <div class="alert alert-info">
                            <strong>Hinweis:</strong> Nach dem Absenden der Reservierungsanfrage erhalten Sie eine 
                            Bestätigungs-E-Mail. Die Reservierung muss von der Verwaltung bestätigt werden, bevor sie 
                            verbindlich ist. Der Status Ihrer Reservierung wird im Kalender zunächst als "angefragt" (gelb) angezeigt.
                        </div>
                        
                        <h5 class="mt-4">Reservierungshilfe nutzen</h5>
                        <p>
                            Das System bietet eine interaktive Reservierungshilfe, die Sie durch den Reservierungsprozess führt:
                        </p>
                        <ol>
                            <li>Klicken Sie im Reservierungsformular auf den Button "Reservierungshilfe starten".</li>
                            <li>Folgen Sie den angezeigten Anweisungen Schritt für Schritt.</li>
                            <li>Sie werden durch alle notwendigen Eingaben geführt und erhalten hilfreiche Hinweise.</li>
                        </ol>
                        
                        <?php if (isset($_SESSION['user_id']) && isset($_SESSION['is_verified']) && $_SESSION['is_verified']): ?>
                        <div class="d-grid gap-2 d-md-flex justify-content-md-start mt-4">
                            <a href="<?php echo getRelativePath('home'); ?>" class="btn btn-primary">
                                <i class="bi bi-calendar-plus"></i> Neue Reservierung erstellen
                            </a>
                        </div>
                        <?php else: ?>
                        <div class="d-grid gap-2 d-md-flex justify-content-md-start mt-4">
                            <a href="<?php echo getRelativePath('Benutzer/Anmelden'); ?>" class="btn btn-primary">
                                <i class="bi bi-box-arrow-in-right"></i> Anmelden, um zu reservieren
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div id="meine-reservierungen" class="mb-5">
                        <h3>Meine Reservierungen</h3>
                        <hr>
                        <p>
                            Sie können Ihre bestehenden Reservierungen einsehen und verwalten:
                        </p>
                        
                        <ol>
                            <li>Klicken Sie nach der Anmeldung im Dropdown-Menü <strong>"Mein Bereich"</strong> auf <strong>"Meine Reservierungen"</strong>.</li>
                            <li>Auf der Seite "Meine Reservierungen" sehen Sie:
                                <ul>
                                    <li>Ihre aktuellen und zukünftigen Reservierungen im oberen Bereich</li>
                                    <li>Ihre vergangenen und stornierten Reservierungen im unteren Bereich</li>
                                    <li>Den Status jeder Reservierung (angefragt, bestätigt, storniert)</li>
                                    <li>Alle Details zu jeder Reservierung (Zeitraum, Kosten, Schlüsselübergabezeiten, etc.)</li>
                                </ul>
                            </li>
                        </ol>
                        
                        <h5 class="mt-4">Reservierung bearbeiten</h5>
                        <p>
                            Bei bestehenden Reservierungen können Sie:
                        </p>
                        <ul>
                            <li>Nachrichten an die Verwalterin hinzufügen oder bearbeiten</li>
                            <li>Öffentliche Veranstaltungen bearbeiten (Name und Anzeigedatum ändern)</li>
                            <li>Reservierungen stornieren</li>
                            <li>Vergangene oder stornierte Reservierungen löschen</li>
                        </ul>
                        
                        <div class="alert alert-warning">
                            <strong>Hinweis:</strong> Eine Stornierung ist für alle Reservierungen möglich, die in der Zukunft liegen. 
                            Vergangene oder stornierte Reservierungen können vollständig gelöscht werden.
                        </div>
                        
                        <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="d-grid gap-2 d-md-flex justify-content-md-start mt-4">
                            <a href="<?php echo getRelativePath('Benutzer/Meine-Reservierungen'); ?>" class="btn btn-primary">
                                <i class="bi bi-list-check"></i> Meine Reservierungen anzeigen
                            </a>
                        </div>
                        <?php else: ?>
                        <div class="d-grid gap-2 d-md-flex justify-content-md-start mt-4">
                            <a href="<?php echo getRelativePath('Benutzer/Anmelden'); ?>" class="btn btn-primary">
                                <i class="bi bi-box-arrow-in-right"></i> Anmelden, um Reservierungen zu verwalten
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div id="oeffentliche-veranstaltungen" class="mb-5">
                        <h3>Öffentliche Veranstaltungen</h3>
                        <hr>
                        <p>
                            Das System ermöglicht es, öffentliche Veranstaltungen zu erstellen, die im Kalender für alle 
                            Besucher sichtbar sind:
                        </p>
                        
                        <ol>
                            <li>Erstellen Sie eine normale Reservierung wie oben beschrieben.</li>
                            <li>Setzen Sie einen Haken bei "Öffentliche Veranstaltung?".</li>
                            <li>Geben Sie einen Namen für Ihre Veranstaltung ein (z.B. "Sommerfest", "Vereinstreffen").</li>
                            <li>Legen Sie den Zeitraum fest, in dem die Veranstaltung im Kalender angezeigt werden soll. Dieser Zeitraum muss innerhalb Ihres Reservierungszeitraums liegen.</li>
                        </ol>
                        
                        <p>
                            Nach Bestätigung durch die Verwaltung wird Ihre Veranstaltung im Kalender in einer speziellen 
                            Farbe (grün-bläulich) angezeigt und ist für alle Besucher der Website sichtbar.
                        </p>
                        
                        <h5 class="mt-4">Veranstaltung bearbeiten</h5>
                        <p>Sie können Ihre öffentlichen Veranstaltungen später jederzeit bearbeiten:</p>
                        <ol>
                            <li>Gehen Sie zu "Meine Reservierungen".</li>
                            <li>Suchen Sie die Reservierung mit der öffentlichen Veranstaltung.</li>
                            <li>Klicken Sie auf den Button "Veranstaltung bearbeiten".</li>
                            <li>Ändern Sie den Namen oder die Anzeigedaten nach Bedarf.</li>
                            <li>Klicken Sie auf "Änderungen speichern".</li>
                        </ol>
                        
                        <div class="alert alert-info">
                            <strong>Hinweis:</strong> Öffentliche Veranstaltungen sind ein guter Weg, um andere über Ihre 
                            Veranstaltung zu informieren. Der Name der Veranstaltung wird im Kalender angezeigt. Die Anzeigedaten können 
                            von Ihren tatsächlichen Reservierungsdaten abweichen, müssen aber innerhalb des Reservierungszeitraums liegen.
                        </div>
                        
                        <?php if (isset($_SESSION['user_id']) && isset($_SESSION['is_verified']) && $_SESSION['is_verified']): ?>
                        <div class="d-grid gap-2 d-md-flex justify-content-md-start mt-4">
                            <a href="<?php echo getRelativePath('home'); ?>" class="btn btn-primary">
                                <i class="bi bi-calendar-event"></i> Öffentliche Veranstaltung erstellen
                            </a>
                        </div>
                        <?php else: ?>
                        <div class="d-grid gap-2 d-md-flex justify-content-md-start mt-4">
                            <a href="<?php echo getRelativePath('Benutzer/Anmelden'); ?>" class="btn btn-primary">
                                <i class="bi bi-box-arrow-in-right"></i> Anmelden, um eine Veranstaltung zu erstellen
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div id="kontakt" class="mb-5">
                        <h3>Kontakt und Hilfe</h3>
                        <hr>
                        <p>
                            Bei Fragen oder Problemen mit dem Reservierungssystem können Sie folgende Kontaktmöglichkeiten 
                            nutzen:
                        </p>
                        
                        <h5 class="mt-4">Kontaktdaten</h5>
                        <ul>
                            <li>Verwalterin: <?php echo $infoData['VerwaltungspersonVorname'] ?? 'Julia'; ?> <?php echo $infoData['VerwaltungspersonNachname'] ?? 'Kitschmann'; ?></li>
                            <li>E-Mail: <a href="javascript:void(0)" class="email-protect" data-encoded="<?php echo base64_encode($infoData['VerwaltungspersonEmail'] ?? 'julia@kitschmann.de'); ?>">E-Mail anzeigen</a></li>
                            <li>Telefon: <a href="javascript:void(0)" class="phone-protect" data-encoded="<?php echo base64_encode($infoData['VerwaltungspersonTelefon'] ?? '0178/8829055'); ?>">Telefonnummer anzeigen</a></li>
                        </ul>
                        
                        <h5 class="mt-4">Technischer Support</h5>
                        <p>
                            Bei technischen Problemen mit dem Reservierungssystem:
                        </p>
                        <ul>
                            <li>E-Mail: <a href="javascript:void(0)" class="email-protect" data-encoded="<?php echo base64_encode($infoData['SystemEmailProbleme'] ?? 'it@feuerwehr-waldems-reichenbach.de'); ?>">IT-Support</a></li>
                        </ul>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-start mt-4">
                            <a href="mailto:<?php echo $infoData['SystemEmailProbleme'] ?? 'it@feuerwehr-waldems-reichenbach.de'; ?>" class="btn btn-primary">
                                <i class="bi bi-envelope-fill"></i> Technischen Support kontaktieren
                            </a>
                        </div>
                    </div>
                    
                    <div id="faq" class="mb-5">
                        <h3>Häufig gestellte Fragen (FAQ)</h3>
                        <hr>
                        
                        <div class="accordion" id="faqAccordion">
                            <div class="accordion-item">
                                <h4 class="accordion-header" id="headingTwo">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                        Wann muss ich bezahlen?
                                    </button>
                                </h4>
                                <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Die Bezahlung (Miete und Kaution) erfolgt in bar bei der Schlüsselübergabe. 
                                        Eine Vorauszahlung oder Überweisung ist nicht möglich.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h4 class="accordion-header" id="headingThree">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                        Was passiert nach meiner Reservierungsanfrage?
                                    </button>
                                </h4>
                                <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Nach Ihrer Anfrage erhalten Sie eine Bestätigungs-E-Mail. Die Verwalterin prüft Ihre 
                                        Anfrage und bestätigt oder lehnt sie ab. Sie werden per E-Mail über die Entscheidung 
                                        informiert. Im Kalender wird Ihre Reservierung zunächst als "angefragt" (gelb) und 
                                        nach Bestätigung als "belegt" (rot) angezeigt.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h4 class="accordion-header" id="headingFour">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                        Kann ich eine bestätigte Reservierung stornieren?
                                    </button>
                                </h4>
                                <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Ja, Sie können auch bestätigte Reservierungen stornieren, solange der Termin in der 
                                        Zukunft liegt. Gehen Sie dazu zu "Meine Reservierungen" und klicken Sie auf "Reservierung stornieren". 
                                        Die Verwalterin wird automatisch per E-Mail über Ihre Stornierung informiert.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h4 class="accordion-header" id="headingFive">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                                        Was bedeutet der blaue "Schlüsselübergabe"-Tag im Kalender?
                                    </button>
                                </h4>
                                <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Ein als "Schlüsselübergabe" markierter Tag bedeutet, dass die Hütte nur eingeschränkt 
                                        nutzbar ist, da an diesem Tag eine Übergabe stattfindet. Die genauen Zeiten können 
                                        Sie sehen, wenn Sie mit der Maus über den Tag fahren. Typischerweise findet die Schlüsselübergabe um 16 Uhr am Vortag der Reservierung statt und die Schlüsselrückgabe um 12 Uhr am Tag nach der Reservierung.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h4 class="accordion-header" id="headingSix">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSix" aria-expanded="false" aria-controls="collapseSix">
                                        Kann ich die Toiletten im Winter benutzen?
                                    </button>
                                </h4>
                                <div id="collapseSix" class="accordion-collapse collapse" aria-labelledby="headingSix" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        In den kälteren Monaten (ca. Oktober bis März) sind die Toiletten möglicherweise nicht 
                                        nutzbar, da das Wasser abgestellt wird, um Frostschäden zu vermeiden. Der genaue 
                                        Zeitraum kann variieren.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h4 class="accordion-header" id="headingSeven">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSeven" aria-expanded="false" aria-controls="collapseSeven">
                                        Kann ich meine Daten aus dem System löschen lassen?
                                    </button>
                                </h4>
                                <div id="collapseSeven" class="accordion-collapse collapse" aria-labelledby="headingSeven" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Ja, gemäß der DSGVO haben Sie das Recht, Ihre Daten löschen zu lassen. Sie können Ihr Profil 
                                        selbst löschen, indem Sie in Ihrem Profil auf "Mein Profil unwiderruflich löschen" klicken. 
                                        Dabei werden alle Ihre persönlichen Daten und Reservierungen gelöscht. Alternativ können Sie 
                                        auch den IT-Support kontaktieren, um Ihre Daten löschen zu lassen.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h4 class="accordion-header" id="headingEight">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEight" aria-expanded="false" aria-controls="collapseEight">
                                        Wie erhalte ich eine Übersicht meiner gespeicherten Daten?
                                    </button>
                                </h4>
                                <div id="collapseEight" class="accordion-collapse collapse" aria-labelledby="headingEight" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        In Ihrem Benutzerprofil finden Sie den Button "Meine Daten per E-Mail erhalten". 
                                        Nach einem Klick darauf erhalten Sie eine E-Mail mit allen über Sie gespeicherten Daten, 
                                        einschließlich Ihrer Reservierungen.
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-start mt-4">
                            <a href="<?php echo getRelativePath('home'); ?>" class="btn btn-primary">
                                <i class="bi bi-house-fill"></i> Zurück zur Startseite
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script nonce="<?php echo $cspNonce; ?>">
    document.addEventListener('DOMContentLoaded', function() {
        // Smooth scrolling für die Navigationslinks
        document.querySelectorAll('#toc a').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                
                const targetId = this.getAttribute('href');
                const targetElement = document.querySelector(targetId);
                
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 20,
                        behavior: 'smooth'
                    });
                    
                    // URL aktualisieren ohne Seite neu zu laden
                    history.pushState(null, null, targetId);
                    
                    // Alle Links deaktivieren und den geklickten aktivieren
                    document.querySelectorAll('#toc a').forEach(a => a.classList.remove('active'));
                    this.classList.add('active');
                }
            });
        });
        
        // Aktiven Link aktualisieren beim Scrollen
        window.addEventListener('scroll', function() {
            let currentSection = '';
            
            document.querySelectorAll('div[id]').forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.offsetHeight;
                
                if (window.pageYOffset >= sectionTop - 50 && window.pageYOffset < sectionTop + sectionHeight - 50) {
                    currentSection = '#' + section.getAttribute('id');
                }
            });
            
            document.querySelectorAll('#toc a').forEach(a => {
                a.classList.remove('active');
                if (a.getAttribute('href') === currentSection) {
                    a.classList.add('active');
                }
            });
        });
        
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

<style>
    /* Styles für die Anleitungsseite */
    .nav-link.active {
        color: #0d6efd;
        font-weight: bold;
        background-color: rgba(13, 110, 253, 0.1);
    }
    
    /* Sticky TOC mit Abstand nach oben */
    .sticky-top {
        top: 20px;
    }
    
    /* Erhöhte Lesbarkeit für den Hauptinhalt */
    .card-body {
        font-size: 1rem;
        line-height: 1.6;
    }
    
    /* Abschnitte mit genügend Abstand */
    .mb-5 {
        margin-bottom: 3rem !important;
    }
    
    /* Verbesserte Sichtbarkeit für Überschriften */
    h3 {
        margin-top: 0.5rem;
        margin-bottom: 1rem;
        color: #0d6efd;
    }
    
    /* Responsives Verhalten für mobile Geräte */
    @media (max-width: 992px) {
        .sticky-top {
            position: relative;
            top: 0;
        }
    }
</style>

<?php require_once '../includes/footer.php'; ?> 