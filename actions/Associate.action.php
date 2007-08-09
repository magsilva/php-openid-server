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

require_once('Action.class.php');

/**
 * Establish a shared secret between consumer and identity provider.
 * Flow: consumer -> IdP -> consumer
 * HTTP method: POST
 */
class Associate extends Action
{
	function requireAuth()
	{
		return false;
	}  

	function process($method, &$request)
	{
		if (! array_key_exists('openid_session_type', $request) || empty($request['openid_session_type'])) {
			$this->log->debug('Client using a cleartext session for key exchange.');
		}
		
	    $decoded_openid_request = $this->openid_server->decodeRequest($request);

	    if (! $decoded_openid_request) {
	        trigger_error('Invalid OpenID request');
	        return false;
	    }

	    if (is_a($decoded_openid_request, 'Auth_OpenID_ServerError')) {
	        $this->log->info('Invalid OpenID request');
	        $this->controller->handleResponse($decoded_openid_request);
	    }

        $response =& $this->openid_server->handleRequest($decoded_openid_request);
		$this->controller->handleResponse($response);
		
		// The $controller->handleResponse() shouldn't return.
		return false;
	}
}

?>
