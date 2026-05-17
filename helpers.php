<?php
// =====================================================
// MEDICORE AI — CORS & RESPONSE HELPERS
// File: includes/helpers.php
// =====================================================

// ── CORS Headers (allow frontend to call backend) ──
function setCorsHeaders() {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    header("Content-Type: application/json; charset=UTF-8");

    // Handle preflight OPTIONS request
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}

// ── JSON Response helpers ──
function sendSuccess($data = [], $message = 'Success', $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data'    => $data
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

function sendError($message = 'Error', $code = 400, $details = null) {
    http_response_code($code);
    $resp = ['success' => false, 'error' => $message];
    if ($details) $resp['details'] = $details;
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit();
}

// ── Get JSON body from request ──
function getRequestBody() {
    $body = file_get_contents('php://input');
    return json_decode($body, true) ?? [];
}

// ── Sanitize input ──
function sanitize($value) {
    return htmlspecialchars(strip_tags(trim($value)));
}

// ── Validate required fields ──
function requireFields($data, $fields) {
    $missing = [];
    foreach ($fields as $field) {
        if (!isset($data[$field]) || $data[$field] === '') {
            $missing[] = $field;
        }
    }
    if (!empty($missing)) {
        sendError('Missing required fields: ' . implode(', ', $missing), 422);
    }
}

// ── Format current timestamp ──
function now() {
    return date('Y-m-d H:i:s');
}