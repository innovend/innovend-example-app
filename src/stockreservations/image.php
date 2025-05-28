<?php
$config = json_decode(file_get_contents('../conf/config.json'), true);

$productId = isset($_GET['product']) ? intval($_GET['product']) : 0;
if (!$productId) {
    http_response_code(400);
    exit('Missing product ID');
}

$apiBaseUrl = $config['apiUrl'] ?? 'https://api.vendingweb.eu';
$url = "{$apiBaseUrl}/api/external/products/downloadthumbimage/{$productId}";

$headers = [
    "Accept: */*"
];

$curl = curl_init($url);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
curl_setopt($curl, CURLOPT_USERPWD, $config['username'] . ":" . $config['password']);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);

$imageData = curl_exec($curl);
$contentType = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
$httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

if ($httpStatus === 200) {
    header("Content-Type: $contentType");
    echo $imageData;
} else {
    http_response_code(404);
    echo "Image not found";
}
