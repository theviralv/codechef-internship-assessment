<?php
  //Links the database.
  
  $servername='localhost';
  $username='root';
  $password='';
  $dbname = "mydb";
  $conn=mysqli_connect($servername,$username,$password,$dbname);
  
  if(!$conn){
    die('Could not Connect MySql Server:' .mysql_error());
  }
?>
