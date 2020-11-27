<?php
//Script for authorization.


function take_user_to_codechef_permissions_page($config){

    $params = array('response_type'=>'code', 'client_id'=> $config['client_id'], 'redirect_uri'=> $config['redirect_uri'], 'state'=> 'xyz');
    header('Location: ' . $config['authorization_code_endpoint'] . '?' . http_build_query($params));
    die();
}

function generate_access_token_first_time($config, $oauth_details){

    $oauth_config = array('grant_type' => 'authorization_code', 'code'=> $oauth_details['authorization_code'], 'client_id' => $config['client_id'],
                          'client_secret' => $config['client_secret'], 'redirect_uri'=> $config['redirect_uri']);
    $response = json_decode(make_curl_request($config['access_token_endpoint'], $oauth_config), true);
    $result = $response['result']['data'];

    $oauth_details['access_token'] = $result['access_token'];
    $oauth_details['refresh_token'] = $result['refresh_token'];
    $oauth_details['scope'] = $result['scope'];

    return $oauth_details;
}

function generate_access_token_from_refresh_token($config, $oauth_details){
    $oauth_config = array('grant_type' => 'refresh_token', 'refresh_token'=> $oauth_details['refresh_token'], 'client_id' => $config['client_id'],
        'client_secret' => $config['client_secret']);
    $response = json_decode(make_curl_request($config['access_token_endpoint'], $oauth_config), true);
    $result = $response['result']['data'];

    $oauth_details['access_token'] = $result['access_token'];
    $oauth_details['refresh_token'] = $result['refresh_token'];
    $oauth_details['scope'] = $result['scope'];

    return $oauth_details;

}

function make_api_request($oauth_config, $path){
    $headers[] = 'Authorization: Bearer ' . $oauth_config['access_token'];
    return make_curl_request($path, false, $headers);
}


function make_curl_request($url, $post = FALSE, $headers = array())
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

    if ($post) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
    }

    $headers[] = 'content-Type: application/json';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    return $response;
}

function make_username_request($config,$oauth_details){
    $path = $config['api_endpoint']."users/me";
    $response = make_api_request($oauth_details, $path);
    return $response;
}

function main($coco = '@@@'){

    $config = array('client_id'=> 'a1eeee1f36779d59986c13c2014d36a1',
        'client_secret' => 'e91cfb86cd5e9667287aa2d19f351412',
        'api_endpoint'=> 'https://api.codechef.com/',
        'authorization_code_endpoint'=> 'https://api.codechef.com/oauth/authorize',
        'access_token_endpoint'=> 'https://api.codechef.com/oauth/token',
        'redirect_uri'=> 'http://localhost/codechef',
        'website_base_url' => 'http://localhost/codechef');

    $oauth_details = array('authorization_code' => '',
        'access_token' => '',
        'refresh_token' => '');

    if($coco != '@@@'){
        $oauth_details['authorization_code'] = $coco;
        $oauth_details = generate_access_token_first_time($config, $oauth_details);
        $response = make_username_request($config, $oauth_details);
        //$oauth_details = generate_access_token_from_refresh_token($config, $oauth_details);         //use this if you want to generate access_token from refresh_token
        $res = json_decode($response, true);
        return $res; //['result']['data']['content']['username'];
    }
    else{
        take_user_to_codechef_permissions_page($config);
    }
}

// function main2(){

//     $config = array('client_id'=> '41723511c73839ecdece5fa90fa569fe7e438ad2',
//         'client_secret' => 'e91cfb86cd5e9667287aa2d19f351412',
//         'api_endpoint'=> 'https://api.codechef.com/',
//         'authorization_code_endpoint'=> 'https://api.codechef.com/oauth/authorize',
//         'access_token_endpoint'=> 'https://api.codechef.com/oauth/token',
//         'redirect_uri'=> 'http://localhost/codechef',
//         'website_base_url' => 'http://localhost/codechef');

//     $oauth_details = array('authorization_code' => '',
//         'access_token' => '',
//         'refresh_token' => '');

//     // if($coco != '@@@'){
//         $oauth_details['authorization_code'] = $coco;
//         $oauth_details = generate_access_token_first_time($config, $oauth_details);
//         $response = make_username_request($config, $oauth_details);
//         //$oauth_details = generate_access_token_from_refresh_token($config, $oauth_details);         //use this if you want to generate access_token from refresh_token
//         $res = json_decode($response, true);
//         return $res; //['result']['data']['content']['username'];
//     // } else{
//         // take_user_to_codechef_permissions_page($config);
//     // }
// }
?>