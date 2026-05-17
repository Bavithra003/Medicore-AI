<?php
// =====================================================
// MEDICORE AI — PATIENTS API
// File: api/patients.php
// URL: http://localhost/medicore/api/patients.php
// =====================================================

require_once '../config/database.php';
require_once '../includes/helpers.php';

setCorsHeaders();

$db     = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

try {
    switch ($method) {

        case 'GET':
            if ($id) {
                $patient = $db->fetchOne("SELECT * FROM patients WHERE id = ?", 'i', [$id]);
                if (!$patient) sendError('Patient not found', 404);
                sendSuccess($patient);
            } else {
                $search = $_GET['search'] ?? '';
                if ($search) {
                    $like = "%$search%";
                    $patients = $db->fetchAll(
                        "SELECT * FROM patients WHERE name LIKE ? OR phone LIKE ? OR email LIKE ? ORDER BY created_at DESC",
                        'sss', [$like, $like, $like]
                    );
                } else {
                    $patients = $db->fetchAll("SELECT * FROM patients ORDER BY created_at DESC");
                }
                sendSuccess($patients);
            }
            break;

        case 'POST':
            $data = getRequestBody();
            requireFields($data, ['name']);
            $id = $db->insert(
                "INSERT INTO patients (name,age,gender,phone,email,address,blood_group,allergies,medical_history)
                 VALUES (?,?,?,?,?,?,?,?,?)",
                'sissssss s',
                [
                    sanitize($data['name']),
                    (int)($data['age'] ?? 0),
                    $data['gender'] ?? 'other',
                    sanitize($data['phone'] ?? ''),
                    sanitize($data['email'] ?? ''),
                    sanitize($data['address'] ?? ''),
                    sanitize($data['blood_group'] ?? ''),
                    sanitize($data['allergies'] ?? ''),
                    sanitize($data['medical_history'] ?? '')
                ]
            );
            $patient = $db->fetchOne("SELECT * FROM patients WHERE id = ?", 'i', [$id]);
            sendSuccess($patient, 'Patient registered', 201);
            break;

        case 'PUT':
        case 'PATCH':
            if (!$id) sendError('Patient ID required', 400);
            $data = getRequestBody();
            $db->execute(
                "UPDATE patients SET name=?,age=?,gender=?,phone=?,email=?,address=?,blood_group=? WHERE id=?",
                'sisssssi',
                [
                    sanitize($data['name'] ?? ''),
                    (int)($data['age'] ?? 0),
                    $data['gender'] ?? 'other',
                    sanitize($data['phone'] ?? ''),
                    sanitize($data['email'] ?? ''),
                    sanitize($data['address'] ?? ''),
                    sanitize($data['blood_group'] ?? ''),
                    $id
                ]
            );
            $patient = $db->fetchOne("SELECT * FROM patients WHERE id = ?", 'i', [$id]);
            sendSuccess($patient, 'Patient updated');
            break;

        case 'DELETE':
            if (!$id) sendError('Patient ID required', 400);
            $db->execute("DELETE FROM patients WHERE id = ?", 'i', [$id]);
            sendSuccess([], 'Patient deleted');
            break;

        default:
            sendError('Method not allowed', 405);
    }
} catch (Exception $e) {
    sendError('Server error: ' . $e->getMessage(), 500);
}