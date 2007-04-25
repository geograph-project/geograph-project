<?php
//Change these settings to change the way the captcha generation works and match your server settings

//Folder Path (relative to this file) where image files can be stored, must be readable and writable by the web server
//Don't forget the trailing slash
$tempfolder = 'b2evo_captcha_tmp/';

//Folder Path (relative to this file) where your captcha font files are stored, must be readable by the web server
//Don't forget the trailing slash
$TTF_folder = 'b2evo_captcha_fonts/';

//The minimum number of characters to use for the captcha
//Set to the same as maxchars to use fixed length captchas
$minchars = 5;

//The maximum number of characters to use for the captcha
//Set to the same as minchars to use fixed length captchas
$maxchars = 7;

//The minimum character font size to use for the captcha
//Set to the same as maxsize to use fixed font size
$minsize = 20;

//The maximum character font size to use for the captcha
//Set to the same as minsize to use fixed font size
$maxsize = 30;

//The maximum rotation (in degrees) for each character
$maxrotation = 25;

//Use background noise instead of a grid
$noise = TRUE;

//Use web safe colors (only 216 colors)
$websafecolors = FALSE;

//Enable debug messages
$debug = FALSE;

//Filename of garbage collector counter which is stored in the tempfolder
$counter_filename = 'b2evo_captcha_counter.txt';

//Prefix of captcha image filenames
$filename_prefix = 'b2evo_captcha_';

//Number of captchas to generate before garbage collection is done
$collect_garbage_after = 50;

//Maximum lifetime of a captcha (in seconds) before being deleted during garbage collection
$maxlifetime = 600;

//Make all letters uppercase (does not preclude symbols)
$case_sensitive = FALSE;

//////////////////////////////////////////
//DO NOT EDIT ANYTHING BELOW THIS LINE!
//
//

//$folder_root = substr(__FILE__,0,(strpos(__FILE__,'.php')));
$folder_root = '.';

$CAPTCHA_CONFIG = array('tempfolder'=>$folder_root.'/'.$tempfolder,'TTF_folder'=>$folder_root.'/'.$TTF_folder,'minchars'=>$minchars,'maxchars'=>$maxchars,'minsize'=>$minsize,'maxsize'=>$maxsize,'maxrotation'=>$maxrotation,'noise'=>$noise,'websafecolors'=>$websafecolors,'debug'=>$debug,'counter_filename'=>$counter_filename,'filename_prefix'=>$filename_prefix,'collect_garbage_after'=>$collect_garbage_after,'maxlifetime'=>maxlifetime,'case_sensitive'=>$case_sensitive);

?>
