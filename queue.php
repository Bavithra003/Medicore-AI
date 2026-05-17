<?php
// =====================================================
// MEDICORE AI — QUEUE TRACKING API
// File: api/queue.php
// URL: http://localhost/medicore/api/queue.php
// =====================================================

require_once '../config/database.php';
require_once '../includes/helpers.php';

setCorsHeaders();

$db     = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id'])   ? (int)$_GET['id']   : null;
$action = $_GET['action']      ?? '';
$dept   = isset($_GET['dept']) ? sanitize($_GET['dept']) : '';

try {
    switch ($method) {

        case 'GET':
            if ($action === 'all-departments') {
                // Summary of all departments
                $rows = $db->fetchAll(
                    "SELECT department,
                            COUNT(*) AS total,
                            SUM(CASE WHEN status='waiting'     THEN 1 ELSE 0 END) AS waiting,
                            SUM(CASE WHEN status='called'      THEN 1 ELSE 0 END) AS called,
                            SUM(CASE WHEN status='in-progress' THEN 1 ELSE 0 END) AS in_progress
                     FROM queue WHERE status != 'done'
                     GROUP BY department ORDER BY department"
                );
                sendSuccess($rows);

            } elseif ($dept) {
                // Patients in a specific department queue
                $rows = $db->fetchAll(
                    "SELECT * FROM queue WHERE department = ? AND status != 'done'
                     ORDER BY token_number",
                    's', [$dept]
                );
                sendSuccess($rows);

            } else {
                // All active queue entries
                $rows = $db->fetchAll(
                    "SELECT * FROM queue WHERE status != 'done' ORDER BY department, token_number"
                );
                sendSuccess($rows);
            }
            break;

        case 'POST':
            // Join queue
            $data = getRequestBody();
            requireFields($data, ['department', 'patient_name']);

            $dept = sanitize($data['department']);
            $name = sanitize($data['patient_name']);

            // Get next token number
            $lastToken = $db->fetchOne(
                "SELECT MAX(token_number) AS last_token FROM queue WHERE department = ? AND DATE(joined_at) = CURDATE()",
                's', [$dept]
            );
            $tokenNum = ($lastToken['last_token'] ?? 0) + 1;

            // Estimate wait time
            $activeCount = $db->fetchOne(
                "SELECT COUNT(*) AS cnt FROM queue WHERE department = ? AND status IN ('waiting','called','in-progress')",
                's', [$dept]
            );
            $waitTime = ($activeCount['cnt'] ?? 0) * 12 + 8;

            $newId = $db->insert(
                "INSERT INTO queue (department, patient_name, patient_id, token_number, status, estimated_wait)
                 VALUES (?,?,?,?,?,?)",
                'ssiiis',
                [$dept, $name, (int)($data['patient_id'] ?? 0), $tokenNum, 'waiting', $waitTime]
            );

            $entry = $db->fetchOne("SELECT * FROM queue WHERE id = ?", 'i', [$newId]);
            sendSuccess($entry, "Joined queue. Token #$tokenNum. Estimated wait: ~{$waitTime} mins.", 201);
            break;

        case 'PATCH':
            if (!$id && !$action) sendError('ID or action required', 400);

            if ($action === 'call-next' && $dept) {
                // Call next patient in department
                $inProgress = $db->fetchOne(
                    "SELECT * FROM queue WHERE department = ? AND status = 'in-progress'",
                    's', [$dept]
                );
                if ($inProgress) {
                    $db->execute(
                        "UPDATE queue SET status = 'done', completed_at = NOW() WHERE id = ?",
                        'i', [$inProgress['id']]
                    );
                }
                // Move 'called' to 'in-progress'
                $called = $db->fetchOne(
                    "SELECT * FROM queue WHERE department = ? AND status = 'called' ORDER BY token_number LIMIT 1",
                    's', [$dept]
                );
                if ($called) {
                    $db->execute("UPDATE queue SET status = 'in-progress' WHERE id = ?", 'i', [$called['id']]);
                }
                // Move first 'waiting' to 'called'
                $waiting = $db->fetchOne(
                    "SELECT * FROM queue WHERE department = ? AND status = 'waiting' ORDER BY token_number LIMIT 1",
                    's', [$dept]
                );
                if ($waiting) {
                    $db->execute(
                        "UPDATE queue SET status = 'called', called_at = NOW() WHERE id = ?",
                        'i', [$waiting['id']]
                    );
                }
                // Return updated queue for this dept
                $rows = $db->fetchAll(
                    "SELECT * FROM queue WHERE department = ? AND status != 'done' ORDER BY token_number",
                    's', [$dept]
                );
                // Update wait times
                foreach ($rows as $i => $row) {
                    $newWait = $i * 12;
                    $db->execute("UPDATE queue SET estimated_wait = ? WHERE id = ?", 'ii', [$newWait, $row['id']]);
                }
                sendSuccess($rows, 'Next patient called');

            } elseif ($id) {
                $data   = getRequestBody();
                $status = sanitize($data['status'] ?? 'done');
                $db->execute("UPDATE queue SET status = ? WHERE id = ?", 'si', [$status, $id]);
                $entry  = $db->fetchOne("SELECT * FROM queue WHERE id = ?", 'i', [$id]);
                sendSuccess($entry, 'Queue status updated');
            }
            break;

        case 'DELETE':
            // Remove a patient from queue
            if (!$id) sendError('ID required', 400);
            $db->execute("DELETE FROM queue WHERE id = ?", 'i', [$id]);
            sendSuccess([], 'Removed from queue');
            break;

        default:
            sendError('Method not allowed', 405);
    }
} catch (Exception $e) {
    sendError('Server error: ' . $e->getMessage(), 500);
}