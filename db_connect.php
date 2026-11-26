<?php 
	//SQL Connection	
	$Local = 'localhost';
	$Username = "root";
	$Password = "";		
	$DBname = "kapwa_kabataan_db";	
	
	//InfinityFree
	//$Local = 'sql312.infinityfree.com';
	//$Username = "if0_42493868";
	//$Password = "cy9FmgyiGoZxEK";		
	//$DBname = "if0_42493868_kapwa_kabataan_db";	
		
	$Connection = mysqli_connect($Local, $Username, $Password, $DBname);
?>