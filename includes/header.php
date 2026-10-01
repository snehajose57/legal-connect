<?php
// Include database configuration
require_once __DIR__ . '/../config/db.php';

// Dynamically build page titles
$site_title = "Legal Connect - Online Lawyer Booking & Support";
$display_title = isset($page_title) ? $page_title . " | " . $site_title : $site_title;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Legal Connect connects users with verified professional lawyers for appointments, legal questions, and real-time support.">
    <title><?php echo $display_title; ?></title>
    <!-- Google Fonts: Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom Style Sheet -->
    <link href="/legalconnect/assets/css/style.css?v=3" rel="stylesheet">
</head>
<body>
<?php 
// Include role-based navbar
include_once __DIR__ . '/navbar.php'; 
?>
