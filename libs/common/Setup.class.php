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

require_once('common/Reflection.class.php');

class SetupUtil
{
	function getMySQLConnectionType($connection)
	{
		$type = mysql_get_host_info($connection);
		$mysql_unix_socket_regexp = '/UNIX socket/';
		$result = preg_match($mysql_unix_socket_regexp, $type);
		if ($result !== FALSE && $result != 0) {
			return 'unix socket';
		} else {
			return 'tcp';
		}
	}
	
	function getMySQLClientVersion()
	{
		$version = mysql_get_client_info();
		return $version;
	}
	
	function getMySQLServerVersion($connection)
	{
		return mysql_get_server_info($connection);	
	}
		
	function getApacheVersion()
	{
		$version = apache_get_version();
		if ($version === FALSE) {
			$apache_version_regexp = '/Apache\/([0-9\.]+/';
			preg_match($apache_version_regexp, $_SERVER['SERVER_SOFTWARE'], $matches);
			$version = $matches[2];
		}
		return $version;
	}

	function getCurlVersion()
	{
		$version = curl_version();
		if (is_array($version)){
			$version = $version['version'];
		} else {
			$version = explode(' ', $version);
			$version = explode('/', $version[0]);
			$version = $version[1];
		}
		return $version;
	}
	
	function getPhpVersion()
	{
		return phpversion();
	}
	
	
	
	function isOk($component, $min_version, $max_version, $extra_args)
	{
		$methodName = 'get' . ucfirst($component) . 'Version';
		if (ReflectionUtil::methodExists('SetupUtil', $methodName)) {
			$cur_version = call_user_func(array('SetupUtil', $methodName), $extra_args); 
			if (version_compare($cur_version, $min_version, '>=')) {
				if (isset($max_version)) {
					if (version_compare($cur_version, $max_version, '<')) {
						return true;
					}
				} else {
					return true;
				}
			}
		}
		return false;
	}
	
	function phpSupportsExtension($extension)
	{
		if (extension_loaded($extension)) {
			return true;
		}
		dl($extension);
		return extension_loaded($extension);
	}
	
	function apacheSupportsExtension($extension)
	{
		if (function_exists('apache_get_modules')) {
			$modules = apache_get_modules();
			return array_search($extension, $modules);
		} else {
			$apache_module_regexp = '/[\s(\w)\/]+/';
			preg_match($apache_module_regexp, $_SERVER['SERVER_SOFTWARE'], $matches);
			$matches = array_slice($matches, 1);
			return array_search($extension, $matches);
		}
	}
	
}

?>