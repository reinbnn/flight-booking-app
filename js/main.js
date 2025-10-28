// ===========================
// MAIN JAVASCRIPT
// ===========================

document.addEventListener('DOMContentLoaded', function() {
    initNavigation();
    initSearchTabs();
    initForms();
    initDatePickers();
    setMinDates();
});

// ===========================
// NAVIGATION
// ===========================
function initNavigation() {
    const hamburger = document.getElementById('hamburger');
    const navbarMenu = document.getElementById('navbarMenu');

    if (hamburger) {
        hamburger.addEventListener('click', function() {
            this.classList.toggle('active');
            navbarMenu.classList.toggle('active');
        });
    }

    // Close menu when link is clicked
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', function() {
            if (hamburger) {
                hamburger.classList.remove('active');
                navbarMenu.classList.remove('active');
            }
        });
    });

    // Set active nav link
    const currentPage = window.location.pathname.split('/').pop() || 'index.html';
    document.querySelectorAll('.nav-link').forEach(link => {
        const href = link.getAttribute('href');
        if (href.includes(currentPage)) {
            link.classList.add('active');
        }
    });
}

// ===========================
// SEARCH TABS
// ===========================
function initSearchTabs() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const searchForms = document.querySelectorAll('.search-form');

    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tab = this.getAttribute('data-tab');
            
            // Remove active from all
            tabButtons.forEach(btn => btn.classList.remove('active'));
            searchForms.forEach(form => form.classList.remove('active'));
            
            // Add active to clicked
            this.classList.add('active');
            document.getElementById(`${tab}-form`).classList.add('active');
        });
    });
}

// ===========================
// DATE PICKERS
// ===========================
function setMinDates() {
    const today = new Date().toISOString().split('T')[0];
    
    const departDate = document.getElementById('departDate');
    const returnDate = document.getElementById('returnDate');
    const checkIn = document.getElementById('checkIn');
    const checkOut = document.getElementById('checkOut');
    const carPickupDate = document.getElementById('carPickupDate');
    const carDropoffDate = document.getElementById('carDropoffDate');

    if (departDate) departDate.min = today;
    if (returnDate) returnDate.min = today;
    if (checkIn) checkIn.min = today;
    if (checkOut) checkOut.min = today;
    if (carPickupDate) carPickupDate.min = today;
    if (carDropoffDate) carDropoffDate.min = today;

    // Update return date min when depart date changes
    if (departDate && returnDate) {
        departDate.addEventListener('change', function() {
            returnDate.min = this.value;
        });
    }

    // Update checkout min when checkin changes
    if (checkIn && checkOut) {
        checkIn.addEventListener('change', function() {
            checkOut.min = this.value;
        });
    }

    // Update dropoff min when pickup changes
    if (carPickupDate && carDropoffDate) {
        carPickupDate.addEventListener('change', function() {
            carDropoffDate.min = this.value;
        });
    }
}

function initDatePickers() {
    // Additional date picker functionality if needed
}

// ===========================
// FORMS
// ===========================
function initForms() {
    const newsletterForm = document.getElementById('newsletterForm');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const email = this.querySelector('input[type="email"]').value;
            
            // Simulate form submission
            const button = this.querySelector('button');
            const originalText = button.textContent;
            button.disabled = true;
            button.textContent = 'Subscribing...';
            
            setTimeout(() => {
                showAlert('Successfully subscribed!', 'success');
                this.reset();
                button.disabled = false;
                button.textContent = originalText;
            }, 1500);
        });
    }
}

// ===========================
// SEARCH HANDLERS
// ===========================
function handleFlightSearch() {
    const from = document.getElementById('from').value;
    const to = document.getElementById('to').value;
    const departDate = document.getElementById('departDate').value;
    const returnDate = document.getElementById('returnDate').value;
    const passengers = document.getElementById('passengers').value;
    const cabin = document.getElementById('cabin').value;

    if (!from || !to || !departDate) {
        showAlert('Please fill in all required fields', 'error');
        return;
    }

    // Store search data in sessionStorage
    sessionStorage.setItem('flightSearch', JSON.stringify({
        from,
        to,
        departDate,
        returnDate,
        passengers,
        cabin
    }));

    // Redirect to flights page
    window.location.href = 'pages/flights.html';
}

// ===========================
// UTILITIES
// ===========================
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} animate-fadeInDown`;
    alertDiv.innerHTML = `
        <i class="fas fa-info-circle"></i>
        <span>${message}</span>
    `;

    document.body.insertBefore(alertDiv, document.body.firstChild);

    setTimeout(() => {
        alertDiv.classList.remove('animate-fadeInDown');
        alertDiv.classList.add('animate-fadeOut');
        setTimeout(() => alertDiv.remove(), 300);
    }, 3000);
}

function showLoading(element) {
    element.innerHTML = '<div class="loading"></div> Loading...';
}

function formatCurrency(amount, currency = 'USD') {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency
    }).format(amount);
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    });
}

// ===========================
// AUTOCOMPLETE
// ===========================
const airports = [
    { code: 'LHR', city: 'London' },
    { code: 'CDG', city: 'Paris' },
    { code: 'AMS', city: 'Amsterdam' },
    { code: 'FRA', city: 'Frankfurt' },
    { code: 'MAD', city: 'Madrid' },
    { code: 'ORY', city: 'Paris Orly' },
    { code: 'MUC', city: 'Munich' },
    { code: 'BCN', city: 'Barcelona' },
    { code: 'FCO', city: 'Rome' },
    { code: 'CDT', city: 'Dublin' }
];

document.querySelectorAll('.autocomplete').forEach(input => {
    input.addEventListener('input', function() {
        const value = this.value.toLowerCase();
        if (value.length < 2) return;

        const filtered = airports.filter(a =>
            a.city.toLowerCase().includes(value) ||
            a.code.toLowerCase().includes(value)
        );

        // Could add dropdown suggestions here
    });
});

// ===========================
// SMOOTH SCROLL
// ===========================
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({ behavior: 'smooth' });
        }
    });
});

// ===========================
// LOGOUT
// ===========================
function logout() {
    sessionStorage.clear();
    localStorage.clear();
    window.location.href = '../index.html';
}

// ===========================
// HANDLE SEARCH BUTTON CLICK
// ===========================
document.addEventListener('DOMContentLoaded', function() {
    const searchBtn = document.getElementById('searchBtn');
    if (searchBtn) {
        searchBtn.addEventListener('click', handleFlightSearch);
    }
});