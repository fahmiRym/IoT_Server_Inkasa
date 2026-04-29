<?php
$token = 'A5QhBqeBlKpSbQlF8gzIU6gi4Frz7gP3';
$res = [];
for($i=0; $i<20; $i++) { 
    $v = 'v'.$i; 
    $u = "https://blynk.cloud/external/api/get?token=$token&$v"; 
    $val = @file_get_contents($u); 
    if($val !== "" && $val !== false) {
        $res[$v] = $val;
    }
}
echo json_encode($res, JSON_PRETTY_PRINT) . "\n";
