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
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link:not(.dropdown-toggle)');
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
    
    // Spezielles Handling für Dropdown-Toggles
    const dropdownToggles = document.querySelectorAll('.navbar-nav .dropdown-toggle');
    if (dropdownToggles) {
        dropdownToggles.forEach(toggle => {
            toggle.addEventListener('click', (e) => {
                e.preventDefault(); // Verhindert das Standardverhalten
                e.stopPropagation(); // Verhindert die Ausbreitung des Events
                
                // Finde das entsprechende Dropdown-Menü
                const dropdownMenu = toggle.nextElementSibling;
                if (dropdownMenu && dropdownMenu.classList.contains('dropdown-menu')) {
                    // Toggle das Dropdown-Menü
                    if (dropdownMenu.style.display === 'block') {
                        dropdownMenu.style.display = 'none';
                        toggle.classList.remove('show');
                    } else {
                        // Erst alle anderen schließen
                        document.querySelectorAll('.dropdown-menu').forEach(menu => {
                            if (menu !== dropdownMenu) {
                                menu.style.display = 'none';
                                const parentToggle = menu.previousElementSibling;
                                if (parentToggle) {
                                    parentToggle.classList.remove('show');
                                }
                            }
                        });
                        
                        dropdownMenu.style.display = 'block';
                        toggle.classList.add('show');
                    }
                }
            });
        });
        
        // Dropdown-Menü schließen, wenn irgendwo außerhalb geklickt wird
        document.addEventListener('click', (e) => {
            const isDropdownToggle = e.target.classList.contains('dropdown-toggle') || 
                                     e.target.closest('.dropdown-toggle');
            const isDropdownMenu = e.target.classList.contains('dropdown-menu') || 
                                  e.target.closest('.dropdown-menu');
            
            if (!isDropdownToggle && !isDropdownMenu) {
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    menu.style.display = 'none';
                    const parentToggle = menu.previousElementSibling;
                    if (parentToggle) {
                        parentToggle.classList.remove('show');
                    }
                });
            }
        });
        
        // Event-Listener für Dropdown-Items
        const dropdownItems = document.querySelectorAll('.dropdown-menu .dropdown-item');
        dropdownItems.forEach(item => {
            item.addEventListener('click', () => {
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

    // Initialize flatpickr for display date fields
    const displayStartPicker = flatpickr('#display_start_date', {
        locale: "de",
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "j. F Y",
        minDate: "today",
        disableMobile: "true",
        onChange: function(selectedDates, dateStr) {
            // Update min date of display end picker
            if (selectedDates[0]) {
                displayEndPicker.set('minDate', selectedDates[0]);
            }
        },
        onOpen: function(selectedDates, dateStr, instance) {
            // Update allowed date range when picker opens
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            
            if (startDate && endDate) {
                instance.set('minDate', startDate);
                instance.set('maxDate', endDate);
            }
        }
    });

    const displayEndPicker = flatpickr('#display_end_date', {
        locale: "de",
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "j. F Y",
        minDate: "today",
        disableMobile: "true",
        onOpen: function(selectedDates, dateStr, instance) {
            // Update allowed date range when picker opens
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            const displayStart = document.getElementById('display_start_date').value;
            
            if (startDate && endDate) {
                // Minimum date is either the reservation start date or the display start date
                instance.set('minDate', displayStart || startDate);
                instance.set('maxDate', endDate);
            }
        }
    });

    // Handle public event checkbox and date range toggle
    const isPublicCheckbox = document.getElementById('is_public');
    const showDateRangeCheckbox = document.getElementById('show_date_range');
    const publicEventDetails = document.getElementById('public-event-details');
    const singleDayField = document.getElementById('single-day-field');
    const dateRangeFields = document.getElementById('date-range-fields');
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    const eventDayInput = document.getElementById('event_day');
    const displayStartInput = document.getElementById('display_start_date');
    const displayEndInput = document.getElementById('display_end_date');

    // Initialize flatpickr for the event day field
    const eventDayPicker = flatpickr('#event_day', {
        locale: "de",
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "j. F Y",
        minDate: "today",
        disableMobile: "true",
        onChange: function(selectedDates, dateStr) {
            // When single day is selected, update both display date fields
            if (selectedDates[0]) {
                displayStartInput.value = dateStr;
                displayEndInput.value = dateStr;
            }
        },
        onOpen: function(selectedDates, dateStr, instance) {
            // Update allowed date range when picker opens
            const startDate = startDateInput.value;
            const endDate = endDateInput.value;
            
            if (startDate && endDate) {
                instance.set('minDate', startDate);
                instance.set('maxDate', endDate);
            }
        }
    });

    if (isPublicCheckbox && publicEventDetails) {
        isPublicCheckbox.addEventListener('change', function() {
            publicEventDetails.style.display = this.checked ? 'block' : 'none';
            if (this.checked && startDateInput.value) {
                // Set the single day display date to the reservation start date if not set
                if (!eventDayInput.value) {
                    eventDayPicker.setDate(startDateInput.value);
                }
            }
        });
    }

    if (showDateRangeCheckbox && dateRangeFields) {
        showDateRangeCheckbox.addEventListener('change', function() {
            dateRangeFields.style.display = this.checked ? 'block' : 'none';
            singleDayField.style.display = this.checked ? 'none' : 'block';
            
            if (this.checked) {
                // When switching to date range, if we have a single day selected,
                // use it as the start and end date
                if (eventDayInput.value) {
                    displayStartPicker.setDate(eventDayInput.value);
                    displayEndPicker.setDate(eventDayInput.value);
                }
            } else {
                // When switching back to single day, use the start date if set
                if (displayStartInput.value) {
                    eventDayPicker.setDate(displayStartInput.value);
                }
                // Make sure both display dates are set to the single day
                if (eventDayInput.value) {
                    displayStartInput.value = eventDayInput.value;
                    displayEndInput.value = eventDayInput.value;
                }
            }
        });
    }

    // Update display date constraints when reservation dates change
    if (startDateInput && endDateInput) {
        const updateDisplayDateConstraints = function() {
            const startDate = startDateInput.value;
            const endDate = endDateInput.value;
            const isDateRange = showDateRangeCheckbox && showDateRangeCheckbox.checked;

            if (startDate && endDate) {
                // Update constraints for the event day picker
                eventDayPicker.set('minDate', startDate);
                eventDayPicker.set('maxDate', endDate);

                if (isDateRange) {
                    // Update constraints for range pickers
                    displayStartPicker.set('minDate', startDate);
                    displayStartPicker.set('maxDate', endDate);
                    displayEndPicker.set('minDate', displayStartInput.value || startDate);
                    displayEndPicker.set('maxDate', endDate);

                    // Validate and clear range dates if needed
                    if (displayStartInput.value) {
                        const displayStartDate = new Date(displayStartInput.value);
                        const reservationStartDate = new Date(startDate);
                        const reservationEndDate = new Date(endDate);
                        
                        if (displayStartDate < reservationStartDate || displayStartDate > reservationEndDate) {
                            displayStartPicker.clear();
                            displayEndPicker.clear();
                        }
                    }
                } else {
                    // Validate and clear single day if needed
                    if (eventDayInput.value) {
                        const eventDay = new Date(eventDayInput.value);
                        const reservationStartDate = new Date(startDate);
                        const reservationEndDate = new Date(endDate);
                        
                        if (eventDay < reservationStartDate || eventDay > reservationEndDate) {
                            eventDayPicker.clear();
                            displayStartInput.value = '';
                            displayEndInput.value = '';
                        }
                    }
                }
            }
        };

        // Listen for changes to reservation dates
        startDateInput.addEventListener('change', updateDisplayDateConstraints);
        endDateInput.addEventListener('change', updateDisplayDateConstraints);
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
    
    // Get the root path from global config if available, otherwise use the default path
    const rootPath = (typeof APP_CONFIG !== 'undefined' && APP_CONFIG.ROOT_PATH) 
                    ? APP_CONFIG.ROOT_PATH 
                    : '/Grillhuette/Reservierung';
    
    // Klare URL mit vollständigem Pfad erstellen, um Pfadprobleme zu vermeiden
    const ajaxUrl = `${rootPath}/Helper/get_calendar_data.php?month=${formattedMonth}&year=${year}`;
    
    
    // Verwende fetch statt XMLHttpRequest für bessere Fehlerbehandlung
    fetch(ajaxUrl)
        .then(response => {
            // Add content type checking
            const contentType = response.headers.get('content-type');
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            if (!contentType || !contentType.includes('application/json')) {
                console.warn(`Expected JSON response but got ${contentType}`);
            }
            return response.text();
        })
        .then(text => {
            try {
                // Check if the response starts with HTML (error output)
                if (text.trim().startsWith('<')) {
                    handleError('Ungültiges Datenformat vom Server');
                    throw new Error('Invalid server response format');
                }
                
                const data = JSON.parse(text);
                if (!data.success) {
                    handleError(data.message || 'Unbekannter Fehler beim Laden der Daten');
                    throw new Error('Server reported error');
                }
                return data;
            } catch (e) {
                handleError('Fehler beim Verarbeiten der Kalenderdaten');
                if (text) {
                    handleError('Ungültige Serverantwort');
                }
                throw new Error('Invalid server response');
            }
        })
        .then(data => {
            // Update the calendar with the retrieved data
            updateDayStatuses(data);
        })
        .catch(error => {
            handleError('Fehler beim Laden der Kalenderdaten');
            // Fallback: Set all days to 'unknown' status
            const dayElements = document.querySelectorAll('.day[data-date]');
            dayElements.forEach(day => {
                day.classList.remove('free', 'pending', 'booked', 'public-event', 'key-handover');
                day.classList.add('free'); // Default to showing as free on error
            });
            
            // Optional: Show a non-intrusive error message
            const calendarContainer = document.getElementById('calendar');
            if (calendarContainer) {
                const errorElement = document.createElement('div');
                errorElement.className = 'alert alert-warning mt-3';
                errorElement.textContent = 'Kalenderdaten konnten nicht geladen werden. Bitte versuchen Sie es später erneut.';
                calendarContainer.appendChild(errorElement);
                
                // Remove after 5 seconds
                setTimeout(() => {
                    if (errorElement.parentNode) {
                        errorElement.parentNode.removeChild(errorElement);
                    }
                }, 5000);
            }
        });
}

// Show a mobile-friendly alert/toast message with sanitized content
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
    
    // Sanitize message to prevent any HTML/script injection
    const sanitizedMessage = document.createTextNode(message);
    toast.textContent = ''; // Clear previous content
    toast.appendChild(sanitizedMessage);
    toast.style.display = 'block';
    
    // Hide toast after 3 seconds
    setTimeout(() => {
        toast.style.display = 'none';
    }, 3000);
}

// Update day status classes in the calendar
function updateDayStatuses(statusData) {
    if (!statusData) {
        handleError('Keine Kalenderdaten verfügbar');
        return;
    }
    
    // Accessing the actual data object inside the response
    const dayStatuses = statusData.data || statusData;
    
    // Verifiziere, dass die zurückgegebenen Daten zum aktuellen Monat/Jahr passen
    const dates = Object.keys(dayStatuses);
    if (dates.length > 0) {
        // Prüfe das erste Datum, um den zurückgegebenen Monat zu erkennen
        const firstDate = dates[0];
        const receivedMonth = firstDate.split('-')[1]; // Format ist YYYY-MM-DD
        
        // Prüfe, ob der Monat im Datums-Key mit dem erwarteten Monat übereinstimmt
        const expectedMonth = document.getElementById('month').value.toString().padStart(2, '0');
        
    } else {
        handleError('Keine Kalenderdaten für diesen Monat verfügbar');
        return; // Exit early if there are no dates
    }
    
    // Get today's date for comparison
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    // First set all days to 'free' by default and mark past days
    document.querySelectorAll('.day[data-date]').forEach(dayElement => {
        const dateStr = dayElement.dataset.date;
        const date = new Date(dateStr);
        
        // Remove all status classes except other-month
        dayElement.classList.remove('free', 'pending', 'booked', 'past', 'public-event', 'key-handover');
        
        // Remove event name tooltip and data
        dayElement.dataset.eventName = '';
        dayElement.title = '';
        
        // Remove any key handover indicators
        const existingIndicators = dayElement.querySelectorAll('.event-indicator, .key-indicator');
        existingIndicators.forEach(indicator => indicator.remove());
        
        // Add free by default
        dayElement.classList.add('free');
        
        // If it's a past date, mark it as such
        if (date < today) {
            dayElement.classList.add('past');
            dayElement.classList.remove('free'); // A past day is not free for booking
        }
    });
    
    // Then update with statuses from the server
    Object.keys(dayStatuses).forEach(date => {
        const statusInfo = dayStatuses[date];
        const dayElement = document.querySelector(`.day[data-date="${date}"]`);
        
        if (dayElement) {
            
            // Remove status classes but keep past if it's set
            dayElement.classList.remove('free', 'pending', 'booked', 'public-event', 'key-handover');
            
            // Remove any previous time restrictions
            delete dayElement.dataset.timeRestrictions;
            
            // For non-past days, update status
            if (!dayElement.classList.contains('past')) {
                // Check if we have an object with a status or just a string status
                if (typeof statusInfo === 'object') {
                    
                    if (statusInfo.status === 'public_event') {
                        // For public event, use a special class
                        dayElement.classList.add('public-event');
                        
                        // Add event name as tooltip and data attribute
                        if (statusInfo.event_name) {
                            dayElement.dataset.eventName = statusInfo.event_name;
                            dayElement.title = statusInfo.event_name;
                            
                            // Add a small indicator for the event name
                            const indicator = document.createElement('span');
                            indicator.className = 'event-indicator';
                            indicator.textContent = statusInfo.event_name.substring(0, 15);
                            if (statusInfo.event_name.length > 15) {
                                indicator.textContent += '...';
                            }
                            dayElement.appendChild(indicator);
                        }
                        
                        // Add key handover info if available for public events
                        if (statusInfo.key_info) {
                            addKeyHandoverInfo(dayElement, statusInfo.key_info);
                        }
                    } else if (statusInfo.status === 'key_handover') {
                        // For key handover days - now reservable with restrictions
                        dayElement.classList.add('key-handover');
                        
                        if (statusInfo.key_info) {
                            addKeyHandoverInfo(dayElement, statusInfo.key_info);
                        }
                        
                        // Add time restrictions if available
                        if (statusInfo.time_restrictions) {
                            dayElement.dataset.timeRestrictions = JSON.stringify(statusInfo.time_restrictions);
                            
                            // Create tooltip message
                            let message = '';
                            if (statusInfo.time_restrictions.available_from) {
                                message += `Verfügbar ab ${statusInfo.time_restrictions.available_from} Uhr`;
                            }
                            if (statusInfo.time_restrictions.available_until) {
                                message += message ? ' bis ' : 'Verfügbar bis ';
                                message += `${statusInfo.time_restrictions.available_until} Uhr`;
                            }
                            
                            if (message) {
                                dayElement.title = message;
                            }
                        }
                    } else {
                        // For booked or pending with key info
                        dayElement.classList.add(statusInfo.status);
                        
                        // Add key handover info if available
                        if (statusInfo.key_info) {
                            addKeyHandoverInfo(dayElement, statusInfo.key_info);
                        }
                    }
                } else {
                    // Add the normal status class if it's just a string
                    dayElement.classList.add(statusInfo);
                }
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
        // Show feedback on mobile
        if (window.matchMedia("(max-width: 768px)").matches) {
            showMobileAlert('Vergangene Daten sind nicht auswählbar.');
        }
        return false;
    }
    
    // Check if the day has booked, pending, or public-event status
    if (dayElement.classList.contains('booked') || 
        dayElement.classList.contains('pending') || 
        dayElement.classList.contains('public-event')) {
        
        // For dates with special status, show appropriate message
        if (window.matchMedia("(max-width: 768px)").matches) {
            let message = 'Dieses Datum ist bereits belegt.';
            
            // If it's a public event, show the event name
            if (dayElement.classList.contains('public-event') && dayElement.dataset.eventName) {
                message = `Dieses Datum ist für "${dayElement.dataset.eventName}" reserviert.`;
            }
            
            showMobileAlert(message);
        }
        return false;
    }
    
    // Check for time restrictions on key handover days
    if (dayElement.dataset.timeRestrictions) {
        const restrictions = JSON.parse(dayElement.dataset.timeRestrictions);
        let message = '';
        
        if (restrictions.available_from) {
            message += `Dieser Tag ist ab ${restrictions.available_from} Uhr verfügbar`;
        }
        if (restrictions.available_until) {
            message += message ? ' und ' : 'Dieser Tag ist ';
            message += `nur bis ${restrictions.available_until} Uhr verfügbar`;
        }
        
        // Show the time restriction message
        if (window.matchMedia("(max-width: 768px)").matches) {
            showMobileAlert(message);
        } else {
            dayElement.title = message;
        }
    }
    
    // The day is in the future or today and is free or has key handover
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
        const isPublicCheckbox = document.getElementById('is_public');
        const showDateRangeCheckbox = document.getElementById('show_date_range');
        const publicEventDetails = document.getElementById('public-event-details');
        const singleDayField = document.getElementById('single-day-field');
        const dateRangeFields = document.getElementById('date-range-fields');
        const eventName = document.getElementById('event_name');
        
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

        // Validate public event fields if public checkbox is checked
        if (isPublicCheckbox && isPublicCheckbox.checked) {
            if (!eventName || !eventName.value.trim()) {
                event.preventDefault();
                const message = 'Bitte geben Sie einen Veranstaltungsnamen ein.';
                if (window.matchMedia("(max-width: 768px)").matches) {
                    showMobileAlert(message);
                } else {
                    alert(message);
                }
                return false;
            }

            const isDateRange = showDateRangeCheckbox && showDateRangeCheckbox.checked;
            
            if (!isDateRange && !eventDayInput.value) {
                event.preventDefault();
                const message = 'Bitte wählen Sie den Veranstaltungstag aus.';
                if (window.matchMedia("(max-width: 768px)").matches) {
                    showMobileAlert(message);
                } else {
                    alert(message);
                }
                return false;
            }

            if (isDateRange && (!displayStartInput.value || !displayEndInput.value)) {
                event.preventDefault();
                const message = 'Bitte wählen Sie Start- und Enddatum für den Veranstaltungszeitraum aus.';
                if (window.matchMedia("(max-width: 768px)").matches) {
                    showMobileAlert(message);
                } else {
                    alert(message);
                }
                return false;
            }

            // Validate dates are within reservation period
            const reservationStart = new Date(startDate.value);
            const reservationEnd = new Date(endDate.value);
            let displayStart, displayEnd;

            if (isDateRange) {
                displayStart = new Date(displayStartInput.value);
                displayEnd = new Date(displayEndInput.value);
            } else {
                displayStart = new Date(eventDayInput.value);
                displayEnd = new Date(eventDayInput.value);
            }

            if (displayStart < reservationStart || displayEnd > reservationEnd) {
                event.preventDefault();
                const message = 'Die Veranstaltungstage müssen innerhalb des Reservierungszeitraums liegen.';
                if (window.matchMedia("(max-width: 768px)").matches) {
                    showMobileAlert(message);
                } else {
                    alert(message);
                }
                return false;
            }
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
    
    // Setup cost calculator with improved event handling
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    
    if (startDateInput && endDateInput) {
        // Flatpickr-Integration für die Kostenberechnung
        function setupFlatpickrEvents() {
            // Versuche, die Flatpickr-Instanzen zu finden
            if (startDateInput._flatpickr) {
                startDateInput._flatpickr.config.onChange.push(function(selectedDates, dateStr) {
                    setTimeout(updateReservationCosts, 50);
                });
            }
            
            if (endDateInput._flatpickr) {
                endDateInput._flatpickr.config.onChange.push(function(selectedDates, dateStr) {
                    setTimeout(updateReservationCosts, 50);
                });
            }
        }
        
        // Versuche sofort und nach einer Verzögerung
        setupFlatpickrEvents();
        setTimeout(setupFlatpickrEvents, 500);
        
        // Standard-Event-Listener
        startDateInput.addEventListener('change', function() {
            setTimeout(updateReservationCosts, 50);
        });
        
        endDateInput.addEventListener('change', function() {
            setTimeout(updateReservationCosts, 50);
        });
        
        // Initiale Berechnung und Aktualisierung
        updateReservationCosts();
        
        // Regelmäßige Überprüfung, ob sich die Werte geändert haben
        setInterval(function() {
            if (startDateInput.value && endDateInput.value) {
                updateReservationCosts();
            }
        }, 1000);
    }
}

// Calculate and update reservation costs
function updateReservationCosts() {
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    const dayCountElement = document.getElementById('day-count');
    const totalCostElement = document.getElementById('total-cost');
    const baseCostElement = document.getElementById('base-cost');
    
    if (!startDateInput || !endDateInput || !dayCountElement || !totalCostElement) {
        handleError('Fehler bei der Preisberechnung: Nicht alle erforderlichen Elemente gefunden');
        return;
    }
    
    // Only calculate if both dates are selected
    if (startDateInput.value && endDateInput.value) {
        try {
            // Datumsobjekte erstellen (nur das Datum ohne Uhrzeit)
            let startDateParts = startDateInput.value.split('-');
            let endDateParts = endDateInput.value.split('-');
            
            // Stellen Sie sicher, dass wir gültige Werte haben
            if (startDateParts.length < 3 || endDateParts.length < 3) {
                throw new Error("Ungültiges Datumsformat");
            }
            
            // Erstelle Datumsobjekte (Monat ist 0-basiert in JavaScript)
            let startDateObj = new Date(
                parseInt(startDateParts[0]), 
                parseInt(startDateParts[1]) - 1, 
                parseInt(startDateParts[2])
            );
            
            let endDateObj = new Date(
                parseInt(endDateParts[0]), 
                parseInt(endDateParts[1]) - 1, 
                parseInt(endDateParts[2])
            );
            
            // Berechne die Tage manuell mit der einfachsten Methode
            // Setze auf UTC-Zeit, um Probleme mit Sommerzeit zu vermeiden
            const startUTC = Date.UTC(startDateObj.getFullYear(), startDateObj.getMonth(), startDateObj.getDate());
            const endUTC = Date.UTC(endDateObj.getFullYear(), endDateObj.getMonth(), endDateObj.getDate());
            
            // Die Differenz in Millisekunden
            const diffMilliseconds = Math.abs(endUTC - startUTC);
            
            // Umrechnung in Tage (1000 * 60 * 60 * 24 = Millisekunden in einem Tag)
            const diffDays = Math.floor(diffMilliseconds / (1000 * 60 * 60 * 24));
            
            // +1 weil wir auch den ersten Tag zählen (inklusiv)
            const days = diffDays + 1;
            
            // DEBUGGING: Füge temporär ein div hinzu, das anzeigt, was berechnet wurde
            let debugInfo = `Start: ${startDateInput.value}, End: ${endDateInput.value}, Tage: ${days}`;
            let debugDiv = document.getElementById('price-debug-info');
            if (!debugDiv) {
                debugDiv = document.createElement('div');
                debugDiv.id = 'price-debug-info';
                debugDiv.style.padding = '8px';
                debugDiv.style.backgroundColor = '#f8f9fa';
                debugDiv.style.fontSize = '12px';
                debugDiv.style.marginTop = '10px';
                
                const costOverview = document.getElementById('cost-overview');
                if (costOverview && costOverview.parentNode) {
                    costOverview.parentNode.appendChild(debugDiv);
                }
            }
            debugDiv.textContent = debugInfo;
            
            // Fetch current pricing information via AJAX
            fetch('Helper/get_pricing_info.php')
                .then(response => response.json())
                .then(priceInfo => {
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
                    console.error("API-Fehler:", error);
                    // Fallback to local calculation
                    const costOverview = document.getElementById('cost-overview');
                    let defaultRate = 100; // Default fallback
                    
                    if (costOverview && costOverview.hasAttribute('data-user-rate')) {
                        const userRateVal = parseFloat(costOverview.getAttribute('data-user-rate'));
                        defaultRate = (!isNaN(userRateVal) && userRateVal !== null) ? userRateVal : 100;
                    }
                    
                    const totalCost = days * defaultRate;
                    
                    // Format for display with German notation
                    const formattedRate = defaultRate.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    const formattedTotal = totalCost.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    
                    // Update UI
                    dayCountElement.textContent = days;
                    baseCostElement.textContent = formattedRate + '€';
                    totalCostElement.textContent = formattedTotal + '€';
                });
        } catch (e) {
            handleError('Fehler bei der Tagesberechnung');
            // Fallback zu einer einfacheren Methode
            calculateDefaultCosts(startDateInput.value, endDateInput.value, dayCountElement, totalCostElement, baseCostElement);
        }
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
                handleError('Fehler beim Laden der Preisinformationen');
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
function calculateDefaultCosts(startDate, endDate, dayCountElement, totalCostElement, baseCostElement) {
    try {
        // Datumsobjekte erstellen (nur das Datum ohne Uhrzeit)
        let startDateParts = startDate.split('-');
        let endDateParts = endDate.split('-');
        
        // Stellen Sie sicher, dass wir gültige Werte haben
        if (startDateParts.length < 3 || endDateParts.length < 3) {
            throw new Error("Ungültiges Datumsformat");
        }
        
        // Erstelle Datumsobjekte (Monat ist 0-basiert in JavaScript)
        let startDateObj = new Date(
            parseInt(startDateParts[0]), 
            parseInt(startDateParts[1]) - 1, 
            parseInt(startDateParts[2])
        );
        
        let endDateObj = new Date(
            parseInt(endDateParts[0]), 
            parseInt(endDateParts[1]) - 1, 
            parseInt(endDateParts[2])
        );
        
        // Berechne die Tage manuell mit der einfachsten Methode
        // Setze auf UTC-Zeit, um Probleme mit Sommerzeit zu vermeiden
        const startUTC = Date.UTC(startDateObj.getFullYear(), startDateObj.getMonth(), startDateObj.getDate());
        const endUTC = Date.UTC(endDateObj.getFullYear(), endDateObj.getMonth(), endDateObj.getDate());
        
        // Die Differenz in Millisekunden
        const diffMilliseconds = Math.abs(endUTC - startUTC);
        
        // Umrechnung in Tage (1000 * 60 * 60 * 24 = Millisekunden in einem Tag)
        const diffDays = Math.floor(diffMilliseconds / (1000 * 60 * 60 * 24));
        
        // +1 weil wir auch den ersten Tag zählen (inklusiv)
        const days = diffDays + 1;
        
        // DEBUGGING: Füge temporär ein div hinzu, das anzeigt, was berechnet wurde
        let debugInfo = `Start: ${startDate}, End: ${endDate}, Tage: ${days}`;
        let debugDiv = document.getElementById('price-debug-info');
        if (!debugDiv) {
            debugDiv = document.createElement('div');
            debugDiv.id = 'price-debug-info';
            debugDiv.style.padding = '8px';
            debugDiv.style.backgroundColor = '#f8f9fa';
            debugDiv.style.fontSize = '12px';
            debugDiv.style.marginTop = '10px';
            
            const costOverview = document.getElementById('cost-overview');
            if (costOverview && costOverview.parentNode) {
                costOverview.parentNode.appendChild(debugDiv);
            }
        }
        debugDiv.textContent = debugInfo;
        
        // Fetch current pricing information via AJAX
        fetch('Helper/get_pricing_info.php')
            .then(response => response.json())
            .then(priceInfo => {
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
                console.error("API-Fehler:", error);
                // Fallback to local calculation
                const costOverview = document.getElementById('cost-overview');
                let defaultRate = 100; // Default fallback
                
                if (costOverview && costOverview.hasAttribute('data-user-rate')) {
                    const userRateVal = parseFloat(costOverview.getAttribute('data-user-rate'));
                    defaultRate = (!isNaN(userRateVal) && userRateVal !== null) ? userRateVal : 100;
                }
                
                const totalCost = days * defaultRate;
                
                // Format for display with German notation
                const formattedRate = defaultRate.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                const formattedTotal = totalCost.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                
                // Update UI
                dayCountElement.textContent = days;
                baseCostElement.textContent = formattedRate + '€';
                totalCostElement.textContent = formattedTotal + '€';
            });
    } catch (e) {
        handleError('Fehler bei der Standard-Preisberechnung');
        // Fallback zu einer einfacheren Methode
        calculateDefaultCosts(startDate, endDate, dayCountElement, totalCostElement, baseCostElement);
    }
}

// Function to add key handover information to a day element
function addKeyHandoverInfo(dayElement, keyInfo) {
    let tooltipText = '';
    
    if (keyInfo.handover) {
        tooltipText += `Schlüsselübergabe: ${keyInfo.handover} Uhr`;
    }
    if (keyInfo.return) {
        if (tooltipText) tooltipText += '\n';
        tooltipText += `Schlüsselrückgabe: ${keyInfo.return} Uhr`;
    }
    
    // Set tooltip or append to existing tooltip
    if (dayElement.title) {
        dayElement.title += '\n\n' + tooltipText;
    } else {
        dayElement.title = tooltipText;
    }
    
    // Add key indicator
    const keyIndicator = document.createElement('span');
    keyIndicator.className = 'key-indicator';
    
    if (keyInfo.handover && keyInfo.return) {
        // Two keys if both handover and return on same day
        keyIndicator.innerHTML = '<i class="bi bi-key"></i><i class="bi bi-key"></i>';
    } else {
        keyIndicator.innerHTML = '<i class="bi bi-key"></i>';
    }
    
    dayElement.appendChild(keyIndicator);
}

// Zentrale Fehlerbehandlungsfunktion
function handleError(message) {
    // Nur in Entwicklungsumgebung detaillierte Fehler loggen
    if (process.env.NODE_ENV === 'development') {
        console.error(message);
    }
    
    // Benutzerfreundliche Fehlermeldung anzeigen
    const calendarContainer = document.getElementById('calendar');
    if (calendarContainer) {
        const errorElement = document.createElement('div');
        errorElement.className = 'alert alert-warning mt-3';
        errorElement.textContent = 'Die Kalenderdaten konnten nicht geladen werden. Bitte versuchen Sie es später erneut.';
        calendarContainer.appendChild(errorElement);
        
        // Nach 5 Sekunden entfernen
        setTimeout(() => {
            if (errorElement.parentNode) {
                errorElement.parentNode.removeChild(errorElement);
            }
        }, 5000);
    }
}