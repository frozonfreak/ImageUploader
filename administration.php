<?php
require("config.php");
	if(isset($_POST['status'])){
		if(trim($_POST['status']) == "error"){
			$messageSubject = "File Upload Error";
			$messageContent = str_replace("\n.", "\n..",trim($_POST['data']));
			$messageContent = wordwrap($messageContent, 70, "\r\n");
			mail($eMail, $messageSubject, $messageContent);
		}
	}
	function NotifyAdmin($message, $userID,$contestID,$imageName){
		$messageSubject = "File Roll Back";
		$messageContent = "File Roll Back User: ".$userID." Contest: ".$contestID." Image: ".$imageName;
		$messageContent = wordwrap($messageContent, 70, "\r\n");
		mail($eMail, $messageSubject, $messageContent);
	}
?>
