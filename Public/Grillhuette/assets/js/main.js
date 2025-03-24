document.addEventListener('DOMContentLoaded', function() {
    // Initialize Flatpickr date pickers
    initializeDatePickers();
    
    // Initialize calendar if it exists on page
    initializeCalendar();
    
    // Add event listeners for reservation selection
    setupReservationSelection();
    
    // Initialize Bootstrap tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Initialize date and time pickers using Flatpickr
function initializeDatePickers() {
    // Date picker config for single date
    const datePickers = document.querySelectorAll('.date-picker');
    if (datePickers.length > 0) {
        datePickers.forEach(function(picker) {
            flatpickr(picker, {
                locale: "de",
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "j. F Y",
                minDate: "today",
                disableMobile: "true"
            });
        });
    }
    
    // Date range picker config
    const dateRangePickers = document.querySelectorAll('.date-range-picker');
    if (dateRangePickers.length > 0) {
        dateRangePickers.forEach(function(picker) {
            flatpickr(picker, {
                locale: "de",
                mode: "range",
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "j. F Y",
                minDate: "today",
                disableMobile: "true"
            });
        });
    }
    
    // Time picker config
    const timePickers = document.querySelectorAll('.time-picker');
    if (timePickers.length > 0) {
        timePickers.forEach(function(picker) {
            flatpickr(picker, {
                locale: "de",
                enableTime: true,
                noCalendar: true,
                dateFormat: "H:i",
                time_24hr: true,
                minuteIncrement: 30,
                disableMobile: "true"
            });
        });
    }
}

// Initialize and render the calendar
function initializeCalendar() {
    const calendarContainer = document.getElementById('calendar');
    if (!calendarContainer) return;
    
    // Get current month/year or use the one in the URL
    let today = new Date();
    let currentMonth = today.getMonth();
    let currentYear = today.getFullYear();
    
    // Check if month and year are specified in URL
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('month') && urlParams.has('year')) {
        currentMonth = parseInt(urlParams.get('month')) - 1; // JS months are 0-11
        currentYear = parseInt(urlParams.get('year'));
    }
    
    // Render the calendar
    renderCalendar(currentMonth, currentYear);
    
    // Add event listeners for next/prev month buttons
    const prevMonthBtn = document.getElementById('prevMonth');
    const nextMonthBtn = document.getElementById('nextMonth');
    
    if (prevMonthBtn) {
        prevMonthBtn.addEventListener('click', function() {
            currentMonth--;
            if (currentMonth < 0) {
                currentMonth = 11;
                currentYear--;
            }
            renderCalendar(currentMonth, currentYear);
        });
    }
    
    if (nextMonthBtn) {
        nextMonthBtn.addEventListener('click', function() {
            currentMonth++;
            if (currentMonth > 11) {
                currentMonth = 0;
                currentYear++;
            }
            renderCalendar(currentMonth, currentYear);
        });
    }
}

// Render the calendar for a specific month/year
function renderCalendar(month, year) {
    const calendarContainer = document.getElementById('calendar');
    if (!calendarContainer) return;
    
    // Update month/year display
    const monthYearDisplay = document.getElementById('monthYear');
    if (monthYearDisplay) {
        const monthNames = ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];
        monthYearDisplay.textContent = monthNames[month] + ' ' + year;
    }
    
    // Update hidden inputs for form submission
    const monthInput = document.getElementById('month');
    const yearInput = document.getElementById('year');
    if (monthInput) monthInput.value = month + 1;
    if (yearInput) yearInput.value = year;
    
    // Get first day of the month and number of days
    const firstDay = new Date(year, month, 1).getDay();
    // In JavaScript, Sunday is 0, but we want Monday as 0
    const firstDayAdjusted = (firstDay === 0) ? 6 : firstDay - 1;
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    
    // Create table rows for the days
    let date = 1;
    let table = '<table class="calendar"><thead><tr>';
    
    // Add weekday headers
    const weekdays = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];
    for (let i = 0; i < 7; i++) {
        table += `<th>${weekdays[i]}</th>`;
    }
    
    table += '</tr></thead><tbody>';
    
    // Create the calendar cells
    for (let i = 0; i < 6; i++) {
        // Create a table row for each week
        table += '<tr>';
        
        for (let j = 0; j < 7; j++) {
            if (i === 0 && j < firstDayAdjusted) {
                // Empty cells before the first day of the month
                table += '<td class="day other-month"></td>';
            } else if (date > daysInMonth) {
                // Empty cells after the last day of the month
                table += '<td class="day other-month"></td>';
            } else {
                // Create the day cell
                const today = new Date();
                const isToday = date === today.getDate() && month === today.getMonth() && year === today.getFullYear();
                
                // Format date string for dataset
                const formattedDate = `${year}-${(month + 1).toString().padStart(2, '0')}-${date.toString().padStart(2, '0')}`;
                
                // Create the day cell without any status class initially
                table += `<td class="day ${isToday ? 'today' : ''}" data-date="${formattedDate}">
                    <span class="date-number">${date}</span>
                </td>`;
                
                date++;
            }
        }
        
        table += '</tr>';
        
        // Stop if we've reached the end of the month
        if (date > daysInMonth) {
            break;
        }
    }
    
    table += '</tbody></table>';
    
    // Set the calendar HTML
    calendarContainer.innerHTML = table;
    
    // Load day statuses via AJAX
    loadDayStatuses(month + 1, year);
    
    // Add click event to days for selection
    const dayElements = calendarContainer.querySelectorAll('.day:not(.other-month)');
    dayElements.forEach(day => {
        day.addEventListener('click', function() {
            selectDay(this);
        });
    });
}

// Load day statuses (free, pending, booked) via AJAX
function loadDayStatuses(month, year) {
    // Format month with leading zero if needed
    const formattedMonth = month.toString().padStart(2, '0');
    
    console.log(`Loading calendar data for ${year}-${formattedMonth}`);
    
    // Create AJAX request
    const xhr = new XMLHttpRequest();
    // Get base URL from current path
    const basePath = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/') + 1);
    xhr.open('GET', `${basePath}get_calendar_data.php?month=${formattedMonth}&year=${year}`, true);
    
    xhr.onload = function() {
        if (this.status === 200) {
            try {
                const response = JSON.parse(this.responseText);
                console.log('Calendar data received:', response);
                if (response.success) {
                    updateDayStatuses(response.data);
                } else {
                    console.error('Error loading calendar data:', response.message);
                }
            } catch (e) {
                console.error('Error parsing calendar data:', e, this.responseText);
            }
        }
    };
    
    xhr.onerror = function() {
        console.error('AJAX request failed');
    };
    
    xhr.send();
}

// Update day status classes in the calendar
function updateDayStatuses(statusData) {
    if (!statusData) return;
    
    // First set all days to 'free' by default
    document.querySelectorAll('.day[data-date]').forEach(dayElement => {
        dayElement.classList.remove('pending', 'booked');
        dayElement.classList.add('free');
    });
    
    // Then update with statuses from the server
    Object.keys(statusData).forEach(date => {
        const status = statusData[date];
        const dayElement = document.querySelector(`.day[data-date="${date}"]`);
        
        if (dayElement) {
            // Remove all status classes
            dayElement.classList.remove('free', 'pending', 'booked');
            // Add the new status class
            dayElement.classList.add(status);
        }
    });
}

// Handle day selection in the calendar
function selectDay(dayElement) {
    // Get date from dataset
    const date = dayElement.dataset.date;
    
    // Get the selected dates input element
    const selectedDatesInput = document.getElementById('selected_dates');
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    
    // If this is a reservation form with start/end dates
    if (startDateInput && endDateInput) {
        // Check if we're selecting the start or end date
        if (!startDateInput.value || (startDateInput.value && endDateInput.value)) {
            // Start a new selection
            startDateInput.value = date;
            endDateInput.value = '';
            
            // Clear previous selections
            document.querySelectorAll('.day.selected').forEach(el => {
                el.classList.remove('selected');
            });
            
            dayElement.classList.add('selected');
        } else if (startDateInput.value && !endDateInput.value) {
            // Complete the selection
            const startDate = new Date(startDateInput.value);
            const endDate = new Date(date);
            
            // Ensure end date is after start date
            if (endDate < startDate) {
                // If end date is before start date, swap them
                endDateInput.value = startDateInput.value;
                startDateInput.value = date;
            } else {
                endDateInput.value = date;
            }
            
            // Highlight all days in the range
            highlightDateRange(startDateInput.value, endDateInput.value);
        }
    } else if (selectedDatesInput) {
        // Multiple date selection for admin blocking
        let selectedDates = [];
        if (selectedDatesInput.value) {
            selectedDates = selectedDatesInput.value.split(',');
        }
        
        // Toggle selection
        const index = selectedDates.indexOf(date);
        if (index === -1) {
            // Add date
            selectedDates.push(date);
            dayElement.classList.add('selected');
        } else {
            // Remove date
            selectedDates.splice(index, 1);
            dayElement.classList.remove('selected');
        }
        
        // Update input
        selectedDatesInput.value = selectedDates.join(',');
    }
}

// Highlight all days in a date range
function highlightDateRange(startDate, endDate) {
    // Clear previous selections
    document.querySelectorAll('.day.selected').forEach(el => {
        el.classList.remove('selected');
    });
    
    // Convert to Date objects
    const start = new Date(startDate);
    const end = new Date(endDate);
    
    // Loop through all days in the range
    const current = new Date(start);
    while (current <= end) {
        const formattedDate = formatDate(current);
        const dayElement = document.querySelector(`.day[data-date="${formattedDate}"]`);
        
        if (dayElement) {
            dayElement.classList.add('selected');
        }
        
        // Move to the next day
        current.setDate(current.getDate() + 1);
    }
}

// Format date as YYYY-MM-DD
function formatDate(date) {
    const year = date.getFullYear();
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const day = date.getDate().toString().padStart(2, '0');
    return `${year}-${month}-${day}`;
}

// Setup reservation form selection and validation
function setupReservationSelection() {
    const reservationForm = document.getElementById('reservationForm');
    if (!reservationForm) return;
    
    // Form submission validation
    reservationForm.addEventListener('submit', function(event) {
        const startDate = document.getElementById('start_date');
        const endDate = document.getElementById('end_date');
        
        if (!startDate.value) {
            event.preventDefault();
            alert('Bitte wählen Sie ein Startdatum aus.');
            return false;
        }
        
        if (!endDate.value) {
            event.preventDefault();
            alert('Bitte wählen Sie ein Enddatum aus.');
            return false;
        }
        
        return true;
    });
} 