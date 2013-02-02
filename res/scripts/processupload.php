<noscript>
<div align="center">Tsk Tsk. No JavaScript. <a href="index.php">Go Back To Upload Form</a></div><!-- If javascript is disabled -->
</noscript>
<?php
error_reporting(E_ALL);

if(isset($_POST))
{
	//Some Settings
	$ThumbSquareSize 		= 200; //Thumbnail will be 200x200
	$BigImageMaxSize 		= 1024; //Image Maximum height or width
	$ThumbPrefix			= "thumb_"; //Normal thumb Prefix
	$DestinationDirectory	= '../../contestentry/'; //Upload Directory ends with / (slash)
	$SourceDirectory		= '../../uploads/'; //Original Images are stored here
	$WatermarkTemplate      = '../img/watermark.png';
	$Quality 				= 90;

	if(!isset($_SESSION['userID']) || !isset($_SESSION['contestID']))
			die('You need to login to submit');


	// check $_FILES['ImageFile'] array is not empty
	// "is_uploaded_file" Tells whether the file was uploaded via HTTP POST
	if(!isset($_FILES['ImageFile']) || !is_uploaded_file($_FILES['ImageFile']['tmp_name']))
	{
			die('Something went wrong with Upload!'); // output error when above checks fail.
	}

	// Random number for both file, will be added after image name
	$RandomNumber 	= rand(0, 9999999999); 

	// Elements (values) of $_FILES['ImageFile'] array
	//let's access these values by using their index position
	$ImageName 		= str_replace(' ','-',strtolower($_FILES['ImageFile']['name'])); 
	$ImageSize 		= $_FILES['ImageFile']['size']; // Obtain original image size
	$TempSrc	 	= $_FILES['ImageFile']['tmp_name']; // Tmp name of image file stored in PHP tmp folder
	$ImageType	 	= $_FILES['ImageFile']['type']; //Obtain file type, returns "image/png", image/jpeg, text/plain etc.

	//EXIF Details
	$ImageEXIF    	= ExtractEXIFData($_FILES['ImageFile']['tmp_name']);
	
	//Elements to store in DB
	$Caption        = $_POST['caption'];
	$Description    = "";//Currently not in Use
	$Category       = $_POST['category'];
	$Tags           = $_POST['tags'];

	switch(strtolower($ImageType))
	{
		case 'image/jpeg':
		case 'image/pjpeg':
			$CreatedImage = imagecreatefromjpeg($_FILES['ImageFile']['tmp_name']);
			break;
		default:
			die('Unsupported File!'); //output error and exit
	}

	//PHP getimagesize() function returns height-width from image file stored in PHP tmp folder.
	//Let's get first two values from image, width and height. list assign values to $CurWidth,$CurHeight
	list($CurWidth,$CurHeight)=getimagesize($TempSrc);
	//Get file extension from Image name, this will be re-added after random name
	$ImageExt = substr($ImageName, strrpos($ImageName, '.'));
  	$ImageExt = str_replace('.','',$ImageExt);
	
	//remove extension from filename
	$ImageName 		= preg_replace("/\\.[^.\\s]{3,4}$/", "", $ImageName); 
	
	//Construct a new image name (with random number added) for our new image.
	$NewImageName = getImageID($UserID,$ContestID).'.'.$ImageExt;

	//set the Destination Image
	$thumb_DestRandImageName 	= $DestinationDirectory.$ContestID.'/'.$UserID.'/'.$ThumbPrefix.$NewImageName; //Thumb name
	$DestRandImageName 			= $DestinationDirectory.$ContestID.'/'.$UserID.'/'.$NewImageName; //Name for Big Image

	//Create folder with Contest ID
	if(!checkUploadPath($DestinationDirectory.$ContestID.'/'.$UserID.'/') || !checkUploadPath($SourceDirectory.$ContestID.'/'.$UserID.'/'))
	{
		die("Unable to create folders");
	}
	//Store the original Image with no WaterMark
	if(!move_uploaded_file($_FILES['ImageFile']['tmp_name'], $SourceDirectory.$ContestID.'/'.$UserID.'/'.$NewImageName))
	{
		die('Error uploading Image');
	}
	//Resize image to our Specified Size by calling resizeImage function.
	if(resizeImage($CurWidth,$CurHeight,$BigImageMaxSize,$DestRandImageName,$CreatedImage,$Quality,$ImageType,$WatermarkTemplate))
	{
		//Create a square Thumbnail right after, this time we are using cropImage() function
		if(!cropImage($CurWidth,$CurHeight,$ThumbSquareSize,$thumb_DestRandImageName,$CreatedImage,$Quality,$ImageType,$WatermarkTemplate))
			{
				echo 'Error Creating thumbnail';
			}
		
		echo "Submission Successfull";

		if(!UpdateDB($UserID,$ContestID,$Caption,$Description,$Category,$Tags,$DestRandImageName,$NewImageName))
		{
			die("Database Error");
		}

	}else{
		die('Resize Error'); //output error
	}
}
// This function will proportionally resize image 
function resizeImage($CurWidth,$CurHeight,$MaxSize,$DestFolder,$SrcImage,$Quality,$ImageType,$WatermarkTemplate)
{
	//Check Image size is not 0
	if($CurWidth <= 0 || $CurHeight <= 0) 
	{
		return false;
	}
	
	//Construct a proportional size of new image
	$ImageScale      	= min($MaxSize/$CurWidth, $MaxSize/$CurHeight); 
	$NewWidth  			= ceil($ImageScale*$CurWidth);
	$NewHeight 			= ceil($ImageScale*$CurHeight);
	
	if($CurWidth < $NewWidth || $CurHeight < $NewHeight)
	{
		$NewWidth = $CurWidth;
		$NewHeight = $CurHeight;
	}
	$NewCanves 	= imagecreatetruecolor($NewWidth, $NewHeight);
	// Resize Image
	if(imagecopyresampled($NewCanves, $SrcImage,0, 0, 0, 0, $NewWidth, $NewHeight, $CurWidth, $CurHeight))
	{

		require_once ("../lib/WideImage.php");
		switch(strtolower($ImageType))
		{		
			case 'image/jpeg':
			case 'image/pjpeg':
				imagejpeg($NewCanves,$DestFolder,$Quality);

				//WaterMark the Image
				$NewCanves = WideImage::load($DestFolder);
				$Watermark = WideImage::load($WatermarkTemplate);
				$NewCanves = $NewCanves->merge($Watermark, 10, 10, 100);
				$NewCanves->saveToFile($DestFolder);
				break;
			default:
				return false;
		}
	//Destroy image, frees up memory	
	if(is_resource($NewCanves)) {imagedestroy($NewCanves);} 
	return true;
	}

}
//This function corps image to create exact square images, no matter what its original size!
function cropImage($CurWidth,$CurHeight,$iSize,$DestFolder,$SrcImage,$Quality,$ImageType,$WatermarkTemplate)
{	 
	//Check Image size is not 0
	if($CurWidth <= 0 || $CurHeight <= 0) 
	{
		return false;
	}
	
	
	if($CurWidth>$CurHeight)
	{
		$y_offset = 0;
		$x_offset = ($CurWidth - $CurHeight) / 2;
		$square_size 	= $CurWidth - ($x_offset * 2);
	}else{
		$x_offset = 0;
		$y_offset = ($CurHeight - $CurWidth) / 2;
		$square_size = $CurHeight - ($y_offset * 2);
	}
	
	$NewCanves 	= imagecreatetruecolor($iSize, $iSize);	
	if(imagecopyresampled($NewCanves, $SrcImage,0, 0, $x_offset, $y_offset, $iSize, $iSize, $square_size, $square_size))
	{
		require_once ("../lib/WideImage.php");
		switch(strtolower($ImageType))
		{	
			case 'image/jpeg':
			case 'image/pjpeg':
				imagejpeg($NewCanves,$DestFolder,$Quality);

				//WaterMark the Image
				$NewCanves = WideImage::load($DestFolder);
				$Watermark = WideImage::load($WatermarkTemplate);
				$NewCanves = $NewCanves->merge($Watermark, 10, 10, 100);
				$NewCanves->saveToFile($DestFolder);
				break;
			default:
				return false;
		}
	//Destroy image, frees up memory	
	if(is_resource($NewCanves)) {imagedestroy($NewCanves);} 
	return true;
	}
	  
}
//Check for path and create path if necessary
function checkUploadPath($folderPath){
	if(is_dir($folderPath)){
		return true;
	}
	else{
		if(!mkdir($folderPath.'/', 0777,true))
      		return false;
    	else
      		return true;
	}
}

//Function to update database
function UpdateDB($UserID,$ContestID,$Caption,$Description,$Category,$Tags,$DestRandImageName,$NewImageName)
{
	require("config.php");
	 $con = mysql_connect($mySqlServer, $mySqlUserName, $mySqlPass);
	 if (!$con) die('Could not connect: ' . mysql_error());
	  	mysql_select_db($mySqlTable, $con);
	  $date = date('Y-m-d');
	  $uploadDate = date('Y-m-d', strtotime($date));
	  $image_path = $DestRandImageName;
	  $Caption = mysql_real_escape_string($Caption);
	  $Category = mysql_real_escape_string($Category);
	  $Tags = mysql_real_escape_string($Tags);
	  
	  $sql = "INSERT INTO `IMAGES` (`IMGE_ID`, `CNTST_ID`, `USR_ID`, `IMGE_CPTION`, `IMGE_CAT`, `IMGE_TAGS`, `IMGE_ACTIVE`, `UPLOAD_DATE`) 
	  VALUES 
	  ('$NewImageName', '$ContestID', '$UserID', '$Caption', '$Category', '$Tags', 'Y', '$uploadDate')";
	  //echo $sql;
	  if(!mysql_query($sql, $con)){
	  	return false;
	  }
	  	mysql_close($con);
	  	return true;
}

//Function to generate Image ID
function getImageID($UserID, $ContestID)
{
		require("config.php");
		$ImageName = $UserID.$ContestID;
		$imageID = 0;
	 	$con = mysql_connect($mySqlServer, $mySqlUserName, $mySqlPass);
	 	if (!$con) die('Could not connect: ' . mysql_error());
	  	mysql_select_db($mySqlTable, $con);

	  	$sql = "SELECT MAX(`IMGE_ID`) FROM `images` WHERE `USR_ID` = '$UserID'";

	  	$result = mysql_query("SELECT MAX(`IMGE_ID`) FROM `IMAGES` WHERE `USR_ID` = '$UserID'");
	  	while ($row = mysql_fetch_assoc($result)) {
	  		if(is_null($row['MAX(`IMGE_ID`)']))
	  			$imageID = $ImageName*100;
	  		else
	  			$imageID = $row['MAX(`IMGE_ID`)']+1;
	  	}	

    	mysql_close($con);
    	return $imageID;
}

//Extract EXIF data from Image
function ExtractEXIFData($imagePath)
{
	if(!extension_loaded ('exif'))
		die("Server does have EXIF extension enabled");

	// There are 2 arrays which contains the information we are after, so it's easier to state them both
    $exif_ifd0 = exif_read_data($imagePath ,'IFD0' ,0);       
    $exif_exif = exif_read_data($imagePath ,'EXIF' ,0);
      
    //error control
    $notFound = "Unavailable";
      
    // Make 
    if (@array_key_exists('Make', $exif_ifd0)) {
      $camMake = $exif_ifd0['Make'];
    } else { $camMake = $notFound; }
      
    // Model
    if (@array_key_exists('Model', $exif_ifd0)) {
      $camModel = $exif_ifd0['Model'];
    } else { $camModel = $notFound; }
      
    // Exposure
    if (@array_key_exists('ExposureTime', $exif_ifd0)) {
      $camExposure = $exif_ifd0['ExposureTime'];
    } else { $camExposure = $notFound; }

    // Aperture
    if (@array_key_exists('ApertureFNumber', $exif_ifd0['COMPUTED'])) {
      $camAperture = $exif_ifd0['COMPUTED']['ApertureFNumber'];
    } else { $camAperture = $notFound; }
      
    // Date
    if (@array_key_exists('DateTime', $exif_ifd0)) {
      $camDate = $exif_ifd0['DateTime'];
    } else { $camDate = $notFound; }
      
    // ISO
    if (@array_key_exists('ISOSpeedRatings',$exif_exif)) {
      $camIso = $exif_exif['ISOSpeedRatings'];
    } else { $camIso = $notFound; }
      
    $return = array();
    $return['make'] = $camMake;
    $return['model'] = $camModel;
    $return['exposure'] = $camExposure;
    $return['aperture'] = $camAperture;
    $return['date'] = $camDate;
    $return['iso'] = $camIso;
    return $return;
}