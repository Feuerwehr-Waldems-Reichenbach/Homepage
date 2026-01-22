/**
 * Public Calendar Script
 * Renders the interactive calendar on the Mitmachen page.
 */
(function() {
    let state = {
        year: new Date().getFullYear(),
        groups: [],
        generatedEvents: [],
        series: [],
        specialEvents: []
    };
    let holidays = [];
    let vacations = [];

    // Initialize
    document.addEventListener('DOMContentLoaded', () => {
        loadPlan();
    });

    function loadPlan() {
        // jahresplan.json is in the same directory (Public/Mitmachen/)
        const jsonPath = 'jahresplan.json'; 
        fetch(jsonPath + '?t=' + Date.now())
            .then(r => {
                 if (!r.ok) throw new Error("Plan database not found.");
                 return r.json();
            })
            .then(data => {
                state = data;
                
                // Update Year Title
                const yearTitle = document.getElementById('publicYearTitle');
                if(yearTitle) yearTitle.textContent = state.year;
                
                // Load Context Data using Shared Renderer
                holidays = CalendarRenderer.calculateHolidays(state.year);
                CalendarRenderer.fetchVacations(state.year).then(vac => {
                    vacations = vac;
                    render();
                });
            })
            .catch(e => {
                console.error("Could not load Jahresplan:", e);
                // Optional: Show error in UI
                const container = document.getElementById('publicCalendarContainer');
                if(container) container.innerHTML = '<div class="alert alert-warning">Der Dienstplan konnte nicht geladen werden.</div>';
            });
    }

    function render() {
        if (!state || !state.year) return;

        CalendarRenderer.render({
            year: state.year,
            containerId: 'publicCalendarBody',
            headerRowId: 'publicMonthHeader',
            events: state.generatedEvents || [],
            groups: state.groups || [],
            holidays: holidays,
            vacations: vacations,
            options: {
                readOnly: true,
                containerId: 'publicCalendarContainer', // Redundant but harmless if kept in options for future use
                legendId: 'publicLegendContainer', // Renderer handles this separately? No, wait.
                specialFooterId: 'publicSpecialFooter',
                holidaysFooterId: 'publicHolidayFooter',
                vacationsFooterId: 'publicVacationFooter'
            }
        });

        // Trigger footer renders explicitly as CalendarRenderer.render() only handles the table
        CalendarRenderer.renderLegend('publicLegendContainer', state.groups || []);
        CalendarRenderer.renderSpecialEventsFooter('publicSpecialFooter', state.specialEvents || [], state.year);
        CalendarRenderer.renderHolidaysFooter('publicHolidayFooter', holidays, state.year);
        CalendarRenderer.renderVacationsFooter('publicVacationFooter', vacations, state.year);
    }
    
    // Export functions exposed globally for the buttons in index.php
    window.publicCalendar = {
        exportPng: function() {
             const element = document.getElementById('publicCalendarContainer');
             if (!element) return;
             
             // Use html2canvas
             html2canvas(element, {
                scale: 2, // Higher resolution
                useCORS: true, 
                logging: false,
                windowWidth: element.scrollWidth,
                windowHeight: element.scrollHeight,
                backgroundColor: '#ffffff'
            }).then(canvas => {
                const link = document.createElement('a');
                link.download = `Feuerwehr_Jahresplan_${state.year}.png`;
                link.href = canvas.toDataURL('image/png');
                link.click();
            }).catch(err => console.error(err));
        },
        
        exportPdf: function() {
            const element = document.getElementById('publicCalendarContainer');
             if (!element) return;
             
            html2canvas(element, {
                scale: 2,
                useCORS: true,
                logging: false,
                backgroundColor: '#ffffff'
            }).then(canvas => {
                const imgData = canvas.toDataURL('image/png');
                const { jsPDF } = window.jspdf;
                
                // A4 Landscape: 297 x 210 mm
                const pdf = new jsPDF('l', 'mm', 'a4');
                const pageWidth = 297;
                const pageHeight = 210;
                
                pdf.addImage(imgData, 'PNG', 0, 0, pageWidth, pageHeight);
                pdf.save(`Feuerwehr_Jahresplan_${state.year}.pdf`);
            }).catch(err => console.error(err));
        }
    };
})();
