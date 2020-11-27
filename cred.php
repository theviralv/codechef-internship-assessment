<?php
//Script used to fetch data from Codechef API.

require_once 'db.php';


function take_user_to_codechef_permissions_page($config){
    $params = array('response_type'=>'code', 'client_id'=> $config['client_id'], 'redirect_uri'=> $config['redirect_uri'], 'state'=> 'xyz');
    header('Location: ' . $config['authorization_code_endpoint'] . '?' . http_build_query($params));
    die();
}


function generate_access_token_first_time($config, $oauth_details){

    $oauth_config = array('grant_type' => 'client_credentials', 'scope'=> 'public', 'client_id' => $config['client_id'],
                          'client_secret' => $config['client_secret'], 'redirect_uri'=> $config['redirect_uri']);
    $response = json_decode(make_curl_request($config['access_token_endpoint'], $oauth_config), true);
    $result = $response['result']['data'];

    $oauth_details['access_token'] = $result['access_token'];

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


function make_contest_problem_api_request($config,$oauth_details, $tag){
    $problem_code = "100";
    $path = $config['api_endpoint']."tags/"."problems?filter=".$tag."&limit=".$problem_code;
    $response = make_api_request($oauth_details, $path);
    return $response;
}


function main($tag){

    $config = array('client_id'=> 'f634232ade6ecebcebbca71b521ee0c1',
        'client_secret' => 'b0804d41fb34010db02e914a79f0f746',
        'api_endpoint'=> 'https://api.codechef.com/',
        'authorization_code_endpoint'=> 'https://api.codechef.com/oauth/authorize',
        'access_token_endpoint'=> 'https://api.codechef.com/oauth/token',
        'redirect_uri'=> 'http://localhost/codechef',
        'website_base_url' => 'http://localhost/codechef');

    $oauth_details = array('client_credentials' => '5ade26d3ffde888909dee1eeb5e9a187ce849c30',
        'access_token' => '',
        'refresh_token' => '');

        $oauth_details = generate_access_token_first_time($config, $oauth_details);
        $response = make_contest_problem_api_request($config, $oauth_details, $tag);
        return json_decode($response, true);
}


$sql = "SELECT tag from all_tags WHERE tag_id NOT BETWEEN 1 AND 839";
$tags = $conn->query($sql);
$id = 1;
$cnt = 0;


while($id < 1100 && $tag = mysqli_fetch_array($tags)){
    $cnt++;
    $id++;
    $arr = main($tag['tag']);
    foreach($arr["result"]["data"]["content"] as $key => $value) {
        for($i = 0;$i<sizeof($value['tags']);$i++){
            $here = $value['tags'][$i];
            $sql = "INSERT INTO questions (question, tag, author, solved, attempted)
                VALUES ('". $key. "','". $here ."','". $value['author'] ."','". $value['solved'] ."','". $value['attempted'] ."')";
            $conn->query($sql);
        }
    }
    if($cnt == 30){
        $cnt = 0;
        sleep(302);
    }
}


$conn->close();