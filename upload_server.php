<?php

require("config.php");
require("error.php");
require("update_DB.php");
require("administration.php");
//Get details from Session
if(isset($_SESSION['userID']) && isset($_SESSION['contestID'])){
	$userID = $_SESSION['userID'];
	$contestID = $_SESSION['contestID'];
}
else{
	$userID = 12345;
	$contestID = 123456;
}

//Read File
if (($_FILES["file"]["type"] == "image/jpeg") && ($_FILES["file"]["size"] > 0) && ($_FILES["file"]["size"]< $fileSize)){
 	//Generate Upload Path
 	$folderPath = $uploadDir.$contestID.'/'.$userID;
 	//Generate Thumbnail Path
 	//$thumbNailPath = $uploadDir.$thumbDir.$contestID.'/'.$userID;
 	//Generate ImageID
 	//Image ID = contestName + UserName +Data(date/month/year)+ timestamp
 	$imageName = generateImageID($userID, $contestID);
  $imageName = getImageID($userID, $imageName);
  if(checkUploadPath($folderPath)){
 		//Upload the file to server
 		if(move_uploaded_file($_FILES['file']['tmp_name'], $folderPath.'/'.$imageName.'.jpeg')){
        $sourcePath = $folderPath.'/'.$imageName.'.jpeg';
        if(validateFile($sourcePath)){
          $destinationPath = $processedImageFolder.$contestID.'/'.$userID;

          //Free Some memory for next operation  
          if(resizeAndWaterMarkImage($sourcePath,$destinationPath, $imageName, $watermarkTemplate)){
            $thumbDestnation = $destinationPath.'/'.$imageName.$thumbFileSufix.'.jpeg';
            //Update Thumbnail image
            createThumbNail($destinationPath.'/'.$imageName.'.jpeg' ,$thumbDestnation, $thumbSize);
          }
   				else{
            error(ERRWRITESERVER);
          }
        }
        else{
          error(ERREXIF);
        }
 		}
 		else{
 			error(ERRUPLOADSERVER);
 		}
 	}
 	else{
 		error(ERRWRITESERVER);
 	}
 	//After succesfully  uploading the files to server, Update DB
 	//Get form details
 	if(isset($_POST['caption'])){
 		$caption = $_POST['caption'];
 	}
 	else{
 		$caption = $defaultImageCaption;
 	}
 	if(isset($_POST['desc'])){
 		$description = $_POST['desc'];
 	}
 	else{
 		$description = "";
 	}
 	if(isset($_POST['category'])){
 		$category= $_POST['category'];
 	}
 	else{
 		$category = "";
 	}
 	if(isset($_POST['tags'])){
 		$tags = $_POST['tags'];
 	}
 	else{
 		$tags = "";
 	}
 	if(updateToDB($userID,$contestID,$caption,$description,$category,$tags,$folderPath,$imageName)){
 		//Display Success to user
 		echo "Success";
 	}
 	else{
    //If Database Update fails remove all files from folder and update user of failure
    rollback($userID,$contestID,$sourcePath,$destinationPath,$thumbDestnation,$imageName);
 		error(ERRUPLOADDB);
 	}
}
else
{
  echo "Invalid file";
  echo $_FILES["file"]["size"];
  echo $_FILES["file"]["type"];
}

//Validate File
function validateFile($sourceFile){
  include("config.php");
  if(extension_loaded ('exif')){
    $cameraDetails = cameraUsed($sourceFile);
    if(!isset($cameraDetails['make']) || 
      !isset($cameraDetails['model']) || 
      !isset($cameraDetails['date'])  ||
      !isset($cameraDetails['aperture'])||
      !isset($cameraDetails['iso'])){
        return false;
    }
    else{
        return true;
    }
  }
  else{
    if($IS_DEBUG == 1){
      return true;
    }
    else{
      NotifyAdmin("EXIF Extension Missing","","","");
      return flase;
    }
  }
}
//Resize and watermark the image
function resizeAndWaterMarkImage($sourcePath,$destinationPath, $imageName, $watermarkTemplate){
  require ("lib/WideImage.php");
  require("config.php");

  //WaterMark the Image
  $originalImage = WideImage::load($sourcePath);
  $resized = $originalImage->resize($resizeFileWidth, $resizeFileHeight);
  $watermark = WideImage::load($watermarkTemplate);
 // echo $watermarkTemplate;
  $resultImage = $resized->merge($watermark, 10, 10, 100);
  if(checkUploadPath($destinationPath)){
    $resultImage->saveToFile($destinationPath.'/'.$imageName.'.jpeg');
    return true;
  }
  else{
    return false;
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
//Create Thumbnail
function createThumbNail($src, $dest, $desired_width){
	  $source_image = imagecreatefromjpeg($src);
	  $width = imagesx($source_image);
	  $height = imagesy($source_image);
	  $desired_height = floor($height * ($desired_width / $width));
	  $virtual_image = imagecreatetruecolor($desired_width, $desired_height);
	  imagecopyresampled($virtual_image, $source_image, 0, 0, 0, 0, $desired_width, $desired_height, $width, $height);
	  imagejpeg($virtual_image, $dest);
}
//Generate Unique Image ID
function generateImageID($userID, $contestID){
	return $userID.$contestID;
}
//Error Messages
function error($message){
	die($message);
}

//Code not used currently
function cameraUsed($imagePath) {

    // Check if the variable is set and if the file itself exists before continuing
    if ((isset($imagePath)) and (file_exists($imagePath))) {
    
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
    
    } else {
      return false; 
    } 
}
function rollback($userID,$contestID,$sourcePath,$destinationPath,$thumbDestnation,$imageName){
  unlink($thumbDestnation);
  unlink($destinationPath.'/'.$imageName.'.jpeg');

 //Keep the original Image safe for now. 
 //Notify admin 

  NotifyAdmin("Roll Back", $userID,$contestID,$imageName);

}
?>