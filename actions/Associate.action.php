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

require_once('OpenID_BaseAction.class.php');

/**
 * Establish a shared secret between consumer and identity provider.
 * Flow: consumer -> IdP -> consumer
 * HTTP method: POST
 */
class Associate extends OpenID_BaseAction
{
	function requireAuth()
	{
		return false;
	}  

	function process($method, &$request)
	{
		if (! array_key_exists('openid_session_type', $request) || empty($request['openid_session_type'])) {
			trigger_error('Client using a cleartext session for key exchange', E_USER_NOTICE);
		}
		
	    parent::process($method, $request);
	    
        $response =& $this->openid_server->handleRequest($this->openid_request);
		$this->log->debug('Client has been associated');
		$this->controller->handleResponse($response);
		
		// The $controller->handleResponse() shouldn't return.
		assert(FALSE);
	}
}

?>
