-- DriveEasy Car Rental Management System
-- Database Schema

CREATE DATABASE IF NOT EXISTS driveeasy;
USE driveeasy;

-- Users table
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Cars table
CREATE TABLE cars (
    car_id INT AUTO_INCREMENT PRIMARY KEY,
    car_name VARCHAR(100) NOT NULL,
    brand VARCHAR(50) NOT NULL,
    model VARCHAR(50) NOT NULL,
    year INT NOT NULL,
    price_per_day DECIMAL(10,2) NOT NULL,
    fuel_type VARCHAR(20) NOT NULL,
    seats INT NOT NULL,
    transmission VARCHAR(20) NOT NULL,
    image VARCHAR(255) DEFAULT 'default-car.jpg',
    availability TINYINT(1) DEFAULT 1,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bookings table
CREATE TABLE bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    car_id INT NOT NULL,
    pickup_date DATE NOT NULL,
    return_date DATE NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'completed', 'cancelled') DEFAULT 'pending',
    pickup_location VARCHAR(255) DEFAULT 'Main Office',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (car_id) REFERENCES cars(car_id) ON DELETE CASCADE
);

-- Payments table
CREATE TABLE payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    transaction_id VARCHAR(100),
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE
);

-- Admins table
CREATE TABLE admins (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Reviews table
CREATE TABLE reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    car_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (car_id) REFERENCES cars(car_id) ON DELETE CASCADE
);

-- Contact messages table
CREATE TABLE contacts (
    contact_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(200),
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin
INSERT INTO admins (username, password, email) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@driveeasy.com');

-- Insert sample cars
INSERT INTO cars (car_name, brand, model, year, price_per_day, fuel_type, seats, transmission, image, availability, description) VALUES
('Toyota Camry', 'Toyota', 'Camry', 2024, 50.00, 'Petrol', 5, 'Automatic', 'camry.jpg', 1, 'A comfortable and reliable sedan perfect for business trips and family outings.'),
('Honda Civic', 'Honda', 'Civic', 2024, 45.00, 'Petrol', 5, 'Automatic', 'civic.jpg', 1, 'Sporty and fuel-efficient compact car with modern features.'),
('BMW X5', 'BMW', 'X5', 2024, 120.00, 'Diesel', 5, 'Automatic', 'bmwx5.jpg', 1, 'Luxury SUV with premium comfort and powerful performance.'),
('Mercedes C-Class', 'Mercedes', 'C-Class', 2023, 100.00, 'Petrol', 5, 'Automatic', 'mercedes.jpg', 1, 'Elegant luxury sedan with cutting-edge technology.'),
('Ford Mustang', 'Ford', 'Mustang', 2024, 150.00, 'Petrol', 4, 'Manual', 'mustang.jpg', 1, 'Iconic American muscle car with thrilling performance.'),
('Toyota RAV4', 'Toyota', 'RAV4', 2024, 65.00, 'Hybrid', 5, 'Automatic', 'rav4.jpg', 1, 'Popular compact SUV with excellent fuel economy.'),
('Audi A4', 'Audi', 'A4', 2024, 90.00, 'Petrol', 5, 'Automatic', 'audia4.jpg', 1, 'German engineering at its finest - luxury and performance combined.'),
('Nissan Altima', 'Nissan', 'Altima', 2023, 40.00, 'Petrol', 5, 'CVT', 'altima.jpg', 1, 'Affordable and reliable mid-size sedan for daily commutes.'),
('Range Rover Sport', 'Land Rover', 'Range Rover Sport', 2024, 200.00, 'Diesel', 5, 'Automatic', 'rangerover.jpg', 1, 'Ultimate luxury SUV with off-road capability and prestige.'),
('Hyundai Tucson', 'Hyundai', 'Tucson', 2024, 55.00, 'Diesel', 5, 'Automatic', 'tucson.jpg', 1, 'Stylish and feature-packed compact SUV.'),
('Tesla Model 3', 'Tesla', 'Model 3', 2024, 130.00, 'Electric', 5, 'Automatic', 'tesla3.jpg', 1, 'Premium electric sedan with autopilot and zero emissions.'),
('Chevrolet Suburban', 'Chevrolet', 'Suburban', 2023, 180.00, 'Petrol', 8, 'Automatic', 'suburban.jpg', 1, 'Full-size SUV perfect for large groups and road trips.');
