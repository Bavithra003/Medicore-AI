<?php
// =====================================================
// MEDICORE AI — APPOINTMENTS API
// File: api/appointments.php
// URL: http://localhost/medicore/api/appointments.php
// =====================================================

require_once '../config/database.php';
require_once '../includes/helpers.php';

setCorsHeaders();

$db     = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id'])     ? (int)$_GET['id']   : null;
$action = $_GET['action'] ?? '';

try {
    switch ($method) {

        case 'GET':
            if ($id) {
                $appt = $db->fetchOne(
                    "SELECT a.*, d.name AS doctor_name, d.specialization AS department
                     FROM appointments a
                     LEFT JOIN doctors d ON a.doctor_id = d.id
                     WHERE a.id = ?",
                    'i', [$id]
                );
                if (!$appt) sendError('Appointment not found', 404);
                sendSuccess($appt);

            } elseif ($action === 'today') {
                $today = date('Y-m-d');
                $appts = $db->fetchAll(
                    "SELECT a.*, d.name AS doctor_full_name, d.specialization
                     FROM appointments a
                     LEFT JOIN doctors d ON a.doctor_id = d.id
                     WHERE a.appt_date = ? AND a.status != 'cancelled'
                     ORDER BY a.time_slot",
                    's', [$today]
                );
                sendSuccess($appts);

            } elseif (!empty($_GET['date'])) {
                $date  = sanitize($_GET['date']);
                $appts = $db->fetchAll(
                    "SELECT a.*, d.name AS doctor_full_name, d.specialization
                     FROM appointments a
                     LEFT JOIN doctors d ON a.doctor_id = d.id
                     WHERE a.appt_date = ? ORDER BY a.time_slot",
                    's', [$date]
                );
                sendSuccess($appts);

            } elseif (!empty($_GET['patient'])) {
                $pname = '%' . sanitize($_GET['patient']) . '%';
                $appts = $db->fetchAll(
                    "SELECT a.*, d.name AS doctor_full_name, d.specialization
                     FROM appointments a
                     LEFT JOIN doctors d ON a.doctor_id = d.id
                     WHERE a.patient_name LIKE ?
                     ORDER BY a.appt_date DESC, a.time_slot",
                    's', [$pname]
                );
                sendSuccess($appts);

            } else {
                $appts = $db->fetchAll(
                    "SELECT a.*, d.name AS doctor_full_name, d.specialization
                     FROM appointments a
                     LEFT JOIN doctors d ON a.doctor_id = d.id
                     ORDER BY a.appt_date DESC, a.time_slot"
                );
                sendSuccess($appts);
            }
            break;

        case 'POST':
            $data = getRequestBody();
            requireFields($data, ['patient_name', 'doctor_id', 'appt_date', 'time_slot']);

            // ── Conflict detection ──
            $conflict = $db->fetchOne(
                "SELECT id FROM appointments
                 WHERE doctor_id = ? AND appt_date = ? AND time_slot = ? AND status != 'cancelled'",
                'iss',
                [(int)$data['doctor_id'], $data['appt_date'], $data['time_slot']]
            );
            if ($conflict) {
                sendError('This time slot is already booked. Please choose another time.', 409);
            }

            // Get doctor info
            $doctor = $db->fetchOne("SELECT * FROM doctors WHERE id = ?", 'i', [(int)$data['doctor_id']]);
            if (!$doctor) sendError('Doctor not found', 404);

            // Get next queue number for this doctor today
            $queueRow = $db->fetchOne(
                "SELECT COUNT(*)+1 AS next_num FROM appointments
                 WHERE doctor_id = ? AND appt_date = ? AND status != 'cancelled'",
                'is', [(int)$data['doctor_id'], $data['appt_date']]
            );
            $queueNum = $queueRow['next_num'] ?? 1;

            $newId = $db->insert(
                "INSERT INTO appointments
                    (patient_name, patient_id, doctor_id, doctor_name, department, appt_date, time_slot, reason, status, queue_number)
                 VALUES (?,?,?,?,?,?,?,?,?,?)",
                'siiisssss i',
                [
                    sanitize($data['patient_name']),
                    (int)($data['patient_id'] ?? 0),
                    (int)$data['doctor_id'],
                    $doctor['name'],
                    $doctor['specialization'],
                    $data['appt_date'],
                    $data['time_slot'],
                    sanitize($data['reason'] ?? ''),
                    'confirmed',
                    $queueNum
                ]
            );

            // Auto-add to queue if appointment is today
            if ($data['appt_date'] === date('Y-m-d')) {
                $qCount = $db->fetchOne(
                    "SELECT COUNT(*) AS cnt FROM queue WHERE department = ? AND status IN ('waiting','called','in-progress')",
                    's', [$doctor['specialization']]
                );
                $waitTime = (($qCount['cnt'] ?? 0) + 1) * 12;
                $db->insert(
                    "INSERT INTO queue (department, patient_name, patient_id, token_number, estimated_wait)
                     VALUES (?,?,?,?,?)",
                    'ssiii',
                    [
                        $doctor['specialization'],
                        sanitize($data['patient_name']),
                        (int)($data['patient_id'] ?? 0),
                        $queueNum,
                        $waitTime
                    ]
                );
            }

            $appt = $db->fetchOne("SELECT * FROM appointments WHERE id = ?", 'i', [$newId]);
            sendSuccess($appt, 'Appointment booked successfully', 201);
            break;

        case 'PATCH':
            if (!$id) sendError('Appointment ID required', 400);
            $data   = getRequestBody();
            $status = $data['status'] ?? 'cancelled';
            $db->execute(
                "UPDATE appointments SET status = ?, updated_at = NOW() WHERE id = ?",
                'si', [$status, $id]
            );
            $appt = $db->fetchOne("SELECT * FROM appointments WHERE id = ?", 'i', [$id]);
            sendSuccess($appt, 'Appointment updated');
            break;

        default:
            sendError('Method not allowed', 405);
    }
} catch (Exception $e) {
    sendError('Server error: ' . $e->getMessage(), 500);
}