<?php

define('COOKIE_FILE', "cookies.oven");



function post($url, $params) {
    $fields_string = "";
    foreach ($params as $key => $value) {
        
        $fields_string .=($fields_string == "") ? "" : "&";
        $fields_string .= $key . '=' . urlencode($value);
    }
    
    // Crea un nuevo recurso cURL
    $ch = curl_init();
    // Establece la URL y otras opciones apropiadas
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    curl_setopt($ch, CURLOPT_COOKIESESSION, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, COOKIE_FILE);
    curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIE_FILE);

    curl_setopt($ch, CURLOPT_POST, count($params));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
    // Captura la URL y la envía al navegador
    $response = curl_exec($ch);
    // Cierrar el recurso cURLy libera recursos del sistema
    curl_close($ch);

    return $response;
}

function get($url) {
    // Crea un nuevo recurso cURL
    $ch = curl_init();


// Establece la URL y otras opciones apropiadas
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    curl_setopt($ch, CURLOPT_COOKIESESSION, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, COOKIE_FILE);
    curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIE_FILE);

// Captura la URL y la envía al navegador
    $response = curl_exec($ch);
    // Cierrar el recurso cURLy libera recursos del sistema
    curl_close($ch);

    return $response;
}

$login = post("http://www.urban-rivals.com/es/player/signin.php", array("login" => "Negruto",
    "password" => base64_decode("cHV0b2NhbHZvMzMz"),
    "action" => "ident",
    "frompage" => "")
);


if (strpos($login, "No ha sido posible identificarte.") === FALSE) {

    echo "OK";
    $market = get("http://www.urban-rivals.com/es/market/");
    
        $items = explode("marketOffersDiv", $market);
} else {
    
}


