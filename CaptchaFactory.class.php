<?php
/*
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.
 
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 
Copyright (C) 2005 JanRain, Inc.
*/

require_once('common/HTTP.class.php');

class CaptchaFactory
{
	var $font_path;
	
	var $char_count;
	
	var $text_size;
	
	function CaptchaFactory()
	{
		$this->font_path = PHP_SERVER_PATH . '/fonts/FreeSans.ttf';
		$this->text_size = 45;
	}
	
	/**
	 * Generate a captcha image with some noise and randomly-chosen,
	 * randomly-rotated lettering.  The parameters supplied will determine
	 * the size of the resulting captcha image.
	 *
	 * @param string $font_path The path to a truetype font file to be
	 * used for rendering the captcha characters.
	 *
	 * @param integer $char_count The number of characters to put into the
	 * captcha image.
	 *
	 * @param integer $text_size The size of the captcha text, in pixels.
	 */
	function generateCaptcha($char_count, $text = '')
	{
	    $width = $this->text_size * $char_count + 10;
	    $height = $this->text_size + 30;
	    $image = imagecreate($width, $height);

	    $white = imagecolorallocate($image, 255, 255, 255);
	    $black = imagecolorallocate($image, 0, 0, 0);
	    imagefill($image, 0, 0, $white);
	
		// Set text color
	    $textcolor = imagecolorallocate($image, 0, 0, 0);
	
	    // Generate a string.
	    if ($text == '') {
		    for ($i = 0; $i < $char_count; $i++) {
		        // Generate an ascii code in the range 49-57, 65-90 (1-9, A-Z).
	    	    $ordinal = rand(49, 90);
	        	if ($ordinal > 57 &&
	            	$ordinal < 65) {
	            	$ordinal += 7;
	        	}
	        	$text .= chr($ordinal);
	    	}
	    }
	
	    $x = 10;
	    $y = $this->text_size + 10;
	    $deg_window = 23;
	    $color_max = 200;
	    $x_deviation_min = -3;
	    $x_deviation_max = 3;
	
	    for ($i = 0; $i < strlen($text); $i++) {
	        $degrees = rand(0, $deg_window * 2) - $deg_window;
	        imagettftext(
	        	$image, $this->text_size, $degrees,
	        	$x + ($i * ($this->text_size - 3)) + rand($x_deviation_min, $x_deviation_max),
	        	$y, $black, $this->font_path, $text[$i]);
	    }
	
	    // Generate 20 random colors and draw random ellipses.
	    $color_count = 20;
	    $color_values = array();
	    $spot_count = intval($width * $height * 0.007);
	
	    $color_min = 80;
	    $color_max = 220;
	
	    $spot_r_min = 2;
	    $spot_r_max = 7;
	    $r_deviation_max = 3;
	
	    for ($i = 0; $i < $color_count; $i++) {
	        $color_values[$i] = imagecolorallocate($image,
	                                               rand($color_min, $color_max),
	                                               rand($color_min, $color_max),
	                                               rand($color_min, $color_max));
	    }
	
	    for ($i = 0; $i < $spot_count; $i++) {
	        $r = rand($spot_r_min, $spot_r_max);
	        $x_deviation = rand(0, $r_deviation_max);
	        $y_deviation = rand(0, $r_deviation_max);
	        imagefilledellipse($image, rand(0, $width - 1), rand(0, $height - 1),
	                           $r + $x_deviation,
	                           $r + $y_deviation,
	                           $color_values[rand(0, $color_count - 1)]);
	    }
		
		HTTPUtil::sendImage($image);
	    imagedestroy($image);
	
	    return md5($text);
	}
}
?>
