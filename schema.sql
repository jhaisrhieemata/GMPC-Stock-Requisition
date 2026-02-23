-- GMPC Stock Requisition System Database Schema
-- For XAMPP MySQL

CREATE DATABASE IF NOT EXISTS gmpc_requisition;
USE gmpc_requisition;

-- ============================================
-- USERS TABLE
-- ============================================
DROP TABLE IF EXISTS users;
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'user',
    branch_id INT DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    password_reset_requested TINYINT(1) DEFAULT 0,
    password_reset_date TIMESTAMP NULL,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================
-- BRANCHES TABLE
-- ============================================
DROP TABLE IF EXISTS branches;
CREATE TABLE branches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) UNIQUE NOT NULL,
    address TEXT,
    contact_number VARCHAR(20),
    email VARCHAR(255),
    classification VARCHAR(100),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================
-- SUPPLIERS TABLE
-- ============================================
DROP TABLE IF EXISTS suppliers;
CREATE TABLE suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    contact_person VARCHAR(255) DEFAULT NULL,
    phone VARCHAR(50) DEFAULT NULL,
    email VARCHAR(255) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    classification ENUM('Multibrand', 'Yamaha 3S Only', 'General Supplies', 'Office Supplies') DEFAULT 'General Supplies',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================
-- INVENTORY TABLE
-- ============================================
DROP TABLE IF EXISTS inventory;
CREATE TABLE inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id VARCHAR(50) UNIQUE NOT NULL,
    supplier_id INT NOT NULL,
    date DATE NOT NULL,
    description VARCHAR(500) NOT NULL,
    unit VARCHAR(50) NOT NULL,
    qty INT NOT NULL DEFAULT 0,
    unit_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_running_stocks INT NOT NULL DEFAULT 0,
    status ENUM('In Stock', 'Low Stock', 'Out of Stock', 'Critical') DEFAULT 'In Stock',
    classification ENUM('Multibrand', 'Yamaha 3S Only', 'General Supplies', 'Office Supplies') DEFAULT 'General Supplies',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE
);

-- ============================================
-- REQUISITIONS TABLE
-- ============================================
DROP TABLE IF EXISTS requisitions;
CREATE TABLE requisitions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_number VARCHAR(255) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    branch_id INT DEFAULT NULL,
    type VARCHAR(50),
    status VARCHAR(50) DEFAULT 'pending',
    total_amount DECIMAL(12,2) DEFAULT 0,
    signature_data LONGTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
);

-- ============================================
-- REQUISITION ITEMS TABLE
-- ============================================
DROP TABLE IF EXISTS requisition_items;
CREATE TABLE requisition_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    requisition_id INT NOT NULL,
    item_id INT DEFAULT NULL,
    description VARCHAR(500) NOT NULL,
    qty INT NOT NULL DEFAULT 0,
    unit VARCHAR(50) NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    status VARCHAR(50) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (requisition_id) REFERENCES requisitions(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES inventory(id) ON DELETE SET NULL
);

-- ============================================
-- SPECIAL REQUESTS TABLE
-- ============================================
DROP TABLE IF EXISTS special_requests;
CREATE TABLE special_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_code VARCHAR(50) UNIQUE NOT NULL,
    branch_id INT NOT NULL,
    description VARCHAR(500) NOT NULL,
    qty INT NOT NULL DEFAULT 1,
    unit VARCHAR(50) NOT NULL,
    estimated_price DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(10,2) DEFAULT 0,
    purpose TEXT DEFAULT NULL,
    status ENUM('Pending', 'Approved By Purchasing', 'Approved By Accounting', 'Petty Cash By Branch', 'Cancelled', 'To Purchased') DEFAULT 'Pending',
    requested_by VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE
);

-- ============================================
-- SAMPLE DATA - USERS (password: password123)
-- ============================================
INSERT INTO users (email, password, full_name, role, branch_id) VALUES 
('admin@gmpc.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Admin', 'admin', NULL),
('lagtang@gmpc.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'LAGTANG Manager', 'user', 1),
('bacayan@gmpc.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'BACAYAN Manager', 'user', 2),
('toledo@gmpc.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'TOLEDO Manager', 'user', 3),
('minglanilla@gmpc.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'MINGLANILLA Manager', 'user', 4),
('bogo@gmpc.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'BOGO Manager', 'user', 5);

-- ============================================
-- SAMPLE DATA - BRANCHES
-- ============================================
INSERT INTO branches (name, email, classification) VALUES 
('ADMIN', 'gmpcpurchasing@gmail.com', 'Multibrand'),
('WAREHOUSE', 'warehousegiant21@gmail.com', 'Multibrand'),
('LAGTANG', 'giantmotoprolagtang@gmail.com', 'Multibrand'),
('V-RAMA', 'gmpcguad_accounting@yahoo.com.ph', 'Multibrand'),
('BULACAO', 'ivyhamistoso@gmail.com', 'Multibrand'),
('Y3S TALISAY', 'gmpcyamaha3st@gmail.com', 'Yamaha 3S'),
('MINGLANILLA', 'giantmotoprolinao@gmail.com', 'Multibrand'),
('Y3S PARDO', 'gmpcy3spardo@yahoo.com', 'Yamaha 3S'),
('LAPU-LAPU', 'giantmotoproopon19@gmail.com', 'Multibrand'),
('BACAYAN', 'gmpcbacayan@gmail.com', 'Multibrand'),
('SAN FERNANDO MB', 'gmpcsanfernando@gmail.com', 'Multibrand'),
('Y3S SAN FERNANDO', 'sanfernandoyamaha3s@gmail.com', 'Yamaha 3S'),
('LILOAN', 'gmpctayud@gmail.com', 'Multibrand'),
('CORDOVA MB', 'gmpccordova@gmail.com', 'Multibrand'),
('Y3S CORDOVA', 'gmpccordovay3s@gmail.com', 'Yamaha 3S'),
('CLARIN', 'gmpcclarin@gmail.com', 'Multibrand'),
('TOLEDO', 'giantmotopro_corporation@yahoo.com.ph', 'Multibrand'),
('UBAY', 'gmpcubay@gmail.com', 'Multibrand'),
('CARMEN', 'gmpccarmenbh@gmail.com', 'Multibrand'),
('TUBIGON', 'gmpctubigon@gmail.com', 'Multibrand'),
('TALIBON', 'gmpctalibon@gmail.com', 'Multibrand'),
('BOGO', 'gmpcbogo@gmail.com', 'Multibrand'),
('BALAMBAN', 'gmpcbalamban@gmail.com', 'Multibrand'),
('Y3S BARILI', 'yamaha3sbarili@gmail.com', 'Yamaha 3S'),
('BARILI MB', 'giantmotoprobarilibranch@gmail.com', 'Multibrand'),
('SIERRA BULLONES', 'gmpcsierra@gmail.com', 'Multibrand'),
('PITOGO', 'gmpcpitogo@gmail.com', 'Multibrand'),
('TAYUD CONSOLACION', 'gmpctayudconsolacion@gmail.com', 'Multibrand'),
('PINAMUNGAJAN', 'gmpcpinamungajan22@gmail.com', 'Multibrand'),
('CANDIJAY', 'giantcandijay@gmail.com', 'Multibrand'),
('YATI LILOAN', 'gmpcyatililoan@gmail.com', 'Multibrand');

-- ============================================
-- SAMPLE DATA - SUPPLIERS
-- ============================================
INSERT INTO suppliers (name, contact_person, phone, email, classification) VALUES 
('Office Depot', 'John Smith', '09171234567', 'sales@officedepot.com', 'Office Supplies'),
('National Bookstore', 'Maria Garcia', '09181234567', 'wholesale@nationalbookstore.com', 'General Supplies'),
('CDR King', 'Pedro Santos', '09191234567', 'business@cdrking.com', 'Multibrand'),
('Yamaha Parts Center', 'Ana Reyes', '09201234567', 'parts@yamaha.com', 'Yamaha 3S Only');

-- ============================================
-- SAMPLE DATA - INVENTORY
-- ============================================
INSERT INTO inventory (item_id, supplier_id, date, description, unit, qty, unit_price, amount, total_running_stocks, status, classification) VALUES 
('OFD-0001', 1, '2024-01-15', 'Bond Paper A4 (500 sheets)', 'REAM', 50, 250.00, 12500.00, 150, 'In Stock', 'Office Supplies'),
('OFD-0002', 1, '2024-01-15', 'Ballpen Black (12 pcs)', 'BOX', 20, 120.00, 2400.00, 45, 'In Stock', 'Office Supplies'),
('NAT-0001', 2, '2024-01-16', 'Stapler #35', 'PC', 10, 350.00, 3500.00, 8, 'Low Stock', 'General Supplies'),
('NAT-0002', 2, '2024-01-16', 'Staple Wire #35', 'BOX', 30, 85.00, 2550.00, 25, 'In Stock', 'General Supplies'),
('CDR-0001', 3, '2024-01-17', 'Yellow Pad 1/2', 'PAD', 50, 45.00, 2250.00, 0, 'Out of Stock', 'Multibrand'),
('CDR-0002', 3, '2024-01-17', 'Correction Tape', 'PC', 40, 65.00, 2600.00, 30, 'In Stock', 'Multibrand'),
('OFD-0003', 1, '2024-01-18', 'Folder Long Brown', 'PC', 100, 15.00, 1500.00, 200, 'In Stock', 'Office Supplies'),
('NAT-0003', 2, '2024-01-18', 'Marker Permanent Black', 'PC', 24, 45.00, 1080.00, 3, 'Critical', 'General Supplies'),
('CDR-0003', 3, '2024-01-19', 'Scissors 8"', 'PC', 10, 120.00, 1200.00, 5, 'Low Stock', 'Multibrand'),
('OFD-0004', 1, '2024-01-19', 'Masking Tape 1"', 'ROLL', 50, 55.00, 2750.00, 40, 'In Stock', 'Office Supplies'),
('YAM-0001', 4, '2024-01-20', 'Yamaha Oil 1L', 'BTL', 100, 450.00, 45000.00, 80, 'In Stock', 'Yamaha 3S Only'),
('NAT-0004', 2, '2024-01-21', 'Plastic Ring Binder', 'PC', 25, 85.00, 2125.00, 50, 'In Stock', 'General Supplies'),
('OFD-0005', 1, '2024-01-22', 'White Board Marker', 'PC', 30, 55.00, 1650.00, 15, 'Low Stock', 'Office Supplies'),
('CDR-0004', 3, '2024-01-23', 'Glue Stick', 'PC', 40, 35.00, 1400.00, 60, 'In Stock', 'Multibrand'),
('YAM-0002', 4, '2024-01-24', 'Yamaha Filter Oil', 'BTL', 50, 180.00, 9000.00, 35, 'In Stock', 'Yamaha 3S Only');

-- ============================================
-- INDEXES FOR PERFORMANCE
-- ============================================
CREATE INDEX idx_inventory_supplier ON inventory(supplier_id);
CREATE INDEX idx_inventory_status ON inventory(status);
CREATE INDEX idx_requisitions_user ON requisitions(user_id);
CREATE INDEX idx_requisitions_branch ON requisitions(branch_id);
CREATE INDEX idx_requisitions_status ON requisitions(status);
CREATE INDEX idx_requisition_items_requisition ON requisition_items(requisition_id);
CREATE INDEX idx_special_requests_branch ON special_requests(branch_id);
CREATE INDEX idx_special_requests_status ON special_requests(status);
