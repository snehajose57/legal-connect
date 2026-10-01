<?php
// Secure session startup
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = 'localhost';
$user = 'root';
$pass = 'Root';
$dbname = 'legal_connect';

try {
    // 1. First connect to MySQL server without database to check/create it
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;");

    // 2. Connect to the database specifically
    $pdo->exec("USE `$dbname` cascade;"); // Note: Cascade is not valid for USE, let's just do USE `$dbname`
} catch (PDOException $e) {
    // Re-try with USE `$dbname` standard
}

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Self-installation helper: check if users table exists. If not, seed database
    $tableCheck = $conn->query("SHOW TABLES LIKE 'users'");
    if ($tableCheck->rowCount() == 0) {
        $sqlPath = __DIR__ . '/../schema.sql';
        if (file_exists($sqlPath)) {
            $sql = file_get_contents($sqlPath);
            // Split SQL by query statements to avoid multi-query execution errors in PDO exec
            // Note: PDO exec can run multi-statements in some drivers, but splitting is safer
            $conn->exec($sql);
        }
    }

    // Self-installation helper: check if cases table exists. If not, create new tables.
    $casesCheck = $conn->query("SHOW TABLES LIKE 'cases'");
    if ($casesCheck->rowCount() == 0) {
        $conn->exec("
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

            CREATE TABLE IF NOT EXISTS case_documents (
                document_id INT AUTO_INCREMENT PRIMARY KEY,
                case_id INT NOT NULL,
                document_name VARCHAR(255) NOT NULL,
                file_path VARCHAR(255) NOT NULL,
                uploaded_by VARCHAR(50) NOT NULL,
                uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (case_id) REFERENCES cases(case_id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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

            CREATE TABLE IF NOT EXISTS case_timeline (
                timeline_id INT AUTO_INCREMENT PRIMARY KEY,
                case_id INT NOT NULL,
                activity VARCHAR(255) NOT NULL,
                description TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (case_id) REFERENCES cases(case_id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
        ");
    }
} catch (PDOException $e) {
    die("Database Connection failed: " . $e->getMessage());
}

// Global safety function to prevent SQL injection and filter output
function sanitize($data)
{
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Function to check if a user is logged in and has a specific role
function check_login($role = null)
{
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        if ($role === 'admin') {
            header("Location: /legalconnect/admin/login.php");
        } else {
            header("Location: /legalconnect/login.php");
        }
        exit();
    }

    if ($role !== null && $_SESSION['role'] !== $role) {
        // Forbidden - redirect to respective dashboard
        if ($_SESSION['role'] === 'admin') {
            header("Location: /legalconnect/admin/dashboard.php");
        } elseif ($_SESSION['role'] === 'lawyer') {
            header("Location: /legalconnect/lawyer/dashboard.php");
        } else {
            header("Location: /legalconnect/user/dashboard.php");
        }
        exit();
    }
}
?>