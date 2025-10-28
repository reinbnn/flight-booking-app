-- Users Table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    date_of_birth DATE,
    country VARCHAR(100),
    passport_number VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE
);

-- Airlines Table
CREATE TABLE airlines (
    id INT PRIMARY KEY AUTO_INCREMENT,
    airline_name VARCHAR(100) NOT NULL,
    airline_code VARCHAR(3) UNIQUE,
    website VARCHAR(255),
    phone VARCHAR(20),
    email VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Airports Table
CREATE TABLE airports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    airport_code VARCHAR(3) UNIQUE,
    airport_name VARCHAR(100),
    city VARCHAR(100),
    country VARCHAR(100),
    timezone VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Flights Table
CREATE TABLE flights (
    id INT PRIMARY KEY AUTO_INCREMENT,
    flight_number VARCHAR(10) NOT NULL,
    airline_id INT NOT NULL,
    departure_airport VARCHAR(3),
    arrival_airport VARCHAR(3),
    departure_time DATETIME NOT NULL,
    arrival_time DATETIME NOT NULL,
    aircraft_type VARCHAR(50),
    total_seats INT DEFAULT 180,
    available_seats INT DEFAULT 180,
    status ENUM('active', 'cancelled', 'delayed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (airline_id) REFERENCES airlines(id),
    INDEX idx_route (departure_airport, arrival_airport),
    INDEX idx_departure (departure_time)
);

-- Flight Prices Table
CREATE TABLE flight_prices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    flight_id INT NOT NULL,
    cabin_class ENUM('economy', 'premium', 'business', 'first') NOT NULL,
    base_price DECIMAL(10, 2),
    taxes DECIMAL(10, 2),
    total_price DECIMAL(10, 2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (flight_id) REFERENCES flights(id),
    UNIQUE KEY unique_flight_cabin (flight_id, cabin_class)
);

-- Bookings Table
CREATE TABLE bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    flight_id INT NOT NULL,
    booking_reference VARCHAR(20) UNIQUE NOT NULL,
    cabin_class ENUM('economy', 'premium', 'business', 'first'),
    number_of_passengers INT,
    total_price DECIMAL(12, 2),
    booking_status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    special_requests TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (flight_id) REFERENCES flights(id),
    INDEX idx_user (user_id),
    INDEX idx_status (booking_status)
);

-- Passengers Table
CREATE TABLE passengers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(20),
    date_of_birth DATE,
    passport_number VARCHAR(50),
    seat_number VARCHAR(10),
    meal_preference VARCHAR(50),
    baggage_allowance INT DEFAULT 23,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    INDEX idx_booking (booking_id)
);

-- Payments Table
CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT NOT NULL,
    amount DECIMAL(12, 2),
    payment_method ENUM('credit_card', 'debit_card', 'paypal', 'bank_transfer'),
    transaction_id VARCHAR(100),
    payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id),
    INDEX idx_booking (booking_id),
    INDEX idx_status (payment_status)
);

-- Hotels Table
CREATE TABLE hotels (
    id INT PRIMARY KEY AUTO_INCREMENT,
    hotel_name VARCHAR(100) NOT NULL,
    city VARCHAR(100),
    country VARCHAR(100),
    address TEXT,
    phone VARCHAR(20),
    email VARCHAR(255),
    website VARCHAR(255),
    rating DECIMAL(3, 1),
    total_rooms INT,
    available_rooms INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Hotel Rooms Table
CREATE TABLE hotel_rooms (
    id INT PRIMARY KEY AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    room_type VARCHAR(50),
    price_per_night DECIMAL(10, 2),
    capacity INT,
    available_count INT,
    amenities TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hotel_id) REFERENCES hotels(id)
);

-- Hotel Bookings Table
CREATE TABLE hotel_bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    hotel_id INT NOT NULL,
    room_id INT NOT NULL,
    check_in_date DATE,
    check_out_date DATE,
    number_of_nights INT,
    number_of_guests INT,
    total_price DECIMAL(12, 2),
    booking_status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (hotel_id) REFERENCES hotels(id),
    FOREIGN KEY (room_id) REFERENCES hotel_rooms(id)
);

-- Cars Table
CREATE TABLE cars (
    id INT PRIMARY KEY AUTO_INCREMENT,
    car_rental_company VARCHAR(100),
    car_type VARCHAR(50),
    brand VARCHAR(100),
    model VARCHAR(100),
    year INT,
    daily_rate DECIMAL(10, 2),
    available_count INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Car Bookings Table
CREATE TABLE car_bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    car_id INT NOT NULL,
    pickup_location VARCHAR(100),
    dropoff_location VARCHAR(100),
    pickup_date DATE,
    dropoff_date DATE,
    number_of_days INT,
    total_price DECIMAL(12, 2),
    booking_status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (car_id) REFERENCES cars(id)
);

-- Insert Sample Airlines
INSERT INTO airlines (airline_name, airline_code, website, phone) VALUES
('British Airways', 'BA', 'www.britishairways.com', '+44 344 222 1111'),
('Air France', 'AF', 'www.airfrance.com', '+33 892 802 802'),
('Lufthansa', 'LH', 'www.lufthansa.com', '+49 69 86799799'),
('KLM Royal Dutch Airlines', 'KL', 'www.klm.com', '+31 20 474 7747');

-- Insert Sample Airports
INSERT INTO airports (airport_code, airport_name, city, country, timezone) VALUES
('LHR', 'London Heathrow', 'London', 'United Kingdom', 'GMT'),
('CDG', 'Charles de Gaulle', 'Paris', 'France', 'CET'),
('AMS', 'Amsterdam Airport Schiphol', 'Amsterdam', 'Netherlands', 'CET'),
('FRA', 'Frankfurt am Main', 'Frankfurt', 'Germany', 'CET'),
('JFK', 'John F. Kennedy International', 'New York', 'United States', 'EST'),
('LAX', 'Los Angeles International', 'Los Angeles', 'United States', 'PST'),
('NRT', 'Narita International', 'Tokyo', 'Japan', 'JST');