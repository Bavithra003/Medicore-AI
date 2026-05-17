<?php
// =====================================================
// MEDICORE AI — AI CHATBOT API
// File: api/chatbot.php
// Proxies Anthropic Claude API securely from backend
// =====================================================

require_once '../config/database.php';
require_once '../includes/helpers.php';

setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Only POST allowed', 405);
}

// ── Load API Key from config ──────────────────────────
require_once '../config/api_keys.php'; // define('ANTHROPIC_API_KEY', 'sk-ant-...');

$db   = Database::getInstance();
$data = getRequestBody();

if (empty($data['messages'])) {
    sendError('Messages array required', 400);
}

$messages   = $data['messages'];
$sessionId  = sanitize($data['session_id'] ?? uniqid('sess_', true));
$patientName = sanitize($data['patient_name'] ?? 'Patient');

// ── Save user message to DB ──────────────────────────
$lastMsg = end($messages);
if ($lastMsg && $lastMsg['role'] === 'user') {
    $db->insert(
        "INSERT INTO chat_history (session_id, patient_name, role, message) VALUES (?,?,?,?)",
        'ssss',
        [$sessionId, $patientName, 'user', $lastMsg['content']]
    );
}

// ── Call Anthropic API ───────────────────────────────
$systemPrompt = "You are MediBot, an AI health assistant for MediCore Smart Hospital in Chennai, India.
Your role:
1. Listen to patient symptoms with empathy and warmth
2. Provide preliminary guidance on possible conditions (always frame as 'possible' or 'could indicate')
3. Recommend specific specialists available at the hospital
4. Offer basic first-aid or lifestyle advice
5. Always remind patients that this is guidance only — a real doctor must diagnose
6. NEVER definitively diagnose or prescribe medications
7. Be concise, warm, and professional. Use simple language.

Specialists at MediCore Hospital:
- Cardiology (Dr. Priya Sharma, Dr. Arjun Das): Heart, chest pain, blood pressure, palpitations
- Neurology (Dr. Rajan Patel, Dr. Nisha Reddy): Headaches, dizziness, seizures, memory issues
- Pediatrics (Dr. Lakshmi Rao, Dr. Kavitha Iyer): Children's health, development, fever in kids
- Orthopedics (Dr. Mohan Krishnan, Dr. Senthil Murugan): Joints, bones, back pain, sports injuries
- Dermatology (Dr. Ananya Singh): Skin conditions, rashes, acne, hair loss
- General Medicine (Dr. Suresh Nair): Fever, cough, cold, diabetes, general checkup
- Gynecology (Dr. Deepa Menon): Women's health, pregnancy
- Oncology (Dr. Vijay Kumar): Cancer screening and management

Format with bullet points where helpful. Keep under 200 words unless complex.";

$payload = json_encode([
    'model'      => 'claude-sonnet-4-20250514',
    'max_tokens' => 1000,
    'system'     => $systemPrompt,
    'messages'   => $messages
]);

$ch = curl_init('https://api.anthropic.com/v1/messages');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'x-api-key: ' . ANTHROPIC_API_KEY,
        'anthropic-version: 2023-06-01'
    ],
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_SSL_VERIFYPEER => true,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    sendError('AI service unavailable: ' . $curlError, 503);
}

$result = json_decode($response, true);

if ($httpCode !== 200 || empty($result['content'])) {
    // Return fallback response
    sendSuccess([
        'reply'    => getFallbackResponse(end($messages)['content'] ?? ''),
        'fallback' => true,
        'session_id' => $sessionId
    ]);
}

$reply = '';
foreach ($result['content'] as $block) {
    if ($block['type'] === 'text') {
        $reply .= $block['text'];
    }
}

// Save assistant reply to DB
$db->insert(
    "INSERT INTO chat_history (session_id, patient_name, role, message) VALUES (?,?,?,?)",
    'ssss', [$sessionId, $patientName, 'assistant', $reply]
);

sendSuccess([
    'reply'      => $reply,
    'fallback'   => false,
    'session_id' => $sessionId
]);

// ── Fallback responses (when API unavailable) ────────
function getFallbackResponse($msg) {
    $lower = strtolower($msg);
    if (str_contains($lower,'chest') || str_contains($lower,'heart'))
        return "Chest discomfort can have cardiac, respiratory, or musculoskeletal causes.\n\n• Rest immediately\n• Avoid physical exertion\n• If severe: seek emergency care\n\n**Recommended:** Cardiology dept (Dr. Priya Sharma). ⚠️ Severe chest pain with sweating is a medical emergency.";
    if (str_contains($lower,'headache') || str_contains($lower,'migraine'))
        return "Headaches may result from tension, dehydration, or neurological causes.\n\n• Rest in a quiet room\n• Stay hydrated\n• OTC pain relief if appropriate\n\n**Recommended:** Neurology (Dr. Rajan Patel) for recurring headaches.";
    if (str_contains($lower,'fever') || str_contains($lower,'temperature'))
        return "Fever indicates your body is fighting infection.\n\n• Rest and drink fluids\n• Paracetamol for temp > 38.5°C\n• Seek emergency care if >40°C or with difficulty breathing\n\n**Recommended:** General Medicine (Dr. Suresh Nair).";
    if (str_contains($lower,'joint') || str_contains($lower,'knee') || str_contains($lower,'back'))
        return "Joint/bone pain can range from muscle strain to arthritis.\n\n• RICE: Rest, Ice, Compression, Elevation\n• Avoid strenuous activity\n\n**Recommended:** Orthopedics (Dr. Mohan Krishnan).";
    return "Thank you for describing your symptoms. I'd recommend visiting **General Medicine** (Dr. Suresh Nair) for an initial evaluation.\n\n• Rest and stay hydrated\n• Note any worsening symptoms\n\nWould you like to provide more details?";
}