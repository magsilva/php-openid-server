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
 * Ask an identity provider if an end user owns the claimed identifier,
 * getting back an immediate 'yes' or 'can't say' anwser.
 * Flow: Consumer -> User Agent -> IdP -> User Agent -> Consumer
 * HTTP method: GET
 */
class CheckIdImmediate extends CheckId
{
	function requireAuth()
	{
		return false;
	}  

	function process($method, &$request)
	{
	    parent::process($method, $request);

		// User is not authenticated
		if ($this->account == null) {
			$this->log->debug("Immediate authentication for user '$this->expected_account' ($this->openid_identity) was denied.");
			$response =& $this->openid_request->answer(false, $this->controller->getServerURL());
		} else {
			// User is authenticated but OpenID doesn't accept it (I don't know how, but...)
			if ($this->account != $this->expected_account) {
		    	trigger_error("User '$this->account' ($this->openid_identity) is authenticated, but not with the expected account ($this->expected_account)", E_USER_NOTICE);
		     	$this->server->clearAccount();
				$response =& $this->openid_request->answer(false, $this->controller->getServerURL());
			} else {
				// User is authenticated.	        
		     	if ($this->storage->isTrusted($this->account, $openid_request->trust_root)) {
					$this->log->debug("User '$this->account' ($this->openid_identity) is authenticated and server '$this->openid_request->trust_root' is trusted");
					$response =& $this->openid_request->answer(true);
				} else {
					$this->log->debug("User '$this->account' ($this->openid_identity) is authenticated but server '$this->openid_request->trust_root' isn't trusted");
					$response =& $this->openid_request->answer(false);
		        }
			}
		}
		
		$this->controller->handleResponse($response);
		
		// The $controller->handleResponse() shouldn't return.
		assert(FALSE);
	}
}

?>