<?php
// =====================================================
// MEDICORE AI — MEDICINE REMINDERS API
// File: api/medicine.php
// URL: http://localhost/medicore/api/medicine.php
// =====================================================

require_once '../config/database.php';
require_once '../includes/helpers.php';

setCorsHeaders();

$db     = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id'])     ? (int)$_GET['id']   : null;
$action = $_GET['action']        ?? '';

try {
    switch ($method) {

        case 'GET':
            if ($id) {
                $med = $db->fetchOne("SELECT * FROM medicine_reminders WHERE id = ?", 'i', [$id]);
                if (!$med) sendError('Reminder not found', 404);
                sendSuccess($med);

            } elseif ($action === 'due-now') {
                // Get reminders due within the next 5 minutes
                $now     = date('H:i');
                $in5     = date('H:i', strtotime('+5 minutes'));
                $today   = date('Y-m-d');
                $meds    = $db->fetchAll(
                    "SELECT mr.*, mdl.id AS log_id FROM medicine_reminders mr
                     LEFT JOIN medicine_dose_log mdl
                       ON mdl.reminder_id = mr.id AND mdl.dose_date = ? AND mdl.status = 'taken'
                     WHERE mr.is_active = 1
                       AND (mr.start_date IS NULL OR mr.start_date <= ?)
                       AND (mr.end_date IS NULL OR mr.end_date >= ?)
                     HAVING log_id IS NULL",
                    'sss', [$today, $today, $today]
                );
                // Filter to times due now
                $due = array_filter($meds, function($m) use ($now, $in5) {
                    $times = explode(',', $m['reminder_times']);
                    foreach ($times as $t) {
                        $t = trim($t);
                        if ($t >= $now && $t <= $in5) return true;
                    }
                    return false;
                });
                sendSuccess(array_values($due));

            } else {
                $patient = $_GET['patient'] ?? '';
                if ($patient) {
                    $like = '%' . sanitize($patient) . '%';
                    $meds = $db->fetchAll(
                        "SELECT * FROM medicine_reminders WHERE patient_name LIKE ? AND is_active=1 ORDER BY created_at DESC",
                        's', [$like]
                    );
                } else {
                    $meds = $db->fetchAll(
                        "SELECT * FROM medicine_reminders WHERE is_active = 1 ORDER BY created_at DESC"
                    );
                }
                sendSuccess($meds);
            }
            break;

        case 'POST':
            if ($action === 'log-dose') {
                // Mark a dose as taken/skipped
                $data = getRequestBody();
                requireFields($data, ['reminder_id', 'dose_date', 'dose_time', 'status']);
                // Check if already logged
                $existing = $db->fetchOne(
                    "SELECT id FROM medicine_dose_log WHERE reminder_id = ? AND dose_date = ? AND dose_time = ?",
                    'iss', [(int)$data['reminder_id'], $data['dose_date'], $data['dose_time']]
                );
                if ($existing) {
                    // Update existing log
                    $db->execute(
                        "UPDATE medicine_dose_log SET status = ? WHERE id = ?",
                        'si', [$data['status'], $existing['id']]
                    );
                    sendSuccess(['id' => $existing['id']], 'Dose log updated');
                }
                $logId = $db->insert(
                    "INSERT INTO medicine_dose_log (reminder_id, dose_date, dose_time, status) VALUES (?,?,?,?)",
                    'isss',
                    [(int)$data['reminder_id'], $data['dose_date'], $data['dose_time'], $data['status']]
                );
                sendSuccess(['id' => $logId], 'Dose logged', 201);

            } else {
                // Create new reminder
                $data = getRequestBody();
                requireFields($data, ['patient_name', 'medicine_name', 'reminder_times']);
                $newId = $db->insert(
                    "INSERT INTO medicine_reminders
                        (patient_name, patient_id, medicine_name, dosage, frequency, reminder_times, start_date, end_date, food_instruction, notes)
                     VALUES (?,?,?,?,?,?,?,?,?,?)",
                    'sissssssss',
                    [
                        sanitize($data['patient_name']),
                        (int)($data['patient_id'] ?? 0),
                        sanitize($data['medicine_name']),
                        sanitize($data['dosage'] ?? ''),
                        $data['frequency'] ?? 'once',
                        sanitize($data['reminder_times']),
                        $data['start_date'] ?? date('Y-m-d'),
                        $data['end_date'] ?? date('Y-m-d', strtotime('+30 days')),
                        $data['food_instruction'] ?? 'any',
                        sanitize($data['notes'] ?? '')
                    ]
                );
                $med = $db->fetchOne("SELECT * FROM medicine_reminders WHERE id = ?", 'i', [$newId]);
                sendSuccess($med, 'Reminder created', 201);
            }
            break;

        case 'DELETE':
            if (!$id) sendError('ID required', 400);
            $db->execute("UPDATE medicine_reminders SET is_active = 0 WHERE id = ?", 'i', [$id]);
            sendSuccess([], 'Reminder deactivated');
            break;

        default:
            sendError('Method not allowed', 405);
    }
} catch (Exception $e) {
    sendError('Server error: ' . $e->getMessage(), 500);
}