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
 * Ask an identity provider if an end user owns the claimed identifier, but be
 * willing to wait for the reply. The consumer will pass the user agent to the
 * identity provider for a short period of time which will return either a
 * 'yes' or 'cancel' answer.
 * HTTP method: GET
 * Flow: Consumer -> User agent -> IdP -> User agent -> Consumer
 */
class CheckAuthentication extends OpenID_BaseAction
{
	function process($method, &$request)
	{
	    parent::process($method, $request);

		$response = $this->openid_server->openid_check_authentication($this->openid_request);
		$this->controller->handleResponse($response);

		// The $controller->handleResponse() shouldn't return.
		assert(FALSE);
	}
}

?>