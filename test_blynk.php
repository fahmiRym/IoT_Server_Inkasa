<?php
require 'vendor/autoload.php';
use Illuminate\Support\Facades\Http;
$token = 'A5QhBqeBlKpSbQlF8gzIU6gi4Frz7gP3';
$server = 'blynk.cloud';
$pins = ['v0','v1','v2','v3','v4','v5','v0','v1','v2','v3','v4','v5','v10','v11','v12'];
$query = "token=" . $token;
foreach ($pins as $p) $query .= "&" . $p;
$url = "https://{$server}/external/api/get?" . $query;
echo "URL: $url\n";
echo "Response: " . file_get_contents($url) . "\n";
