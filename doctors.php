<?php
// =====================================================
// MEDICORE AI — DOCTORS API
// File: api/doctors.php
// URL: http://localhost/medicore/api/doctors.php
// =====================================================

require_once '../config/database.php';
require_once '../includes/helpers.php';

setCorsHeaders();

$db     = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

try {
    switch ($method) {

        // ── GET ──────────────────────────────────────
        case 'GET':
            if ($id) {
                // Single doctor
                $doctor = $db->fetchOne(
                    "SELECT * FROM doctors WHERE id = ?",
                    'i', [$id]
                );
                if (!$doctor) sendError('Doctor not found', 404);
                sendSuccess($doctor);

            } elseif ($action === 'available') {
                // Only available doctors
                $doctors = $db->fetchAll(
                    "SELECT * FROM doctors WHERE availability = 'available' ORDER BY rating DESC"
                );
                sendSuccess($doctors);

            } elseif ($action === 'specializations') {
                // Unique specializations list
                $specs = $db->fetchAll(
                    "SELECT DISTINCT specialization FROM doctors ORDER BY specialization"
                );
                sendSuccess(array_column($specs, 'specialization'));

            } else {
                // All doctors, optional filter
                $spec = $_GET['spec'] ?? '';
                if ($spec) {
                    $doctors = $db->fetchAll(
                        "SELECT * FROM doctors WHERE specialization = ? ORDER BY availability, rating DESC",
                        's', [$spec]
                    );
                } else {
                    $doctors = $db->fetchAll(
                        "SELECT * FROM doctors ORDER BY
                            FIELD(availability,'available','busy','offline'), rating DESC"
                    );
                }
                sendSuccess($doctors);
            }
            break;

        // ── POST (Create) ─────────────────────────────
        case 'POST':
            $data = getRequestBody();
            requireFields($data, ['name', 'specialization']);
            $id = $db->insert(
                "INSERT INTO doctors (name,specialization,qualification,experience_yrs,rating,availability,working_days,working_hours,consult_fee,bio,phone,email)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?)",
                'sssidsssdss s',
                [
                    sanitize($data['name']),
                    sanitize($data['specialization']),
                    sanitize($data['qualification'] ?? ''),
                    (int)($data['experience_yrs'] ?? 0),
                    (float)($data['rating'] ?? 4.5),
                    $data['availability'] ?? 'available',
                    sanitize($data['working_days'] ?? ''),
                    sanitize($data['working_hours'] ?? ''),
                    (float)($data['consult_fee'] ?? 0),
                    sanitize($data['bio'] ?? ''),
                    sanitize($data['phone'] ?? ''),
                    sanitize($data['email'] ?? '')
                ]
            );
            $doctor = $db->fetchOne("SELECT * FROM doctors WHERE id = ?", 'i', [$id]);
            sendSuccess($doctor, 'Doctor created', 201);
            break;

        // ── PUT/PATCH (Update) ────────────────────────
        case 'PUT':
        case 'PATCH':
            if (!$id) sendError('Doctor ID required', 400);
            $data = getRequestBody();
            // Update availability status
            if (isset($data['availability'])) {
                $db->execute(
                    "UPDATE doctors SET availability = ?, updated_at = NOW() WHERE id = ?",
                    'si', [$data['availability'], $id]
                );
            }
            $doctor = $db->fetchOne("SELECT * FROM doctors WHERE id = ?", 'i', [$id]);
            sendSuccess($doctor, 'Doctor updated');
            break;

        default:
            sendError('Method not allowed', 405);
    }
} catch (Exception $e) {
    sendError('Server error: ' . $e->getMessage(), 500);
}