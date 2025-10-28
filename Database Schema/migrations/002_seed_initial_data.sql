-- ===========================
-- SEED DATA
-- ===========================

-- Insert Airlines
INSERT INTO airlines (airline_name, airline_code, website, phone, email, country, commission_rate) VALUES
('British Airways', 'BA', 'www.britishairways.com', '+44 344 222 1111', 'contact@ba.com', 'United Kingdom', 5.00),
('Air France', 'AF', 'www.airfrance.com', '+33 892 802 802', 'contact@airfrance.com', 'France', 5.00),
('Lufthansa', 'LH', 'www.lufthansa.com', '+49 69 86799799', 'contact@lufthansa.com', 'Germany', 5.00),
('KLM Royal Dutch Airlines', 'KL', 'www.klm.com', '+31 20 474 7747', 'contact@klm.com', 'Netherlands', 5.00),
('Emirates', 'EK', 'www.emirates.com', '+971 4 2444444', 'contact@emirates.com', 'United Arab Emirates', 5.00),
('Singapore Airlines', 'SQ', 'www.singaporeair.com', '+65 6223 8888', 'contact@sia.com', 'Singapore', 5.00);

-- Insert Airports
INSERT INTO airports (airport_code, airport_name, city, country, timezone) VALUES
('LHR', 'London Heathrow', 'London', 'United Kingdom', 'GMT'),
('CDG', 'Charles de Gaulle', 'Paris', 'France', 'CET'),
('AMS', 'Amsterdam Airport Schiphol', 'Amsterdam', 'Netherlands', 'CET'),
('FRA', 'Frankfurt am Main', 'Frankfurt', 'Germany', 'CET'),
('JFK', 'John F. Kennedy International', 'New York', 'United States', 'EST'),
('LAX', 'Los Angeles International', 'Los Angeles', 'United States', 'PST'),
('NRT', 'Narita International', 'Tokyo', 'Japan', 'JST'),
('DXB', 'Dubai International', 'Dubai', 'United Arab Emirates', 'GST'),
('SIN', 'Singapore Changi', 'Singapore', 'Singapore', 'SGT'),
('HND', 'Haneda International', 'Tokyo', 'Japan', 'JST');

-- Insert Aircraft
INSERT INTO aircraft (aircraft_type, manufacturer, economy_seats, premium_seats, business_seats, first_seats) VALUES
('Boeing 737', 'Boeing', 150, 20, 8, 2),
('Boeing 777', 'Boeing', 300, 40, 12, 8),
('Airbus A380', 'Airbus', 400, 60, 16, 14),
('Airbus A320', 'Airbus', 180, 25, 10, 4),
('Boeing 787', 'Boeing', 242, 35, 21, 8),
('Airbus A350', 'Airbus', 300, 50, 24, 14);

-- Insert Admin User

-- Insert Admin User
INSERT INTO admin_users (username, email, password, full_name, role, is_active) VALUES
('admin', 'admin@skybirdtravel.com', '$2y$10$YIjlrJyEvLayvYRLUVQvO.ErKZH8F7Z8qX5Z5V5V5V5V5V5V', 'System Administrator', 'super_admin', TRUE),
('moderator', 'moderator@skybirdtravel.com', '$2y$10$YIjlrJyEvLayvYRLUVQvO.ErKZH8F7Z8qX5Z5V5V5V5V5V5V', 'Site Moderator', 'moderator', TRUE),
('support', 'support@skybirdtravel.com', '$2y$10$YIjlrJyEvLayvYRLUVQvO.ErKZH8F7Z8qX5Z5V5V5V5V5V5V', 'Support Team', 'support', TRUE);

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
('car_commission', '6.00', 'decimal', 'Default car commission percentage'),
('loyalty_enabled', '1', 'boolean', 'Enable loyalty program'),
('maintenance_mode', '0', 'boolean', 'Enable maintenance mode'),
('max_booking_days', '365', 'integer', 'Maximum days for advance booking'),
('booking_cancellation_days', '7', 'integer', 'Days allowed for free cancellation'),
('tax_rate', '10', 'decimal', 'Default tax rate percentage'),
('phone_verification_required', '0', 'boolean', 'Require phone verification'),
('email_verification_required', '1', 'boolean', 'Require email verification');

-- Insert Sample Users
INSERT INTO users (first_name, last_name, email, password, phone, country, is_active, email_verified) VALUES
('John', 'Doe', 'john@example.com', '$2y$10$YIjlrJyEvLayvYRLUVQvO.ErKZH8F7Z8qX5Z5V5V5V5V5V5V', '+1-555-0101', 'United States', TRUE, TRUE),
('Jane', 'Smith', 'jane@example.com', '$2y$10$YIjlrJyEvLayvYRLUVQvO.ErKZH8F7Z8qX5Z5V5V5V5V5V5V', '+1-555-0102', 'United Kingdom', TRUE, TRUE),
('Robert', 'Johnson', 'robert@example.com', '$2y$10$YIjlrJyEvLayvYRLUVQvO.ErKZH8F7Z8qX5Z5V5V5V5V5V5V', '+1-555-0103', 'Canada', TRUE, TRUE),
('Maria', 'Garcia', 'maria@example.com', '$2y$10$YIjlrJyEvLayvYRLUVQvO.ErKZH8F7Z8qX5Z5V5V5V5V5V5V', '+34-555-0104', 'Spain', TRUE, TRUE),
('Ahmed', 'Hassan', 'ahmed@example.com', '$2y$10$YIjlrJyEvLayvYRLUVQvO.ErKZH8F7Z8qX5Z5V5V5V5V5V5V', '+966-555-0105', 'Saudi Arabia', TRUE, TRUE);

-- Insert Sample Flights
INSERT INTO flights (flight_number, airline_id, aircraft_id, departure_airport_id, arrival_airport_id, departure_time, arrival_time, duration_minutes, status, distance_km) VALUES
(1, 1, 1, 1, 5, '2025-10-25 08:00:00', '2025-10-25 12:30:00', 510, 'scheduled', 5500),
(2, 2, 2, 1, 2, '2025-10-26 10:30:00', '2025-10-26 15:00:00', 270, 'scheduled', 345),
(3, 3, 3, 4, 6, '2025-10-27 14:00:00', '2025-10-27 22:45:00', 530, 'scheduled', 8600),
(4, 4, 4, 2, 1, '2025-10-28 09:15:00', '2025-10-28 13:45:00', 270, 'scheduled', 345),
(5, 5, 5, 8, 1, '2025-10-29 16:00:00', '2025-10-30 06:30:00', 630, 'scheduled', 7000);

-- Insert Flight Prices (BA 294 - LHR to JFK)
INSERT INTO flight_prices (flight_id, cabin_class, base_price, taxes, fees, total_price, available_seats, booked_seats) VALUES
(1, 'economy', 450, 45, 20, 515, 150, 0),
(1, 'premium', 520, 52, 30, 602, 20, 0),
(1, 'business', 850, 85, 50, 985, 8, 0),
(1, 'first', 1200, 120, 80, 1400, 2, 0);

-- Insert Flight Prices (AF 105 - LHR to CDG)
INSERT INTO flight_prices (flight_id, cabin_class, base_price, taxes, fees, total_price, available_seats, booked_seats) VALUES
(2, 'economy', 320, 32, 15, 367, 180, 0),
(2, 'premium', 380, 38, 20, 438, 25, 0),
(2, 'business', 620, 62, 35, 717, 10, 0),
(2, 'first', 950, 95, 55, 1100, 4, 0);

-- Insert Flight Prices (LH 201 - FRA to LAX)
INSERT INTO flight_prices (flight_id, cabin_class, base_price, taxes, fees, total_price, available_seats, booked_seats) VALUES
(3, 'economy', 410, 41, 18, 469, 400, 0),
(3, 'premium', 480, 48, 25, 553, 60, 0),
(3, 'business', 780, 78, 45, 903, 16, 0),
(3, 'first', 1100, 110, 70, 1280, 14, 0);

-- Insert Flight Prices (KL 456 - CDG to LHR)
INSERT INTO flight_prices (flight_id, cabin_class, base_price, taxes, fees, total_price, available_seats, booked_seats) VALUES
(4, 'economy', 320, 32, 15, 367, 180, 0),
(4, 'premium', 380, 38, 20, 438, 25, 0),
(4, 'business', 620, 62, 35, 717, 10, 0),
(4, 'first', 950, 95, 55, 1100, 4, 0);

-- Insert Flight Prices (EK 999 - DXB to LHR)
INSERT INTO flight_prices (flight_id, cabin_class, base_price, taxes, fees, total_price, available_seats, booked_seats) VALUES
(5, 'economy', 380, 38, 18, 436, 242, 0),
(5, 'premium', 450, 45, 25, 520, 35, 0),
(5, 'business', 750, 75, 45, 870, 21, 0),
(5, 'first', 1050, 105, 65, 1220, 8, 0);

-- Insert Sample Hotels
INSERT INTO hotels (hotel_name, city, country, address, phone, email, website, rating, review_count, total_rooms, available_rooms, commission_rate) VALUES
('The Ritz London', 'London', 'United Kingdom', '150 Piccadilly, London W1J 9BR', '+44 20 7493 8181', 'info@theritzlondon.com', 'www.theritzlondon.com', 4.9, 2540, 136, 136, 8.00),
('Parisian Palace', 'Paris', 'France', '10 Avenue Montaigne, 75008 Paris', '+33 1 49 52 70 00', 'contact@parisianpalace.com', 'www.parisianpalace.com', 4.8, 3120, 200, 185, 8.00),
('Amsterdam Grand Hotel', 'Amsterdam', 'Netherlands', 'Herengracht 101, Amsterdam', '+31 20 552 3100', 'hello@amsterdamgrand.com', 'www.amsterdamgrand.com', 4.7, 2890, 150, 140, 8.00),
('Frankfurt Executive', 'Frankfurt', 'Germany', 'Mainzer Landstr. 84, 60311 Frankfurt', '+49 69 133 80', 'info@frankfurtexec.de', 'www.frankfurtexec.de', 4.6, 1950, 200, 180, 8.00),
('New York Plaza', 'New York', 'United States', '768 Fifth Avenue, New York, NY 10019', '+1 212 759 3000', 'contact@nycplaza.com', 'www.nycplaza.com', 4.8, 4120, 435, 410, 8.00),
('Los Angeles Luxury', 'Los Angeles', 'United States', '9876 Wilshire Boulevard, Beverly Hills', '+1 310 275 5200', 'info@laluxury.com', 'www.laluxury.com', 4.7, 3450, 300, 285, 8.00);

-- Insert Hotel Rooms
INSERT INTO hotel_rooms (hotel_id, room_type, price_per_night, capacity, available_count, bed_type, amenities) VALUES
(1, 'Standard Room', 350, 2, 30, 'Double Bed', 'WiFi, TV, Air Conditioning, Mini Bar'),
(1, 'Deluxe Room', 550, 2, 25, 'King Bed', 'WiFi, TV, Air Conditioning, Mini Bar, Bathrobe'),
(1, 'Suite', 950, 4, 15, 'King Bed + Sofa', 'WiFi, TV, Air Conditioning, Mini Bar, Living Area, Marble Bathroom'),
(2, 'Standard Room', 320, 2, 40, 'Double Bed', 'WiFi, TV, Air Conditioning'),
(2, 'Deluxe Room', 520, 2, 35, 'King Bed', 'WiFi, TV, Air Conditioning, Balcony'),
(2, 'Suite', 880, 4, 20, 'King Bed + Sofa', 'WiFi, TV, Air Conditioning, Living Area, Spa Bath'),
(3, 'Standard Room', 280, 2, 35, 'Double Bed', 'WiFi, TV, Air Conditioning, Canal View'),
(3, 'Deluxe Room', 450, 2, 30, 'King Bed', 'WiFi, TV, Air Conditioning, Balcony, Canal View'),
(3, 'Suite', 750, 4, 18, 'King Bed + Sofa', 'WiFi, TV, Air Conditioning, Living Area, Jacuzzi, Canal View'),
(4, 'Standard Room', 250, 2, 45, 'Double Bed', 'WiFi, TV, Air Conditioning'),
(4, 'Deluxe Room', 420, 2, 40, 'King Bed', 'WiFi, TV, Air Conditioning, City View'),
(4, 'Suite', 700, 4, 22, 'King Bed + Sofa', 'WiFi, TV, Air Conditioning, Living Area, City View');

-- Insert Sample Cars
INSERT INTO cars (car_rental_company, car_make, car_model, car_type, year, transmission, fuel_type, passengers_capacity, luggage_capacity, daily_rate, weekly_rate, monthly_rate, total_available, available_count, commission_rate) VALUES
('Skybird Rentals', 'Toyota', 'Corolla', 'economy', 2024, 'automatic', 'petrol', 5, 3, 35, 210, 700, 50, 50, 6.00),
('Skybird Rentals', 'Honda', 'Civic', 'compact', 2024, 'automatic', 'petrol', 5, 3, 40, 240, 800, 45, 45, 6.00),
('Skybird Rentals', 'BMW', '3 Series', 'sedan', 2024, 'automatic', 'diesel', 5, 4, 75, 450, 1500, 30, 28, 6.00),
('Skybird Rentals', 'Toyota', 'Highlander', 'suv', 2024, 'automatic', 'hybrid', 7, 5, 90, 540, 1800, 25, 22, 6.00),
('Skybird Rentals', 'Mercedes', 'E-Class', 'luxury', 2024, 'automatic', 'diesel', 5, 4, 150, 900, 3000, 15, 12, 6.00),
('Skybird Rentals', 'Ford', 'Transit', 'van', 2024, 'manual', 'diesel', 9, 8, 85, 510, 1700, 10, 9, 6.00),
('Skybird Rentals', 'Hyundai', 'i10', 'economy', 2024, 'manual', 'petrol', 5, 2, 30, 180, 600, 60, 60, 6.00),
('Skybird Rentals', 'Volkswagen', 'Golf', 'compact', 2024, 'automatic', 'petrol', 5, 3, 45, 270, 900, 40, 38, 6.00);

-- Insert Promo Codes
INSERT INTO promo_codes (code, description, discount_type, discount_value, max_discount, min_booking_amount, max_usage, current_usage, valid_from, valid_until, is_active) VALUES
('WELCOME10', 'Welcome bonus - 10% off', 'percentage', 10, 100, 100, 1000, 245, '2025-01-01', '2025-12-31', TRUE),
('SAVE50', 'Save 50 USD on bookings', 'fixed', 50, 50, 200, 500, 189, '2025-01-01', '2025-12-31', TRUE),
('STUDENT15', 'Student discount - 15% off', 'percentage', 15, 150, 150, 300, 67, '2025-01-01', '2025-12-31', TRUE),
('FAMILY20', 'Family package - 20% off', 'percentage', 20, 200, 300, 200, 45, '2025-01-01', '2025-12-31', TRUE),
('EARLYBIRD', 'Early booking - 25% off', 'percentage', 25, 300, 250, 150, 32, '2025-01-01', '2025-12-31', TRUE),
('LOYALTY5', 'Loyalty member - 5% discount', 'percentage', 5, 50, 100, 500, 123, '2025-01-01', '2025-12-31', TRUE);

-- Create Loyalty Members for Sample Users
INSERT INTO loyalty_members (user_id, member_level, total_points, lifetime_bookings, lifetime_spending, joined_date) VALUES
(1, 'gold', 2500, 8, 5400.50, '2024-03-15'),
(2, 'silver', 1200, 4, 2100.75, '2024-06-20'),
(3, 'platinum', 5000, 15, 12300.00, '2023-11-10'),
(4, 'bronze', 300, 1, 515.00, '2025-08-05'),
(5, 'silver', 1500, 5, 3200.25, '2024-04-12');

-- Create Sample Booking (for testing)
INSERT INTO bookings (user_id, flight_id, booking_reference, cabin_class, number_of_passengers, total_price, booking_status, created_at) VALUES
(1, 1, 'SK20251001', 'economy', 2, 1030, 'confirmed', NOW());

-- Create Sample Passengers
INSERT INTO passengers (booking_id, first_name, last_name, email, phone, date_of_birth, passport_number, seat_number, meal_preference) VALUES
(1, 'John', 'Doe', 'john@example.com', '+1-555-0101', '1980-05-15', 'AB123456', '1A', 'Vegetarian'),
(1, 'Sarah', 'Doe', 'sarah@example.com', '+1-555-0101', '1982-08-20', 'CD654321', '1B', 'Standard');

-- Create Sample Payment
INSERT INTO payments (booking_id, amount, currency, payment_method, transaction_id, payment_status, payment_gateway, created_at) VALUES
(1, 1030, 'USD', 'credit_card', 'TXN20251001001', 'completed', 'Stripe', NOW());

-- Create Sample Hotel Booking
INSERT INTO hotel_bookings (user_id, hotel_id, room_id, booking_reference, check_in_date, check_out_date, number_of_nights, number_of_guests, total_price, booking_status) VALUES
(1, 1, 1, 'HB20251001', '2025-10-25', '2025-10-28', 3, 2, 1050, 'confirmed');

-- Create Sample Car Booking
INSERT INTO car_bookings (user_id, car_id, booking_reference, pickup_location, dropoff_location, pickup_date, dropoff_date, number_of_days, total_price, booking_status) VALUES
(1, 1, 'CB20251001', 'London Heathrow', 'London City', '2025-10-25', '2025-10-28', 3, 105, 'confirmed');

-- Insert Sample Reviews
INSERT INTO reviews (user_id, booking_id, item_type, item_id, rating, review_text, verified_purchase, status) VALUES
(1, 1, 'flight', 1, 5, 'Excellent flight experience! Professional crew and on-time arrival.', TRUE, 'approved'),
(2, NULL, 'hotel', 1, 4, 'Great location and comfortable rooms. Service could be improved.', FALSE, 'approved'),
(3, NULL, 'car', 1, 5, 'Smooth rental process and well-maintained vehicle. Highly recommended!', FALSE, 'approved'),
(4, NULL, 'flight', 2, 4, 'Good flight, but seat comfort could be better in economy.', FALSE, 'approved'),
(5, NULL, 'hotel', 2, 5, 'Amazing Parisian experience! Recommend to everyone.', FALSE, 'approved');

-- Create Sample Support Ticket
INSERT INTO support_tickets (ticket_number, user_id, subject, description, category, priority, status) VALUES
('TK-2025-001', 1, 'Booking Issue', 'I am unable to complete my booking', 'booking', 'medium', 'open');

-- Insert Ticket Message
INSERT INTO ticket_messages (ticket_id, sender_id, sender_type, message) VALUES
(1, 1, 'user', 'I am having trouble with the payment processing on your website.');

-- Insert Activity Logs
INSERT INTO activity_logs (user_id, activity_type, description, ip_address) VALUES
(1, 'login', 'User logged in successfully', '192.168.1.1'),
(1, 'booking_created', 'Flight booking SK20251001 created', '192.168.1.1'),
(1, 'payment_processed', 'Payment of 1030 USD completed', '192.168.1.1'),
(2, 'login', 'User logged in successfully', '192.168.1.2'),
(3, 'review_submitted', 'Review submitted for flight', '192.168.1.3');