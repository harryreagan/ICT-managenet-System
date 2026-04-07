-- Migration: Add Knowledge Base Features
-- Features: Bulk Actions, Advanced Search, Multiple Attachments, Comments, Analytics, Related Issues, Time Tracking
-- Created: 2026-02-15

-- =====================================================
-- 1. ISSUE ATTACHMENTS (Multiple File Uploads)
-- =====================================================
CREATE TABLE IF NOT EXISTS issue_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    issue_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50),
    file_size INT,
    uploaded_by INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (issue_id) REFERENCES troubleshooting_logs(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id),
    INDEX idx_issue_id (issue_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2. ISSUE COMMENTS (Comments & Activity Timeline)
-- =====================================================
CREATE TABLE IF NOT EXISTS issue_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    issue_id INT NOT NULL,
    user_id INT NOT NULL,
    comment_text TEXT NOT NULL,
    is_internal BOOLEAN DEFAULT 0 COMMENT '0=public, 1=internal note',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (issue_id) REFERENCES troubleshooting_logs(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_issue_id (issue_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. ISSUE RELATIONS (Related Issues & Duplicates)
-- =====================================================
CREATE TABLE IF NOT EXISTS issue_relations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    issue_id INT NOT NULL,
    related_issue_id INT NOT NULL,
    relation_type ENUM('related', 'duplicate', 'blocks', 'blocked_by') DEFAULT 'related',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (issue_id) REFERENCES troubleshooting_logs(id) ON DELETE CASCADE,
    FOREIGN KEY (related_issue_id) REFERENCES troubleshooting_logs(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_issue_id (issue_id),
    INDEX idx_related_issue_id (related_issue_id),
    UNIQUE KEY unique_relation (issue_id, related_issue_id, relation_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 4. TIME LOGS (Time Tracking)
-- =====================================================
CREATE TABLE IF NOT EXISTS time_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    issue_id INT NOT NULL,
    user_id INT NOT NULL,
    hours_spent DECIMAL(5,2) NOT NULL,
    description TEXT,
    logged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (issue_id) REFERENCES troubleshooting_logs(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_issue_id (issue_id),
    INDEX idx_user_id (user_id),
    INDEX idx_logged_at (logged_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 5. SAVED FILTERS (Advanced Search)
-- =====================================================
CREATE TABLE IF NOT EXISTS saved_filters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    filter_name VARCHAR(100) NOT NULL,
    filter_criteria JSON NOT NULL,
    is_default BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 6. UPDATE EXISTING TABLE (troubleshooting_logs)
-- =====================================================
-- Add time tracking column
ALTER TABLE troubleshooting_logs 
ADD COLUMN IF NOT EXISTS total_time_spent DECIMAL(5,2) DEFAULT 0 COMMENT 'Total hours spent on this issue';

-- Add indexes for better search performance
ALTER TABLE troubleshooting_logs 
ADD INDEX IF NOT EXISTS idx_status (status),
ADD INDEX IF NOT EXISTS idx_priority (priority),
ADD INDEX IF NOT EXISTS idx_created_at (created_at),
ADD INDEX IF NOT EXISTS idx_assigned_to (assigned_to);

-- Add fulltext index for advanced search
ALTER TABLE troubleshooting_logs 
ADD FULLTEXT INDEX IF NOT EXISTS idx_search (title, symptoms, resolution);

-- =====================================================
-- 7. ACTIVITY LOG (Track all changes)
-- =====================================================
CREATE TABLE IF NOT EXISTS issue_activity (
    id INT AUTO_INCREMENT PRIMARY KEY,
    issue_id INT NOT NULL,
    user_id INT,
    activity_type ENUM('created', 'updated', 'status_changed', 'assigned', 'commented', 'attachment_added', 'time_logged', 'linked') NOT NULL,
    old_value TEXT,
    new_value TEXT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (issue_id) REFERENCES troubleshooting_logs(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_issue_id (issue_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INITIAL DATA / SAMPLE DATA (Optional)
-- =====================================================
-- You can add sample data here if needed for testing
