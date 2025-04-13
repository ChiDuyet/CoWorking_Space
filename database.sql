CREATE DATABASE IF NOT EXISTS coworking;
USE coworking;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    role ENUM('admin', 'receptionist', 'customer') NOT NULL
);

CREATE TABLE floors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    floor_number INT,
    image_path VARCHAR(255)
);

CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    floor_id INT,
    seats INT,
    projector BOOLEAN DEFAULT 0,
    tv BOOLEAN DEFAULT 0,
    mic BOOLEAN DEFAULT 0,
    FOREIGN KEY (floor_id) REFERENCES floors(id)
);

CREATE TABLE seats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT,
    seat_number VARCHAR(10),
    FOREIGN KEY (room_id) REFERENCES rooms(id)
);

CREATE TABLE bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer VARCHAR(255),
  room_id INT,
  seat_number INT,
  date DATE,
  start_time TIME,
  end_time TIME,
  payment_method VARCHAR(50),
  status VARCHAR(50) DEFAULT 'pending'
);

CREATE TABLE notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user VARCHAR(255),
  content TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT,
    customer VARCHAR(100),
    description TEXT,
    amount INT,
    created_at DATETIME
);

ALTER TABLE bookings ADD COLUMN price INT DEFAULT 0;
