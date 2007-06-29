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

require_once('Log.php');

class Logging
{
	function Logging()
	{
	}
	
	function instance()
	{
		$handler_options = array();
		$handler_options['append'] = true;
		$handler_options['mode'] = 0700;
		$handler_options['eol'] = "\n";
		$handler_options['append'] = true;
		
		$trace = debug_backtrace();
		/*
		$trace[1]['function']
		$trace[1]['line']
		$trace[1]['file']
		$trace[1]['class']
		$trace[1]['object']
		$trace[1]['type']
		$trace[1]['args']
		*/
		 
		$error_level = LOG_ERROR_LEVEL;
		return Log::singleton('file', LOG_FILENAME, 'PHP-OPENID-SERVER');
	}
}



?>