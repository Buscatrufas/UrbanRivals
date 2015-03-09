<?php

header("Content-Type: text/plain");
error_reporting(E_ALL);

require_once("oauth/OAuth.php"); // From http://oauth.googlecode.com/svn/code/php/
// ===== CUSTOM SYSTEM FOR LINKING/STORING _YOUR_ USER ID WITH THE URBAN API TOKEN, YOU WILL NEED TO MODIFY IT =====
// Using session as a "store" for the simplicity of the demo
session_start();

function remote_get_contents($uri) {
    $content = @file_get_contents($uri);
    if (!$content && extension_loaded('curl')) {
        $c = curl_init();
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_URL, $uri);
        $content = curl_exec($c);
        curl_close($c);
    }
    return $content;
}

function saveOAuthToken($user_id, $token_key, $token_secret, $type_of_token = 'request') {
    // I'm saving in a temp session, you'll certainly insert/update in your DB to link your user with the urban api token.
    $urban_api_token = array(
        'key' => $token_key,
        'secret' => $token_secret,
        'type' => $type_of_token
    );

    $_SESSION['urban_api_token'] = $urban_api_token;

    return $urban_api_token;
}

// Get the current oauth token save for a user
function getOAuthToken($user_id) {
    // I'm storing in a temp session (that's why I'm not using a user_id), but you might want to do a database lookup here
    if (array_key_exists('urban_api_token', $_SESSION))
        return $_SESSION['urban_api_token'];
    else
        return false;
}

// ===== END OF CUSTOM SYSTEM =====



define('COOKIE_FILE', "cookies.oven");
class WebPage
{
    

    function post($url, $params) {
        $data = "";
        foreach ($params as $key => $value) {
            $data .= ($data == "") ? "" : "&";
            $data .= $key . "=" . urlencode($value);
        }

        // Crea un nuevo recurso cURL
        $ch = curl_init();

        // Establece la URL y otras opciones apropiadas
        curl_setopt($ch, CURLOPT_URL, $url); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt( $ch, CURLOPT_COOKIESESSION, true );
        curl_setopt( $ch, CURLOPT_COOKIEJAR, COOKIE_FILE );
        curl_setopt( $ch, CURLOPT_COOKIEFILE, COOKIE_FILE );

        curl_setopt($ch, CURLOPT_POST, count($params));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        // Captura la URL y la envía al navegador
        $response = curl_exec($ch);

        // Cierrar el recurso cURLy libera recursos del sistema
        curl_close($ch);

        return $response;
    }

    function get($url)
    {
    // Crea un nuevo recurso cURL
        $ch = curl_init();

        // Establece la URL y otras opciones apropiadas
        curl_setopt($ch, CURLOPT_URL, $url); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt( $ch, CURLOPT_COOKIESESSION, true );
        curl_setopt( $ch, CURLOPT_COOKIEJAR, COOKIE_FILE );
        curl_setopt( $ch, CURLOPT_COOKIEFILE, COOKIE_FILE );

        // Captura la URL y la envía al navegador
        $response = curl_exec($ch);

        // Cierrar el recurso cURLy libera recursos del sistema
        curl_close($ch);

        return $response;

    }

    public function login()
    {
        $login = $this->post( "http://www.urban-rivals.com/es/player/signin.php", array("login" => "Negruto",
            "password" => base64_decode("cHV0b2NhbHZvMzMz"),
            "action" => "ident",
            "frompage" => "")
                );

        if(strpos($login, "No ha sido posible identificarte.") === FALSE)
        {
            return true;
        }
        return false;
    }
    
    public function buyItem($itemId, $captha = "")
    {
        $result = $this->post("http://www.urban-rivals.com/ajax/market/", array("action" => "purchase",
            "id_bourse" => $itemId,
            "captcha" => $captha));
        $json = json_decode($result, true);
        
        return ($json['errorCode'] == 0);
        
    }
    
    public function sellItem($itemId, $price)
    {
        $result = $this->post("http://www.urban-rivals.com/ajax/collection/sell_card.php", array("price" => $price,
            "id_preso_joeur" => $itemId
                ));
        $json = json_decode($result, true);
        return ($json['errorCode'] == 0);
    }



}
/**
 * ApiRequest
 */
class ApiRequest {

    private $apiURL;
    private $oauthConsumer;
    private $oauthAccessToken;
    private $sigMethod;
    private $apiCalls = array();

    function __construct($apiURL, $oauthConsumer, $oauthAccessToken, $sigMethod) {
        $this->apiURL = $apiURL;
        $this->oauthConsumer = $oauthConsumer;
        $this->oauthAccessToken = $oauthAccessToken;
        $this->sigMethod = $sigMethod;
    }

    public function addApiCall($callName, $callParams = array()) {
        $this->apiCalls[] = array(
            'call' => $callName,
            'params' => $callParams
        );
    }

    public function reset() {
        $this->apiCalls = array();
    }

    public function execute($reset = false) {
        if (!$this->apiCalls) {
            die("Can't execute request as there are no apiCalls");
        }

        $params = array('request' => json_encode($this->apiCalls));

        // ... pass the json encoded api request as the "request" parameter of the OAuth request
        // Use the consumer (your app/site) and the access token (the user authorized token) to build the actual signed http request
        $api_req = OAuthRequest::from_consumer_and_token($this->oauthConsumer, $this->oauthAccessToken, "GET", $this->apiURL, $params);
        $api_req->sign_request($this->sigMethod, $this->oauthConsumer, $this->oauthAccessToken);

        $data = remote_get_contents($api_req->__toString());
        if($apiResponse = json_decode($data, true))
        {
            
            if ($reset)
                $this->reset();
            return $apiResponse;
        }
        else
        {
            echo $data;
        }

    }

}

// Your api key/secret - callback url is your site url where you want the user to go after authorizing your app (basically, _this_ php script but maybe you want to build a more complicated process)
$api_key = 'c483d972eade29624dbd67472109374d054dffbe4';
$api_secret = '8fc8d867aaf340db136ca6de56146bc0';
$callback_url = "http://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];

// OAuth parameters (from http://www.urban-rivals.com/api)
$request_token_url = "http://www.urban-rivals.com/api/auth/request_token.php";
$authorize_token_url = "http://www.urban-rivals.com/api/auth/authorize.php";
$access_token_url = "http://www.urban-rivals.com/api/auth/access_token.php";
$api_url = "http://www.urban-rivals.com/api/";

// User id in the external site - should be something real in your case
// You'll then link this user id in your site with the api token
$user_id = 0;


if (!$api_key || !$api_secret)
    die("You must set your Urban API key & secret - go get one: http://www.urban-rivals.com/api/ !");

$oauthConsumer = new OAuthConsumer($api_key, $api_secret);
$sigMethod = new OAuthSignatureMethod_HMAC_SHA1();

$urban_api_token = getOAuthToken($user_id);

// No token at all - request one
if (!$urban_api_token) {
    // Request a token using your consumer
    $req_req = OAuthRequest::from_consumer_and_token($oauthConsumer, NULL, "GET", $request_token_url, NULL);
    $req_req->sign_request($sigMethod, $oauthConsumer, NULL);

    // Get the request token
    $request_token_str = remote_get_contents($req_req->__toString());
    // Parse the response (it will create 2 globals variables "oauth_token" & "oauth_token_secret")
    parse_str($request_token_str);

    if (!$oauth_token || !$oauth_token_secret)
        die("Error while getting the request token: " . $request_token_str);

    // Save them, linked to the current user id on your site
    saveOAuthToken($user_id, $oauth_token, $oauth_token_secret, 'request');

    // Redirect the user's browser to the authorization page on Urban Rivals website
    Header("Location: " . $authorize_token_url . "?oauth_token=" . $oauth_token . "&oauth_callback=" . urlencode($callback_url));
}
// Already got a request token - hopefully we come back from authorization, use the authorized request token to get an access token
else if ($urban_api_token['type'] == 'request') {
    $oauthRequestToken = new OAuthToken($urban_api_token['key'], $urban_api_token['secret']);

    // Request an access token using your consumer (representing you) and the authorized request token (representing the user)
    $acc_req = OAuthRequest::from_consumer_and_token($oauthConsumer, $oauthRequestToken, "GET", $access_token_url, NULL);
    $acc_req->sign_request($sigMethod, $oauthConsumer, $oauthRequestToken);

    // Get the access token
    $access_token_str = remote_get_contents($acc_req->__toString());
    // Parse the response (it will create 2 globals variables "oauth_token" & "oauth_token_secret")
    parse_str($access_token_str);

    if (!$oauth_token || !$oauth_token_secret)
        die("Error while getting the access token: " . $request_token_str);

    // Save them, linked to the current user id on your site
    $urban_api_token = saveOAuthToken($user_id, $oauth_token, $oauth_token_secret, 'access');

    // Now you have a usable access token for the user, you can start having fun with the api!
}


// Got an access token! Make calls to the api
if ($urban_api_token['type'] == 'access') {
//    die("LOL!");
    $oauthAccessToken = new OAuthToken($urban_api_token['key'], $urban_api_token['secret']);

    $apiRequest = new ApiRequest($api_url, $oauthConsumer, $oauthAccessToken, $sigMethod);
    global $apiRequest;
    function list_market($apiRequest)
    {
        global $apiRequest;
        $apiRequest->addApiCall('market.getMarketPage', array("page" => 0, "nbPerPage" => 50, "sortBy" => "date", "orderBy" => "desc"));

        $apiResponse = $apiRequest->execute(true);

        return($apiResponse['market.getMarketPage']['items']);
    }
    
    function list_offers($id)
    {
        global $apiRequest;
        $apiRequest->addApiCall('market.getOffersForCharacter', array("characterID" => $id));

        $apiResponse = $apiRequest->execute(true);

        return($apiResponse['market.getOffersForCharacter']['items']);
    }
    $items = list_market();
    
    $json = json_decode(file_get_contents("data.json"), true);
    if(!$json) $json = array();
    file_put_contents("data.json", json_encode($json));
    foreach ($items as $value) {
        $array = array();
        $array['data'] =  $value;
        $array['time'] = time();
        $json[$value['id']][] = $array;
    }
    
    foreach ($json as $key => $val) {
        $result = array();
        foreach ($val as $item) {
            if($item['time'] > time() - (60 * 60 * 24))
            {
                $result[] = $item;
            }
        }
        
        $json[$key] = $result;
    }
    
    
    foreach ($json as $key => & $val) {
        $value = -1;
        
        if(count($val) >= 2)
        {
            $a = & $val[count($val) - 2];
            $b = $val[count($val) - 1];
            
            if($a['data']['min_price'] > $b['data']['min_price'])
            {
                $precent = (($a['data']['min_price'] -  $b['data']['min_price'])/$a['data']['min_price']) * 100;
                if($precent > 6)
                {
                    if(!isset($a['buy']) || !$a['buy'])
                    {
                        $a['buy'] = true;
                        echo "Buy: " . $key . "\n";
                    }
                }
            }
            
        }
        
    }
    file_put_contents("data.json", json_encode($json, 128));
    
}
?>