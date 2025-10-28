-- SKYJET Flight Booking Database

-- Users Table
CREATE TABLE IF NOT EXISTS users (
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
CREATE TABLE IF NOT EXISTS airlines (
    id INT PRIMARY KEY AUTO_INCREMENT,
    airline_name VARCHAR(100) NOT NULL,
    airline_code VARCHAR(3) UNIQUE,
    website VARCHAR(255),
    phone VARCHAR(20),
    email VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Airports Table
CREATE TABLE IF NOT EXISTS airports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    airport_code VARCHAR(3) UNIQUE,
    airport_name VARCHAR(100),
    city VARCHAR(100),
    country VARCHAR(100),
    timezone VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Flights Table
CREATE TABLE IF NOT EXISTS flights (
    id INT PRIMARY KEY AUTO_INCREMENT,
    flight_number VARCHAR(10) NOT NULL UNIQUE,
    airline_id INT NOT NULL,
    departure_airport VARCHAR(3) NOT NULL,
    arrival_airport VARCHAR(3) NOT NULL,
    departure_time DATETIME NOT NULL,
    arrival_time DATETIME NOT NULL,
    aircraft_type VARCHAR(50),
    total_seats INT DEFAULT 180,
    available_seats INT DEFAULT 180,
    price DECIMAL(10, 2) NOT NULL,
    duration INT,
    stops INT DEFAULT 0,
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (airline_id) REFERENCES airlines(id)
);

-- Bookings Table
CREATE TABLE IF NOT EXISTS bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    flight_id INT NOT NULL,
    booking_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    departure_date DATE NOT NULL,
    return_date DATE,
    number_of_passengers INT NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    status VARCHAR(50) DEFAULT 'confirmed',
    booking_reference VARCHAR(20) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (flight_id) REFERENCES flights(id)
);

-- Passengers Table
CREATE TABLE IF NOT EXISTS passengers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(20),
    date_of_birth DATE,
    passport_number VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);

-- Payments Table
CREATE TABLE IF NOT EXISTS payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT NOT NULL,
    payment_method VARCHAR(50),
    amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    transaction_id VARCHAR(100),
    status VARCHAR(50) DEFAULT 'pending',
    payment_date DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id)
);

-- Refunds Table
CREATE TABLE IF NOT EXISTS refunds (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT NOT NULL,
    payment_id INT,
    refund_amount DECIMAL(10, 2) NOT NULL,
    reason VARCHAR(255),
    status VARCHAR(50) DEFAULT 'pending',
    processed_date DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id),
    FOREIGN KEY (payment_id) REFERENCES payments(id)
);

-- Hotels Table
CREATE TABLE IF NOT EXISTS hotels (
    id INT PRIMARY KEY AUTO_INCREMENT,
    hotel_name VARCHAR(100) NOT NULL,
    city VARCHAR(100),
    country VARCHAR(100),
    address VARCHAR(255),
    rating DECIMAL(3, 1),
    price_per_night DECIMAL(10, 2),
    total_rooms INT,
    available_rooms INT,
    amenities JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Hotel Bookings Table
CREATE TABLE IF NOT EXISTS hotel_bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    hotel_id INT NOT NULL,
    check_in_date DATE NOT NULL,
    check_out_date DATE NOT NULL,
    number_of_rooms INT,
    total_price DECIMAL(10, 2),
    status VARCHAR(50) DEFAULT 'confirmed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (hotel_id) REFERENCES hotels(id)
);

-- Cars Table
CREATE TABLE IF NOT EXISTS cars (
    id INT PRIMARY KEY AUTO_INCREMENT,
    car_type VARCHAR(100),
    rental_company VARCHAR(100),
    price_per_day DECIMAL(10, 2),
    available_count INT,
    city VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Car Bookings Table
CREATE TABLE IF NOT EXISTS car_bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    car_id INT NOT NULL,
    pickup_date DATE NOT NULL,
    return_date DATE NOT NULL,
    total_price DECIMAL(10, 2),
    status VARCHAR(50) DEFAULT 'confirmed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (car_id) REFERENCES cars(id)
);

-- Invoices Table
CREATE TABLE IF NOT EXISTS invoices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT,
    invoice_number VARCHAR(50) UNIQUE,
    issue_date DATE,
    due_date DATE,
    total_amount DECIMAL(10, 2),
    status VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id)
);

-- Error Logs Table
CREATE TABLE IF NOT EXISTS error_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    error_message TEXT,
    error_code VARCHAR(50),
    stack_trace LONGTEXT,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Audit Logs Table
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100),
    resource_type VARCHAR(100),
    resource_id INT,
    changes JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Add some sample data
INSERT INTO airlines (airline_name, airline_code, website) VALUES
('British Airways', 'BA', 'www.britishairways.com'),
('Emirates', 'EK', 'www.emirates.com'),
('Lufthansa', 'LH', 'www.lufthansa.com'),
('Air France', 'AF', 'www.airfrance.com');

INSERT INTO airports (airport_code, airport_name, city, country, timezone) VALUES
('LHR', 'London Heathrow', 'London', 'UK', 'UTC+0'),
('JFK', 'John F Kennedy', 'New York', 'USA', 'UTC-5'),
('CDG', 'Charles de Gaulle', 'Paris', 'France', 'UTC+1'),
('DXB', 'Dubai International', 'Dubai', 'UAE', 'UTC+4');

INSERT INTO flights (flight_number, airline_id, departure_airport, arrival_airport, departure_time, arrival_time, aircraft_type, total_seats, available_seats, price, duration, stops) VALUES
('BA112', 1, 'LHR', 'JFK', '2025-11-15 10:00:00', '2025-11-15 13:00:00', 'Boeing 787', 300, 50, 450.00, 480, 0),
('EK210', 2, 'DXB', 'LHR', '2025-11-16 14:00:00', '2025-11-16 18:30:00', 'Boeing 777', 350, 120, 350.00, 270, 0),
('LH401', 3, 'CDG', 'JFK', '2025-11-17 08:00:00', '2025-11-17 12:00:00', 'Airbus A380', 500, 200, 500.00, 480, 0);

