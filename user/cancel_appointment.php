<?php
/**
 * user/cancel_appointment.php
 * Handles client-initiated appointment cancellation with smart refund messaging.
 *
 * Refund tiers (paid appointments only):
 *   > 24 hrs before   → 100% refund  (₹300.00)
 *   ≤ 24 hrs & > 2 hrs → 50% refund  (₹150.00)
 *   ≤ 2 hrs before    → 25% refund   (₹75.00)
 *   Not paid yet      → No charge, appointment cancelled
 */

require_once __DIR__ . '/../config/db.php';
check_login('user');

$user_id = $_SESSION['user_id'];

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /legalconnect/user/dashboard.php");
    exit();
}

$appt_id = isset($_POST['appointment_id']) ? (int)$_POST['appointment_id'] : 0;

if (!$appt_id) {
    $_SESSION['error'] = "Invalid appointment reference.";
    header("Location: /legalconnect/user/dashboard.php");
    exit();
}

try {
    // Fetch appointment – must belong to this client
    $stmt = $conn->prepare(
        "SELECT a.*, l.name AS lawyer_name,
                p.payment_id, p.amount AS paid_amount
         FROM appointments a
         JOIN lawyers l ON a.lawyer_id = l.lawyer_id
         LEFT JOIN payments p ON a.appointment_id = p.appointment_id
         WHERE a.appointment_id = ? AND a.user_id = ?"
    );
    $stmt->execute([$appt_id, $user_id]);
    $appt = $stmt->fetch();

    if (!$appt) {
        $_SESSION['error'] = "Appointment not found or access denied.";
        header("Location: /legalconnect/user/dashboard.php");
        exit();
    }

    // Only allow cancellation on active statuses
    $cancellable = ['Pending', 'Accepted', 'Approved'];
    if (!in_array($appt['status'], $cancellable)) {
        $_SESSION['error'] = "This appointment cannot be cancelled (current status: " . htmlspecialchars($appt['status']) . ").";
        header("Location: /legalconnect/user/dashboard.php");
        exit();
    }

    // ── Determine refund tier ──────────────────────────────────────────────
    $was_paid      = !empty($appt['payment_id']);
    $appt_datetime = strtotime($appt['appointment_date'] . ' ' . $appt['appointment_time']);
    $now           = time();
    $hours_until   = ($appt_datetime - $now) / 3600;  // may be negative if past

    $refund_msg = "";
    $consultation_fee = 300.00;

    if (!$was_paid) {
        // Not paid yet (Pending or Accepted state) — no charge at all
        $refund_msg = "Your appointment with Adv. " . $appt['lawyer_name']
            . " scheduled for " . date('d M, Y', strtotime($appt['appointment_date']))
            . " at " . date('h:i A', strtotime($appt['appointment_time']))
            . " has been cancelled. No payment was made, so there is no charge.";
    } elseif ($hours_until > 24) {
        // More than 24 hours away → 100% refund
        $refund_msg = "Your appointment with Adv. " . $appt['lawyer_name']
            . " scheduled for " . date('d M, Y', strtotime($appt['appointment_date']))
            . " at " . date('h:i A', strtotime($appt['appointment_time']))
            . " has been cancelled. Since you cancelled well in advance, a full refund of"
            . " \u20b9300.00 will be processed within 2\u20133 working days.";
    } elseif ($hours_until > 2) {
        // Within 24 hours but more than 2 hours → 50% partial refund
        $refund_amount = number_format($consultation_fee * 0.50, 2);
        $refund_msg = "Your appointment with Adv. " . $appt['lawyer_name']
            . " scheduled for " . date('d M, Y', strtotime($appt['appointment_date']))
            . " at " . date('h:i A', strtotime($appt['appointment_time']))
            . " has been cancelled on short notice (within 24 hours). A partial refund of"
            . " \u20b9" . $refund_amount . " (50%) will be processed within 2\u20133 working days."
            . " We regret that the remaining 50% cannot be refunded due to the short-notice policy.";
    } else {
        // Within 2 hours (or past) → 25% partial refund
        $refund_amount = number_format($consultation_fee * 0.25, 2);
        $refund_msg = "Your appointment with Adv. " . $appt['lawyer_name']
            . " scheduled for " . date('d M, Y', strtotime($appt['appointment_date']))
            . " at " . date('h:i A', strtotime($appt['appointment_time']))
            . " has been cancelled at the last minute (within 2 hours of the scheduled time). A partial refund of"
            . " \u20b9" . $refund_amount . " (25%) will be processed within 2\u20133 working days."
            . " We regret that the remaining 75% cannot be refunded due to the last-minute cancellation policy.";
    }

    // ── Update appointment status ──────────────────────────────────────────
    $upd = $conn->prepare("UPDATE appointments SET status = 'Cancelled by Client' WHERE appointment_id = ?");
    $upd->execute([$appt_id]);

    // ── Send notification to client ────────────────────────────────────────
    $notif = $conn->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
    $notif->execute([$user_id, "Appointment Cancelled", $refund_msg]);

    $_SESSION['success'] = "Your appointment has been cancelled. " . ($was_paid ? "Refund details have been sent to your notifications." : "No charges were made.");
    header("Location: /legalconnect/user/dashboard.php");
    exit();

} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header("Location: /legalconnect/user/dashboard.php");
    exit();
}
