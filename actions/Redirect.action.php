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
		$response = $_SESSION['php_openidserver_response'];
		if (isset($response) && ! empty($response)) {
		    // $this->controller->clearRequestInfo();
		    $this->controller->handleResponse($response);
		}
		
		// The Controller->handleResponse shouldn't return. If it has,
		// something wrong has gone wrong.
	    return false;   
	}
}


?>
