<?php

function updateToDB($userID,$contestID,$caption,$description,$category,$tags,$folderPath,$imageName){
	require("config.php");
	 //$imageID = getImageID($userID, $imageName);
	 $con = mysql_connect($mySqlServer, $mySqlUserName, $mySqlPass);
	 if (!$con) die('Could not connect: ' . mysql_error());
	  	mysql_select_db($mySqlTable, $con);
	  $date = date('Y-m-d');
	  $uploadDate = date('Y-m-d', strtotime($date));
	  $image_path = $folderPath.'/'.$imageName.'.jpeg';
	  //$imageName = intval($imageName);
	  //Increment Image ID Counter
	  //$imageName = $imageID+1;

	  //Check for special chars
	  $userID = mysql_real_escape_string($userID);
	  $imageName = mysql_real_escape_string($imageName);
	  $contestID = mysql_real_escape_string($contestID);
	  $caption = mysql_real_escape_string($caption);
	  $description = mysql_real_escape_string($description);
	  $category = mysql_real_escape_string($category);
	  $tags = mysql_real_escape_string($tags);
	  $folderPath = mysql_real_escape_string($folderPath);

	  
	  $sql = "INSERT INTO `IMAGES` (`IMGE_ID`, `CNTST_ID`, `USR_ID`, `IMGE_CPTION`, `IMGE_CAT`, `IMGE_TAGS`, `IMGE_ACTIVE`, `UPLOAD_DATE`) 
	  VALUES 
	  ('$imageName', '$contestID', '$userID', '$caption', '$category', '$tags', 'Y', '$uploadDate')";
	  //echo $sql;
	  if(!mysql_query($sql, $con)){
	  	return false;
	  }
	  	mysql_close($con);
	  	return true;
	}
	function getImageID($userID, $imageName){
		require("config.php");
		$imageID = 0;
	 	$con = mysql_connect($mySqlServer, $mySqlUserName, $mySqlPass);
	 	if (!$con) die('Could not connect: ' . mysql_error());
	  	mysql_select_db($mySqlTable, $con);

	  	$sql = "SELECT MAX(`IMGE_ID`) FROM `images` WHERE `USR_ID` = '$userID'";

	  	$result = mysql_query("SELECT MAX(`IMGE_ID`) FROM `IMAGES` WHERE `USR_ID` = '$userID'");
	  	while ($row = mysql_fetch_assoc($result)) {
	  		if(is_null($row['MAX(`IMGE_ID`)']))
	  			$imageID = $imageName*100;
	  		else
	  			$imageID = $row['MAX(`IMGE_ID`)']+1;
	  	}	

    	mysql_close($con);
    	return $imageID;
	}
?>