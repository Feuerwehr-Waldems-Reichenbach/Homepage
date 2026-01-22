<?php
// Include required files
require_once dirname(__DIR__, 3) . '/Private/Database/Database.php';
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/Security.php';

// Check if user is logged in (handled in header, but good practice to have here too if standalone)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$pageTitle = "Jahresplanung Generator";
$useFluidContainer = true;
include dirname(__DIR__) . '/templates/header.php';
?>

<!-- Specific CSS -->
<link rel="stylesheet" href="style.css">

<div class="row">
    <!-- Configuration Panel -->
    <div class="col-lg-3 mb-4">
        <div class="card glass-card h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="m-0"><i class="fas fa-cogs me-2"></i>Konfiguration</h5>
            </div>
            <div class="card-body">
                <ul class="nav nav-tabs" id="configTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="groups-tab" data-bs-toggle="tab" data-bs-target="#groups"
                            type="button" role="tab">Gruppen</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="events-tab" data-bs-toggle="tab" data-bs-target="#events"
                            type="button" role="tab">Serien-Termine</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="special-tab" data-bs-toggle="tab" data-bs-target="#special"
                            type="button" role="tab">Termine</button>
                    </li>
                </ul>

                <div class="tab-content mt-3" id="configTabsContent">
                    <!-- Groups Tab -->
                    <div class="tab-pane fade show active" id="groups" role="tabpanel">
                        <div class="mb-3">
                            <label class="form-label">Neue Gruppe</label>
                            <div class="input-group mb-2">
                                <input type="text" class="form-control" id="groupName"
                                    placeholder="Name (z.B. Einsatzabteilung)">
                                <input type="color" class="form-control form-control-color" id="groupColor"
                                    value="#ff0000" title="Farbe wählen">
                                <button class="btn btn-success" id="addGroupBtn"><i class="fas fa-plus"></i></button>
                            </div>
                        </div>
                        <div id="groupsList" class="list-group">
                            <!-- Groups will be added here dynamically -->
                        </div>
                    </div>

                    <!-- Recurring Events Tab -->
                    <div class="tab-pane fade" id="events" role="tabpanel">
                        <form id="recurringEventForm">
                            <div class="mb-3">
                                <label class="form-label">Gruppe</label>
                                <select class="form-select" id="eventGroupSelect" required></select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Rhythmus</label>
                                <select class="form-select" id="eventRhythm" required>
                                    <option value="weekly">Wöchentlich</option>
                                    <option value="biweekly">Alle 2 Wochen</option>
                                    <option value="monthly">Monatlich (Wochentag)</option>
                                    <option value="monthly_date">Monatlich (Datum)</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Startdatum / Referenz</label>
                                <input type="date" class="form-control" id="eventStartDate" required>
                                <div class="form-text">Der erste Termin der Serie.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Wochentag</label>
                                <select class="form-select" id="eventWeekday">
                                    <option value="1">Montag</option>
                                    <option value="2">Dienstag</option>
                                    <option value="3">Mittwoch</option>
                                    <option value="4">Donnerstag</option>
                                    <option value="5">Freitag</option>
                                    <option value="6">Samstag</option>
                                    <option value="0">Sonntag</option>
                                </select>
                            </div>

                            <!-- Winter/Summer Mode Toggle -->
                            <div class="mb-3 form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="seasonalToggle">
                                <label class="form-check-label" for="seasonalToggle">Saisonale Abweichung
                                    (Sommer/Winter)</label>
                            </div>

                            <div id="seasonalConfig" class="d-none border p-2 rounded mb-3 bg-light text-dark">
                                <h6>Winterzeitraum</h6>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <input type="date" class="form-control form-control-sm" id="winterStart"
                                            placeholder="Von">
                                    </div>
                                    <div class="col-6">
                                        <input type="date" class="form-control form-control-sm" id="winterEnd"
                                            placeholder="Bis">
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <label class="form-label small">Winter-Rhythmus</label>
                                    <select class="form-select form-select-sm" id="winterRhythm">
                                        <option value="biweekly">Alle 2 Wochen</option>
                                        <option value="weekly">Wöchentlich</option>
                                    </select>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Serie Hinzufügen</button>
                        </form>

                        <hr>
                        <h6>Aktive Serien</h6>
                        <div id="seriesList" class="list-group small">
                            <!-- Series list -->
                        </div>
                    </div>

                    <!-- Special Events Tab -->
                    <div class="tab-pane fade" id="special" role="tabpanel">
                        <form id="specialEventForm">
                            <div class="mb-3">
                                <label class="form-label">Titel (z.B. Jahreshauptversammlung)</label>
                                <input type="text" class="form-control" id="specialTitle" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Datum</label>
                                <input type="date" class="form-control" id="specialDate" required>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="isHoliday">
                                <label class="form-check-label" for="isHoliday">Als Feiertag/Gesondert
                                    hervorheben</label>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Gruppe (Optional) oder Eigene Farbe</label>
                                <div class="input-group">
                                    <select class="form-select" id="specialGroupSelect">
                                        <option value="">-- Keine / Eigene Farbe --</option>
                                    </select>
                                    <input type="color" class="form-control form-control-color" id="specialColor"
                                        value="#333333" title="Farbe wählen">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-warning w-100 text-dark">Termin Hinzufügen</button>
                        </form>
                        <div id="specialList" class="list-group mt-3 small"></div>
                    </div>
                </div>
            </div>

            <div class="card-footer bg-white border-top-0 d-flex justify-content-end gap-2 flex-wrap">
                <button class="btn btn-secondary btn-sm" id="exportJsonBtn"><i class="fas fa-file-code me-2"></i>Backup
                    Speichern</button>
                <input type="file" id="importJsonInput" accept=".json" style="display:none">
                <button class="btn btn-outline-secondary btn-sm"
                    onclick="document.getElementById('importJsonInput').click()"><i
                        class="fas fa-file-upload me-2"></i>Backup Laden</button>
                <button class="btn btn-outline-dark btn-sm" id="loadServerBtn"><i
                        class="fas fa-cloud-download-alt me-2"></i>Aktuellen Plan Laden</button>

                <div class="vr mx-2"></div>

                <button class="btn btn-primary btn-sm" id="generateBtn"><i class="fas fa-sync-alt me-2"></i>Vorschau
                    aktualisieren</button>
                <div class="vr mx-2"></div>
                <button class="btn btn-success btn-sm" id="publishBtn"><i class="fas fa-cloud-upload-alt me-2"></i>Plan
                    Veröffentlichen</button>
            </div>
        </div>
    </div>

    <!-- Preview Panel -->
    <div class="col-lg-9">
        <div class="card glass-card">
            <div class="card-header d-flex justify-content-between align-items-center bg-dark text-white">
                <h5 class="m-0"><i class="fas fa-calendar-alt me-2"></i>Vorschau: <span id="yearDisplay">2026</span>
                </h5>
                <div>
                    <button class="btn btn-sm btn-light" id="prevYear"><i class="fas fa-chevron-left"></i></button>
                    <button class="btn btn-sm btn-light" id="nextYear"><i class="fas fa-chevron-right"></i></button>
                    <div class="btn-group ms-2">
                        <button class="btn btn-sm btn-success" id="exportPngBtn"><i class="fas fa-image me-1"></i>
                            PNG</button>
                        <button class="btn btn-sm btn-danger" id="exportPdfBtn"><i class="fas fa-file-pdf me-1"></i>
                            PDF</button>
                    </div>
                </div>
            </div>
            <div class="card-body p-0 bg-white" style="min-height: 600px;">
                <div class="overflow-auto p-3">
                    <div id="calendarContainer" class="bg-white text-dark">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h2 class="text-center fw-bold m-0 flex-grow-1">Jahresplan Feuerwehr Reichenbach <span
                                    id="calendarYearTitle">2026</span></h2>
                        </div>
                        <table class="table table-bordered table-sm text-center align-middle" id="annualPlanTable">
                            <thead>
                                <tr id="monthHeaderRow">
                                    <th style="width: 40px;">Tag</th>
                                    <!-- Months will be generated here -->
                                </tr>
                            </thead>
                            <tbody id="calendarBody">
                                <!-- Days will be generated here -->
                            </tbody>
                        </table>

                        <div class="mt-4 row" id="calendarFooter">
                            <div class="col-md-3">
                                <h5>Legende</h5>
                                <div id="legendContainer">
                                    <!-- Legend items -->
                                </div>
                                <!-- Removed (F) text here as it will be in legend or obvious -->
                            </div>
                            <div class="col-md-3">
                                <h5>Termine</h5>
                                <ul class="list-unstyled small" id="specialEventsFooter">
                                    <!-- Special events list -->
                                </ul>
                            </div>
                            <div class="col-md-3">
                                <h5>Ferien</h5>
                                <ul class="list-unstyled small" id="vacationsFooter">
                                    <!-- Vacations list -->
                                </ul>
                            </div>
                            <div class="col-md-3">
                                <h5>Feiertage</h5>
                                <ul class="list-unstyled small" id="holidaysFooter">
                                    <!-- Holidays list -->
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

<!-- Libraries for export -->
<script src="<?php echo $ADMIN_ROOT; ?>/assets/js/libs/html2canvas.min.js"></script>
<script src="<?php echo $ADMIN_ROOT; ?>/assets/js/libs/jspdf.umd.min.js"></script>

<!-- Shared Logic -->
<script src="../../assets/js/calendar-renderer.js"></script>

<!-- Application Logic -->
<script src="script.js"></script>

<?php include dirname(__DIR__) . '/templates/footer.php'; ?>