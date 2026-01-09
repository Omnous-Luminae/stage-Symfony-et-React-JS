<?php
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => 'http://localhost:8001/api/events',
    CURLOPT_RETURNTRANSFER => true
]);
$response = curl_exec($curl);
curl_close($curl);
$data = json_decode($response, true);
if (is_array($data) && count($data) > 0) {
    foreach ($data as $event) {
        echo 'ID: ' . $event['id'] . ' - ' . $event['title'] . "\n";
    }
} else {
    echo 'Pas d\'événements trouvés' . "\n";
}
?>
