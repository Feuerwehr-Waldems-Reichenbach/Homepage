class Calendar {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        this.options = {
            onDateSelect: () => {},
            minDate: null,
            maxDate: null,
            disabledDates: [],
            bookedDates: [],
            isReadOnly: false,
            ...options
        };
        
        this.currentDate = new Date();
        this.selectedDates = {
            start: null,
            end: null
        };
        
        this.init();
    }

    init() {
        this.render();
        this.attachEventListeners();
        this.initInputFields();
    }

    initInputFields() {
        // Add input fields for manual date entry
        const startDateInput = document.getElementById('start_date_input');
        const endDateInput = document.getElementById('end_date_input');

        if (startDateInput && endDateInput) {
            startDateInput.addEventListener('change', (e) => {
                const date = new Date(e.target.value);
                if (!isNaN(date)) {
                    this.selectedDates.start = date;
                    this.updateSelection();
                }
            });

            endDateInput.addEventListener('change', (e) => {
                const date = new Date(e.target.value);
                if (!isNaN(date)) {
                    this.selectedDates.end = date;
                    this.updateSelection();
                }
            });
        }
    }

    updateSelection() {
        this.render();
        this.attachEventListeners();
        this.updateInputFields();
        this.options.onDateSelect(this.selectedDates);
    }

    updateInputFields() {
        const startDateInput = document.getElementById('start_date_input');
        const endDateInput = document.getElementById('end_date_input');
        const hiddenStartDate = document.getElementById('start_date');
        const hiddenEndDate = document.getElementById('end_date');

        if (startDateInput && this.selectedDates.start) {
            startDateInput.value = this.selectedDates.start.toISOString().split('T')[0];
            if (hiddenStartDate) {
                hiddenStartDate.value = startDateInput.value;
            }
        }

        if (endDateInput && this.selectedDates.end) {
            endDateInput.value = this.selectedDates.end.toISOString().split('T')[0];
            if (hiddenEndDate) {
                hiddenEndDate.value = endDateInput.value;
            }
        }
    }

    render() {
        const year = this.currentDate.getFullYear();
        const month = this.currentDate.getMonth();
        
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        
        const firstDayIndex = firstDay.getDay();
        const lastDayIndex = lastDay.getDay();
        const daysInMonth = lastDay.getDate();
        
        const monthNames = [
            'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni',
            'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'
        ];

        let html = `
            <div class="calendar-container">
                <div class="calendar-header">
                    <button class="btn btn-link" data-calendar-prev>&laquo;</button>
                    <h2>${monthNames[month]} ${year}</h2>
                    <button class="btn btn-link" data-calendar-next>&raquo;</button>
                </div>
                <div class="calendar-weekdays">
                    <div>So</div>
                    <div>Mo</div>
                    <div>Di</div>
                    <div>Mi</div>
                    <div>Do</div>
                    <div>Fr</div>
                    <div>Sa</div>
                </div>
                <div class="calendar-grid">
        `;

        // Previous month's days
        for (let i = firstDayIndex; i > 0; i--) {
            const prevDate = new Date(year, month, -i + 1);
            html += this.createDayElement(prevDate, 'prev-month');
        }

        // Current month's days
        for (let day = 1; day <= daysInMonth; day++) {
            const date = new Date(year, month, day);
            html += this.createDayElement(date);
        }

        // Next month's days
        for (let i = 1; i <= (6 - lastDayIndex); i++) {
            const nextDate = new Date(year, month + 1, i);
            html += this.createDayElement(nextDate, 'next-month');
        }

        html += `
                </div>
                <div class="calendar-footer mt-3">
                    <div class="selected-dates">
                        ${this.getSelectedDatesHTML()}
                    </div>
                </div>
            </div>
        `;

        this.container.innerHTML = html;
    }

    createDayElement(date, className = '') {
        const isToday = this.isToday(date);
        const isSelected = this.isDateSelected(date);
        const isDisabled = this.isDateDisabled(date);
        const isInRange = this.isDateInRange(date);
        const bookingInfo = this.getBookingInfo(date);
        
        const classes = [
            'calendar-day',
            className,
            isToday ? 'today' : '',
            isSelected ? 'selected' : '',
            isInRange ? 'in-range' : '',
            isDisabled ? 'disabled' : '',
            bookingInfo ? 'booked' : ''
        ].filter(Boolean).join(' ');

        let timeInfo = '';
        if (bookingInfo) {
            timeInfo = `<div class="booking-time">${bookingInfo.start_time.substr(0,5)} - ${bookingInfo.end_time.substr(0,5)}</div>`;
        }

        return `
            <div class="${classes}" 
                 data-date="${date.toISOString()}"
                 ${isDisabled || bookingInfo ? 'disabled' : ''}>
                <div class="day-number">${date.getDate()}</div>
                ${timeInfo}
            </div>
        `;
    }

    getSelectedDatesHTML() {
        if (!this.selectedDates.start) {
            return '<p class="text-muted">Bitte wählen Sie ein Startdatum</p>';
        }

        const startDate = window.app.formatDate(this.selectedDates.start);
        if (!this.selectedDates.end) {
            return `
                <p>Startdatum: ${startDate}</p>
                <p class="text-muted">Bitte wählen Sie ein Enddatum</p>
            `;
        }

        const endDate = window.app.formatDate(this.selectedDates.end);
        return `
            <p>Startdatum: ${startDate}</p>
            <p>Enddatum: ${endDate}</p>
        `;
    }

    attachEventListeners() {
        // Navigation buttons
        this.container.querySelector('[data-calendar-prev]')
            .addEventListener('click', () => this.previousMonth());
        
        this.container.querySelector('[data-calendar-next]')
            .addEventListener('click', () => this.nextMonth());

        // Day selection
        this.container.querySelectorAll('.calendar-day:not(.disabled)')
            .forEach(day => {
                day.addEventListener('click', (e) => {
                    const date = new Date(e.target.dataset.date);
                    this.handleDateSelection(date);
                });
            });
    }

    handleDateSelection(date) {
        if (this.options.isReadOnly) return;
        
        if (!this.selectedDates.start || (this.selectedDates.start && this.selectedDates.end)) {
            // First click or new selection
            if (this.isDateRangeValid(date, null)) {
                this.selectedDates.start = date;
                this.selectedDates.end = null;
            }
        } else {
            // Second click
            if (this.isDateRangeValid(this.selectedDates.start, date)) {
                if (date >= this.selectedDates.start) {
                    this.selectedDates.end = date;
                } else {
                    this.selectedDates.end = this.selectedDates.start;
                    this.selectedDates.start = date;
                }
            }
        }

        this.updateSelection();
    }

    isToday(date) {
        const today = new Date();
        return date.toDateString() === today.toDateString();
    }

    isDateSelected(date) {
        if (!this.selectedDates.start) return false;

        const dateStr = date.toDateString();
        const isStart = dateStr === this.selectedDates.start.toDateString();
        const isEnd = this.selectedDates.end && dateStr === this.selectedDates.end.toDateString();

        return isStart || isEnd;
    }

    isDateInRange(date) {
        if (!this.selectedDates.start || !this.selectedDates.end) return false;

        return date > this.selectedDates.start && date < this.selectedDates.end;
    }

    isDateDisabled(date) {
        if (this.options.minDate && date < this.options.minDate) return true;
        if (this.options.maxDate && date > this.options.maxDate) return true;
        
        return this.options.disabledDates.some(disabled => 
            disabled.toDateString() === date.toDateString()
        );
    }

    isDateBooked(date) {
        return this.options.bookedDates.some(bookedDate => {
            const booked = new Date(bookedDate);
            return date.toDateString() === booked.toDateString();
        });
    }

    previousMonth() {
        this.currentDate = new Date(
            this.currentDate.getFullYear(),
            this.currentDate.getMonth() - 1,
            1
        );
        this.render();
        this.attachEventListeners();
    }

    nextMonth() {
        this.currentDate = new Date(
            this.currentDate.getFullYear(),
            this.currentDate.getMonth() + 1,
            1
        );
        this.render();
        this.attachEventListeners();
    }

    getSelectedDates() {
        return this.selectedDates;
    }

    clearSelection() {
        this.selectedDates = {
            start: null,
            end: null
        };
        this.render();
        this.attachEventListeners();
    }

    getBookingInfo(date) {
        const dateStr = date.toISOString().split('T')[0];
        return this.options.bookedDates.find(booking => {
            if (typeof booking === 'string') {
                return booking === dateStr;
            }
            return booking.date === dateStr;
        });
    }

    isDateRangeValid(startDate, endDate) {
        if (!startDate) return true;
        if (!endDate) return true;

        const start = new Date(Math.min(startDate, endDate));
        const end = new Date(Math.max(startDate, endDate));
        
        // Check each day in the range
        const current = new Date(start);
        while (current <= end) {
            if (this.isDateBooked(current) || this.isDateDisabled(current)) {
                return false;
            }
            current.setDate(current.getDate() + 1);
        }
        
        return true;
    }
}

// Time Picker Component
class TimePicker {
    constructor(inputId, options = {}) {
        this.input = document.getElementById(inputId);
        this.options = {
            minTime: '00:00',
            maxTime: '23:59',
            interval: 30, // minutes
            ...options
        };

        this.init();
    }

    init() {
        this.createTimePickerDropdown();
        this.attachEventListeners();
    }

    createTimePickerDropdown() {
        const wrapper = document.createElement('div');
        wrapper.className = 'time-picker-wrapper position-relative';
        
        const dropdown = document.createElement('div');
        dropdown.className = 'time-picker-dropdown dropdown-menu';
        
        const times = this.generateTimeOptions();
        times.forEach(time => {
            const option = document.createElement('a');
            option.className = 'dropdown-item';
            option.href = '#';
            option.textContent = time;
            option.dataset.time = time;
            dropdown.appendChild(option);
        });

        this.input.parentNode.insertBefore(wrapper, this.input);
        wrapper.appendChild(this.input);
        wrapper.appendChild(dropdown);
    }

    generateTimeOptions() {
        const times = [];
        const [minHour, minMinute] = this.options.minTime.split(':').map(Number);
        const [maxHour, maxMinute] = this.options.maxTime.split(':').map(Number);
        
        const startMinutes = minHour * 60 + minMinute;
        const endMinutes = maxHour * 60 + maxMinute;
        
        for (let minutes = startMinutes; minutes <= endMinutes; minutes += this.options.interval) {
            const hour = Math.floor(minutes / 60);
            const minute = minutes % 60;
            times.push(`${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`);
        }

        return times;
    }

    attachEventListeners() {
        this.input.addEventListener('click', (e) => {
            e.preventDefault();
            const dropdown = e.target.nextElementSibling;
            dropdown.classList.toggle('show');
        });

        document.addEventListener('click', (e) => {
            if (!e.target.closest('.time-picker-wrapper')) {
                document.querySelectorAll('.time-picker-dropdown').forEach(dropdown => {
                    dropdown.classList.remove('show');
                });
            }
        });

        this.input.nextElementSibling.querySelectorAll('.dropdown-item').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                this.input.value = e.target.dataset.time;
                e.target.closest('.dropdown-menu').classList.remove('show');
            });
        });
    }
}

// Initialize calendar and time pickers when the page loads
document.addEventListener('DOMContentLoaded', () => {
    // Initialize calendar if container exists
    const calendarContainer = document.getElementById('calendar');
    if (calendarContainer) {
        const calendar = new Calendar('calendar', {
            onDateSelect: (dates) => {
                // Update hidden inputs or form fields with selected dates
                const startDateInput = document.getElementById('start_date');
                const endDateInput = document.getElementById('end_date');
                
                if (startDateInput && dates.start) {
                    startDateInput.value = dates.start.toISOString().split('T')[0];
                }
                if (endDateInput && dates.end) {
                    endDateInput.value = dates.end.toISOString().split('T')[0];
                }

                // Enable/disable submit button based on selection
                const submitButton = document.querySelector('#bookingForm button[type="submit"]');
                if (submitButton) {
                    submitButton.disabled = !dates.start || !dates.end;
                }
            },
            minDate: new Date(), // Can't select dates in the past
            disabledDates: [], // Add any dates that should be disabled
            bookedDates: [] // Add booked dates
        });

        // Initialize time pickers if they exist
        const startTimePicker = document.getElementById('start_time');
        const endTimePicker = document.getElementById('end_time');

        if (startTimePicker) {
            new TimePicker('start_time', {
                minTime: '08:00',
                maxTime: '20:00',
                interval: 30
            });
        }

        if (endTimePicker) {
            new TimePicker('end_time', {
                minTime: '08:00',
                maxTime: '20:00',
                interval: 30
            });
        }

        // Add form submission handler
        const bookingForm = document.getElementById('bookingForm');
        if (bookingForm) {
            bookingForm.addEventListener('submit', async (e) => {
                e.preventDefault();

                if (!window.app.validateForm(bookingForm)) {
                    return;
                }

                const formData = new FormData(bookingForm);
                const bookingData = {
                    start_date: formData.get('start_date'),
                    end_date: formData.get('end_date'),
                    start_time: formData.get('start_time'),
                    end_time: formData.get('end_time'),
                    message: formData.get('message')
                };

                try {
                    const response = await window.app.makeRequest('/backend/process_booking.php', {
                        method: 'POST',
                        body: JSON.stringify(bookingData)
                    });

                    if (response.success) {
                        // Show success message
                        const successModal = new bootstrap.Modal(document.getElementById('bookingSuccessModal'));
                        successModal.show();

                        // Reset form and calendar
                        bookingForm.reset();
                        calendar.clearSelection();

                        // Redirect to dashboard after delay
                        setTimeout(() => {
                            window.location.href = '/dashboard.php';
                        }, 3000);
                    }
                } catch (error) {
                    console.error('Booking error:', error);
                }
            });
        }
    }
});

// Update CSS styles
const style = document.createElement('style');
style.textContent = `
    .calendar-day {
        position: relative;
        min-height: 80px;
        padding: 5px;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .calendar-day .day-number {
        font-size: 1.1em;
        font-weight: 500;
        margin-bottom: 5px;
    }

    .calendar-day .booking-time {
        font-size: 0.8em;
        color: var(--danger-color);
        text-align: center;
        line-height: 1.2;
    }

    .calendar-day.booked {
        background-color: #FFE5E5;
    }

    .calendar-day.booked:hover {
        background-color: #FFE5E5 !important;
    }

    @media (max-width: 768px) {
        .calendar-day {
            min-height: 60px;
            font-size: 0.9em;
        }

        .calendar-day .booking-time {
            font-size: 0.7em;
        }
    }
`;
document.head.appendChild(style); 