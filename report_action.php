<?php
header('Content-Type: application/json; charset=utf-8');
require_once "layout/session.php";
require_once "db/connect.php";

$userId = getCurrentUserId();
if (!$userId) {
    echo json_encode(["status" => "error", "message" => "Please log in first."]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
    exit;
}

$snippetId = isset($_POST['snippet_id']) ? (int)$_POST['snippet_id'] : 0;
$reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
$details = isset($_POST['details']) ? trim($_POST['details']) : '';

if ($snippetId <= 0) {
    echo json_encode(["status" => "error", "message" => "Snippet not found."]);
    exit;
}

// Validate Enum Values
$allowedReasons = ['spam', 'inappropriate', 'malicious_code', 'other'];
if (!in_array($reason, $allowedReasons)) {
    echo json_encode(["status" => "error", "message" => "Invalid report reason."]);
    exit;
}

if ($reason === 'other' && empty($details)) {
    echo json_encode(["status" => "error", "message" => "Please fill in details for 'Other'."]);
    exit;
}

// Check duplicate report
if ($control->hasUserReported($userId, $snippetId)) {
    echo json_encode(["status" => "error", "message" => "You have already reported this snippet."]);
    exit;
}

// Insert report
if ($control->createReport($snippetId, $userId, $reason, $details)) {
    echo json_encode(["status" => "success", "message" => "Thank you. We will review your report shortly."]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to submit report."]);
}
exit;
?>
