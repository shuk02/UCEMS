<?php 
	$server="localhost";
	$user="root";
	$password="";
	$database="ucems";
	$con=mysqli_connect($server,$user,$password,$database);
	if(!$con)
		echo 'Connection failed !';
?>