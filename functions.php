<?php

function GetHash($params, $secret) 
{
    $values = "";
    foreach ($params as $value) {
        $values .= (is_array($value) ? implode("", $value) : $value);  
    }
    return md5($values . $secret);
}

function request_to_senler($params)
{
    $myCurl = curl_init(); 
    curl_setopt_array($myCurl, [ 
        CURLOPT_URL => $params['senler_api_url'], 
        CURLOPT_RETURNTRANSFER => true, 
        CURLOPT_POST => true, 
        CURLOPT_POSTFIELDS => http_build_query($params['curl_params']) 
    ]);
    $response = json_decode(curl_exec($myCurl), 1); 
    curl_close($myCurl);

    return $response;
}