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
 
Copyright (C) 2007 Marco Aurélio Graciotto Silva <magsilva@gmail.com>
*/

class HTTPUtil
{
	
	function sendImage($image)
	{
		header('Pragma: public');
		header('Expires: 0');
		header('Cache-Control: no-store, no-cache');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Cache-Control: public');
	
	    if (imagetypes() & IMG_PNG) {
		    header('Content-type: image/png');
		    // imagepng($image, NULL, 9, PNG_ALL_FILTERS);
		    imagepng($image, '/home/msilva/Projects/php-openid-server/teste.png');
		    imagepng($image);
		    ob_start();
		    ob_end_flush();
	    } else if (imagetypes() & IMG_GIF) {
		    ob_start();
	        imagegif($image);
		    ob_end_flush();
	        header('Content-type: image/gif');
	    } else if (imagetypes() & IMG_JPG) {
		    ob_start();
	        imagejpeg($image, '', 90);
		    ob_end_flush();
	        header('Content-type: image/jpeg');
	    }
	}

	function getRequestHeaders()
	{
		$headers = array();
		if (function_exists('getallheaders') && getallheaders() !== FALSE) {
			$tmp = getallheaders();
			foreach ($tmp as $key => $value) {
				$headers[strtolower($key)] = $value;
			}
		} else {
			foreach($_SERVER as $name => $value) {
				if (substr($name, 0, 5) == 'HTTP_') {
					$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
				}
			}
		}
		return $headers;
	}
}
?>