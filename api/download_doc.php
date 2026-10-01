<?php
require_once __DIR__ . '/../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    http_response_code(403);
    die("Access Denied: Please log in.");
}

// Get and sanitize file parameter
if (!isset($_GET['file']) || empty($_GET['file'])) {
    http_response_code(400);
    die("Bad Request: File parameter is required.");
}

$file = basename($_GET['file']);
$filepath = __DIR__ . '/../uploads/case_documents/' . $file;

if (!file_exists($filepath)) {
    http_response_code(404);
    die("Error: File not found.");
}

// Determine Content-Type
$mime_type = 'application/octet-stream';
if (function_exists('finfo_open')) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $filepath);
    finfo_close($finfo);
} else {
    $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
    $mimes = [
        'pdf'  => 'application/pdf',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];
    if (isset($mimes[$ext])) {
        $mime_type = $mimes[$ext];
    }
}

// Allow inline preview for PDF and images, force attachment for others
$ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
$inline_allowed = ['pdf', 'png', 'jpg', 'jpeg'];
$disposition = (isset($_GET['inline']) && $_GET['inline'] == 1 && in_array($ext, $inline_allowed)) ? 'inline' : 'attachment';

header("Content-Description: File Transfer");
header("Content-Type: " . $mime_type);
header("Content-Disposition: " . $disposition . "; filename=\"" . $file . "\"");
header("Content-Transfer-Encoding: binary");
header("Expires: 0");
header("Cache-Control: must-revalidate");
header("Pragma: public");
header("Content-Length: " . filesize($filepath));

// Clear output buffers
if (ob_get_level()) {
    ob_end_clean();
}
flush();
readfile($filepath);
exit();
?>
