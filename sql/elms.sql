CREATE DATABASE elms;
USE elms;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('employee', 'manager', 'admin') DEFAULT 'employee',
    department VARCHAR(100),
    position VARCHAR(100),
    leave_balance INT DEFAULT 20,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE leave_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    max_days INT NOT NULL,
    can_carry_over BOOLEAN DEFAULT FALSE,
    carry_over_limit INT DEFAULT 0
);

CREATE TABLE leave_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    leave_type_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    manager_id INT,
    comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES users(id),
    FOREIGN KEY (leave_type_id) REFERENCES leave_types(id),
    FOREIGN KEY (manager_id) REFERENCES users(id)
);

CREATE TABLE leave_balances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    leave_type_id INT NOT NULL,
    balance INT NOT NULL,
    year YEAR NOT NULL,
    FOREIGN KEY (employee_id) REFERENCES users(id),
    FOREIGN KEY (leave_type_id) REFERENCES leave_types(id)
);

INSERT INTO leave_types (name, description, max_days, can_carry_over, carry_over_limit) VALUES
('Annual Leave', 'Paid time off work', 20, TRUE, 5),
('Sick Leave', 'Leave for health reasons', 10, FALSE, 0),
('Maternity Leave', 'Leave for new mothers', 90, FALSE, 0),
('Paternity Leave', 'Leave for new fathers', 10, FALSE, 0),
('Emergency Leave', 'Leave for urgent matters', 5, FALSE, 0);

INSERT INTO users (employee_id, first_name, last_name, email, password, role, department, position, leave_balance) VALUES
('SFX001', 'Admin', 'User', 'admin@sfxhospital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'HR', 'HR Manager', 20),
('SFX002', 'John', 'Manager', 'manager@sfxhospital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager', 'Cardiology', 'Department Head', 20),
('SFX003', 'Jane', 'Doe', 'employee@sfxhospital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', 'Nursing', 'Senior Nurse', 20);