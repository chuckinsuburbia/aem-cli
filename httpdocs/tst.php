<?php //print "hello";
phpinfo(); die();
header("Content-Type: image/png");
$im = @imagecreate(110, 20)
    or die("Cannot Initialize new GD image stream");
$background_color = imagecolorallocate($im, 0, 0, 0);
$text_color = imagecolorallocate($im, 233, 14, 91);
imagestring($im, 1, 5, 5,  "A Simple Text String", $text_color);
imagepng($im);
imagedestroy($im);

// Create a 100x100 image
/*$im = imagecreatetruecolor(100, 100);
$white = imagecolorallocate($im, 0xFF, 0xFF, 0xFF);

// Draw a vertical dashed line
imagedashedline($im, 50, 25, 50, 75, $white);

// Save the image
imagepng($im, './dashedline.png');
imagedestroy($im);*/


?>