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
    
    // Mobile-specific enhancements
    setupMobileEnhancements();
});

// Mobile-specific enhancements
function setupMobileEnhancements() {
    // Detect if device is mobile
    const isMobile = window.matchMedia("(max-width: 768px)").matches;
    
    if (isMobile) {
        // Improve touch interactions for buttons
        document.querySelectorAll('.btn').forEach(button => {
            button.addEventListener('touchstart', function() {
                this.classList.add('active');
            });
            
            button.addEventListener('touchend', function() {
                this.classList.remove('active');
            });
        });
        
        // Fix for 300ms delay on mobile devices
        document.querySelectorAll('a, button, .nav-link, .day').forEach(element => {
            element.addEventListener('touchstart', function() {}, {passive: true});
        });
        
        // Adjust datepicker for better mobile experience
        configureMobileDatepickers();
        
        // Handle navbar collapse after click on mobile
        setupMobileNavbar();
    }
    
    // Handle window resize events
    window.addEventListener('resize', handleResize);
    
    // Initial call to set proper sizes
    handleResize();
}

// Configure datepickers for mobile devices
function configureMobileDatepickers() {
    // Mobile specific flatpickr configuration
    if (document.querySelectorAll('.date-picker').length > 0) {
        document.querySelectorAll('.date-picker').forEach(function(picker) {
            if (picker._flatpickr) {
                picker._flatpickr.set('disableMobile', false); // Enable native datepicker on mobile for better UX
            }
        });
    }
}

// Handle window resize events
function handleResize() {
    // Adjust calendar height based on screen width
    const calendar = document.querySelector('.calendar');
    if (calendar) {
        const isMobile = window.matchMedia("(max-width: 768px)").matches;
        const isVerySmall = window.matchMedia("(max-width: 480px)").matches;
        
        document.querySelectorAll('.calendar .day').forEach(day => {
            if (isVerySmall) {
                day.style.height = '40px';
            } else if (isMobile) {
                day.style.height = '50px';
            } else {
                day.style.height = '60px';
            }
        });
    }
    
    // Adjust modal height on smaller screens
    const modals = document.querySelectorAll('.modal-dialog');
    if (modals.length > 0) {
        const viewportHeight = window.innerHeight;
        modals.forEach(modal => {
            if (window.matchMedia("(max-width: 768px)").matches) {
                modal.style.maxHeight = (viewportHeight * 0.9) + 'px';
                modal.style.overflowY = 'auto';
            } else {
                modal.style.maxHeight = '';
                modal.style.overflowY = '';
            }
        });
    }
}

// Setup navbar to auto-collapse on mobile after clicking a link
function setupMobileNavbar() {
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
    const navbarToggler = document.querySelector('.navbar-toggler');
    const navbarCollapse = document.querySelector('.navbar-collapse');
    
    if (navLinks && navbarToggler && navbarCollapse) {
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (navbarCollapse.classList.contains('show')) {
                    navbarToggler.click();
                }
            });
        });
    }
}

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
                disableMobile: "true",
                // Better positioning for mobile devices
                position: window.matchMedia("(max-width: 768px)").matches ? "auto" : "below",
                // Mobile-friendly settings
                appendTo: window.matchMedia("(max-width: 768px)").matches ? document.body : undefined
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
                disableMobile: "true",
                // Better positioning for mobile devices
                position: window.matchMedia("(max-width: 768px)").matches ? "auto" : "below",
                // Mobile-friendly settings
                appendTo: window.matchMedia("(max-width: 768px)").matches ? document.body : undefined
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
    
    // Add touch swipe for mobile calendar navigation
    setupCalendarSwipe(calendarContainer, prevMonthBtn, nextMonthBtn);
}

// Add swipe gesture support for calendar navigation on mobile
function setupCalendarSwipe(element, prevBtn, nextBtn) {
    let touchStartX = 0;
    let touchEndX = 0;
    
    // Only setup swipe if on mobile
    if (!window.matchMedia("(max-width: 768px)").matches) return;
    
    element.addEventListener('touchstart', function(e) {
        touchStartX = e.changedTouches[0].screenX;
    }, { passive: true });
    
    element.addEventListener('touchend', function(e) {
        touchEndX = e.changedTouches[0].screenX;
        handleCalendarSwipe();
    }, { passive: true });
    
    function handleCalendarSwipe() {
        // Define minimum swipe distance for a valid swipe (in pixels)
        const minSwipeDistance = 50;
        
        if (touchStartX - touchEndX > minSwipeDistance) {
            // Swipe left - go to next month
            if (nextBtn) nextBtn.click();
        } else if (touchEndX - touchStartX > minSwipeDistance) {
            // Swipe right - go to previous month
            if (prevBtn) prevBtn.click();
        }
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
    
    // Add click event to days for selection - only for future dates
    const dayElements = calendarContainer.querySelectorAll('.day:not(.other-month)');
    dayElements.forEach(day => {
        // Use touchend for mobile devices to prevent delay
        if (window.matchMedia("(max-width: 768px)").matches) {
            day.addEventListener('touchend', function(e) {
                e.preventDefault();
                // Check if the day is selectable
                if (!isSelectable(this)) {
                    return;
                }
                selectDay(this);
            }, { passive: false });
        } else {
            day.addEventListener('click', function() {
                // Check if the day is selectable
                if (!isSelectable(this)) {
                    return;
                }
                selectDay(this);
            });
        }
    });
    
    // Apply mobile-specific adjustments
    if (window.matchMedia("(max-width: 768px)").matches) {
        handleResize();
    }
}

// Load day statuses (free, pending, booked) via AJAX
function loadDayStatuses(month, year) {
    // Format month with leading zero if needed
    const formattedMonth = month.toString().padStart(2, '0');
    
    console.log(`Loading calendar data for ${year}-${formattedMonth}`);
    
    // Create AJAX request
    const xhr = new XMLHttpRequest();
    // Statt die Basis-URL selbst zu berechnen, verwenden wir den Pfad zur Helper-Datei
    xhr.open('GET', `Helper/get_calendar_data.php?month=${formattedMonth}&year=${year}`, true);
    
    xhr.onload = function() {
        if (this.status === 200) {
            try {
                const response = JSON.parse(this.responseText);
                console.log('Calendar data received:', response);
                if (response.success) {
                    updateDayStatuses(response.data);
                } else {
                    console.error('Error loading calendar data:', response.message);
                    // Display error for mobile users with toast or alert
                    if (window.matchMedia("(max-width: 768px)").matches) {
                        showMobileAlert('Fehler beim Laden der Kalenderdaten. Bitte versuchen Sie es später erneut.');
                    }
                }
            } catch (e) {
                console.error('Error parsing calendar data:', e, this.responseText);
                // Display parse error for mobile users
                if (window.matchMedia("(max-width: 768px)").matches) {
                    showMobileAlert('Fehler beim Laden der Kalenderdaten. Bitte versuchen Sie es später erneut.');
                }
            }
        }
    };
    
    xhr.onerror = function() {
        console.error('AJAX request failed');
        // Show network error to mobile users
        if (window.matchMedia("(max-width: 768px)").matches) {
            showMobileAlert('Netzwerkfehler. Bitte überprüfen Sie Ihre Internetverbindung.');
        }
    };
    
    xhr.send();
}

// Show a mobile-friendly alert/toast message
function showMobileAlert(message) {
    // Create a toast element if it doesn't exist
    let toast = document.getElementById('mobileToast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'mobileToast';
        toast.className = 'mobile-toast';
        toast.style.cssText = `
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #343a40;
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            z-index: 9999;
            font-size: 14px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            max-width: 90%;
            text-align: center;
        `;
        document.body.appendChild(toast);
    }
    
    // Set message and show toast
    toast.textContent = message;
    toast.style.display = 'block';
    
    // Hide toast after 3 seconds
    setTimeout(() => {
        toast.style.display = 'none';
    }, 3000);
}

// Update day status classes in the calendar
function updateDayStatuses(statusData) {
    if (!statusData) return;
    
    // Get today's date for comparison
    const today = new Date();
    today.setHours(0, 0, 0, 0); // Reset time part for comparison
    
    // First set all days to 'free' by default and mark past days
    document.querySelectorAll('.day[data-date]').forEach(dayElement => {
        const dateStr = dayElement.dataset.date;
        const date = new Date(dateStr);
        
        // Remove all status classes except other-month
        dayElement.classList.remove('free', 'pending', 'booked', 'past');
        
        // Add free by default
        dayElement.classList.add('free');
        
        // If it's a past date, mark it as such
        if (date < today) {
            dayElement.classList.add('past');
            dayElement.classList.remove('free'); // A past day is not free for booking
        }
    });
    
    // Then update with statuses from the server
    Object.keys(statusData).forEach(date => {
        const status = statusData[date];
        const dayElement = document.querySelector(`.day[data-date="${date}"]`);
        
        if (dayElement) {
            // For non-past days, update status
            if (!dayElement.classList.contains('past')) {
                // Remove status classes but keep past if it's set
                dayElement.classList.remove('free', 'pending', 'booked');
                // Add the new status class
                dayElement.classList.add(status);
            }
        }
    });
}

// Check if a day is selectable (must be free or today or future)
function isSelectable(dayElement) {
    // Get date from dataset
    const dateStr = dayElement.dataset.date;
    const date = new Date(dateStr);
    const today = new Date();
    today.setHours(0, 0, 0, 0); // Reset time part for proper comparison
    
    // Check if it's a past date (before today)
    if (date < today) {
        console.log('Date is in the past and not selectable:', dateStr);
        
        // Show feedback on mobile
        if (window.matchMedia("(max-width: 768px)").matches) {
            showMobileAlert('Vergangene Daten sind nicht auswählbar.');
        }
        
        return false;
    }
    
    // Check if the day has booked or pending status
    if (dayElement.classList.contains('booked') || dayElement.classList.contains('pending')) {
        // For dates with pending or booked status, we might need an extra check
        // to see if the time slot is partially available
        // This would require additional logic with backend communication
        console.log('Date is not free:', dateStr);
        
        // Show feedback on mobile
        if (window.matchMedia("(max-width: 768px)").matches) {
            showMobileAlert('Dieses Datum ist bereits reserviert oder angefragt.');
        }
        
        return false;
    }
    
    // The day is in the future or today and is free
    return true;
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
            
            // Mobile feedback
            if (window.matchMedia("(max-width: 768px)").matches) {
                showMobileAlert('Startdatum ausgewählt. Bitte wählen Sie jetzt das Enddatum.');
            }
            
            // Trigger cost update
            updateReservationCosts();
        } else if (startDateInput.value && !endDateInput.value) {
            // Complete the selection
            const startDate = new Date(startDateInput.value);
            const endDate = new Date(date);
            
            // Ensure end date is after start date
            if (endDate < startDate) {
                // If end date is before start date, swap them
                endDateInput.value = startDateInput.value;
                startDateInput.value = date;
                
                // Mobile feedback
                if (window.matchMedia("(max-width: 768px)").matches) {
                    showMobileAlert('Daten wurden getauscht, da das Enddatum vor dem Startdatum lag.');
                }
            } else {
                endDateInput.value = date;
            }
            
            // Highlight all days in the range
            highlightDateRange(startDateInput.value, endDateInput.value);
            
            // Trigger cost update after selection is complete
            updateReservationCosts();
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
    let allDaysInRangeAreSelectable = true;
    
    while (current <= end) {
        const formattedDate = formatDate(current);
        const dayElement = document.querySelector(`.day[data-date="${formattedDate}"]`);
        
        if (dayElement) {
            // Check if this day is selectable
            if (!isSelectable(dayElement) && formattedDate !== startDate && formattedDate !== endDate) {
                // If we encounter a non-selectable day in the range, we have a problem
                allDaysInRangeAreSelectable = false;
                console.warn(`Date range contains unavailable day: ${formattedDate}`);
                
                // We'll still highlight what we can up to this point
                dayElement.classList.add('selected');
                break;
            }
            
            dayElement.classList.add('selected');
        }
        
        // Move to the next day
        current.setDate(current.getDate() + 1);
    }
    
    // If the range contains unavailable days, show a warning to the user
    if (!allDaysInRangeAreSelectable) {
        const message = 'Achtung: Der ausgewählte Zeitraum enthält Tage, die nicht verfügbar sind. Bitte wählen Sie einen anderen Zeitraum aus.';
        
        if (window.matchMedia("(max-width: 768px)").matches) {
            showMobileAlert(message);
        } else {
            alert(message);
        }
        
        // Reset the selection
        document.querySelectorAll('.day.selected').forEach(el => {
            el.classList.remove('selected');
        });
        
        // Select only the start date
        const startDayElement = document.querySelector(`.day[data-date="${startDate}"]`);
        if (startDayElement) {
            startDayElement.classList.add('selected');
        }
        
        // Clear the end date
        const endDateInput = document.getElementById('end_date');
        if (endDateInput) {
            endDateInput.value = '';
        }
    }
    
    // Update the cost calculation after the range is highlighted
    updateReservationCosts();
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
            const message = 'Bitte wählen Sie ein Startdatum aus.';
            
            if (window.matchMedia("(max-width: 768px)").matches) {
                showMobileAlert(message);
            } else {
                alert(message);
            }
            
            return false;
        }
        
        if (!endDate.value) {
            event.preventDefault();
            const message = 'Bitte wählen Sie ein Enddatum aus.';
            
            if (window.matchMedia("(max-width: 768px)").matches) {
                showMobileAlert(message);
            } else {
                alert(message);
            }
            
            return false;
        }
        
        // Check if booking is at least 1 day
        if (!isMinimumBookingDuration(startDate.value, endDate.value)) {
            event.preventDefault();
            const message = 'Der Mindestbuchungszeitraum beträgt 1 Tag.';
            
            if (window.matchMedia("(max-width: 768px)").matches) {
                showMobileAlert(message);
            } else {
                alert(message);
            }
            
            return false;
        }
        
        // Validate that all days in the range are available
        const start = new Date(startDate.value);
        const end = new Date(endDate.value);
        const current = new Date(start);
        
        while (current <= end) {
            const formattedDate = formatDate(current);
            const dayElement = document.querySelector(`.day[data-date="${formattedDate}"]`);
            
            if (dayElement && !isSelectable(dayElement)) {
                // Found a non-selectable day in the range
                event.preventDefault();
                
                const message = 'Der ausgewählte Zeitraum enthält Tage, die nicht verfügbar sind. Bitte wählen Sie einen anderen Zeitraum aus.';
                
                if (window.matchMedia("(max-width: 768px)").matches) {
                    showMobileAlert(message);
                } else {
                    alert(message);
                }
                
                return false;
            }
            
            // Move to the next day
            current.setDate(current.getDate() + 1);
        }
        
        return true;
    });
    
    // Setup cost calculator
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    const startTimeInput = document.getElementById('start_time');
    const endTimeInput = document.getElementById('end_time');
    
    if (startDateInput && endDateInput) {
        // Einfache Event-Listener für Wertänderungen hinzufügen, ohne den Setter zu überschreiben
        startDateInput.addEventListener('input', updateReservationCosts);
        endDateInput.addEventListener('input', updateReservationCosts);
        
        // Update costs when dates change (durch MutationObserver)
        const observer = new MutationObserver(function(mutations) {
            updateReservationCosts();
        });
        
        observer.observe(startDateInput, { attributes: true });
        observer.observe(endDateInput, { attributes: true });
        
        // Auch bei direkter Änderung aktualisieren
        startDateInput.addEventListener('change', updateReservationCosts);
        endDateInput.addEventListener('change', updateReservationCosts);
        
        // Auch bei Uhrzeitänderungen aktualisieren
        if (startTimeInput) {
            startTimeInput.addEventListener('change', updateReservationCosts);
            startTimeInput.addEventListener('input', updateReservationCosts);
        }
        
        if (endTimeInput) {
            endTimeInput.addEventListener('change', updateReservationCosts);
            endTimeInput.addEventListener('input', updateReservationCosts);
        }
        
        // Initiale Berechnung
        updateReservationCosts();
    }
}

// Calculate and update reservation costs
function updateReservationCosts() {
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    const startTimeInput = document.getElementById('start_time');
    const endTimeInput = document.getElementById('end_time');
    const dayCountElement = document.getElementById('day-count');
    const totalCostElement = document.getElementById('total-cost');
    const baseCostElement = document.getElementById('base-cost');
    
    if (!startDateInput || !endDateInput || !dayCountElement || !totalCostElement) return;
    
    // Only calculate if both dates are selected
    if (startDateInput.value && endDateInput.value) {
        // Fetch current pricing information via AJAX
        fetch('Helper/get_pricing_info.php')
            .then(response => response.json())
            .then(priceInfo => {
                // Erstelle vollständige Datums-Zeit-Objekte
                let startDateTime = new Date(startDateInput.value);
                let endDateTime = new Date(endDateInput.value);
                
                // Füge die Uhrzeiten hinzu, falls verfügbar
                if (startTimeInput && startTimeInput.value) {
                    const [startHours, startMinutes] = startTimeInput.value.split(':').map(Number);
                    startDateTime.setHours(startHours, startMinutes, 0);
                }
                
                if (endTimeInput && endTimeInput.value) {
                    const [endHours, endMinutes] = endTimeInput.value.split(':').map(Number);
                    endDateTime.setHours(endHours, endMinutes, 0);
                }
                
                // Berechne die Differenz in Millisekunden
                const diffTime = Math.abs(endDateTime - startDateTime);
                
                // Berechne die Anzahl der Tage als Dezimalzahl (z.B. 1,5 Tage)
                const diffDays = diffTime / (24 * 60 * 60 * 1000);
                
                // Runde auf ganze Tage auf (mindestens 1 Tag)
                const days = Math.max(1, Math.ceil(diffDays));
                
                // Use proper check to preserve 0 values
                const rate = (priceInfo.user_rate !== undefined && priceInfo.user_rate !== null) ? priceInfo.user_rate : 100;
                const totalCost = days * rate;
                
                // Format for display with German notation (comma for decimal)
                const formattedRate = rate.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                const formattedTotal = totalCost.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                
                // Update the UI
                dayCountElement.textContent = days;
                baseCostElement.textContent = formattedRate + '€';
                totalCostElement.textContent = formattedTotal + '€';
                
                // If we have special pricing, add a note
                const costOverview = document.getElementById('cost-overview');
                if (costOverview) {
                    // Remove any existing special pricing notes
                    const existingNote = document.querySelector('.special-price-note');
                    if (existingNote) {
                        existingNote.remove();
                    }
                    
                    // Add a special note if using a special rate
                    if (priceInfo.rate_type !== 'normal') {
                        const noteText = priceInfo.rate_type === 'feuerwehr' ? 
                            'Spezialpreis für Feuerwehr' : 'Spezialpreis für aktives Mitglied';
                        
                        const specialNote = document.createElement('li');
                        specialNote.className = 'special-price-note text-success';
                        const priceDisplay = priceInfo.rate_type === 'feuerwehr' ? '0,00€' : `${rate.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}€`;
                        specialNote.innerHTML = `<i class="bi bi-check-circle"></i> ${noteText} (${priceDisplay})`;
                        costOverview.appendChild(specialNote);
                    }
                }
            })
            .catch(error => {
                console.error('Error fetching pricing information:', error);
                // Fallback to default calculation
                calculateDefaultCosts(startDateTime, endDateTime, dayCountElement, totalCostElement, baseCostElement);
            });
    } else {
        // Default values if dates not selected
        dayCountElement.textContent = '1';
        
        // Try to get default prices
        fetch('Helper/get_pricing_info.php')
            .then(response => response.json())
            .then(priceInfo => {
                // Use proper check to preserve 0 values
                const rate = (priceInfo.user_rate !== undefined && priceInfo.user_rate !== null) ? priceInfo.user_rate : 100;
                const formattedRate = rate.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                baseCostElement.textContent = formattedRate + '€';
                totalCostElement.textContent = formattedRate + '€';
                
                // If we have special pricing, add a note
                const costOverview = document.getElementById('cost-overview');
                if (costOverview) {
                    // Remove any existing special pricing notes
                    const existingNote = document.querySelector('.special-price-note');
                    if (existingNote) {
                        existingNote.remove();
                    }
                    
                    // Add a special note if using a special rate
                    if (priceInfo.rate_type !== 'normal') {
                        const noteText = priceInfo.rate_type === 'feuerwehr' ? 
                            'Spezialpreis für Feuerwehr' : 'Spezialpreis für aktives Mitglied';
                        
                        const specialNote = document.createElement('li');
                        specialNote.className = 'special-price-note text-success';
                        const priceDisplay = priceInfo.rate_type === 'feuerwehr' ? '0,00€' : `${rate.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}€`;
                        specialNote.innerHTML = `<i class="bi bi-check-circle"></i> ${noteText} (${priceDisplay})`;
                        costOverview.appendChild(specialNote);
                    }
                }
            })
            .catch(() => {
                // Try to get the user rate from the data attribute
                const costOverview = document.getElementById('cost-overview');
                let defaultRate = 100; // Default fallback
                
                if (costOverview && costOverview.hasAttribute('data-user-rate')) {
                    const userRateVal = parseFloat(costOverview.getAttribute('data-user-rate'));
                    defaultRate = (!isNaN(userRateVal) && userRateVal !== null) ? userRateVal : 100;
                }
                
                // Format for display with German notation
                const formattedRate = defaultRate.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                
                // Hard fallback
                baseCostElement.textContent = formattedRate + '€';
                totalCostElement.textContent = formattedRate + '€';
            });
    }
}

// Fallback function for calculating costs with default values
function calculateDefaultCosts(startDateTime, endDateTime, dayCountElement, totalCostElement, baseCostElement) {
    // Calculate days
    const diffTime = Math.abs(endDateTime - startDateTime);
    const diffDays = diffTime / (24 * 60 * 60 * 1000);
    const days = Math.max(1, Math.ceil(diffDays));
    
    // Try to get the user rate from the data attribute
    const costOverview = document.getElementById('cost-overview');
    let dailyRate = 100; // Default fallback
    
    if (costOverview && costOverview.hasAttribute('data-user-rate')) {
        const userRateVal = parseFloat(costOverview.getAttribute('data-user-rate'));
        dailyRate = (!isNaN(userRateVal) && userRateVal !== null) ? userRateVal : 100;
    }
    
    const totalCost = days * dailyRate;
    
    // Format for display with German notation
    const formattedRate = dailyRate.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const formattedTotal = totalCost.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    
    // Update UI
    dayCountElement.textContent = days;
    baseCostElement.textContent = formattedRate + '€';
    totalCostElement.textContent = formattedTotal + '€';
}

// Check if booking meets minimum duration requirement (1 day)
function isMinimumBookingDuration(startDateStr, endDateStr) {
    if (!startDateStr || !endDateStr) return false;
    
    const startTimeInput = document.getElementById('start_time');
    const endTimeInput = document.getElementById('end_time');
    
    // Erstelle vollständige Datums-Zeit-Objekte
    let startDateTime = new Date(startDateStr);
    let endDateTime = new Date(endDateStr);
    
    // Füge die Uhrzeiten hinzu, falls verfügbar
    if (startTimeInput && startTimeInput.value) {
        const [startHours, startMinutes] = startTimeInput.value.split(':').map(Number);
        startDateTime.setHours(startHours, startMinutes, 0);
    }
    
    if (endTimeInput && endTimeInput.value) {
        const [endHours, endMinutes] = endTimeInput.value.split(':').map(Number);
        endDateTime.setHours(endHours, endMinutes, 0);
    }
    
    // Berechne die Differenz in Millisekunden
    const diffTime = Math.abs(endDateTime - startDateTime);
    
    // Berechne die Anzahl der Tage als Dezimalzahl
    const diffDays = diffTime / (24 * 60 * 60 * 1000);
    
    // Prüfe, ob mindestens 1 Tag gebucht wird
    return diffDays >= 1;
} 