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


class DebugUtil
{
	function dumpTrace()
	{
		return var_dump(DebugUtil::trace());		
	}


	function exportTrace()
	{
		return var_export(DebugUtil::trace(), true);		
	}
	
	function trace()
	{
		$trace = array();
		if (DebugUtil::isHTTP()) {
			$trace[] = DebugUtil::traceHTTP();
		}
		
		return $trace;
	}
	
	function isHTTP()
	{
		if (array_key_exists('REQUEST_METHOD', $_SERVER)) {
			return true;
		}
		return false;
	}
	
	function traceHTTP()
	{
		$http = array();
		$http['referer'] = $_SERVER['HTTP_REFERER'];
		$http['cookie'] = $_SERVER['HTTP_COOKIE'];
		$http['client_ip'] = $_SERVER['REMOTE_ADDR'];
		
		return $http;
	}
}

?>