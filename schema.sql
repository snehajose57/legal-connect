-- Create database if not exists
CREATE DATABASE IF NOT EXISTS legal_connect;
USE legal_connect;

-- 2. users Table
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    address TEXT NOT NULL,
    profile_image VARCHAR(255) DEFAULT 'default_user.png',
    password VARCHAR(255) NOT NULL,
    status VARCHAR(50) DEFAULT 'Pending', -- Pending, Approved, Rejected, Blocked
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. lawyers Table
CREATE TABLE IF NOT EXISTS lawyers (
    lawyer_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    address TEXT NOT NULL,
    qualification VARCHAR(255) NOT NULL,
    specialization VARCHAR(150) NOT NULL,
    experience INT NOT NULL,
    certificate VARCHAR(255) NOT NULL, -- file path or name
    profile_image VARCHAR(255) DEFAULT 'default_lawyer.png',
    password VARCHAR(255) NOT NULL,
    status VARCHAR(50) DEFAULT 'Pending', -- Pending, Approved, Rejected, Blocked
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. categories Table
CREATE TABLE IF NOT EXISTS categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NOT NULL
);

-- 5. ipc_sections Table
CREATE TABLE IF NOT EXISTS ipc_sections (
    ipc_id INT AUTO_INCREMENT PRIMARY KEY,
    section_number VARCHAR(50) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL
);

-- 6. appointments Table
CREATE TABLE IF NOT EXISTS appointments (
    appointment_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    lawyer_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status VARCHAR(50) DEFAULT 'Pending', -- Pending, Approved, Completed, Cancelled
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (lawyer_id) REFERENCES lawyers(lawyer_id) ON DELETE CASCADE
);

-- 7. legal_questions Table
CREATE TABLE IF NOT EXISTS legal_questions (
    question_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    lawyer_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    question TEXT NOT NULL,
    response TEXT DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Pending', -- Pending, Answered
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (lawyer_id) REFERENCES lawyers(lawyer_id) ON DELETE CASCADE
);

-- 8. chats Table
CREATE TABLE IF NOT EXISTS chats (
    chat_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    lawyer_id INT NOT NULL,
    sender_type VARCHAR(20) NOT NULL, -- 'user' or 'lawyer'
    message TEXT NOT NULL,
    date_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (lawyer_id) REFERENCES lawyers(lawyer_id) ON DELETE CASCADE
);

-- 9. payments Table
CREATE TABLE IF NOT EXISTS payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    lawyer_id INT NOT NULL,
    appointment_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL, -- UPI, Card, Net Banking
    transaction_id VARCHAR(100) NOT NULL UNIQUE,
    payment_status VARCHAR(50) DEFAULT 'Completed', -- Completed, Pending, Failed
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (lawyer_id) REFERENCES lawyers(lawyer_id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE CASCADE
);

-- 10. feedbacks Table
CREATE TABLE IF NOT EXISTS feedbacks (
    feedback_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    lawyer_id INT NOT NULL,
    rating INT NOT NULL,
    comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (lawyer_id) REFERENCES lawyers(lawyer_id) ON DELETE CASCADE
);

-- Seed Default Legal Categories
INSERT INTO categories (category_name, description) VALUES
('Criminal Law', 'Covers offenses against the state or society, such as theft, assault, murder, and fraud.'),
('Civil Law', 'Resolves disputes between private individuals or organizations, including contracts and property.'),
('Cyber Law', 'Specializes in computer crimes, digital privacy, internet abuse, and information security.'),
('Family Law', 'Handles domestic relations like marriage, divorce, child custody, and inheritance disputes.'),
('Corporate Law', 'Deals with the formation, operations, and governance of business corporations.'),
('Property Law', 'Governs ownership and tenancy disputes of tangible real estate and personal property.')
ON DUPLICATE KEY UPDATE category_name=category_name;

-- Seed Default IPC Sections
INSERT INTO ipc_sections (section_number, title, description) VALUES
('Section 302', 'Punishment for Murder', 'Whoever commits murder shall be punished with death, or imprisonment for life, and shall also be liable to fine.'),
('Section 378', 'Theft', 'Whoever, intending to take dishonestly any moveable property out of the possession of any person without that person''s consent, moves that property in order to such taking, is said to commit theft.'),
('Section 420', 'Cheating and dishonestly inducing delivery of property', 'Whoever cheats and thereby dishonestly induces the person deceived to deliver any property to any person... shall be punished with imprisonment of either description for a term which may extend to seven years, and shall also be liable to fine.'),
('Section 354', 'Assault or criminal force to woman with intent to outrage her modesty', 'Whoever assaults or uses criminal force to any woman, intending to outrage... shall be punished with imprisonment of either description for a term which shall not be less than one year but which may extend to five years, and shall also be liable to fine.'),
('Section 498A', 'Husband or relative of husband of a woman subjecting her to cruelty', 'Whoever, being the husband or the relative of the husband of a woman, subjects such woman to cruelty shall be punished with imprisonment for a term which may extend to three years and shall also be liable to fine.')
ON DUPLICATE KEY UPDATE section_number=section_number;

-- 11. cases Table
CREATE TABLE IF NOT EXISTS cases (
    case_id INT AUTO_INCREMENT PRIMARY KEY,
    case_number VARCHAR(50) NOT NULL UNIQUE,
    appointment_id INT NOT NULL,
    user_id INT NOT NULL,
    lawyer_id INT NOT NULL,
    category_id INT NOT NULL,
    case_title VARCHAR(255) NOT NULL,
    case_type VARCHAR(150) NOT NULL,
    case_description TEXT NOT NULL,
    incident_date DATE NOT NULL,
    incident_location VARCHAR(255) NOT NULL,
    opposite_party_name VARCHAR(255) NOT NULL,
    opposite_party_details TEXT NOT NULL,
    police_station VARCHAR(255) NOT NULL,
    fir_number VARCHAR(100) DEFAULT NULL,
    court_name VARCHAR(255) DEFAULT NULL,
    priority VARCHAR(50) NOT NULL,
    status VARCHAR(50) DEFAULT 'Submitted',
    additional_notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (lawyer_id) REFERENCES lawyers(lawyer_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. case_documents Table
CREATE TABLE IF NOT EXISTS case_documents (
    document_id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL,
    document_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_by VARCHAR(50) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (case_id) REFERENCES cases(case_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 13. case_updates Table
CREATE TABLE IF NOT EXISTS case_updates (
    update_id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL,
    lawyer_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    status VARCHAR(50) NOT NULL,
    attachment VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (case_id) REFERENCES cases(case_id) ON DELETE CASCADE,
    FOREIGN KEY (lawyer_id) REFERENCES lawyers(lawyer_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 14. hearings Table
CREATE TABLE IF NOT EXISTS hearings (
    hearing_id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL,
    hearing_date DATE NOT NULL,
    hearing_time TIME NOT NULL,
    hearing_location VARCHAR(255) NOT NULL,
    remarks TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (case_id) REFERENCES cases(case_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 15. case_timeline Table
CREATE TABLE IF NOT EXISTS case_timeline (
    timeline_id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL,
    activity VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (case_id) REFERENCES cases(case_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 16. notifications Table
CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    lawyer_id INT DEFAULT NULL,
    is_admin TINYINT DEFAULT 0,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (lawyer_id) REFERENCES lawyers(lawyer_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
