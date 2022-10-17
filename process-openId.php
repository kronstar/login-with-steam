<?php
session_start();
function p($arr){
    return '<pre>'.print_r($arr,true).'</pre>';
}

echo p($_GET);

//Xây dựng các tham số để gửi đến steam openid
$params = [
    'openid.assoc_handle' => $_GET['openid_assoc_handle'],
    'openid.signed'       => $_GET['openid_signed'],
    'openid.sig'          => $_GET['openid_sig'],
    'openid.ns'           => 'http://specs.openid.net/auth/2.0',
    'openid.mode'         => 'check_authentication',
];

$signed = explode(',', $_GET['openid_signed']);
    
foreach ($signed as $item) {
    $val = $_GET['openid_'.str_replace('.', '_', $item)];
    $params['openid.'.$item] = stripslashes($val);
}

echo p($params);

$data = http_build_query($params);
//data prep
$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Accept-language: en\r\n".
        "Content-type: application/x-www-form-urlencoded\r\n".
        'Content-Length: '.strlen($data)."\r\n",
        'content' => $data,
    ],
]);

//get data
$result = file_get_contents('https://steamcommunity.com/openid/login', false, $context);

if(preg_match("#is_valid\s*:\s*true#i", $result)){
    preg_match('#^https://steamcommunity.com/openid/id/([0-9]{17,25})#', $_GET['openid_claimed_id'], $matches);
    $steamID64 = is_numeric($matches[1]) ? $matches[1] : 0;
    echo 'Yêu cầu đã được xác thực bởi OpenID, trả về id ứng dụng của khách (Steam ID): ' . $steamID64;    

}else{
    echo 'Lỗi: Không thể xác thực yêu cầu!';
    exit();
}

$steam_api_key = '1EDC0D204A7716E809F0B2DABE207BE7';

$response = file_get_contents('https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key='.$steam_api_key.'&steamids='.$steamID64);
$response = json_decode($response,true);


$userData = $response['response']['players'][0];

$_SESSION['logged_in'] = true;
$_SESSION['userData'] = [
    'steam_id'=>$userData['steamid'],
    'name'=>$userData['personaname'],
    'avatar'=>$userData['avatarmedium'],
    'realname'=>$userData['realname'],
];

$redirect_url = "account.php";
header("Location: $redirect_url"); 
exit();