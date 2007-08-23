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

require_once('CheckId.class.php');

/**
 * Ask an identity provider if an end user owns the claimed identifier, but be
 * willing to wait for the reply. The consumer will pass the user agent to the
 * identity provider for a short period of time which will return either a
 * 'yes' or 'cancel' answer.
 * HTTP method: GET
 * Flow: Consumer -> User agent -> IdP -> User agent -> Consumer
 */
class CheckAuthentication extends Action
{
	var $decoded_openid_request;
	
	function process($method, &$request)
	{
	    $decoded_openid_request = $this->openid_server->decodeRequest();

	    if (! $decoded_openid_request) {
	        trigger_error('Invalid OpenID request: ' . $decoded_openid_request->text);
	        return false;
	    }

	    if (is_a($decoded_openid_request, 'Auth_OpenID_ServerError')) {
	        trigger_error('Invalid OpenID request: ' . $decoded_openid_request->text);
	        $this->controller->handleResponse($decoded_openid_request);
	    }

		$response = $this->openid_server->openid_check_authentication($decoded_openid_request);
		$this->controller->handleResponse($response);

		// The $controller->handleResponse() shouldn't return.
		return false;
	}
}

?>