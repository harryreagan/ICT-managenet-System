-- ============================================================================
-- Dallas Premiere Hotel - ICT Management System
-- Complete Database Script
-- ============================================================================
-- Version: 3.0
-- Date: 2026-02-12
-- Description: Single consolidated SQL file for easy database deployment
-- Features: Full schema + Initial seed data
-- ============================================================================

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS hotel_ict;
USE hotel_ict;

-- ============================================================================
-- CORE TABLES
-- ============================================================================

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    email VARCHAR(100),
    department VARCHAR(100),
    role ENUM('admin', 'technician', 'viewer', 'staff') DEFAULT 'staff',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Vendors
CREATE TABLE IF NOT EXISTS vendors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    service_type VARCHAR(100),
    contact_person VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    sla_notes TEXT,
    last_service_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Renewals & Subscriptions
CREATE TABLE IF NOT EXISTS renewals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(100) NOT NULL,
    vendor_id INT,
    contact_details VARCHAR(255),
    amount_paid DECIMAL(10, 2),
    renewal_date DATE,
    status ENUM('active', 'expired', 'cancelled') DEFAULT 'active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE SET NULL
);

-- Floor Management
CREATE TABLE IF NOT EXISTS floors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    floor_number INT NOT NULL UNIQUE,
    label VARCHAR(50), 
    map_image_path VARCHAR(255),
    status ENUM('operational', 'issue', 'offline') DEFAULT 'operational',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Hardware Assets
CREATE TABLE IF NOT EXISTS hardware_assets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    serial_number VARCHAR(100),
    category ENUM('Access Point', 'Switch', 'Workstation', 'Server', 'Printer', 'CCTV Camera', 'Other') DEFAULT 'Workstation',
    location VARCHAR(100),
    department VARCHAR(100),
    floor_id INT,
    condition_status ENUM('working', 'needs_service', 'faulty') DEFAULT 'working',
    condition_notes TEXT,
    warranty_expiry DATE,
    maintenance_log TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (floor_id) REFERENCES floors(id) ON DELETE SET NULL
);

-- Data Links / Cabinet Tracking
CREATE TABLE IF NOT EXISTS data_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    floor_id INT,
    cabinet_name VARCHAR(50) NOT NULL,
    total_u_space INT DEFAULT 42,
    used_u_space INT DEFAULT 0,
    switch_count INT DEFAULT 0,
    connectivity_type ENUM('Fiber', 'Ethernet', 'Wireless Link') DEFAULT 'Fiber',
    status ENUM('online', 'degraded', 'offline') DEFAULT 'online',
    notes TEXT,
    FOREIGN KEY (floor_id) REFERENCES floors(id) ON DELETE CASCADE
);

-- Floor Asset Pins (Visual Mapping)
CREATE TABLE IF NOT EXISTS floor_pins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    floor_id INT,
    asset_id INT,
    data_link_id INT,
    pos_x INT,
    pos_y INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (floor_id) REFERENCES floors(id) ON DELETE CASCADE,
    FOREIGN KEY (asset_id) REFERENCES hardware_assets(id) ON DELETE CASCADE,
    FOREIGN KEY (data_link_id) REFERENCES data_links(id) ON DELETE CASCADE
);

-- Secure Credential Vault
CREATE TABLE IF NOT EXISTS credential_vault (
    id INT AUTO_INCREMENT PRIMARY KEY,
    system_name VARCHAR(100) NOT NULL,
    username VARCHAR(100),
    encrypted_password TEXT NOT NULL,
    url VARCHAR(255),
    notes TEXT,
    responsible_staff VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Troubleshooting Knowledge Base & Ticketing
CREATE TABLE IF NOT EXISTS troubleshooting_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    status ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
    visibility ENUM('public', 'internal') DEFAULT 'public',
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    system_affected VARCHAR(100),
    requester_username VARCHAR(100),
    symptoms TEXT,
    root_cause TEXT,
    steps_taken TEXT,
    resolution TEXT,
    solution_image VARCHAR(255),
    technician_name VARCHAR(100),
    assigned_to VARCHAR(100),
    incident_date DATE,
    due_date DATETIME,
    vendor_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE SET NULL
);

-- Improvement & Maintenance Tracker
CREATE TABLE IF NOT EXISTS maintenance_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    description TEXT NOT NULL,
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    proposed_solution TEXT,
    estimated_cost DECIMAL(10, 2),
    status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
    assigned_to VARCHAR(100),
    is_recurring BOOLEAN DEFAULT FALSE,
    frequency ENUM('daily', 'weekly', 'monthly', 'quarterly', 'yearly'),
    next_due_date DATE,
    start_time DATETIME,
    end_time DATETIME,
    show_on_portal TINYINT(1) DEFAULT 0,
    impact ENUM('none', 'low', 'medium', 'high', 'outage') DEFAULT 'none',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- External Systems
CREATE TABLE IF NOT EXISTS external_systems (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    url VARCHAR(255) NOT NULL,
    notes TEXT,
    owner VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message TEXT NOT NULL,
    type ENUM('info', 'warning', 'alert') DEFAULT 'info',
    link_url VARCHAR(255),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Audit Logs
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(255),
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================================
-- NETWORK MANAGEMENT TABLES
-- ============================================================================

-- IPAM: Networks table (with WiFi Hotspot fields)
CREATE TABLE IF NOT EXISTS networks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    vlan_tag INT,
    subnet VARCHAR(50),
    gateway VARCHAR(50),
    wifi_password VARCHAR(255),
    is_wifi_hotspot BOOLEAN DEFAULT FALSE,
    hotspot_location VARCHAR(100),
    hotspot_ssid VARCHAR(100),
    password_last_changed DATETIME,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Static Devices (Printers, POS, IP Phones, etc.)
CREATE TABLE IF NOT EXISTS static_devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_name VARCHAR(100) NOT NULL,
    device_type ENUM('Printer', 'POS', 'Scanner', 'IP Phone', 'Camera', 'Access Point', 'Other') DEFAULT 'Printer',
    ip_address VARCHAR(50) NOT NULL,
    location VARCHAR(100) NOT NULL,
    network_id INT,
    mac_address VARCHAR(50),
    manufacturer VARCHAR(100),
    model VARCHAR(100),
    notes TEXT,
    status ENUM('online', 'offline', 'maintenance') DEFAULT 'online',
    last_seen DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (network_id) REFERENCES networks(id) ON DELETE SET NULL
);

-- IPAM: IP Assignments (Legacy)
CREATE TABLE IF NOT EXISTS ip_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    network_id INT,
    ip_address VARCHAR(50) NOT NULL,
    device_name VARCHAR(100),
    mac_address VARCHAR(50),
    status ENUM('static', 'dhcp_reserved', 'dynamic') DEFAULT 'static',
    notes TEXT,
    FOREIGN KEY (network_id) REFERENCES networks(id) ON DELETE CASCADE
);

-- ============================================================================
-- OPERATIONS TABLES
-- ============================================================================

-- Backup Status Tracker
CREATE TABLE IF NOT EXISTS backup_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_name VARCHAR(100) NOT NULL,
    backup_type VARCHAR(50),
    last_verified DATETIME,
    status ENUM('safe', 'at_risk', 'failed') DEFAULT 'safe',
    verified_by VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Procurement Lifecycle
CREATE TABLE IF NOT EXISTS procurement_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(100) NOT NULL,
    vendor_id INT,
    estimated_cost DECIMAL(10, 2),
    status ENUM('requested', 'approved', 'ordered', 'received', 'installed', 'cancelled') DEFAULT 'requested',
    requester VARCHAR(100),
    date_requested DATE,
    date_received DATE,
    notes TEXT,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE SET NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- SOP & Policy Center (Documentation)
CREATE TABLE IF NOT EXISTS sop_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    category VARCHAR(100),
    content TEXT,
    version VARCHAR(20) DEFAULT '1.0',
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    author VARCHAR(100),
    image_path VARCHAR(255),
    visibility ENUM('public', 'private') DEFAULT 'public'
);

-- Quick Notes / Personal To-Do
CREATE TABLE IF NOT EXISTS quick_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    content TEXT NOT NULL,
    is_done TINYINT(1) DEFAULT 0,
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- IT Inventory & Consumables
CREATE TABLE IF NOT EXISTS inventory_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(100),
    stock_level INT DEFAULT 0,
    reorder_threshold INT DEFAULT 5,
    unit_price DECIMAL(10, 2),
    last_restocked DATETIME,
    status ENUM('in_stock', 'low_stock', 'out_of_stock') AS (
        CASE 
            WHEN stock_level <= 0 THEN 'out_of_stock'
            WHEN stock_level <= reorder_threshold THEN 'low_stock'
            ELSE 'in_stock'
        END
    ),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================================
-- HR & LEAVE MANAGEMENT
-- ============================================================================

-- ICT Staff Leave Tracker
CREATE TABLE IF NOT EXISTS ict_leave_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    leave_type VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_by INT DEFAULT NULL,
    rejection_reason TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================================================
-- INFRASTRUCTURE TABLES
-- ============================================================================

-- Power Systems (Solar, Battery, Main)
CREATE TABLE IF NOT EXISTS power_systems (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    system_type ENUM('Main Utility', 'UPS Cluster', 'Solar Array', 'Battery Storage') NOT NULL,
    current_load_kw DECIMAL(10, 2) DEFAULT 0.00,
    battery_percentage INT DEFAULT NULL,
    status ENUM('operational', 'maintenance', 'warning', 'fault') DEFAULT 'operational',
    location VARCHAR(255),
    notes TEXT,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================================================
-- SEED DATA
-- ============================================================================

-- Floors (9 Floors + External)
INSERT IGNORE INTO floors (floor_number, label) VALUES 
(0, 'Basement'),
(1, 'Main Server & Lobby'),
(2, 'Conference Center'),
(3, 'Guest Rooms (F3)'),
(4, 'Guest Rooms (F4)'),
(5, 'Guest Rooms (F5)'),
(6, 'Guest Rooms (F6)'),
(7, 'Executive Suite (F7)'),
(8, 'Gym & Backup Hub'),
(9, 'Solar Roof'),
(99, 'External / Playground');

-- Default Users (Password: 'password' - Change in production!)
INSERT IGNORE INTO users (username, password_hash, role, department, full_name, email) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'IT', 'System Administrator', 'admin@dallaspremiere.com'),
('tech_support', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 'IT', 'Technical Support', 'support@dallaspremiere.com');

-- Sample Vendors
INSERT INTO vendors (name, service_type, contact_person, phone, email, sla_notes, last_service_date) VALUES 
('Telecom Plus', 'ISP & Telephony', 'Alice Johnson', '555-0101', 'alice@telecomplus.com', '4-hour onsite response time for critical outages.', '2023-12-15'),
('HotelSys Global', 'PMS Support', 'Support Desk', '1-800-PMS-HELP', 'support@hotelsys.com', '24/7 Remote Support. Escalation to L2 after 1 hour.', '2024-01-10'),
('CoolTech AC', 'Server Room Cooling', 'Bob Smith', '555-0202', 'bob@cooltech.com', 'Quarterly maintenance visits included.', '2024-02-01');

-- Networks (with WiFi Hotspots)
INSERT INTO networks (name, vlan_tag, subnet, gateway, wifi_password, is_wifi_hotspot, hotspot_location, hotspot_ssid, notes) VALUES 
('Office LAN', 10, '192.168.10.0/24', '192.168.10.1', NULL, FALSE, NULL, NULL, 'Main administrative network'),
('Guest WiFi', 20, '10.0.0.0/16', '10.0.0.1', '@Premiere-2024', TRUE, 'Main Reception', 'HotelGuest-Public', 'Public guest access network'),
('Voice & CCTV', 30, '172.16.0.0/24', '172.16.0.1', NULL, FALSE, NULL, NULL, 'Critical infrastructure segment'),
('Manager Office WiFi', 15, '192.168.15.0/24', '192.168.15.1', 'Manager@2024Secure', TRUE, 'Manager Office - 3rd Floor', 'HotelStaff-Mgmt', 'Private WiFi for management team'),
('Reception Office WiFi', 16, '192.168.16.0/24', '192.168.16.1', 'Reception#WiFi2024', TRUE, 'Reception Desk', 'HotelStaff-Reception', 'WiFi for reception staff'),
('Kitchen Office WiFi', 17, '192.168.17.0/24', '192.168.17.1', 'Kitchen$Pass2024', TRUE, 'Kitchen Office', 'HotelStaff-Kitchen', 'WiFi for kitchen management'),
('Housekeeping WiFi', 18, '192.168.18.0/24', '192.168.18.1', 'HouseKeep!2024', TRUE, 'Housekeeping Office - 2nd Floor', 'HotelStaff-HK', 'WiFi for housekeeping department');

-- Power Systems
INSERT IGNORE INTO power_systems (name, system_type, location, current_load_kw, battery_percentage) VALUES 
('Main Grid Incomer', 'Main Utility', '1st Floor Main Server Room', 450.50, NULL),
('Solar Bank Alpha', 'Solar Array', 'Roof Top', 85.00, 92),
('Server Room UPS A', 'UPS Cluster', '8th Floor Backup Room', 12.80, 100),
('Solar Storage Beta', 'Battery Storage', '8th Floor Backup Room', 0.00, 88);

-- Static Devices
INSERT INTO static_devices (device_name, device_type, ip_address, location, network_id, manufacturer, model, status) VALUES 
('Kitchen POS Terminal', 'POS', '192.168.10.50', 'Kitchen POS', 1, 'HP', 'ElitePOS G1', 'online'),
('Reception Printer', 'Printer', '192.168.10.51', 'Front Desk Reception', 1, 'HP', 'LaserJet Pro M404dn', 'online'),
('Bar POS', 'POS', '192.168.10.52', 'Bar Counter', 1, 'HP', 'ElitePOS G1', 'online'),
('Accounting Printer', 'Printer', '192.168.10.53', 'Accounting Office', 1, 'Canon', 'imageCLASS MF445dw', 'online');

-- Sample Hardware Assets
INSERT INTO hardware_assets (name, serial_number, location, department, condition_status, floor_id) VALUES 
('Dell PowerEdge R740', 'SRV-001-HQ', 'Rack 1', 'IT', 'working', 2),
('Reception WS-01', 'DT-992-REC', 'Front Desk', 'Front Office', 'working', 2),
('Unifi Switch Pro 48', 'SW-L2-02', 'IDF Cabinet', 'IT', 'needs_service', 2);

-- Sample Renewals
INSERT INTO renewals (service_name, vendor_id, contact_details, amount_paid, renewal_date, status) VALUES 
('Fiber Internet Line 1', 1, 'Account #88291', 1200.00, DATE_ADD(CURDATE(), INTERVAL 15 DAY), 'active'),
('Oracle Hospitality License', 2, 'Contract #OH-2024-X', 15000.00, DATE_ADD(CURDATE(), INTERVAL 3 MONTH), 'active');

-- Sample Troubleshooting Log
INSERT INTO troubleshooting_logs (title, system_affected, symptoms, root_cause, steps_taken, resolution, technician_name, incident_date) VALUES 
('Guest WiFi Slow in West Wing', 'UniFi WiFi', 'Guests reporting buffering and drops.', 'Interference from microwave.', 'Scanned RF environment.', 'Moved APs to Channel 1 and 11.', 'tech_support', DATE_SUB(CURDATE(), INTERVAL 5 DAY));

-- ============================================================================
-- END OF MIGRATION SCRIPT
-- ============================================================================
-- Next Steps:
-- 1. Import this file: mysql -u root -p < hotel_ict.sql
-- 2. Change default passwords immediately
-- 3. Configure backup schedules
-- 4. Review and customize seed data for your environment
-- ============================================================================
