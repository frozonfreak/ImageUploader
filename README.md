ImageUploader
=============

PHP AJAX Image Uploader

Image Uploader along with form for Image Caption, Description, Category and Tags 

Resize Image, Watermark , extract EXIF data and save them to db

Create DB with 

CREATE TABLE IF NOT EXISTS `IMAGES` (
  `IMGE_ID` bigint(255) NOT NULL,
  `CNTST_ID` int(11) NOT NULL,
  `USR_ID` varchar(10000) NOT NULL,
  `IMGE_CPTION` varchar(10000) NOT NULL,
  `IMGE_CAT` varchar(10000) NOT NULL,
  `IMGE_TAGS` varchar(10000) NOT NULL,
  `IMGE_ACTIVE` char(1) NOT NULL default 'Y',
  `UPLOAD_DATE` date NOT NULL,
  PRIMARY KEY  (`IMGE_ID`)
)

