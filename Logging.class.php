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
		
		/*
		 * PEAR_LOG_EMERG 	emerg() 	System is unusable
		 * PEAR_LOG_ALERT 	alert() 	Immediate action required
		 * PEAR_LOG_CRIT 	crit()	 	Critical conditions
		 * PEAR_LOG_ERR 	err() 		Error conditions
		 * PEAR_LOG_WARNING warning()	Warning conditions
		 * PEAR_LOG_NOTICE	notice() 	Normal but significant
		 * PEAR_LOG_INFO	info()		Informational
		 * PEAR_LOG_DEBUG	debug()		Debug-level messages
		 */
		$error_level = LOG_ERROR_LEVEL;
		return Log::singleton('file', LOG_FILENAME, 'PHP-OPENID-SERVER', $handler_options, PEAR_LOG_NOTICE);
	}
}



?>