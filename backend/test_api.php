<?php
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => 'http://localhost:8001/api/events',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode([
        'title' => 'Test Événement',
        'start' => '2026-01-15T09:00',
        'end' => '2026-01-15T11:00',
        'type' => 'course',
        'location' => 'Salle A',
        'description' => 'Test de création'
    ])
]);
$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);
echo "HTTP Code: " . $httpCode . "\n";
echo "Response: " . $response . "\n";
?>
