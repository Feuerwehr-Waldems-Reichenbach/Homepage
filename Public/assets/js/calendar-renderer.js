/**
 * Shared Calendar Renderer
 * Handles rendering of the annual firefighter plan.
 */
const CalendarRenderer = {
    
    /**
     * Main Render Function (Table)
     */
    render: function (config) {
        const { year, containerId, headerRowId, events, holidays, vacations, groups, options } = config;
        const tbody = document.getElementById(containerId);
        const theadRow = document.getElementById(headerRowId);

        if (!tbody || !theadRow) return;

        // Clear existing
        theadRow.innerHTML = '<th style="width: 40px; background:#e9ecef;">Tag</th>';
        tbody.innerHTML = '';

        const months = ['Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'];
        const monthFull = ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];

        // Render Month Headers
        months.forEach((m, i) => {
            const th = document.createElement('th');
            th.textContent = m;
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
                const date = new Date(year, m, day);
                const dateString = this.formatDateISO(date);

                // Add date attribute
                td.dataset.date = dateString;

                // Check if valid date
                if (date.getMonth() !== m) {
                    td.style.backgroundColor = '#ddd';
                    td.classList.add('invalid-date');
                    tr.appendChild(td);
                    continue;
                }

                const dayOfWeek = date.getDay();
                const dayName = this.getDayShortName(dayOfWeek);

                // Weekend highlighting
                if (dayOfWeek === 0 || dayOfWeek === 6) {
                    td.style.backgroundColor = '#f8f9fa';
                    td.classList.add('weekend-row');
                }
                if (dayOfWeek === 0) {
                    td.style.backgroundColor = '#f1f3f5';
                }

                // Holiday / Vacation highlighting
                const holiday = holidays.find(h => h.date === dateString);
                const vac = vacations.find(v => dateString >= v.start && dateString <= v.end);

                if (vac) {
                    td.classList.add('vacation-bg');
                    td.title = vac.name;
                }
                if (holiday) {
                    td.classList.add('holiday-bg');
                    td.title = holiday.name;
                }
                if (vac && holiday) {
                    td.title = `${holiday.name} (${vac.name})`;
                }

                // Cell Content
                const cellContent = document.createElement('div');
                cellContent.className = 'cell-content';

                // Date Label
                const dateLabel = document.createElement('div');
                dateLabel.className = 'cell-date';
                
                const dayNum = String(day).padStart(2, '0');
                let infoIndicator = '';
                if (holiday) {
                    infoIndicator = `<span class="holiday-indicator" title="${holiday.name}">FT</span>`;
                }
                dateLabel.innerHTML = `${dayName} ${dayNum}. ${infoIndicator}`;
                cellContent.appendChild(dateLabel);

                // Events
                const eventContainer = document.createElement('div');
                eventContainer.className = 'event-container';

                const dayEvents = events.filter(e => e.date === dateString);

                if (dayEvents.length > 0) {
                    dayEvents.forEach(ev => {
                        const el = document.createElement('div');
                        el.className = 'event-marker';
                        
                        let color = '#333';
                        if (ev.groupId) {
                            const group = groups.find(g => g.id === ev.groupId);
                            if (group) color = group.color;
                        } else if (ev.customColor) {
                            color = ev.customColor;
                        }

                        el.style.backgroundColor = color;
                        el.title = ev.title;
                        el.textContent = ev.isHoliday ? '!' : '';
                        
                        if (!options.readOnly) {
                            el.setAttribute('draggable', 'true');
                            el.dataset.id = ev.id;
                            el.onclick = (e) => {
                                e.stopPropagation();
                                if (options.onEventClick) options.onEventClick(ev.id);
                            };
                        } else {
                            el.style.cursor = 'default';
                        }
                        
                        eventContainer.appendChild(el);
                    });
                }

                cellContent.appendChild(eventContainer);
                td.appendChild(cellContent);

                if (!options.readOnly && options.onDrop) {
                    td.ondragover = (e) => e.preventDefault();
                    td.ondrop = (e) => options.onDrop(e);
                }

                tr.appendChild(td);
            }
            tbody.appendChild(tr);
        }
    },

    /**
     * Render Legend
     */
    renderLegend: function(containerId, groups) {
        const container = document.getElementById(containerId);
        if (!container) return;
        container.innerHTML = '';
        
        const extras = [
            {name: 'Ferien', color: '#e0f7fa'},
            {name: 'Feiertag', color: '#ffebee'}
        ];
        
        groups.forEach(group => {
            const div = document.createElement('div');
            div.className = 'legend-item';
            div.innerHTML = `<span class="legend-color" style="background:${group.color}"></span> ${group.name}`;
            container.appendChild(div);
        });
        
        extras.forEach(ex => {
            const div = document.createElement('div');
            div.className = 'legend-item';
            div.innerHTML = `<span class="legend-color" style="background:${ex.color}; border:1px solid #ccc;"></span> ${ex.name}`;
            container.appendChild(div);
        });
    },

    /**
     * Render Special Events List
     */
    renderSpecialEventsFooter: function(containerId, specialEvents, year) {
        const ul = document.getElementById(containerId);
        if (!ul) return;
        ul.innerHTML = '';

        const sorted = [...specialEvents].sort((a, b) => new Date(a.date) - new Date(b.date));

        sorted.forEach(ev => {
            if (ev.date.toString().startsWith(year)) {
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
    },

    /**
     * Render Holidays List
     */
    renderHolidaysFooter: function(containerId, holidays, year) {
        const ul = document.getElementById(containerId);
        if(!ul) return;
        ul.innerHTML = '';

        const relevant = holidays.filter(h => h.date.startsWith(year));
        
        relevant.forEach(h => {
            const li = document.createElement('li');
            li.className = 'mb-1';
            const date = new Date(h.date).toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit' });
            li.innerHTML = `<strong>${date}</strong> <span style="color:#d63384;">${h.name}</span>`;
            ul.appendChild(li);
        });
    },

    /**
     * Render Vacations List
     */
    renderVacationsFooter: function(containerId, vacations, year) {
        const ul = document.getElementById(containerId);
        if (!ul) return;
        ul.innerHTML = '';

        if (!vacations || vacations.length === 0) {
            const li = document.createElement('li');
            li.className = 'text-muted';
            li.textContent = 'Keine Ferien geladen';
            ul.appendChild(li);
            return;
        }

        const relevant = vacations.filter(v => {
            const vStart = v.start;
            const vEnd = v.end;
            const yearStart = `${year}-01-01`;
            const yearEnd = `${year}-12-31`;
            return vStart <= yearEnd && vEnd >= yearStart;
        });

        const sorted = relevant.sort((a, b) => a.start.localeCompare(b.start));

        sorted.forEach(vac => {
            const li = document.createElement('li');
            li.className = 'mb-1';
            const startDate = new Date(vac.start + 'T00:00:00').toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year:'2-digit' });
            const endDate = new Date(vac.end + 'T00:00:00').toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year:'2-digit' });
            const vacName = vac.name.charAt(0).toUpperCase() + vac.name.slice(1).replace(/_/g, ' ');
            
            li.innerHTML = `<strong>${startDate}-${endDate}</strong><br><span style="color:#0dcaf0;">${vacName}</span>`;
            ul.appendChild(li);
        });
    },

    formatDateISO: function(date) {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    },

    getDayShortName: function(dayIndex) {
        const days = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];
        return days[dayIndex];
    },

    formatDateShort: function(isoString) {
        if(!isoString) return '';
        const parts = isoString.split('-');
        return `${parts[2]}.${parts[1]}.`;
    },

    /**
     * Calculate Holidays (Gaussian Easter)
     */
    calculateHolidays: function(year) {
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
    
            { date: this.formatDateISO(addDays(easterDate, -2)), name: 'Karfreitag' },
            { date: this.formatDateISO(addDays(easterDate, 1)), name: 'Ostermontag' },
            { date: this.formatDateISO(addDays(easterDate, 39)), name: 'Christi Himmelfahrt' },
            { date: this.formatDateISO(addDays(easterDate, 50)), name: 'Pfingstmontag' },
            { date: this.formatDateISO(addDays(easterDate, 60)), name: 'Fronleichnam' }
        ];
    
        return list;
    },

    /**
     * Fetch Vacations (Hessen)
     * Returns a Promise that resolves to an array of vacation objects.
     */
    fetchVacations: function(year) {
        // Fetch vacations for current year AND previous year (to catch winter vacations starting in Dec)
        const years = [year - 1, year];
        
        return Promise.all(years.map(y => fetch(`https://schulferien-api.de/api/v1/${y}/HE`).then(r => r.ok ? r.json() : [])))
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
                return allVacations;
            })
            .catch(error => {
                console.error('Error fetching vacations:', error);
                return [];
            });
    }
};
