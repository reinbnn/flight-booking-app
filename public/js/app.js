/**
 * SKYJET Main Application
 */

// DOM Helpers
const dom = {
    get: (id) => document.getElementById(id),
    query: (selector) => document.querySelector(selector),
    queryAll: (selector) => document.querySelectorAll(selector),
    create: (tag, classes = '') => {
        const el = document.createElement(tag);
        if (classes) el.className = classes;
        return el;
    }
};

// Storage Manager
const storage = {
    set: (key, value) => localStorage.setItem(key, JSON.stringify(value)),
    get: (key) => JSON.parse(localStorage.getItem(key)) || null,
    remove: (key) => localStorage.removeItem(key),
    clear: () => localStorage.clear()
};

// UI Utilities
const ui = {
    loading: (element, show = true) => {
        if (show) {
            element.innerHTML = '<div style="text-align:center;padding:2rem;"><i class="fas fa-spinner fa-spin" style="font-size:2rem;margin-bottom:1rem;"></i><p>Loading...</p></div>';
        }
    },

    error: (element, message) => {
        element.innerHTML = `<div style="background:#fee;border:1px solid #f99;padding:1rem;border-radius:4px;color:#c33;"><strong>Error:</strong> ${message}</div>`;
    },

    success: (message) => {
        const alert = dom.create('div');
        alert.style.cssText = 'position:fixed;top:20px;right:20px;background:#1dd1a1;color:white;padding:1rem 2rem;border-radius:4px;box-shadow:0 2px 8px rgba(0,0,0,0.2);z-index:1000;';
        alert.textContent = '✓ ' + message;
        document.body.appendChild(alert);
        setTimeout(() => alert.remove(), 3000);
    },

    renderFlightCard: (flight) => {
        return `
            <div class="result-card" style="background:white;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.1);overflow:hidden;transition:transform 0.3s;padding:1.5rem;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
                    <h4 style="margin:0;color:#FF6B35;">${flight.flight_number}</h4>
                    <span style="background:#1dd1a1;color:white;padding:0.5rem 1rem;border-radius:4px;">${flight.status}</span>
                </div>
                <div style="margin-bottom:1rem;">
                    <p style="margin:0.5rem 0;"><strong>${flight.departure_airport}</strong> → <strong>${flight.arrival_airport}</strong></p>
                    <p style="margin:0.5rem 0;font-size:0.9rem;color:#666;">Departure: ${flight.departure_time}</p>
                    <p style="margin:0.5rem 0;font-size:0.9rem;color:#666;">Available: ${flight.available_seats}/${flight.total_seats} seats</p>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-size:1.5rem;color:#FF6B35;font-weight:bold;">$${parseFloat(flight.price).toFixed(2)}</span>
                    <button class="btn" onclick="bookFlight(${flight.id})" style="background:#FF6B35;color:white;border:none;padding:0.5rem 1rem;border-radius:4px;cursor:pointer;">Book</button>
                </div>
            </div>
        `;
    },

    renderHotelCard: (hotel) => {
        return `
            <div class="result-card" style="background:white;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.1);overflow:hidden;transition:transform 0.3s;padding:1.5rem;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
                    <h4 style="margin:0;color:#004E89;">${hotel.hotel_name}</h4>
                    <span style="color:#FDCB6E;">⭐ ${hotel.rating}</span>
                </div>
                <div style="margin-bottom:1rem;">
                    <p style="margin:0.5rem 0;"><strong>${hotel.city}, ${hotel.country}</strong></p>
                    <p style="margin:0.5rem 0;font-size:0.9rem;color:#666;">Available: ${hotel.available_rooms}/${hotel.total_rooms} rooms</p>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-size:1.5rem;color:#004E89;font-weight:bold;">$${parseFloat(hotel.price_per_night).toFixed(2)}/night</span>
                    <button class="btn" onclick="bookHotel(${hotel.id})" style="background:#004E89;color:white;border:none;padding:0.5rem 1rem;border-radius:4px;cursor:pointer;">Book</button>
                </div>
            </div>
        `;
    },

    renderCarCard: (car) => {
        return `
            <div class="result-card" style="background:white;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.1);overflow:hidden;transition:transform 0.3s;padding:1.5rem;">
                <div style="margin-bottom:1rem;">
                    <h4 style="margin:0;color:#1DD1A1;">${car.brand} ${car.model}</h4>
                    <p style="margin:0.5rem 0;font-size:0.9rem;color:#666;">${car.car_type} • ${car.year}</p>
                </div>
                <div style="margin-bottom:1rem;">
                    <p style="margin:0.5rem 0;font-size:0.9rem;">✓ ${car.seats} seats</p>
                    <p style="margin:0.5rem 0;font-size:0.9rem;">✓ ${car.transmission}</p>
                    <p style="margin:0.5rem 0;font-size:0.9rem;">Available: ${car.available_count}/${car.total_count}</p>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-size:1.5rem;color:#1DD1A1;font-weight:bold;">$${parseFloat(car.daily_rate).toFixed(2)}/day</span>
                    <button class="btn" onclick="bookCar(${car.id})" style="background:#1DD1A1;color:white;border:none;padding:0.5rem 1rem;border-radius:4px;cursor:pointer;">Book</button>
                </div>
            </div>
        `;
    }
};

// Booking Functions
async function searchFlights(departure, arrival, date) {
    const resultsDiv = dom.get('flightResults') || dom.query('#flightResults');
    if (!resultsDiv) return;

    ui.loading(resultsDiv);

    try {
        const data = await skyjetAPI.searchFlights({ departure, arrival, date });

        if (data.success && data.data.flights.length > 0) {
            resultsDiv.innerHTML = data.data.flights
                .map(f => ui.renderFlightCard(f))
                .join('');
        } else {
            resultsDiv.innerHTML = '<p style="text-align:center;padding:2rem;color:#666;">No flights found</p>';
        }
    } catch (error) {
        ui.error(resultsDiv, error.message);
    }
}

async function searchHotels(city, country) {
    const resultsDiv = dom.get('hotelResults') || dom.query('#hotelResults');
    if (!resultsDiv) return;

    ui.loading(resultsDiv);

    try {
        const data = await skyjetAPI.searchHotels({ city, country });

        if (data.success && data.data.hotels.length > 0) {
            resultsDiv.innerHTML = data.data.hotels
                .map(h => ui.renderHotelCard(h))
                .join('');
        } else {
            resultsDiv.innerHTML = '<p style="text-align:center;padding:2rem;color:#666;">No hotels found</p>';
        }
    } catch (error) {
        ui.error(resultsDiv, error.message);
    }
}

async function searchCars(type, maxPrice) {
    const resultsDiv = dom.get('carResults') || dom.query('#carResults');
    if (!resultsDiv) return;

    ui.loading(resultsDiv);

    try {
        const data = await skyjetAPI.searchCars({ type, max_price: maxPrice });

        if (data.success && data.data.cars.length > 0) {
            resultsDiv.innerHTML = data.data.cars
                .map(c => ui.renderCarCard(c))
                .join('');
        } else {
            resultsDiv.innerHTML = '<p style="text-align:center;padding:2rem;color:#666;">No cars found</p>';
        }
    } catch (error) {
        ui.error(resultsDiv, error.message);
    }
}

function bookFlight(id) {
    storage.set('selectedFlight', { id, type: 'flight' });
    window.location.href = '/pages/booking-details.html';
}

function bookHotel(id) {
    storage.set('selectedHotel', { id, type: 'hotel' });
    window.location.href = '/pages/hotel-booking-details.html';
}

function bookCar(id) {
    storage.set('selectedCar', { id, type: 'car' });
    window.location.href = '/pages/car-booking-details.html';
}

// Login/Register
async function handleLogin(email, password) {
    try {
        const data = await skyjetAPI.loginUser(email, password);
        if (data.success) {
            storage.set('user', data.data.user);
            storage.set('token', data.data.token);
            ui.success('Login successful!');
            setTimeout(() => window.location.href = '/pages/my-bookings.html', 1000);
        } else {
            ui.error(dom.get('loginError'), data.message);
        }
    } catch (error) {
        ui.error(dom.get('loginError'), error.message);
    }
}

async function handleRegister(userData) {
    try {
        const data = await skyjetAPI.registerUser(userData);
        if (data.success) {
            ui.success('Registration successful!');
            setTimeout(() => window.location.href = '/pages/login.html', 1000);
        }
    } catch (error) {
        ui.error(dom.get('registerError'), error.message);
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    console.log('SKYJET Application Loaded');
    
    // Check if user is logged in
    const user = storage.get('user');
    if (user) {
        console.log('User logged in:', user.email);
    }
});

console.log('App.js Loaded');
