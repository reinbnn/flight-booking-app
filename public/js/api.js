/**
 * Skybird Travel - API Client
 */

const API_BASE_URL = 'http://localhost/flight-booking-app/api';

class SkybirdAPI {
    constructor() {
        this.token = localStorage.getItem('token');
    }

    /**
     * Set authorization token
     */
    setToken(token) {
        this.token = token;
        localStorage.setItem('token', token);
    }

    /**
     * Get headers with authentication
     */
    getHeaders() {
        const headers = {
            'Content-Type': 'application/json'
        };

        if (this.token) {
            headers['Authorization'] = `Bearer ${this.token}`;
        }

        return headers;
    }

    /**
     * Make API request
     */
    async request(endpoint, method = 'GET', data = null) {
        try {
            const options = {
                method,
                headers: this.getHeaders()
            };

            if (data) {
                options.body = JSON.stringify(data);
            }

            const response = await fetch(`${API_BASE_URL}${endpoint}`, options);
            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.error || result.message || 'API Error');
            }

            return result;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }

    /**
     * Flight APIs
     */
    searchFlights(from, to, date, passengers, cabin) {
        return this.request(
            `/flights.php?action=search&from=${from}&to=${to}&date=${date}&passengers=${passengers}&cabin=${cabin}`
        );
    }

    getFlightDetails(flightId) {
        return this.request(`/flights.php?action=details&id=${flightId}`);
    }

    /**
     * Hotel APIs
     */
    searchHotels(city, checkIn, checkOut) {
        return this.request(
            `/hotels.php?action=search&city=${city}&check_in=${checkIn}&check_out=${checkOut}`
        );
    }

    getHotelDetails(hotelId) {
        return this.request(`/hotels.php?action=details&id=${hotelId}`);
    }

    /**
     * Car APIs
     */
    searchCars(pickupLocation, pickupDate, dropoffDate) {
        return this.request(
            `/cars.php?action=search&location=${pickupLocation}&pickup_date=${pickupDate}&dropoff_date=${dropoffDate}`
        );
    }

    getCarDetails(carId) {
        return this.request(`/cars.php?action=details&id=${carId}`);
    }

    /**
     * Booking APIs
     */
    createFlightBooking(data) {
        return this.request('/bookings.php?action=create', 'POST', data);
    }

    getBookings(userId) {
        return this.request(`/bookings.php?action=list&user_id=${userId}`);
    }

    getBookingDetails(bookingId) {
        return this.request(`/bookings.php?action=details&booking_id=${bookingId}`);
    }

    cancelBooking(bookingId) {
        return this.request(`/bookings.php?action=cancel&booking_id=${bookingId}`, 'DELETE');
    }

    /**
     * Payment APIs
     */
    processPayment(data) {
        return this.request('/payments.php?action=process', 'POST', data);
    }

    /**
     * User APIs
     */
    register(data) {
        return this.request('/users.php?action=register', 'POST', data);
    }

    login(email, password) {
        return this.request('/users.php?action=login', 'POST', {
            email,
            password
        });
    }

    getProfile() {
        return this.request('/users.php?action=profile');
    }

    updateProfile(data) {
        return this.request('/users.php?action=update', 'POST', data);
    }

    logout() {
        this.token = null;
        localStorage.removeItem('token');
    }

    /**
     * Admin APIs
     */
    getDashboardStats() {
        return this.request('/admin/dashboard.php?action=stats');
    }

    getRevenueReport(month, year) {
        return this.request(`/admin/dashboard.php?action=revenue&month=${month}&year=${year}`);
    }

    getAllBookings(page = 1, perPage = 20) {
        return this.request(`/admin/dashboard.php?action=bookings&page=${page}&per_page=${perPage}`);
    }

    getAllUsers(page = 1, perPage = 20) {
        return this.request(`/admin/dashboard.php?action=users&page=${page}&per_page=${perPage}`);
    }

    blockUser(userId) {
        return this.request('/admin/dashboard.php?action=block_user', 'POST', {
            user_id: userId
        });
    }

    updateBookingStatus(bookingId, status) {
        return this.request('/admin/dashboard.php?action=update_booking', 'PUT', {
            booking_id: bookingId,
            status
        });
    }
}

// Create global API instance
const api = new SkybirdAPI();