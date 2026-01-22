/**
 * Jahresplan Generator Logic
 */

// State
let state = {
    groups: [],
    series: [], // Configuration for recurring events
    specialEvents: [], // Configuration for special events
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
    // In a real app, this might come from local storage or DB
    if (state.groups.length === 0) {
        state.groups = [...defaultGroups];
    }

    // Fetch Holidays (Hessen)
    fetchHolidays(state.year);
    fetchVacations(state.year);

    // Initial Render
    renderGroupsUI();
    renderGroupSelects();
    renderCalendar();
    // Event Listeners
    setupEventListeners();
}

// Holiday Data Store
let holidays = [];

function fetchHolidays(year) {
    // Using feiertage-api.de
    const url = `https://get.api-feiertage.de?years=${year}&states=he`;

    // Since we need to avoid cross-origin issues or rely on external API availability...
    // Let's use a static calculation for common holidays or a public JSON API if CORS permits.
    // 'feiertage-api.de' is good but requires API key? No.
    // 'ferien-api.de' is for vacations.

    // Let's use a simple static calculator for German holidays (Gaussian Easter) for robustness without external deps.
    holidays = calculateHolidays(year);
    renderCalendar();
}

function calculateHolidays(year) {
    // Easter calculation (Gaussian)
    const a = year % 19;
    const b = Math.floor(year / 100);
    const c = year % 100;
    const d = Math.floor(b / 4);
    const e = b % 4;
    const f = Math.floor((b + 8) / 25);
    const g = Math.floor((b - f + 1) / 3);
    const h = (19 * a + b - d - g + 15) % 30;
    const i = Math.floor(c / 4);
    const k = c % 4;
    const l = (32 + 2 * e + 2 * i - h - k) % 7;
    const m = Math.floor((a + 11 * h + 22 * l) / 451);

    const easterMonth = Math.floor((h + l - 7 * m + 114) / 31) - 1;
    const easterDay = ((h + l - 7 * m + 114) % 31) + 1;
    const easterDate = new Date(year, easterMonth, easterDay);

    const addDays = (date, days) => {
        const result = new Date(date);
        result.setDate(result.getDate() + days);
        return result;
    };

    const list = [
        { date: `${year}-01-01`, name: 'Neujahr' },
        { date: `${year}-05-01`, name: 'Tag der Arbeit' },
        { date: `${year}-10-03`, name: 'Tag der Deutschen Einheit' },
        { date: `${year}-12-25`, name: '1. Weihnachtstag' },
        { date: `${year}-12-26`, name: '2. Weihnachtstag' },

        { date: formatDateISO(addDays(easterDate, -2)), name: 'Karfreitag' },
        { date: formatDateISO(addDays(easterDate, 1)), name: 'Ostermontag' },
        { date: formatDateISO(addDays(easterDate, 39)), name: 'Christi Himmelfahrt' },
        { date: formatDateISO(addDays(easterDate, 50)), name: 'Pfingstmontag' },
        { date: formatDateISO(addDays(easterDate, 60)), name: 'Fronleichnam' }
    ];

    return list;
}

// Vacation Data Store
let vacations = [];

function fetchVacations(year) {
    // Fetch vacations for current year AND previous year (to catch winter vacations starting in Dec)
    const years = [year - 1, year];
    
    Promise.all(years.map(y => fetch(`https://schulferien-api.de/api/v1/${y}/HE`).then(r => r.ok ? r.json() : [])))
        .then(results => {
            let allVacations = [];
            results.forEach(data => {
                const items = Array.isArray(data) ? data : data?.data;
                if (Array.isArray(items)) {
                    allVacations = allVacations.concat(items.map(v => ({
                        start: v.start.substring(0, 10),
                        end: v.end.substring(0, 10),
                        name: v.name_cp || v.name
                    })));
                }
            });
            vacations = allVacations;
            console.log('Loaded vacations:', vacations);
            renderCalendar();
        })
        .catch(error => {
            console.error('Error fetching vacations:', error);
            vacations = [];
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

    const exportJsonBtn = document.getElementById('exportJsonBtn');
    if (exportJsonBtn) exportJsonBtn.onclick = exportJson;

    const importJsonInput = document.getElementById('importJsonInput');
    if (importJsonInput) importJsonInput.onchange = importJson;

    const exportPngBtn = document.getElementById('exportPngBtn');
    if (exportPngBtn) exportPngBtn.onclick = exportPng;

    const exportPdfBtn = document.getElementById('exportPdfBtn');
    if (exportPdfBtn) exportPdfBtn.onclick = exportPdf;

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
    const selects = [document.getElementById('eventGroupSelect'), document.getElementById('specialGroupSelect')];
    selects.forEach(select => {
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
 * Calendar Rendering
 */
function renderCalendar() {
    const theadRow = document.getElementById('monthHeaderRow');
    const tbody = document.getElementById('calendarBody');

    // Clear existing
    theadRow.innerHTML = '<th style="width: 40px; background:#e9ecef;">Tag</th>';
    tbody.innerHTML = '';

    const months = ['Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'];
    const monthFull = ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];

    // Render Month Headers
    months.forEach((m, i) => {
        const th = document.createElement('th');
        th.textContent = m;
        // Optional: Add full name as title
        th.title = monthFull[i];
        theadRow.appendChild(th);
    });

    // Render Days (1-31)
    for (let day = 1; day <= 31; day++) {
        const tr = document.createElement('tr');

        // Day Number Cell
        const tdNum = document.createElement('td');
        tdNum.textContent = day;
        tdNum.className = 'fw-bold bg-light';
        tr.appendChild(tdNum);

        // Month Cells
        for (let m = 0; m < 12; m++) {
            const td = document.createElement('td');
            const date = new Date(state.year, m, day);
            const dateString = formatDateISO(date);

            // Add date attribute for Drag & Drop
            td.dataset.date = dateString;

            // Check if valid date (e.g. Feb 30 is invalid)
            if (date.getMonth() !== m) {
                td.style.backgroundColor = '#ddd'; // Invalid date
                td.classList.add('invalid-date');
                tr.appendChild(td);
                continue;
            }

            const dayOfWeek = date.getDay();
            const dayName = getDayShortName(dayOfWeek);

            // Weekend highlighting
            if (dayOfWeek === 0 || dayOfWeek === 6) {
                td.style.backgroundColor = '#f8f9fa';
                td.classList.add('weekend-row');
            }
            if (dayOfWeek === 0) { // Sunday slightly darker
                td.style.backgroundColor = '#f1f3f5';
            }

            // Holiday highlighting override with CSS classes
            const holiday = holidays.find(h => h.date === dateString);
            const vac = vacations.find(v => dateString >= v.start && dateString <= v.end);

            if (vac) {
                td.classList.add('vacation-bg');
                td.title = vac.name;
            }
            if (holiday) {
                td.classList.add('holiday-bg'); // Red takes precedence via CSS if needed
                td.title = holiday.name;
            }
            if (vac && holiday) {
                td.title = `${holiday.name} (${vac.name})`;
            }

            // Cell Content Container
            const cellContent = document.createElement('div');
            cellContent.className = 'cell-content';

            // Date Label
            const dateLabel = document.createElement('div');
            dateLabel.className = 'cell-date';
            
            // Format: "Mo 01."
            // Pad Zero
            const dayNum = String(day).padStart(2, '0');
            let labelText = `${dayName} ${dayNum}.`;

            let infoIndicator = '';
            if (holiday) {
                infoIndicator = '<span class="holiday-indicator" title="' + holiday.name + '">FT</span>';
            }

            dateLabel.innerHTML = `${labelText} ${infoIndicator}`;
            cellContent.appendChild(dateLabel);

            // Container for Events
            const eventContainer = document.createElement('div');
            eventContainer.className = 'event-container';

            // --- RENDER EVENTS ---
            const events = state.generatedEvents.filter(e => e.date === dateString);

            // Render events in flex container
            if (events.length > 0) {
                events.forEach(ev => {
                    const el = document.createElement('div');
                    el.className = 'event-marker';

                    // Get group color OR custom color
                    let color = '#333';
                    if (ev.groupId) {
                        const group = state.groups.find(g => g.id === ev.groupId);
                        if (group) color = group.color;
                    } else if (ev.customColor) {
                        color = ev.customColor;
                    }

                    el.style.backgroundColor = color;
                    // Only show title if special event, otherwise color block is enough or small tooltip
                    // For "Termine" (special), we probably want to see the title?
                    // Let's modify: Always show short title or icon? 
                    // User said "eigene termine...".
                    // Let's show truncated title if space permits.
                    el.title = ev.title;
                    el.textContent = ev.isHoliday ? '!' : '';

                    // If it's a "Termin" (special), maybe show a dot or small text?
                    // For now, keep it simple blocks.

                    el.setAttribute('draggable', 'true');
                    el.dataset.id = ev.id;

                    // Interaction: Click to edit/delete
                    el.onclick = (e) => {
                        e.stopPropagation();
                        editEvent(ev.id);
                    };

                    eventContainer.appendChild(el);
                });
            }

            cellContent.appendChild(eventContainer);
            td.appendChild(cellContent);

            // Drop zone for dragging
            td.ondragover = (e) => e.preventDefault();
            td.ondrop = (e) => handleDrop(e); // Removing dateString arg, handleDrop uses dataset

            tr.appendChild(td);
        }
        tbody.appendChild(tr);
    }

    renderLegend();
    renderSpecialEventsFooter();
    renderVacationsFooter();
    renderHolidaysFooter();
}

function renderLegend() {
    const container = document.getElementById('legendContainer');
    container.innerHTML = '';
    
    // Add (F) = Ferien manually to legend? 
    // User requested lighter/compact legend. 
    // Let's add standard items plus Ferien/Holiday status
    
    const extras = [
        {name: 'Ferien', color: '#e0f7fa'},
        {name: 'Feiertag', color: '#ffebee'}
    ];
    
    // Render custom groups
    state.groups.forEach(group => {
        const div = document.createElement('div');
        div.className = 'legend-item';
        div.innerHTML = `<span class="legend-color" style="background:${group.color}"></span> ${group.name}`;
        container.appendChild(div);
    });
    
    // Render status colors
    extras.forEach(ex => {
        const div = document.createElement('div');
        div.className = 'legend-item';
        div.innerHTML = `<span class="legend-color" style="background:${ex.color}; border:1px solid #ccc;"></span> ${ex.name}`;
        container.appendChild(div);
    });
}

function renderSpecialEventsFooter() {
    const ul = document.getElementById('specialEventsFooter');
    ul.innerHTML = '';

    // Mix custom special events AND Generated events that are marked 'special' logic?
    // Actually, user wants "list special appointments separately".
    // I should list all events that are defined in 'specialEvents' separate list.

    // Sort chronologically
    const sorted = [...state.specialEvents].sort((a, b) => new Date(a.date) - new Date(b.date));

    sorted.forEach(ev => {
        // Only show for current selected year
        if (ev.date.startsWith(state.year)) {
            const li = document.createElement('li');
            li.className = 'mb-1';
            const date = new Date(ev.date).toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: '2-digit' });
            li.innerHTML = `<strong>${date}</strong> ${ev.title}`;
            if (ev.isHoliday) {
                li.style.color = '#d63384';
                li.style.fontWeight = 'bold';
            }
            ul.appendChild(li);
        }
    });
}

/**
 * Render Holidays Footer
 */
function renderHolidaysFooter() {
    const ul = document.getElementById('holidaysFooter');
    if(!ul) return;
    ul.innerHTML = '';

    // Filter for current year
    const relevant = holidays.filter(h => h.date.startsWith(state.year));
    
    relevant.forEach(h => {
        const li = document.createElement('li');
        li.className = 'mb-1';
        const date = new Date(h.date).toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit' });
        li.innerHTML = `<strong>${date}</strong> <span style="color:#d63384;">${h.name}</span>`;
        ul.appendChild(li);
    });
}

function renderVacationsFooter() {
    const ul = document.getElementById('vacationsFooter');
    ul.innerHTML = '';

    if (!vacations || vacations.length === 0) {
        const li = document.createElement('li');
        li.className = 'text-muted';
        li.textContent = 'Keine Ferien geladen';
        ul.appendChild(li);
        return;
    }

    // Sort chronologically by start date
    // Filter out vacations that ended before this year or start after this year?
    // User wants "this year's vacations". 
    // If a vacation started in Prev Year but overlaps Jan 1st of Current Year, it is relevant.
    // If a vacation starts in Current Year, it is relevant.
    
    const relevant = vacations.filter(v => {
        // Overlap logic: Start <= EndYear AND End >= StartYear
        const vStart = v.start;
        const vEnd = v.end;
        const yearStart = `${state.year}-01-01`;
        const yearEnd = `${state.year}-12-31`;
        
        return vStart <= yearEnd && vEnd >= yearStart;
    });

    const sorted = relevant.sort((a, b) => a.start.localeCompare(b.start));

    sorted.forEach(vac => {
        const li = document.createElement('li');
        li.className = 'mb-1';
        
        // Format dates
        const startDate = new Date(vac.start + 'T00:00:00').toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year:'2-digit' });
        const endDate = new Date(vac.end + 'T00:00:00').toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year:'2-digit' });
        
        // Format vacation name (capitalize first letter)
        const vacName = vac.name.charAt(0).toUpperCase() + vac.name.slice(1).replace(/_/g, ' ');
        
        li.innerHTML = `<strong>${startDate}-${endDate}</strong><br><span style="color:#0dcaf0;">${vacName}</span>`;
        ul.appendChild(li);
    });
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
                const dateStr = formatDateISO(patternDate);
                const overrideKey = `${dateStr}_${group.id}`;

                if (overrides.has(overrideKey)) {
                    // Use the existing modified event
                    newEvents.push(overrides.get(overrideKey));
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

            // 1. Save original date if not yet saved (for Series preservation)
            if (!ev.originalDate) {
                ev.originalDate = ev.date;
            }

            // 2. Update date
            ev.date = newDate;
            ev.modified = true;

            // 3. Sync with Special Events Configuration if needed
            if (ev.type === 'special') {
                // Try to find by ID
                const specIndex = state.specialEvents.findIndex(s => s.id === ev.id);
                if (specIndex > -1) {
                    state.specialEvents[specIndex].date = newDate;
                } else {
                    // Fallback match by properties if ID missing (legacy)
                    const fallback = state.specialEvents.find(s => s.title === ev.title && s.date === ev.originalDate);
                    if (fallback) fallback.date = newDate;
                }
                // Update Special List List (UI)
                renderSpecialList();
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
function formatDateISO(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

function formatDateShort(iso) {
    if (!iso) return '';
    const [y, m, d] = iso.split('-');
    return `${d}.${m}.`;
}

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

function getDayShortName(num) {
    const days = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];
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
