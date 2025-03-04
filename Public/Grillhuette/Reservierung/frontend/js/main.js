// Utility Functions
const formatDate = (date) => {
    return date.toLocaleDateString('de-DE', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    });
};

const formatTime = (time) => {
    return time.toLocaleTimeString('de-DE', {
        hour: '2-digit',
        minute: '2-digit'
    });
};

const showAlert = (message, type = 'success') => {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.role = 'alert';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;

    const container = document.querySelector('.container');
    container.insertBefore(alertDiv, container.firstChild);

    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        alertDiv.classList.remove('show');
        setTimeout(() => alertDiv.remove(), 150);
    }, 5000);
};

// Form Validation
const validateForm = (form) => {
    const inputs = form.querySelectorAll('input[required]');
    let isValid = true;

    inputs.forEach(input => {
        if (!input.value.trim()) {
            isValid = false;
            input.classList.add('is-invalid');
        } else {
            input.classList.remove('is-invalid');
        }
    });

    return isValid;
};

// AJAX Request Helper
const makeRequest = async (url, options = {}) => {
    try {
        const response = await fetch(url, {
            ...options,
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            }
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'Ein Fehler ist aufgetreten');
        }

        return data;
    } catch (error) {
        showAlert(error.message, 'danger');
        throw error;
    }
};

// Booking Form Handler
const handleBookingSubmit = async (event) => {
    event.preventDefault();

    const form = event.target;
    if (!validateForm(form)) {
        return;
    }

    const formData = new FormData(form);
    const bookingData = {
        start_date: formData.get('start_date'),
        end_date: formData.get('end_date'),
        start_time: formData.get('start_time'),
        end_time: formData.get('end_time'),
        message: formData.get('message')
    };

    try {
        const response = await makeRequest('/backend/process_booking.php', {
            method: 'POST',
            body: JSON.stringify(bookingData)
        });

        if (response.success) {
            showAlert('Buchung erfolgreich erstellt!');
            form.reset();
            // Redirect to dashboard after successful booking
            setTimeout(() => {
                window.location.href = '/dashboard.php';
            }, 1500);
        }
    } catch (error) {
        console.error('Booking error:', error);
    }
};

// Profile Update Handler
const handleProfileUpdate = async (event) => {
    event.preventDefault();

    const form = event.target;
    if (!validateForm(form)) {
        return;
    }

    const formData = new FormData(form);
    const profileData = {
        name: formData.get('name'),
        email: formData.get('email')
    };

    try {
        const response = await makeRequest('/backend/process_profile_update.php', {
            method: 'POST',
            body: JSON.stringify(profileData)
        });

        if (response.success) {
            showAlert('Profil erfolgreich aktualisiert!');
        }
    } catch (error) {
        console.error('Profile update error:', error);
    }
};

// Password Change Handler
const handlePasswordChange = async (event) => {
    event.preventDefault();

    const form = event.target;
    if (!validateForm(form)) {
        return;
    }

    const formData = new FormData(form);
    const passwordData = {
        current_password: formData.get('current_password'),
        new_password: formData.get('new_password'),
        confirm_password: formData.get('confirm_password')
    };

    if (passwordData.new_password !== passwordData.confirm_password) {
        showAlert('Die Passwörter stimmen nicht überein.', 'danger');
        return;
    }

    try {
        const response = await makeRequest('/backend/process_password_change.php', {
            method: 'POST',
            body: JSON.stringify(passwordData)
        });

        if (response.success) {
            showAlert('Passwort erfolgreich geändert!');
            form.reset();
        }
    } catch (error) {
        console.error('Password change error:', error);
    }
};

// Booking Cancellation Handler
const handleBookingCancellation = async (bookingId) => {
    if (!confirm('Möchten Sie diese Buchung wirklich stornieren?')) {
        return;
    }

    try {
        const response = await makeRequest('/backend/process_booking_cancellation.php', {
            method: 'POST',
            body: JSON.stringify({ booking_id: bookingId })
        });

        if (response.success) {
            showAlert('Buchung erfolgreich storniert!');
            // Remove the booking element from the DOM
            document.querySelector(`[data-booking-id="${bookingId}"]`)?.remove();
        }
    } catch (error) {
        console.error('Booking cancellation error:', error);
    }
};

// Admin User Creation Handler
const handleAdminUserCreate = async (event) => {
    event.preventDefault();

    const form = event.target;
    if (!validateForm(form)) {
        return;
    }

    const formData = new FormData(form);
    const userData = {
        name: formData.get('name'),
        email: formData.get('email'),
        password: formData.get('password'),
        is_admin: formData.get('is_admin') === 'on'
    };

    try {
        const response = await makeRequest('/backend/process_admin_user_create.php', {
            method: 'POST',
            body: JSON.stringify(userData)
        });

        if (response.success) {
            showAlert('Benutzer erfolgreich erstellt!');
            form.reset();
            bootstrap.Modal.getInstance(document.getElementById('newUserModal')).hide();
        }
    } catch (error) {
        console.error('User creation error:', error);
    }
};

// Admin Booking Creation Handler
const handleAdminBookingCreate = async (event) => {
    event.preventDefault();

    const form = event.target;
    if (!validateForm(form)) {
        return;
    }

    const formData = new FormData(form);
    const bookingData = {
        user_id: formData.get('user_id'),
        start_date: formData.get('start_date'),
        end_date: formData.get('end_date'),
        start_time: formData.get('start_time'),
        end_time: formData.get('end_time'),
        message: formData.get('message')
    };

    try {
        const response = await makeRequest('/backend/process_admin_booking.php', {
            method: 'POST',
            body: JSON.stringify(bookingData)
        });

        if (response.success) {
            showAlert('Buchung erfolgreich erstellt!');
            form.reset();
            bootstrap.Modal.getInstance(document.getElementById('newBookingModal')).hide();
            // Reload calendar if on calendar page
            if (document.getElementById('calendar')) {
                window.location.reload();
            }
        }
    } catch (error) {
        console.error('Booking creation error:', error);
    }
};

// Load users for admin booking form
const loadUsers = async () => {
    try {
        const response = await makeRequest('/backend/get_users.php');
        if (response.success) {
            const select = document.getElementById('booking_user');
            if (select) {
                response.users.forEach(user => {
                    const option = document.createElement('option');
                    option.value = user.id;
                    option.textContent = `${user.name} (${user.email})`;
                    select.appendChild(option);
                });
            }
        }
    } catch (error) {
        console.error('Error loading users:', error);
    }
};

// Event Listeners
document.addEventListener('DOMContentLoaded', () => {
    // Booking form
    const bookingForm = document.getElementById('bookingForm');
    if (bookingForm) {
        bookingForm.addEventListener('submit', handleBookingSubmit);
    }

    // Profile form
    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        profileForm.addEventListener('submit', handleProfileUpdate);
    }

    // Password form
    const passwordForm = document.getElementById('passwordForm');
    if (passwordForm) {
        passwordForm.addEventListener('submit', handlePasswordChange);
    }

    // Booking cancellation buttons
    document.querySelectorAll('.cancel-booking-btn').forEach(button => {
        button.addEventListener('click', () => {
            const bookingId = button.dataset.bookingId;
            handleBookingCancellation(bookingId);
        });
    });

    // Admin user creation form
    const newUserForm = document.getElementById('newUserForm');
    if (newUserForm) {
        newUserForm.addEventListener('submit', handleAdminUserCreate);
    }

    // Admin booking creation form
    const adminBookingForm = document.getElementById('adminBookingForm');
    if (adminBookingForm) {
        adminBookingForm.addEventListener('submit', handleAdminBookingCreate);
        // Load users when modal is shown
        document.getElementById('newBookingModal').addEventListener('show.bs.modal', loadUsers);
    }
});

// Export functions for use in other scripts
window.app = {
    formatDate,
    formatTime,
    showAlert,
    validateForm,
    makeRequest
}; 