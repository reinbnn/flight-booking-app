// ===========================
// ADMIN DASHBOARD JAVASCRIPT
// ===========================

const API_BASE_URL = 'http://localhost/api/';
const ADMIN_TOKEN = localStorage.getItem('admin_token');

document.addEventListener('DOMContentLoaded', function() {
    initializeAdmin();
    // NEW: Load real data from API
    loadDashboardData();
});

function initializeAdmin() {
    setupSidebar();
    setupMenuToggle();
    setupProfileMenu();
    setupCharts();
}

// ===========================
// SIDEBAR FUNCTIONS (YOUR CODE - KEEP IT)
// ===========================
function setupSidebar() {
    const menuToggle = document.getElementById('menuToggle');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('adminSidebar');

    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.remove('active');
        });
    }

    // Set active nav item
    const currentPage = window.location.pathname.split('/').pop();
    document.querySelectorAll('.nav-item').forEach(link => {
        const href = link.getAttribute('href');
        if (href === currentPage || (currentPage === '' && href === 'index.html')) {
            link.classList.add('active');
        }
    });
}

function setupMenuToggle() {
    const sidebar = document.getElementById('adminSidebar');
    const menuToggle = document.getElementById('menuToggle');

    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
}

// ===========================
// PROFILE MENU (YOUR CODE - KEEP IT)
// ===========================
function setupProfileMenu() {
    const profileMenu = document.querySelector('.admin-profile');
    
    if (profileMenu) {
        profileMenu.addEventListener('click', function() {
            console.log('Profile menu clicked');
        });
    }
}

// ===========================
// CHARTS (YOUR CODE - MODIFIED)
// ===========================
let revenueChart, statusChart;

function setupCharts() {
    // Charts will be initialized with real data from loadDashboardData()
}

// ===========================
// NEW: LOAD DASHBOARD DATA FROM API
// ===========================
async function loadDashboardData() {
    try {
        console.log('Loading dashboard data...');

        // Fetch dashboard summary data
        const response = await fetch(API_BASE_URL + 'admin/dashboard', {
            headers: {
                'Authorization': 'Bearer ' + ADMIN_TOKEN,
                'Content-Type': 'application/json'
            }
        });

        const result = await response.json();

        if (result.success) {
            console.log('Dashboard data received:', result.data);
            
            const data = result.data;

            // Update stat cards with real numbers
            updateStatCards(data);

            // Update recent bookings table
            if (data.recent_bookings && data.recent_bookings.length > 0) {
                updateRecentBookings(data.recent_bookings);
            }

            // Update top routes
            if (data.top_routes && data.top_routes.length > 0) {
                updateTopRoutes(data.top_routes);
            }

            // Update activity feed
            if (data.recent_activity && data.recent_activity.length > 0) {
                updateActivityFeed(data.recent_activity);
            }

            // Initialize/update charts with real data
            initializeChartsWithData(data);

        } else {
            console.error('Error loading dashboard:', result.error);
            showNotification('Error loading dashboard data', 'error');
        }

    } catch (error) {
        console.error('Dashboard error:', error);
        showNotification('Connection error: ' + error.message, 'error');
    }
}

// ===========================
// NEW: UPDATE STAT CARDS WITH REAL DATA
// ===========================
function updateStatCards(data) {
    console.log('Updating stat cards...');

    // Update Total Flights
    const flightCard = document.querySelector('.stat-card:nth-child(1) .stat-number');
    if (flightCard) {
        flightCard.textContent = data.flights?.total || '0';
    }

    // Update Total Bookings
    const bookingCard = document.querySelector('.stat-card:nth-child(2) .stat-number');
    if (bookingCard) {
        bookingCard.textContent = data.bookings?.total || '0';
    }

    // Update Total Revenue
    const revenueCard = document.querySelector('.stat-card:nth-child(3) .stat-number');
    if (revenueCard) {
        revenueCard.textContent = '$' + formatNumber(data.revenue?.total || 0);
    }

    // Update Total Users
    const userCard = document.querySelector('.stat-card:nth-child(4) .stat-number');
    if (userCard) {
        userCard.textContent = data.users?.total || '0';
    }
}

// ===========================
// NEW: UPDATE RECENT BOOKINGS TABLE
// ===========================
function updateRecentBookings(bookings) {
    console.log('Updating bookings table...');

    const tbody = document.querySelector('.data-table tbody');
    if (!tbody) return;

    let html = '';

    bookings.slice(0, 5).forEach(booking => {
        // Determine badge color based on status
        let badgeClass = 'badge-success';
        if (booking.status === 'pending') badgeClass = 'badge-warning';
        if (booking.status === 'cancelled') badgeClass = 'badge-danger';

        html += `
            <tr>
                <td>#${booking.booking_reference || booking.id}</td>
                <td>${booking.customer_name || booking.first_name + ' ' + booking.last_name}</td>
                <td>${booking.flight_number || 'N/A'} (${booking.route || booking.from + ' → ' + booking.to})</td>
                <td><span class="badge ${badgeClass}">${booking.status || 'unknown'}</span></td>
                <td>$${formatNumber(booking.amount || booking.total_price || 0)}</td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

// ===========================
// NEW: UPDATE TOP ROUTES
// ===========================
function updateTopRoutes(routes) {
    console.log('Updating top routes...');

    const routeList = document.querySelector('.route-list');
    if (!routeList) return;

    let html = '';
    const maxBookings = Math.max(...routes.map(r => r.bookings || r.booking_count || 0));

    routes.forEach(route => {
        const bookingCount = route.bookings || route.booking_count || 0;
        const percentage = maxBookings > 0 ? (bookingCount / maxBookings) * 100 : 0;
        const routeName = route.from && route.to ? 
            `${route.from} → ${route.to}` : 
            route.route || 'Unknown Route';

        html += `
            <div class="route-item">
                <div class="route-info">
                    <h4>${routeName}</h4>
                    <p class="route-code">${route.from_code || ''} → ${route.to_code || ''}</p>
                </div>
                <div class="route-stats">
                    <p class="route-count">${bookingCount} bookings</p>
                    <div class="route-bar">
                        <div class="route-fill" style="width: ${percentage}%;"></div>
                    </div>
                </div>
            </div>
        `;
    });

    routeList.innerHTML = html;
}

// ===========================
// NEW: UPDATE ACTIVITY FEED
// ===========================
function updateActivityFeed(activities) {
    console.log('Updating activity feed...');

    const feed = document.querySelector('.activity-feed');
    if (!feed) return;

    let html = '';

    activities.forEach(activity => {
        const iconClass = getActivityIcon(activity.type || activity.activity_type || 'flight');
        const bgClass = getActivityBgColor(activity.type || activity.activity_type || 'flight');
        const timeAgo = getTimeAgo(activity.timestamp || activity.created_at);

        html += `
            <div class="activity-item">
                <div class="activity-icon ${bgClass}">
                    <i class="fas ${iconClass}"></i>
                </div>
                <div class="activity-content">
                    <p>${activity.description || activity.message || 'Activity'}</p>
                    <span class="activity-time">${timeAgo}</span>
                </div>
            </div>
        `;
    });

    feed.innerHTML = html;
}

// ===========================
// NEW: INITIALIZE CHARTS WITH REAL DATA
// ===========================
function initializeChartsWithData(data) {
    console.log('Initializing charts...');

    // Revenue Chart Data
    if (data.revenue_data) {
        const revenueCtx = document.getElementById('revenueChart');
        if (revenueCtx) {
            // Destroy previous chart if exists
            if (revenueChart) {
                revenueChart.destroy();
            }

            revenueChart = new Chart(revenueCtx, {
                type: 'line',
                data: {
                    labels: data.revenue_data.labels || ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{
                        label: 'Revenue',
                        data: data.revenue_data.values || [12000, 19000, 15000, 22000, 25000, 20000, 23000],
                        borderColor: '#0066cc',
                        backgroundColor: 'rgba(0, 102, 204, 0.1)',
                        tension: 0.4,
                        fill: true,
                        pointRadius: 5,
                        pointBackgroundColor: '#0066cc'
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value / 1000 + 'k';
                                }
                            }
                        }
                    }
                }
            });
        }
    }

    // Status Chart Data
    if (data.booking_status) {
        const statusCtx = document.getElementById('statusChart');
        if (statusCtx) {
            // Destroy previous chart if exists
            if (statusChart) {
                statusChart.destroy();
            }

            statusChart = new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: data.booking_status.labels || ['Confirmed', 'Pending', 'Cancelled'],
                    datasets: [{
                        data: data.booking_status.values || [65, 20, 15],
                        backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                        borderColor: 'white',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
    }
}

// ===========================
// YOUR EXISTING DATA FUNCTIONS (KEEP THEM)
// ===========================
async function fetchDashboardData() {
    try {
        const response = await fetch('../api/admin/dashboard.php');
        const data = await response.json();
        updateDashboard(data);
    } catch (error) {
        console.error('Error fetching dashboard data:', error);
    }
}

function updateDashboard(data) {
    // Update stat cards
    document.querySelectorAll('.stat-number').forEach((element, index) => {
        // Update with real data
    });
}

// ===========================
// YOUR EXISTING TABLE FUNCTIONS (KEEP THEM)
// ===========================
function deleteRow(rowId) {
    if (confirm('Are you sure you want to delete this item?')) {
        console.log('Deleting row:', rowId);
    }
}

function editRow(rowId) {
    console.log('Editing row:', rowId);
}

// ===========================
// YOUR EXISTING NOTIFICATIONS (KEEP THEM)
// ===========================
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// ===========================
// YOUR EXISTING EXPORT FUNCTIONS (KEEP THEM)
// ===========================
function exportToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    let csv = [];
    
    const headers = [];
    table.querySelectorAll('th').forEach(th => {
        headers.push(th.textContent);
    });
    csv.push(headers.join(','));
    
    table.querySelectorAll('tbody tr').forEach(tr => {
        const row = [];
        tr.querySelectorAll('td').forEach(td => {
            row.push('"' + td.textContent + '"');
        });
        csv.push(row.join(','));
    });
    
    const csvContent = csv.join('
');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename || 'export.csv';
    a.click();
}

function exportToPDF(tableId, filename) {
    console.log('PDF export not implemented');
}

// ===========================
// YOUR EXISTING SEARCH FUNCTION (KEEP IT)
// ===========================
function filterTable(tableId, searchTerm) {
    const table = document.getElementById(tableId);
    const rows = table.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm.toLowerCase()) ? '' : 'none';
    });
}

// ===========================
// YOUR EXISTING FORM HANDLING (KEEP IT)
// ===========================
function submitForm(formId, endpoint) {
    const form = document.getElementById(formId);
    const formData = new FormData(form);
    
    fetch(endpoint, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Operation completed successfully', 'success');
        } else {
            showNotification('Error: ' + data.error, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    });
}

// ===========================
// YOUR EXISTING MODAL FUNCTIONS (KEEP THEM)
// ===========================
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        modal.style.display = 'flex';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        modal.style.display = 'none';
    }
}

// ===========================
// YOUR EXISTING LOGOUT (KEEP IT)
// ===========================
function logout() {
    if (confirm('Are you sure you want to logout?')) {
        localStorage.removeItem('adminToken');
        window.location.href = 'login.html';
    }
}

// ===========================
// NEW: UTILITY FUNCTIONS
// ===========================

function formatNumber(num) {
    return Number(num).toLocaleString('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    });
}

function getActivityIcon(type) {
    const icons = {
        'flight': 'fa-plane',
        'booking': 'fa-check-circle',
        'user': 'fa-user-plus',
        'cancel': 'fa-times-circle',
        'payment': 'fa-credit-card'
    };
    return icons[type] || 'fa-circle';
}

function getActivityBgColor(type) {
    const colors = {
        'flight': 'bg-blue',
        'booking': 'bg-green',
        'user': 'bg-orange',
        'cancel': 'bg-red',
        'payment': 'bg-purple'
    };
    return colors[type] || 'bg-gray';
}

function getTimeAgo(timestamp) {
    if (!timestamp) return 'just now';
    
    const now = new Date();
    const time = new Date(timestamp);
    const seconds = Math.floor((now - time) / 1000);

    if (seconds < 60) return 'just now';
    if (seconds < 3600) return Math.floor(seconds / 60) + ' minute' + (seconds / 60 > 1 ? 's' : '') + ' ago';
    if (seconds < 86400) return Math.floor(seconds / 3600) + ' hour' + (seconds / 3600 > 1 ? 's' : '') + ' ago';
    return Math.floor(seconds / 86400) + ' day' + (seconds / 86400 > 1 ? 's' : '') + ' ago';
}