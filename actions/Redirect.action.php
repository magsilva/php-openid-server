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
 
Copyright (C) 2007 Marco Aurélio Graciotto Silva
*/


require_once('Action.class.php');

class Redirect extends Action
{
	function process($method, &$request)
	{
		if (! array_key_exists('php_openidserver_response', $_SESSION) && ! empty($_SESSION['php_openidserver_response'])) {
			trigger_error('Invalid redirect request', E_USER_NOTICE);
		    $this->controller->forward($method, $request, 'index');
		}

		$response = $_SESSION['php_openidserver_response'];
	    $this->controller->handleResponse($response);
		
		// The Controller->handleResponse shouldn't return.
		assert(FALSE);
	}
}


?>
