<?php

session_start();

if(isset($_COOKIE['username'])){
    $_SESSION['username'] = $_COOKIE['username'];
}

require 'vendor/autoload.php';
require_once 'db.php';  //To lonk database
require_once 'auth.php';    //To link authorization script.

$app = new \Slim\App([
    'settings' => [
        'displayErrorDetails' => true    
    ]    
]);


// Get container
$container = $app->getContainer();


// Register component on container
$container['view'] = function ($container) {
    $view = new \Slim\Views\Twig('resources/views', [
        'cache' => false
    ]);

    // Instantiate and add Slim specific extension
    $router = $container->get('router');
    $uri = \Slim\Http\Uri::createFromEnvironment(new \Slim\Http\Environment($_SERVER));
    $view->addExtension(new \Slim\Views\TwigExtension($router, $uri));

    return $view;
};


function csv_to_arr($csv){
    $arr = array();
    $tra = str_split($csv);
    $here = '';
    foreach($tra as $ch){
        if($ch === ','){
            $here = trim($here);
            array_push($arr, $here);
            $here = '';
            continue;
        }
        $here .= $ch;
    }
    if(!empty($here)){
        $here = trim($here);
        array_push($arr, $here);
    }
    return $arr;
}


$container["user"] = "@@@";


$container['csrf'] = function($container){
    $guard = new \Slim\Csrf\Guard;
    return $guard;
};

//For all get methods and for the home page calls.
$app->get('/', function($request, $response) use($conn){
    
    // When redirected to home-page while authorization from codechef.

    if(!isset($_SESSION['username']) && isset($_GET['code'])){
        $res = main($_GET['code']);
    
        if(isset($res['result']['data']['content']['username'])){
            $_SESSION['username'] = $res['result']['data']['content']['username'];
        }
    }

    // When some tag or a list of tags is/are searched.

    if(isset($_GET['search'])){
        $search = trim($_GET['search']);    //the searched list.
        $to_search = csv_to_arr($search);   //searched list stored in a array.
        sort($to_search);

        $searched = array();   //stores the non-user(non-personal) tags in the search list.
        $searched_user_tags = array();  //stores the user(personal) tags in the search list.

        $sql = "SELECT question FROM questions ";
        
        if(isset($_SESSION['username'])){
            $sql .= "WHERE (user = '@@@' OR user = '".$_SESSION['username']."') ";
        }
        else{
            $sql .= "WHERE (user = '@@@') ";
        }
        
        $sql .= "GROUP BY question HAVING ";
        
        for($i =0;$i<sizeof($to_search);$i++){
            $sql .= "SUM(CASE WHEN tag = '". $to_search[$i] ."' THEN 1 ELSE 0 END) > 0";
            if($i < sizeof($to_search)-1){
                $sql .= " AND ";
            }
            else{
                $sql .= ";";
            }
        }

        $result = $conn->query($sql);   //Query fetches all the questions having all tags linked to them.

        $s_q = "";  //To store the questions as a comma separated string.

        while(mysqli_num_rows($result) > 0 && $row = $result->fetch_assoc()){
            $s_q .= "'". $row['question'] ."',";
        }

        if(!empty($s_q) && $s_q[strlen($s_q)-1] == ','){
            $s_q = substr($s_q, 0, strlen($s_q)-1);
        }

        $sql = "SELECT question, tag, author, accuracy, submissions, user FROM questions 
            WHERE question IN (". $s_q .") AND ";
        
        if(isset($_SESSION['username'])){
            $sql .= "(user = '@@@' OR user = '".$_SESSION['username']."') ";
        }
        else{
            $sql .= "(user = '@@@') ";
        }
        
        $sql .= "ORDER BY question ASC";
        $result = $conn->query($sql);   //Query to fetch all the questions, tags and related information for all previously fetched questions.

        $questions = array();   //It stores all questions, tags(non-user, user, searched etc) to be diaplayed in frontend.

        if($result != null){
            $row = $result->fetch_assoc();
            $to_push = array(); //To store individual questions and related informations.
            $tags = array();    //To store non-personal tags.
            $user_tags = array();   //To store personal tags.
            
            array_push($to_push, $row["question"], $row["author"], $row["accuracy"], $row["submissions"]);
            
            if(array_search($row['tag'], $to_search) === false){    //If the tag is not searched
                if($row['user'] == '@@@'){
                    array_push($tags, $row['tag']);
                }
                else{
                    array_push($user_tags, $row['tag']);
                }
            }
            else{   //If its a searched tag. So that they get highlighted in frontend. 
                if($row['user'] == '@@@'){
                    array_push($searched, $row['tag']);
                }
                else{
                    array_push($searched_user_tags, $row['tag']);
                }
            }
            
            $now = $row["question"];    //It stores the current question code.

            while($row = $result->fetch_assoc()){
                
                // If the question fetched is not same as the current one.
                // We store the information for the current question. and update $now. 
                if($row['question'] != $now){
                    //Saving the data collected for the question.
                    array_push($to_push, $tags, $user_tags);
                    array_push($questions, $to_push);
                    
                    $now = $row['question'];
                    $to_push = array();
                    $tags = array();
                    $user_tags = array();
                    array_push($to_push, $row["question"], $row["author"], $row["accuracy"], $row["submissions"]);
                    
                    if(array_search($row['tag'], $to_search) === false){
                        if($row['user'] == '@@@'){
                            array_push($tags, $row['tag']);
                        }
                        else{
                            array_push($user_tags, $row['tag']);
                        }
                    }
                    else{
                        if($row['user'] == '@@@'){
                            array_push($searched, $row['tag']);
                        }
                        else{
                            array_push($searched_user_tags, $row['tag']);
                        }
                    }

                    continue;
                }
                
                if(array_search($row['tag'], $to_search) === false){
                    if($row['user'] == '@@@'){
                        array_push($tags, $row['tag']);
                    }
                    else{
                        array_push($user_tags, $row['tag']);
                    }
                }
                else{
                    if($row['user'] == '@@@'){
                        array_push($searched, $row['tag']);
                    }
                    else{
                        array_push($searched_user_tags, $row['tag']);
                    }
                }

                //Loops around till the question fetched is the same.
                //At last saves the information for the question.
                while($row = $result->fetch_assoc()){
                    if($row['question'] != $now){
                        break;
                    }
                    if(array_search($row['tag'], $to_search) === false){
                        if($row['user'] == '@@@'){
                            array_push($tags, $row['tag']);
                        }
                        else{
                            array_push($user_tags, $row['tag']);
                        }
                    }
                    else{
                        if($row['user'] == '@@@'){
                            array_push($searched, $row['tag']);
                        }
                        else{
                            array_push($searched_user_tags, $row['tag']);
                        }
                    }
                }
                //Saving the data collected.
                array_push($to_push, $tags, $user_tags);
                array_push($questions, $to_push);
                
                //If there are no questions left.
                if(!isset($row['question'])){
                    $to_push = array();
                    $tags = array();
                    $user_tags = array();
                    break;
                }
                
                $now = $row['question'];
                $to_push = array();
                $tags = array();
                $user_tags = array();
                array_push($to_push, $row["question"], $row["author"], $row["accuracy"], $row["submissions"]);
                
                if(array_search($row['tag'], $to_search) === false){
                    if($row['user'] == '@@@'){
                        array_push($tags, $row['tag']);
                    }
                    else{
                        array_push($user_tags, $row['tag']);
                    }
                }
                else{
                    if($row['user'] == '@@@'){
                        array_push($searched, $row['tag']);
                    }
                    else{
                        array_push($searched_user_tags, $row['tag']);
                    }
                }
            }
            
            //If data of last question remains unsaved.
            if(!empty($to_push)){
                array_push($to_push, $tags, $user_tags);
                array_push($questions, $to_push);
            }
        }

        //If there are No questions related to the searched query.
        //The page showing the user "No questions" is sent as response.
        if(sizeof($questions) === 0){
            if(isset($_SESSION['username'])){
                return $this->view->render($response, 'dug.twig', [
                    'user' => $_SESSION['username'],
                    'clear' => 1,
                    'search' => $search
                ]);
            }
            else{
                return $this->view->render($response, 'dug.twig', [
                    'user' => '@@@',
                    'clear' => 1,
                    'search' => $search
                ]);
            }    
        }

        if($search[strlen($search)-1] != ','){
            $search .= ',';
        }
        
        //If all things are right then page containing all questions list with tags and other data is returned.
        if(isset($_SESSION['username'])){
            return $this->view->render($response, 'dug.twig', [
                'search' => $search,
                'questions' => $questions,
                'user' => $_SESSION['username'],
                'searched' => array_unique($searched),
                'searched_user_tags' => array_unique($searched_user_tags)
            ]);
        }
        else{
            return $this->view->render($response, 'dug.twig', [
                'search' => $search,
                'questions' => $questions,
                'user' => '@@@',
                'searched' => array_unique($searched),
                'searched_user_tags' => array_unique($searched_user_tags)
            ]);
        }
    }
    
    //If no GET method called then the homepage that is all tags in a grid is returned.
    $sql = "SELECT tag, count, type, user FROM all_tags WHERE ";
    
    if(isset($_SESSION['username'])){
        $sql .= "user='@@@' OR user='".$_SESSION['username']."'";
    }
    else{
        $sql .= "user='@@@'";
    }
    
    $result = $conn->query($sql);   //SQL Query fetching all non-user and user tags.

    $arr = array();     //To store ALL the tags.
    $auth = array();    //To store all author type tags.
    $tag = array();     //To store all the actual_tags.
    $my_tags = array(); //To store all the user_tags.
    
    //Segregates the tags into diffrent types.
    if($result->num_rows > 0){
        while($row = $result->fetch_assoc()){
            array_push($arr, array($row["tag"], $row["count"], $row["type"]));
            if($row["type"] == 'author'){
                array_push($auth, array($row["tag"], $row["count"], $row["type"]));
            }
            else if($row["type"] == 'user_tag'){
                array_push($my_tags, array($row["tag"], $row["count"], $row["type"]));
            }
            else{
                array_push($tag, array($row["tag"], $row["count"], $row["type"]));
            }
        }
    }
    
    //renders the view as response. 
    if(isset($_SESSION['username'])){
        return $this->view->render($response, 'home.twig', [
            'arr' => $arr,
            'auth' => $auth,
            'tag' => $tag,
            'my_tags' => $my_tags,
            'user' => $_SESSION['username']
        ]);
    }
    else{
        return $this->view->render($response, 'home.twig', [
            'arr' => $arr,
            'auth' => $auth,
            'tag' => $tag,
            'my_tags' => $my_tags,
            'user' => '@@@'
        ]);
    }
})->setName('home');


//For all the post method calls.
$app->post('/', function($request, $response) use($conn, $app){
    $data = $request->getParams();  //get the post data.

    //Executes this if some user-tag linked to a certain question is deleted.(Just for the question) 
    if(isset($data['delete']) && isset($data['question'])){
        $sql = "DELETE FROM questions WHERE user='".$data['user']."' AND tag='".$data['tag']."' AND question='".$data['question']."'";
        $result = mysqli_query($conn, $sql);    //Query to delete row linking the question to the tag.
        
        $sql = "SELECT count FROM all_tags WHERE tag = '".$data['tag']."' AND user = '".$data['user']."' AND type = 'user_tag';";
        $result = mysqli_query($conn, $sql);    //Query to fetch the total count of the user-tag.
        
        $row = $result->fetch_assoc();
        $count = $row['count'];
        
        //If the count>1 then count-- in database.
        //Else delete the tag from the list as count-1 = 0.
        if($count > 1){
            $count--;
            $sql = "UPDATE all_tags SET count=".strval($count)." WHERE user='".$data['user']."' AND tag='".$data['tag']."'";
            $result = mysqli_query($conn, $sql);
        }
        else{
            $sql = "DELETE FROM all_tags WHERE user='".$data['user']."' AND tag='".$data['tag']."'";
            $result = mysqli_query($conn, $sql);
        }
        
        return;
    }
    
    //Executed if a tag has to deleted for all the questions.
    if(isset($data['delete'])){
        $sql = "DELETE FROM questions WHERE user='".$data['user']."' AND tag='".$data['tag']."'";
        $result = mysqli_query($conn, $sql);    //deletes all the questions linked to the row.

        $sql = "DELETE FROM all_tags WHERE user='".$data['user']."' AND tag='".$data['tag']."'";
        $result = mysqli_query($conn, $sql);    //deletes the row containg the tag.
        
        return;
    }

    //Executed if a new user-tag is created by the user for certain question. 
    if(isset($data['question'])){
        $sql = "SELECT question, tag, user FROM questions WHERE question='".$data['question']."' AND tag='".$data['tag']."' AND user='".$data['user']."';";
        $result = mysqli_query($conn, $sql);    //Query to check if the tag already exists for the question. 
        
        if($result->num_rows > 0){  //If tag already exists for the question then it is not inserted.
            return json_encode(false);
        }
        
        $sql = "INSERT INTO questions (question, tag, author, accuracy, submissions, user) VALUES ('".$data['question']."', '".$data['tag']."', '".$data['author']."', ".$data['acc'].", ".$data['subs'].", '".$data['user']."')"; 
        $result = mysqli_query($conn, $sql);    //Inserts the row linking the question to the user-tag.

        $sql = "SELECT count FROM all_tags WHERE tag = '".$data['tag']."' AND user = '".$data['user']."' AND type = 'user_tag';";
        $result = mysqli_query($conn, $sql);    //To fetch the count of the tag if it exists.
        
        if($result->num_rows > 0){  //If tag exists update the count.
            $row = $result->fetch_assoc();
            $count = $row['count'] + 1;
            $sql = "UPDATE all_tags SET count=".strval($count)." WHERE tag='".$data['tag']."' AND user = '".$data['user']."' AND type = 'user_tag';";
            $result = mysqli_query($conn, $sql);    
        }
        else{   //Else create new tag.
            $sql = "INSERT INTO all_tags (tag, type, count, user) VALUES ('".$data['tag']."', 'user_tag', 1, '".$_SESSION['username']."');";
            $result = mysqli_query($conn, $sql);
        }

        return json_encode(true); 
    }

    //Executed when user asks to login.
    if(isset($_POST['login']) && !isset($_SESSION['username'])){
        main();
    }

    //Executed when user asks to logout.
    if(isset($_POST['logout']) && isset($_SESSION['username'])){
        session_unset(); 
    }
    
    //The section noe returns the home page template.
    //It is executed only after lagin/logout.

    $sql = "SELECT tag, count, type, user FROM all_tags WHERE ";
    
    if(isset($_SESSION['username'])){
        $sql .= "user='@@@' OR user='".$_SESSION['username']."'";
    }
    else{
        $sql .= "user='@@@'";
    }
    
    $result = $conn->query($sql);
    
    $arr = array();
    $auth = array();
    $tag = array();
    $my_tags = array();
    
    if($result->num_rows > 0){
        while($row = $result->fetch_assoc()){
            array_push($arr, array($row["tag"], $row["count"], $row["type"]));
            
            if($row["type"] == 'author'){
                array_push($auth, array($row["tag"], $row["count"], $row["type"]));
            }
            else if($row["type"] == 'user_tag'){
                array_push($my_tags, array($row["tag"], $row["count"], $row["type"]));
            }
            else{
                array_push($tag, array($row["tag"], $row["count"], $row["type"]));
            }
        }
    }
    
    if(isset($_SESSION['username'])){
        return $this->view->render($response, 'home.twig', [
            'arr' => $arr,
            'auth' => $auth,
            'tag' => $tag,
            'my_tags' => $my_tags,
            'user' => $_SESSION['username']
        ]);
    }
    else{
        return $this->view->render($response, 'home.twig', [
            'arr' => $arr,
            'auth' => $auth,
            'tag' => $tag,
            'my_tags' => $my_tags,
            'user' => '@@@'
        ]);
    }
})->setName('home-post');

$app->run();
mysqli_close($conn);