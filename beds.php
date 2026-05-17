<?php
// =====================================================
// MEDICORE AI — BEDS API
// File: api/beds.php
// =====================================================

require_once '../config/database.php';
require_once '../includes/helpers.php';

setCorsHeaders();

$db     = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id'])   ? (int)$_GET['id'] : null;
$action = $_GET['action']      ?? '';

try {
    switch ($method) {
        case 'GET':
            if ($action === 'stats') {
                $stats = $db->fetchOne(
                    "SELECT
                        COUNT(*) AS total,
                        SUM(status='available') AS available,
                        SUM(status='occupied')  AS occupied,
                        SUM(status='maintenance') AS maintenance,
                        ROUND(SUM(status='occupied')/COUNT(*)*100) AS occupancy_rate
                     FROM beds"
                );
                sendSuccess($stats);

            } elseif ($action === 'available') {
                $beds = $db->fetchAll("SELECT * FROM beds WHERE status = 'available' ORDER BY ward, bed_number");
                sendSuccess($beds);

            } else {
                $beds = $db->fetchAll(
                    "SELECT b.*, p.name AS patient_full_name
                     FROM beds b LEFT JOIN patients p ON b.patient_id = p.id
                     ORDER BY b.ward, b.bed_number"
                );
                sendSuccess($beds);
            }
            break;

        case 'PATCH':
            if (!$id) sendError('Bed ID required', 400);
            $data = getRequestBody();
            if ($action === 'assign') {
                $db->execute(
                    "UPDATE beds SET status='occupied', patient_id=?, patient_name=?, admitted_at=NOW() WHERE id=?",
                    'isi', [(int)($data['patient_id'] ?? 0), sanitize($data['patient_name'] ?? ''), $id]
                );
            } elseif ($action === 'release') {
                $db->execute(
                    "UPDATE beds SET status='available', patient_id=NULL, patient_name=NULL, admitted_at=NULL WHERE id=?",
                    'i', [$id]
                );
            }
            $bed = $db->fetchOne("SELECT * FROM beds WHERE id = ?", 'i', [$id]);
            sendSuccess($bed, 'Bed updated');
            break;

        default:
            sendError('Method not allowed', 405);
    }
} catch (Exception $e) {
    sendError($e->getMessage(), 500);
}