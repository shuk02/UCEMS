<?php 
		$server="localhost";
		$lecturers="root";
		$password="";
		$database="ucems";
		$con=mysqli_connect($server,$lecturers,$password,$database);
		if(!$con)
		echo 'Connection failed !';
?>