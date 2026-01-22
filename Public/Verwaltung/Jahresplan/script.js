/**
 * Jahresplan Generator Logic
 */

// State
let state = {
    groups: [],
    series: [], // Configuration for recurring events
    specialEvents: [], // Configuration for special events
    manualEvents: [], // Single group events (Abweichende Termine)
    hiddenEvents: [], // Keys of auto-generated events to suppress
    generatedEvents: [], // The actual calculated events {date: 'YYYY-MM-DD', groupId: '...', title: '...', id: '...'}
    year: new Date().getFullYear(),
};

// Default Groups (Example)
const defaultGroups = [
    { id: 'g1', name: 'Einsatzabteilung', color: '#ff0000' },
    { id: 'g2', name: 'Voraushelfer', color: '#0000ff' },
    { id: 'g3', name: 'Gruppen- / Zugführer', color: '#008000' },
    { id: 'g4', name: 'Jugendfeuerwehr', color: '#ff7500' },
    { id: 'g5', name: 'Kinderfeuerwehr', color: '#f0d500' }
];

document.addEventListener('DOMContentLoaded', () => {
    init();
});

function init() {
    // Determine year from URL or default
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('year')) {
        state.year = parseInt(urlParams.get('year'));
    }

    document.getElementById('yearDisplay').textContent = state.year;
    document.getElementById('calendarYearTitle').textContent = state.year;


    // Load presets or empty state
    if (!state.groups) state.groups = [];
    if (!state.series) state.series = [];
    if (!state.specialEvents) state.specialEvents = [];
    if (!state.manualEvents) state.manualEvents = [];

    if (state.groups.length === 0) {
        state.groups = [...defaultGroups];
    }

    // Fetch Holidays (Hessen)
    fetchHolidays(state.year);
    fetchVacations(state.year);

    // Initial Render
    renderGroupsUI();
    renderGroupSelects();
    renderManualEventsList();
    renderCalendar();
    // Event Listeners
    setupEventListeners();
}

// Holiday Data Store
let holidays = [];

function fetchHolidays(year) {
    holidays = CalendarRenderer.calculateHolidays(year);
    renderCalendar();
}

// calculateHolidays moved to CalendarRenderer


// Vacation Data Store
let vacations = [];

function fetchVacations(year) {
    CalendarRenderer.fetchVacations(year).then(data => {
        vacations = data;
        console.log('Loaded vacations:', vacations);
        renderCalendar();
    });
}


/**
 * Event Listeners
 */
function setupEventListeners() {
    // Config Panel
    const addGroupBtn = document.getElementById('addGroupBtn');
    if (addGroupBtn) addGroupBtn.onclick = addGroup;

    const recurringEventForm = document.getElementById('recurringEventForm');
    if (recurringEventForm) recurringEventForm.onsubmit = addSeries;

    const specialEventForm = document.getElementById('specialEventForm');
    if (specialEventForm) specialEventForm.onsubmit = addSpecialEvent;

    // Actions
    const generateBtn = document.getElementById('generateBtn');
    if (generateBtn) generateBtn.onclick = generatePlan;

    const prevYearBtn = document.getElementById('prevYear');
    if (prevYearBtn) prevYearBtn.onclick = () => changeYear(-1);

    const nextYearBtn = document.getElementById('nextYear');
    if (nextYearBtn) nextYearBtn.onclick = () => changeYear(1);

    const addManualEventBtn = document.getElementById('addManualEventBtn');
    if (addManualEventBtn) addManualEventBtn.onclick = addManualEvent;

    const exportJsonBtn = document.getElementById('exportJsonBtn');
    if (exportJsonBtn) exportJsonBtn.onclick = exportJson;

    const importJsonInput = document.getElementById('importJsonInput');
    if (importJsonInput) importJsonInput.onchange = importJson;

    const exportPngBtn = document.getElementById('exportPngBtn');
    if (exportPngBtn) exportPngBtn.onclick = exportPng;

    const exportPdfBtn = document.getElementById('exportPdfBtn');
    if (exportPdfBtn) exportPdfBtn.onclick = exportPdf;

    // Publish button
    const publishBtn = document.getElementById('publishBtn');
    if (publishBtn) publishBtn.onclick = publishPlan;

    // Load from Server button
    const loadServerBtn = document.getElementById('loadServerBtn');
    if (loadServerBtn) loadServerBtn.onclick = loadPublishedPlan;

    // Toggle seasonal inputs
    const seasonalToggle = document.getElementById('seasonalToggle');
    if (seasonalToggle) {
        seasonalToggle.onchange = (e) => {
            const config = document.getElementById('seasonalConfig');
            if (e.target.checked) config.classList.remove('d-none');
            else config.classList.add('d-none');
        };
    }

    // Global Drop Listener for Calendar
    document.addEventListener('dragstart', handleDragStart);
    document.addEventListener('dragover', (e) => e.preventDefault()); // Allow drop
    document.addEventListener('drop', handleDrop);
}

function changeYear(delta) {
    state.year += delta;
    document.getElementById('yearDisplay').textContent = state.year;
    document.getElementById('calendarYearTitle').textContent = state.year;

    // Refresh Holidays/Vacations for new year
    fetchHolidays(state.year);
    fetchVacations(state.year);

    // We should probably NOT regenerate the whole plan from scratch automatically?
    // User expectation: If I move year, do I want to see provisions for that year?
    // Usually yes.
    // Ensure day-grid is updated
    renderCalendar();
}

/**
 * UI Rendering - Configuration
 */
function renderGroupsUI() {
    const container = document.getElementById('groupsList');
    container.innerHTML = '';
    state.groups.forEach(group => {
        const item = document.createElement('div');
        item.className = 'list-group-item d-flex justify-content-between align-items-center p-2';
        item.innerHTML = `
            <span class="d-flex align-items-center">
                <span style="display:inline-block; width:15px; height:15px; background:${group.color}; margin-right:8px; border-radius:3px; border:1px solid #ccc;"></span>
                ${group.name}
            </span>
            <button class="btn btn-sm btn-outline-danger" onclick="removeGroup('${group.id}')"><i class="fas fa-trash"></i></button>
        `;
        container.appendChild(item);
    });
    renderLegend();
}

function renderGroupSelects() {
    const selects = [
        document.getElementById('eventGroupSelect'), 
        document.getElementById('specialGroupSelect'),
        document.getElementById('manualEventGroupSelect')
    ];
    selects.forEach(select => {
        if (!select) return;
        const hasNone = select.querySelector('option[value=""]');
        const selectedValue = select.value;
        select.innerHTML = '';
        if (hasNone && select.id === 'specialGroupSelect') select.appendChild(hasNone);

        state.groups.forEach(group => {
            const option = document.createElement('option');
            option.value = group.id;
            option.textContent = group.name;
            select.appendChild(option);
        });

        // Restore selection if possible
        if (selectedValue) select.value = selectedValue;
    });
}

function renderSeriesList() {
    const container = document.getElementById('seriesList');
    container.innerHTML = '';
    state.series.forEach((s, index) => {
        const group = state.groups.find(g => g.id === s.groupId);
        if (!group) return;

        let desc = `${getRhythmName(s.rhythm)} am ${getDayName(s.weekday)}`;
        if (s.seasonal) {
            desc += `<br><span class="text-info"><i class="fas fa-snowflake me-1"></i>Winter: ${getRhythmName(s.winterRhythm)} (${formatDateShort(s.winterStart)} - ${formatDateShort(s.winterEnd)})</span>`;
        }

        const item = document.createElement('div');
        item.className = 'list-group-item';
        item.innerHTML = `
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <strong style="color:${group.color}">${group.name}</strong><br>
                    <small>${desc}</small><br>
                    <small class="text-muted">Start: ${new Date(s.startDate).toLocaleDateString('de-DE')}</small>
                </div>
                <button class="btn btn-sm text-danger" onclick="removeSeries(${index})"><i class="fas fa-times"></i></button>
            </div>
        `;
        container.appendChild(item);
    });
}

function renderSpecialList() {
    const container = document.getElementById('specialList');
    container.innerHTML = '';
    state.specialEvents.forEach((s, index) => {
        const item = document.createElement('div');
        item.className = 'list-group-item d-flex justify-content-between align-items-center p-1';
        item.innerHTML = `
            <div>
                <strong>${new Date(s.date).toLocaleDateString('de-DE')}</strong>: ${s.title}
                ${s.isHoliday ? '<span class="badge bg-warning text-dark">!</span>' : ''}
            </div>
            <button class="btn btn-sm text-danger" onclick="removeSpecial(${index})"><i class="fas fa-times"></i></button>
        `;
        container.appendChild(item);
    });
    renderSpecialEventsFooter();
}

/**
 * Logic - Manual Events
 */
function addManualEvent() {
    const groupId = document.getElementById('manualEventGroupSelect').value;
    const date = document.getElementById('manualEventDate').value;
    
    if(!groupId || !date) {
        alert('Bitte Gruppe und Datum angeben.');
        return;
    }
    
    state.manualEvents.push({
        groupId: groupId,
        date: date
    });
    
    renderManualEventsList();
}

function removeManualEvent(index) {
    state.manualEvents.splice(index, 1);
    renderManualEventsList();
}

function renderManualEventsList() {
    const container = document.getElementById('manualEventsList');
    container.innerHTML = '';
    
    // Sort by date
    state.manualEvents.sort((a,b) => new Date(a.date) - new Date(b.date));
    
    state.manualEvents.forEach((ev, index) => {
        const group = state.groups.find(g => g.id === ev.groupId);
        if(!group) return;
        
        const item = document.createElement('div');
        item.className = 'list-group-item d-flex justify-content-between align-items-center p-1';
        item.innerHTML = `
            <div>
                <strong>${new Date(ev.date).toLocaleDateString('de-DE')}</strong>: <span style="color:${group.color}">${group.name}</span>
            </div>
            <button class="btn btn-sm text-danger" onclick="removeManualEvent(${index})"><i class="fas fa-times"></i></button>
        `;
        container.appendChild(item);
    });
}

/**
 * Calendar Rendering
 */
function renderCalendar() {
    CalendarRenderer.render({
        year: state.year,
        containerId: 'calendarBody',
        headerRowId: 'monthHeaderRow',
        events: state.generatedEvents,
        groups: state.groups,
        holidays: holidays,
        vacations: vacations,
        options: {
            readOnly: false,
            onEventClick: editEvent,
            onDrop: handleDrop
        }
    });

    renderLegend();
    renderSpecialEventsFooter();
    renderVacationsFooter();
    renderHolidaysFooter();
}

function renderLegend() {
    CalendarRenderer.renderLegend('legendContainer', state.groups);
}

function renderSpecialEventsFooter() {
    CalendarRenderer.renderSpecialEventsFooter('specialEventsFooter', state.specialEvents, state.year);
}

function renderHolidaysFooter() {
    CalendarRenderer.renderHolidaysFooter('holidaysFooter', holidays, state.year);
}

function renderVacationsFooter() {
    CalendarRenderer.renderVacationsFooter('vacationsFooter', vacations, state.year);
}



/**
 * Logic - Generation
 */
function generatePlan() {
    // 1. Capture existing manual overrides from Series Events
    // defined by: modified=true, type='auto', and having an originalDate
    const overrides = new Map();
    if (state.generatedEvents) {
        state.generatedEvents.forEach(ev => {
            if (ev.modified && ev.type === 'auto' && ev.originalDate && ev.groupId) {
                // Key: originalDate + groupId
                // This uniquely identifies a series instance on a specific day for a specific group
                overrides.set(`${ev.originalDate}_${ev.groupId}`, ev);
            }
        });
    }

    // 2. Clear generated
    state.generatedEvents = [];

    // 3. Process Series
    // ... (existing series logic) ...

    let newEvents = [];
    let idCounter = 1;

    // 1. Generate from Series
    state.series.forEach(serie => {
        const group = state.groups.find(g => g.id === serie.groupId);
        if (!group) return;

        let patternDate = new Date(serie.startDate);
        patternDate.setHours(0, 0, 0, 0);

        let currentLoop = 0;

        // Fast forward to near year start
        while (patternDate.getFullYear() < state.year - 1 && currentLoop < 5000) {
            patternDate = getNextSeriesDate(patternDate, serie);
            currentLoop++;
        }

        // Generate for selected year
        while (patternDate.getFullYear() <= state.year && currentLoop < 10000) {
            // Only add if it falls in the current year
            if (patternDate.getFullYear() === state.year) {
                const dateStr = CalendarRenderer.formatDateISO(patternDate);
                const overrideKey = `${dateStr}_${group.id}`;

                if (overrides.has(overrideKey)) {
                    // Use the existing modified event
                    newEvents.push(overrides.get(overrideKey));
                } else if (state.hiddenEvents && state.hiddenEvents.includes(overrideKey)) {
                    // Skip hidden events
                } else {
                    // Create new event
                    newEvents.push({
                        id: 'evt_' + (idCounter++),
                        date: dateStr,
                        title: group.name,
                        groupId: group.id,
                        type: 'auto'
                    });
                }
            }
            patternDate = getNextSeriesDate(patternDate, serie);
            currentLoop++;
        }
    });

    // 2. Add Special Events (from Config)
    // If a special event was moved, 'state.specialEvents' should have been updated by handleDrop
    state.specialEvents.forEach(ev => {
        if (ev.date.startsWith(state.year)) {
            newEvents.push({
                id: ev.id || ('spec_' + (idCounter++)),
                date: ev.date,
                title: ev.title,
                groupId: ev.groupId || null,
                customColor: ev.customColor,
                type: 'special',
                isHoliday: ev.isHoliday
            });
        }
    });


    // 3. Process Manual Events
    if(state.manualEvents) {
        state.manualEvents.forEach(ev => {
            const group = state.groups.find(g => g.id === ev.groupId);
            if(!group) return;
            
            // Check if year matches
            if(new Date(ev.date).getFullYear() !== state.year) return;

            newEvents.push({
                id: 'man_' + Math.random().toString(36).substr(2, 9),
                date: ev.date,
                groupId: ev.groupId,
                title: group.name,
                type: 'manual', 
                color: group.color
            });
        });
    }

    state.generatedEvents = newEvents;
    renderCalendar();
}

/**
 * Advanced Date Logic
 */
function getNextSeriesDate(currentDate, serie) {
    let next = new Date(currentDate);

    // Check if current date falls in winter period
    let useWinter = false;
    if (serie.seasonal && serie.winterStart && serie.winterEnd) {
        useWinter = isDateInWinter(next, serie.winterStart, serie.winterEnd);
    }

    const rhythm = useWinter ? serie.winterRhythm : serie.rhythm;

    if (rhythm === 'weekly') {
        next.setDate(next.getDate() + 7);
    } else if (rhythm === 'biweekly') {
        next.setDate(next.getDate() + 14);
    } else if (rhythm === 'monthly') {
        // "Monatlich (Wochentag)" -> Same Nth weekday next month
        // 1. Determine which occurrence of the weekday the current date represents
        // Actually, we should stick to the SERIES defined pattern.
        // It's safer to calculate from the StartDate every time or preserve "Nth" info.
        // But since we are iterating step-by-step:

        let targetMonth = next.getMonth() + 1; // Next month
        let targetYear = next.getFullYear();
        if (targetMonth > 11) {
            targetMonth = 0;
            targetYear++;
        }

        // Find Nth occurrence of weekday in target month
        // We know 'serie.weekday' is the target day (0-6)
        // We need to know 'N' from the start date?
        // Let's calculate 'N' from the start date ONCE.
        if (!serie.nthOccurrence) {
            serie.nthOccurrence = getNthOccurrence(new Date(serie.startDate));
        }

        next = getNthWeekdayOfMonth(targetYear, targetMonth, serie.weekday, serie.nthOccurrence);

    } else if (rhythm === 'monthly_date') {
        next.setMonth(next.getMonth() + 1);
    }

    return next;
}

function getNthOccurrence(date) {
    // Returns 1 for 1st instance, 2 for 2nd... 5 for 5th (or last?)
    // Simple calculation: (Day - 1) / 7 + 1
    return Math.ceil(date.getDate() / 7);
}

function getNthWeekdayOfMonth(year, month, weekday, n) {
    let date = new Date(year, month, 1);
    // Find first instance of weekday
    while (date.getDay() != weekday) {
        date.setDate(date.getDate() + 1);
    }
    // Now add (n-1) weeks
    date.setDate(date.getDate() + (n - 1) * 7);

    // Check if we overflowed the month (e.g. asking for 5th Monday but there are only 4)
    // If overflow, what to do? User requirement ambiguous. Standard is usually "last" or skip?
    // Let's clip to previous week if overflow? Or skip month?
    // Usually if 5th doesn't exist, it might mean "Last".
    if (date.getMonth() !== month) {
        // Fallback: Return the 4th? Or simply skip?
        // Let's return the last available instance for now
        date.setDate(date.getDate() - 7);
    }
    return date;
}

function isDateInWinter(date, startISO, endISO) {
    // startISO and endISO are YYYY-MM-DD strings (but year is ignored, only MM-DD matters)
    // We compare M * 100 + D

    const m = date.getMonth() + 1;
    const d = date.getDate();
    const val = m * 100 + d;

    const startM = parseInt(startISO.split('-')[1]);
    const startD = parseInt(startISO.split('-')[2]);
    const startVal = startM * 100 + startD;

    const endM = parseInt(endISO.split('-')[1]);
    const endD = parseInt(endISO.split('-')[2]);
    const endVal = endM * 100 + endD;

    if (startVal <= endVal) {
        // Winter is within same year (e.g. Jan to Mar)
        return val >= startVal && val <= endVal;
    } else {
        // Winter crosses year boundary (e.g. Nov to Mar)
        return val >= startVal || val <= endVal;
    }
}

/**
 * Interaction Logic
 */
let draggedEventId = null;

function handleDragStart(e) {
    if (e.target.classList.contains('event-marker')) {
        draggedEventId = e.target.dataset.id;
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', draggedEventId);
        setTimeout(() => e.target.style.opacity = '0.5', 0);
    }
}

function handleDrop(e) {
    e.preventDefault();
    if (!draggedEventId) return;

    // Reset opacity
    const markers = document.querySelectorAll(`.event-marker[data-id="${draggedEventId}"]`);
    markers.forEach(m => m.style.opacity = '1');

    // Find target date
    let target = e.target;
    while (target && !target.dataset.date) {
        target = target.parentElement;
    }

    if (target && target.dataset.date) {
        const newDate = target.dataset.date;

        // Update event in state
        const evIndex = state.generatedEvents.findIndex(ev => ev.id === draggedEventId);
        if (evIndex > -1) {
            const ev = state.generatedEvents[evIndex];
            const oldDate = ev.date;

            // 1. Save original date if not yet saved (for Series preservation)
            if (!ev.originalDate) {
                ev.originalDate = oldDate;
            }

            // 2. Update generated event date
            ev.date = newDate;
            ev.modified = true;

            // 3. Sync with Configuration
            if (ev.type === 'special') {
                const specIndex = state.specialEvents.findIndex(s => s.id === ev.id);
                if (specIndex > -1) {
                    state.specialEvents[specIndex].date = newDate;
                } else {
                    const fallback = state.specialEvents.find(s => s.title === ev.title && s.date === ev.originalDate);
                    if (fallback) fallback.date = newDate;
                }
                renderSpecialList();
            }

            if (ev.type === 'manual') {
                const manIndex = state.manualEvents.findIndex(m => m.date === oldDate && m.groupId === ev.groupId);
                if (manIndex > -1) {
                    state.manualEvents[manIndex].date = newDate;
                }
                renderManualEventsList();
            }

            renderCalendar();
        }
    }

    draggedEventId = null;
}

function editEvent(id) {
    const ev = state.generatedEvents.find(e => e.id === id);
    if (!ev) return;

    if (confirm(`Termin "${ev.title}" am ${ev.date} löschen?`)) {
        if (ev.type === 'auto') {
            const key = `${ev.originalDate || ev.date}_${ev.groupId}`;
            if (!state.hiddenEvents) state.hiddenEvents = [];
            state.hiddenEvents.push(key);
        } else if (ev.type === 'special') {
            state.specialEvents = state.specialEvents.filter(s => s.id !== ev.id);
            renderSpecialList();
        } else if (ev.type === 'manual') {
            // Match manual event by date and group
            const dateToMatch = ev.originalDate || ev.date;
            state.manualEvents = state.manualEvents.filter(m => !(m.date === dateToMatch && m.groupId === ev.groupId));
            renderManualEventsList();
        }
        
        state.generatedEvents = state.generatedEvents.filter(e => e.id !== id);
        renderCalendar();
    }
}

/**
 * Data Management
 */
function addGroup() {
    const name = document.getElementById('groupName').value;
    const color = document.getElementById('groupColor').value;
    if (!name) return;

    state.groups.push({
        id: 'g_' + Date.now(),
        name,
        color
    });

    document.getElementById('groupName').value = '';
    renderGroupsUI();
    renderGroupSelects();
}

function removeGroup(id) {
    if (confirm('Gruppe löschen?')) {
        state.groups = state.groups.filter(g => g.id !== id);
        renderGroupsUI();
        renderGroupSelects();
    }
}

function addSeries(e) {
    e.preventDefault();
    const groupId = document.getElementById('eventGroupSelect').value;
    const rhythm = document.getElementById('eventRhythm').value;
    const startDate = document.getElementById('eventStartDate').value;
    const weekday = document.getElementById('eventWeekday').value;

    const seasonal = document.getElementById('seasonalToggle').checked;
    const winterStart = document.getElementById('winterStart').value;
    const winterEnd = document.getElementById('winterEnd').value;
    const winterRhythm = document.getElementById('winterRhythm').value;

    state.series.push({
        groupId,
        rhythm,
        startDate,
        weekday,
        seasonal,
        winterStart,
        winterEnd,
        winterRhythm
    });

    renderSeriesList();
    e.target.reset();
}

function removeSeries(index) {
    state.series.splice(index, 1);
    renderSeriesList();
}

function addSpecialEvent(e) {
    e.preventDefault();
    const title = document.getElementById('specialTitle').value;
    const date = document.getElementById('specialDate').value;
    const isHoliday = document.getElementById('isHoliday').checked;
    const groupId = document.getElementById('specialGroupSelect').value;
    const customColor = document.getElementById('specialColor').value;

    if (!title || !date) return;

    // Logic: If groupId is chosen, use that. Else use customColor.
    // Store both for flexibility
    
    // Generate ID for robust syncing
    const id = 'spec_' + Date.now();

    state.specialEvents.push({
        id,
        title,
        date,
        isHoliday,
        groupId,
        customColor: !groupId ? customColor : null
    });

    if (state.generatedEvents.length > 0) {
        state.generatedEvents.push({
            id,
            date,
            title,
            groupId,
            customColor: !groupId ? customColor : null,
            type: 'special',
            isHoliday
        });
        renderCalendar();
    }


    renderSpecialList();
    e.target.reset();
}

function removeSpecial(index) {
    state.specialEvents.splice(index, 1);
    renderSpecialList();
    // Re-render calendar to remove it visually if present
    // Simpler to just re-generate or warn user to regenerate
    // Or filter generatedEvents
    // let's leave it for manual regeneration or next update
}

function changeYear(delta) {
    state.year += delta;
    init();
}

// Global functions for inline HTML calls
window.removeGroup = removeGroup;
window.removeSeries = removeSeries;
window.removeSpecial = removeSpecial;


/**
 * Export / Import / Helpers
 */


function getRhythmName(r) {
    const map = {
        'weekly': 'Wöchentlich',
        'biweekly': 'Alle 2 Wochen',
        'monthly': 'Monatlich'
    };
    return map[r] || r;
}

function getDayName(num) {
    const days = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];
    return days[parseInt(num)];
}



function exportJson() {
    const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(state));
    const link = document.createElement('a');
    link.href = dataStr;
    link.download = "jahresplan_" + state.year + ".json";
    link.click();
}

function importJson(e) {
    const file = e.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function (e) {
        try {
            state = JSON.parse(e.target.result);
            init();
            renderCalendar();
            alert('Plan erfolgreich geladen!');
        } catch (err) {
            console.error(err);
            alert('Fehler beim Importieren der Datei');
        }
    };
    reader.readAsText(file);
}

function getExportCanvas() {
    const element = document.getElementById('calendarContainer');
    if (!element) {
        alert('Der Kalender konnte nicht gefunden werden.');
        return null;
    }

    if (typeof window.html2canvas !== 'function') {
        console.error('html2canvas konnte nicht geladen werden.');
        alert('Export-Library fehlt. Bitte laden Sie die Seite neu.');
        return null;
    }

    let legacyResolve;
    const legacyPromise = new Promise(resolve => {
        legacyResolve = resolve;
    });
    const options = { scale: 2, onrendered: legacyResolve };

    let renderResult;
    try {
        renderResult = window.html2canvas(element, options);
    } catch (error) {
        console.error('html2canvas konnte nicht ausgeführt werden.', error);
        alert('Export konnte nicht gestartet werden. Bitte laden Sie die Seite neu.');
        return null;
    }

    if (renderResult instanceof HTMLCanvasElement) {
        return Promise.resolve(renderResult);
    }

    if (renderResult && typeof renderResult.then === 'function') {
        return renderResult;
    }

    if (renderResult === undefined) {
        return legacyPromise;
    }

    console.error('html2canvas hat kein Promise zurückgegeben.');
    alert('Export konnte nicht gestartet werden. Bitte laden Sie die Seite neu.');
    return null;
}

function exportPng() {
    const renderPromise = getExportCanvas();
    if (!renderPromise) return;

    renderPromise
        .then(canvas => {
            const link = document.createElement('a');
            link.download = 'jahresplan_' + state.year + '.png';
            link.href = canvas.toDataURL();
            link.click();
        })
        .catch(error => {
            console.error(error);
            alert('Fehler beim PNG-Export.');
        });
}

function exportPdf() {
    const renderPromise = getExportCanvas();
    if (!renderPromise) return;

    const jsPDF = window.jspdf?.jsPDF;
    if (!jsPDF) {
        console.error('jsPDF konnte nicht geladen werden.');
        alert('PDF-Library fehlt. Bitte laden Sie die Seite neu.');
        return;
    }

    renderPromise
        .then(canvas => {
            const imgData = canvas.toDataURL('image/png');
            // Create PDF in Landscape, units mm, format A4
            const pdf = new jsPDF('l', 'mm', 'a4');
            
            // A4 Landscape dimensions
            const pageWidth = 297;
            const pageHeight = 210;

            // Since the container is styled to be exactly A4 (297mm x 210mm) with internal padding,
            // we place the image at 0,0 and stretch to full page width/height.
            pdf.addImage(imgData, 'PNG', 0, 0, pageWidth, pageHeight);
            
            pdf.save('jahresplan_' + state.year + '.pdf');
        })
        .catch(error => {
            console.error(error);
            alert('Fehler beim PDF-Export.');
        });
}

function publishPlan() {
    if (!confirm('Möchten Sie den aktuellen Dienstplan wirklich veröffentlichen? Dies überschreibt den öffentlichen Plan.')) return;
    
    console.log('Publishing plan...', state);
    
    // Show loading indicator
    const publishBtn = document.getElementById('publishBtn');
    const originalText = publishBtn ? publishBtn.innerHTML : '';
    if (publishBtn) {
        publishBtn.disabled = true;
        publishBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Speichere...';
    }
    
    fetch('save_plan.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(state)
    })
    .then(r => {
        console.log('Response status:', r.status, r.statusText);
        if (!r.ok) {
            return r.text().then(text => {
                console.error('Error response:', text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    throw new Error('Server error: ' + r.status + ' ' + r.statusText + ' - ' + text.substring(0, 200));
                }
            });
        }
        return r.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if(data.success) {
            alert('Plan erfolgreich veröffentlicht!' + (data.fileSize ? ' (Größe: ' + data.fileSize + ' Bytes)' : ''));
            if (data.debug) {
                console.log('Debug info:', data.debug);
            }
        } else {
            alert('Fehler: ' + (data.error || 'Unbekannter Fehler'));
            console.error('Error details:', data);
        }
    })
    .catch(e => {
        console.error('Fetch error:', e);
        alert('Netzwerkfehler beim Speichern: ' + e.message);
    })
    .finally(() => {
        if (publishBtn) {
            publishBtn.disabled = false;
            publishBtn.innerHTML = originalText;
        }
    });
}

function loadPublishedPlan() {
    const jsonPath = '../../Mitmachen/jahresplan.json';
    fetch(jsonPath + '?t=' + Date.now())
        .then(response => {
             if(!response.ok) throw new Error("Plan konnte nicht geladen werden.");
             return response.json();
        })
        .then(data => {
            if (data) {
                // Ensure groups have ids if missing (legacy support)
                if (data.groups) {
                     data.groups.forEach((g, i) => { if(!g.id) g.id = 'g_' + i; });
                }

                state = data;
                // Verify structure
                if (!state.generatedEvents) state.generatedEvents = [];
                if (!state.series) state.series = [];
                if (!state.specialEvents) state.specialEvents = [];
                if (!state.manualEvents) state.manualEvents = [];
                if (!state.hiddenEvents) state.hiddenEvents = [];
                if (!state.groups) state.groups = [];

                // Re-render
                document.getElementById('yearDisplay').textContent = state.year;
                document.getElementById('calendarYearTitle').textContent = state.year;
                renderGroupsUI();
                renderGroupSelects();
                renderSeriesList();
                renderSpecialList();
                renderManualEventsList();
                renderCalendar();
                alert('Plan erfolgreich geladen!');
            }
        })
        .catch(err => {
            console.error(err);
            alert('Fehler beim Laden des Plans: ' + err.message);
        });
}
