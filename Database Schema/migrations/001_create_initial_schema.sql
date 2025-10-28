-- ===========================
-- DATABASE CREATION
-- ===========================
CREATE DATABASE IF NOT EXISTS skybird_travel;
USE skybird_travel;

-- ===========================
-- USERS TABLE
-- ===========================
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    date_of_birth DATE,
    country VARCHAR(100),
    passport_number VARCHAR(50) UNIQUE,
    profile_image VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    email_verified BOOLEAN DEFAULT FALSE,
    phone_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_active (is_active)
);

-- ===========================
-- AIRLINES TABLE
-- ===========================
CREATE TABLE airlines (
    id INT PRIMARY KEY AUTO_INCREMENT,
    airline_name VARCHAR(100) NOT NULL UNIQUE,
    airline_code VARCHAR(3) UNIQUE,
    website VARCHAR(255),
    phone VARCHAR(20),
    email VARCHAR(255),
    country VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    commission_rate DECIMAL(5, 2) DEFAULT 5.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (airline_code),
    INDEX idx_active (is_active)
);

-- ===========================
-- AIRPORTS TABLE
-- ===========================
CREATE TABLE airports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    airport_code VARCHAR(3) UNIQUE NOT NULL,
    airport_name VARCHAR(100) NOT NULL,
    city VARCHAR(100) NOT NULL,
    country VARCHAR(100) NOT NULL,
    timezone VARCHAR(50),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_code (airport_code),
    INDEX idx_city (city),
    INDEX idx_country (country)
);

-- ===========================
-- AIRCRAFT TABLE
-- ===========================
CREATE TABLE aircraft (
    id INT PRIMARY KEY AUTO_INCREMENT,
    aircraft_type VARCHAR(50) NOT NULL,
    manufacturer VARCHAR(100),
    economy_seats INT DEFAULT 150,
    premium_seats INT DEFAULT 20,
    business_seats INT DEFAULT 8,
    first_seats INT DEFAULT 2,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type (aircraft_type)
);

-- ===========================
-- FLIGHTS TABLE
-- ===========================
CREATE TABLE flights (
    id INT PRIMARY KEY AUTO_INCREMENT,
    flight_number VARCHAR(10) NOT NULL,
    airline_id INT NOT NULL,
    aircraft_id INT,
    departure_airport_id INT NOT NULL,
    arrival_airport_id INT NOT NULL,
    departure_time DATETIME NOT NULL,
    arrival_time DATETIME NOT NULL,
    duration_minutes INT,
    status ENUM('scheduled', 'boarding', 'departed', 'in_flight', 'landed', 'cancelled', 'delayed') DEFAULT 'scheduled',
    distance_km INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (airline_id) REFERENCES airlines(id),
    FOREIGN KEY (aircraft_id) REFERENCES aircraft(id),
    FOREIGN KEY (departure_airport_id) REFERENCES airports(id),
    FOREIGN KEY (arrival_airport_id) REFERENCES airports(id),
    INDEX idx_flight_number (flight_number),
    INDEX idx_departure (departure_time),
    INDEX idx_status (status),
    UNIQUE KEY unique_flight_schedule (flight_number, departure_time)
);

-- ===========================
-- FLIGHT PRICES TABLE
-- ===========================
CREATE TABLE flight_prices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    flight_id INT NOT NULL,
    cabin_class ENUM('economy', 'premium', 'business', 'first') NOT NULL,
    base_price DECIMAL(10, 2) NOT NULL,
    taxes DECIMAL(10, 2) DEFAULT 0,
    fees DECIMAL(10, 2) DEFAULT 0,
    total_price DECIMAL(10, 2) NOT NULL,
    available_seats INT NOT NULL,
    booked_seats INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (flight_id) REFERENCES flights(id) ON DELETE CASCADE,
    UNIQUE KEY unique_flight_cabin (flight_id, cabin_class),
    INDEX idx_flight (flight_id)
);

-- ===========================
-- BOOKINGS TABLE
-- ===========================
CREATE TABLE bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    flight_id INT NOT NULL,
    booking_reference VARCHAR(20) UNIQUE NOT NULL,
    cabin_class ENUM('economy', 'premium', 'business', 'first') NOT NULL,
    number_of_passengers INT NOT NULL,
    total_price DECIMAL(12, 2) NOT NULL,
    booking_status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    special_requests LONGTEXT,
    cancellation_reason VARCHAR(255),
    cancelled_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (flight_id) REFERENCES flights(id),
    INDEX idx_user (user_id),
    INDEX idx_status (booking_status),
    INDEX idx_reference (booking_reference),
    INDEX idx_created (created_at)
);

-- ===========================
-- PASSENGERS TABLE
-- ===========================
CREATE TABLE passengers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(20),
    date_of_birth DATE,
    passport_number VARCHAR(50),
    passport_expiry DATE,
    nationality VARCHAR(100),
    seat_number VARCHAR(10),
    seat_class ENUM('economy', 'premium', 'business', 'first'),
    meal_preference VARCHAR(50),
    special_services LONGTEXT,
    boarding_pass_number VARCHAR(50),
    check_in_status ENUM('not_checked_in', 'checked_in', 'boarded') DEFAULT 'not_checked_in',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    INDEX idx_booking (booking_id),
    INDEX idx_passport (passport_number)
);

-- ===========================
-- PAYMENTS TABLE
-- ===========================
CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT NOT NULL,
    amount DECIMAL(12, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    payment_method ENUM('credit_card', 'debit_card', 'paypal', 'bank_transfer', 'apple_pay', 'google_pay') NOT NULL,
    transaction_id VARCHAR(100) UNIQUE NOT NULL,
    reference_number VARCHAR(50),
    payment_status ENUM('pending', 'completed', 'failed', 'refunded', 'cancelled') DEFAULT 'pending',
    payment_gateway VARCHAR(50),
    gateway_response LONGTEXT,
    refund_amount DECIMAL(12, 2) DEFAULT 0,
    refund_reason VARCHAR(255),
    refunded_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id),
    INDEX idx_booking (booking_id),
    INDEX idx_status (payment_status),
    INDEX idx_transaction (transaction_id),
    INDEX idx_created (created_at)
);

-- ===========================
-- HOTELS TABLE
-- ===========================
CREATE TABLE hotels (
    id INT PRIMARY KEY AUTO_INCREMENT,
    hotel_name VARCHAR(100) NOT NULL,
    city VARCHAR(100) NOT NULL,
    country VARCHAR(100) NOT NULL,
    address TEXT NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(255),
    website VARCHAR(255),
    rating DECIMAL(3, 1),
    review_count INT DEFAULT 0,
    total_rooms INT NOT NULL,
    available_rooms INT,
    check_in_time TIME DEFAULT '14:00:00',
    check_out_time TIME DEFAULT '11:00:00',
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    description LONGTEXT,
    amenities LONGTEXT,
    is_active BOOLEAN DEFAULT TRUE,
    commission_rate DECIMAL(5, 2) DEFAULT 8.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_city (city),
    INDEX idx_country (country),
    INDEX idx_active (is_active)
);

-- ===========================
-- HOTEL ROOMS TABLE
-- ===========================
CREATE TABLE hotel_rooms (
    id INT PRIMARY KEY AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    room_type VARCHAR(50) NOT NULL,
    room_number VARCHAR(10),
    price_per_night DECIMAL(10, 2) NOT NULL,
    capacity INT NOT NULL,
    available_count INT,
    bed_type VARCHAR(50),
    amenities LONGTEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (hotel_id) REFERENCES hotels(id),
    INDEX idx_hotel (hotel_id),
    INDEX idx_type (room_type)
);

-- ===========================
-- HOTEL BOOKINGS TABLE
-- ===========================
CREATE TABLE hotel_bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    hotel_id INT NOT NULL,
    room_id INT NOT NULL,
    booking_reference VARCHAR(20) UNIQUE NOT NULL,
    check_in_date DATE NOT NULL,
    check_out_date DATE NOT NULL,
    number_of_nights INT NOT NULL,
    number_of_guests INT NOT NULL,
    total_price DECIMAL(12, 2) NOT NULL,
    booking_status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    special_requests LONGTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (hotel_id) REFERENCES hotels(id),
    FOREIGN KEY (room_id) REFERENCES hotel_rooms(id),
    INDEX idx_user (user_id),
    INDEX idx_status (booking_status),
    INDEX idx_dates (check_in_date, check_out_date)
);

-- ===========================
-- CARS TABLE
-- ===========================
CREATE TABLE cars (
    id INT PRIMARY KEY AUTO_INCREMENT,
    car_rental_company VARCHAR(100) NOT NULL,
    car_make VARCHAR(100) NOT NULL,
    car_model VARCHAR(100) NOT NULL,
    car_type ENUM('economy', 'compact', 'sedan', 'suv', 'van', 'luxury') NOT NULL,
    year INT NOT NULL,
    license_plate VARCHAR(20),
    transmission ENUM('automatic', 'manual') DEFAULT 'automatic',
    fuel_type ENUM('petrol', 'diesel', 'hybrid', 'electric') DEFAULT 'petrol',
    passengers_capacity INT DEFAULT 5,
    luggage_capacity INT DEFAULT 3,
    daily_rate DECIMAL(10, 2) NOT NULL,
    weekly_rate DECIMAL(10, 2),
    monthly_rate DECIMAL(10, 2),
    total_available INT NOT NULL,
    available_count INT,
    mileage INT DEFAULT 0,
    last_service_date DATE,
    next_service_date DATE,
    features LONGTEXT,
    is_active BOOLEAN DEFAULT TRUE,
    commission_rate DECIMAL(5, 2) DEFAULT 6.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_type (car_type),
    INDEX idx_active (is_active)
);

-- ===========================
-- CAR BOOKINGS TABLE
-- ===========================
CREATE TABLE car_bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    car_id INT NOT NULL,
    booking_reference VARCHAR(20) UNIQUE NOT NULL,
    pickup_location VARCHAR(100) NOT NULL,
    dropoff_location VARCHAR(100) NOT NULL,
    pickup_date DATE NOT NULL,
    dropoff_date DATE NOT NULL,
    pickup_time TIME DEFAULT '09:00:00',
    dropoff_time TIME DEFAULT '17:00:00',
    number_of_days INT NOT NULL,
    total_price DECIMAL(12, 2) NOT NULL,
    booking_status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    driver_license_number VARCHAR(50),
    driver_license_expiry DATE,
    insurance_selected BOOLEAN DEFAULT FALSE,
    insurance_type VARCHAR(50),
    additional_services LONGTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (car_id) REFERENCES cars(id),
    INDEX idx_user (user_id),
    INDEX idx_status (booking_status),
    INDEX idx_dates (pickup_date, dropoff_date)
);

-- ===========================
-- LOYALTY PROGRAM TABLE
-- ===========================
CREATE TABLE loyalty_members (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL UNIQUE,
    member_level ENUM('bronze', 'silver', 'gold', 'platinum') DEFAULT 'bronze',
    total_points INT DEFAULT 0,
    lifetime_bookings INT DEFAULT 0,
    lifetime_spending DECIMAL(12, 2) DEFAULT 0,
    joined_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_level (member_level)
);

-- ===========================
-- LOYALTY POINTS TABLE
-- ===========================
CREATE TABLE loyalty_points (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    points INT NOT NULL,
    point_type ENUM('booking', 'review', 'referral', 'promotion', 'redemption') NOT NULL,
    reference_id INT,
    description VARCHAR(255),
    expiry_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user (user_id),
    INDEX idx_expiry (expiry_date)
);

-- ===========================
-- REVIEWS TABLE
-- ===========================
CREATE TABLE reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    booking_id INT,
    item_type ENUM('flight', 'hotel', 'car') NOT NULL,
    item_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    review_text LONGTEXT,
    verified_purchase BOOLEAN DEFAULT TRUE,
    helpful_count INT DEFAULT 0,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (booking_id) REFERENCES bookings(id),
    INDEX idx_item (item_type, item_id),
    INDEX idx_rating (rating),
    INDEX idx_status (status)
);

-- ===========================
-- ADMIN USERS TABLE
-- ===========================
CREATE TABLE admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    role ENUM('super_admin', 'admin', 'moderator', 'support') DEFAULT 'admin',
    permissions LONGTEXT,
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
);

-- ===========================
-- ACTIVITY LOG TABLE
-- ===========================
CREATE TABLE activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    admin_id INT,
    activity_type VARCHAR(100) NOT NULL,
    description VARCHAR(255),
    ip_address VARCHAR(50),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (admin_id) REFERENCES admin_users(id),
    INDEX idx_user (user_id),
    INDEX idx_activity (activity_type),
    INDEX idx_created (created_at)
);

-- ===========================
-- NOTIFICATIONS TABLE
-- ===========================
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message LONGTEXT,
    data LONGTEXT,
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user (user_id),
    INDEX idx_read (is_read),
    INDEX idx_created (created_at)
);

-- ===========================
-- PROMO CODES TABLE
-- ===========================
CREATE TABLE promo_codes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(50) UNIQUE NOT NULL,
    description VARCHAR(255),
    discount_type ENUM('percentage', 'fixed') NOT NULL,
    discount_value DECIMAL(10, 2) NOT NULL,
    max_discount DECIMAL(10, 2),
    min_booking_amount DECIMAL(10, 2),
    max_usage INT,
    current_usage INT DEFAULT 0,
    valid_from DATE NOT NULL,
    valid_until DATE NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_active (is_active),
    INDEX idx_validity (valid_from, valid_until)
);

-- ===========================
-- SUPPORT TICKETS TABLE
-- ===========================
CREATE TABLE support_tickets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ticket_number VARCHAR(20) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    admin_id INT,
    subject VARCHAR(255) NOT NULL,
    description LONGTEXT NOT NULL,
    category VARCHAR(50),
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('open', 'in_progress', 'waiting_customer', 'resolved', 'closed') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (admin_id) REFERENCES admin_users(id),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_user (user_id)
);

-- ===========================
-- TICKET MESSAGES TABLE
-- ===========================
CREATE TABLE ticket_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ticket_id INT NOT NULL,
    sender_id INT,
    sender_type ENUM('user', 'admin') NOT NULL,
    message LONGTEXT NOT NULL,
    attachments LONGTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,
    INDEX idx_ticket (ticket_id)
);

-- ===========================
-- SYSTEM SETTINGS TABLE
-- ===========================
CREATE TABLE system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value LONGTEXT,
    setting_type VARCHAR(50),
    description VARCHAR(255),
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES admin_users(id),
    INDEX idx_key (setting_key)
);

-- ===========================
-- INDEXES AND CONSTRAINTS
-- ===========================
CREATE INDEX idx_user_email ON users(email);
CREATE INDEX idx_booking_status ON bookings(booking_status);
CREATE INDEX idx_payment_status ON payments(payment_status);
CREATE INDEX idx_flight_departure ON flights(departure_time);

-- ===========================
-- SEED DATA
-- ===========================

-- Insert Airlines
INSERT INTO airlines (airline_name, airline_code, website, phone, email, country, commission_rate) VALUES
('British Airways', 'BA', 'www.britishairways.com', '+44 344 222 1111', 'contact@ba.com', 'United Kingdom', 5.00),
('Air France', 'AF', 'www.airfrance.com', '+33 892 802 802', 'contact@airfrance.com', 'France', 5.00),
('Lufthansa', 'LH', 'www.lufthansa.com', '+49 69 86799799', 'contact@lufthansa.com', 'Germany', 5.00),
('KLM Royal Dutch Airlines', 'KL', 'www.klm.com', '+31 20 474 7747', 'contact@klm.com', 'Netherlands', 5.00);

-- Insert Airports
INSERT INTO airports (airport_code, airport_name, city, country, timezone) VALUES
('LHR', 'London Heathrow', 'London', 'United Kingdom', 'GMT'),
('CDG', 'Charles de Gaulle', 'Paris', 'France', 'CET'),
('AMS', 'Amsterdam Airport Schiphol', 'Amsterdam', 'Netherlands', 'CET'),
('FRA', 'Frankfurt am Main', 'Frankfurt', 'Germany', 'CET'),
('JFK', 'John F. Kennedy International', 'New York', 'United States', 'EST'),
('LAX', 'Los Angeles International', 'Los Angeles', 'United States', 'PST'),
('NRT', 'Narita International', 'Tokyo', 'Japan', 'JST'),
('DXB', 'Dubai International', 'Dubai', 'United Arab Emirates', 'GST');

-- Insert Aircraft
INSERT INTO aircraft (aircraft_type, manufacturer, economy_seats, premium_seats, business_seats, first_seats) VALUES
('Boeing 737', 'Boeing', 150, 20, 8, 2),
('Boeing 777', 'Boeing', 300, 40, 12, 8),
('Airbus A380', 'Airbus', 400, 60, 16, 14),
('Airbus A320', 'Airbus', 180, 25, 10, 4);

-- Insert Admin User
INSERT INTO admin_users (username, email, password, full_name, role, is_active) VALUES
('admin', 'admin@skybirdtravel.com', '$2y$10$YIjlrJyEvLayvYRLUVQvO.ErKZH8F7Z8qX5Z5V5V5V5V5V5V5V', 'System Administrator', 'super_admin', TRUE);

-- Insert System Settings
INSERT INTO system_settings (setting_key, setting_value, setting_type, description) VALUES
('site_name', 'Skybird Travel', 'string', 'Website name'),
('site_url', 'https://skybirdtravel.com', 'string', 'Website URL'),
('support_email', 'support@skybirdtravel.com', 'email', 'Support email address'),
('admin_email', 'admin@skybirdtravel.com', 'email', 'Admin email address'),
('default_currency', 'USD', 'string', 'Default currency code'),
('timezone', 'UTC', 'string', 'Default timezone'),
('booking_confirmation_email', '1', 'boolean', 'Send booking confirmation email'),
('flight_commission', '5.00', 'decimal', 'Default flight commission percentage'),
('hotel_commission', '8.00', 'decimal', 'Default hotel commission percentage'),
('car_commission', '6.00', 'decimal', 'Default car commission percentage');