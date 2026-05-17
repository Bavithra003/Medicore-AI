<?php
// =====================================================
// MEDICORE AI — SYSTEM HEALTH CHECK
// File: api/health.php
// URL: http://localhost/medicore/api/health.php
// =====================================================

require_once '../config/database.php';
require_once '../includes/helpers.php';

setCorsHeaders();

try {
    $db = Database::getInstance();
    // Try a simple query
    $result = $db->fetchOne("SELECT COUNT(*) AS doctor_count FROM doctors");

    sendSuccess([
        'status'       => 'MediCore API Running ✓',
        'timestamp'    => date('Y-m-d H:i:s'),
        'database'     => 'Connected ✓',
        'doctor_count' => (int)($result['doctor_count'] ?? 0),
        'php_version'  => phpversion(),
        'server'       => 'XAMPP/Apache'
    ], 'System healthy');

} catch (Exception $e) {
    sendError('Health check failed: ' . $e->getMessage(), 500);
}