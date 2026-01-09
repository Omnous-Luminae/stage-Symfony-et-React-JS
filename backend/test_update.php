<?php
// Test update event
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => 'http://localhost:8001/api/events/2',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'PUT',
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode([
        'title' => 'MODIFIÉ - Événement Test',
        'start' => '2026-01-25T14:00',
        'end' => '2026-01-25T16:00',
        'type' => 'meeting',
        'location' => 'Nouvelle Salle',
        'description' => 'Description complètement modifiée'
    ])
]);
$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$error = curl_error($curl);
curl_close($curl);

echo "HTTP Code: " . $httpCode . "\n";
if ($error) echo "Error: " . $error . "\n";
echo "Response: " . $response . "\n";
?>
