<?php
$IS_DEBUG = 1;
if($_SERVER['SERVER_NAME'] == "localhost"){
	$mySqlServer = "localhost";
	$mySqlUserName = "root";
	$mySqlPass = "";
	$mySqlTable = "table";
}
else{
	//Database Connection
	$mySqlServer = "localhost";
	$mySqlUserName = "root";
	$mySqlPass = "pass";
	$mySqlTable = "table";
}
//beyond123!@#A

//Server path
$uploadDir = 'uploads/';
$thumbDir = 'thumbnail/';
$thumbFileSufix = '-small';
$watermarkTemplate = 'img/watermark.png';
$processedImageFolder = 'submissions/';


//File Params
$fileSize = 20971520;
$thumbSize = 200;
$resizeFileWidth = 1024;
$resizeFileHeight = 768;
$defaultImageCaption = "Image Caption";

//Administration
$eMail = "email@gmail.com";
?>
