<?php
require_once "db.php";

session_start();

//Script for the Auto-complete search bar.

if (isset($_GET['term'])){
  $query = "SELECT * FROM all_tags WHERE tag LIKE '%{$_GET['term']}%' ";
  
  if(isset($_SESSION['username'])){
    $query .= "AND (user='@@@' OR user='".$_SESSION['username']."') LIMIT 25;";
  }
  else{
    $query .= "AND user='@@@' LIMIT 25;";
  }
  
  $result = mysqli_query($conn, $query);

  if (mysqli_num_rows($result) > 0) {
    while ($user = mysqli_fetch_array($result)) {
      $res[] = $user['tag'];
    }
  }
  else {
    $res = array();
  }
  
  echo json_encode($res);
}
?>