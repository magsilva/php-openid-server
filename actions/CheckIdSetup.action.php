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
class CheckIdSetup extends CheckId
{
	function requireAuth()
	{
		return true;
	}  

	function process($method, &$request)
	{
	    parent::process($method, $request);
	    
	    // User is authenticated but OpenID doesn't accept it (I don't know how, but...)
		if ($this->account !== $this->expected_account) {
	    	$this->log->info("User '$this->account' ($this->openid_identity) is authenticated, but not with the expected account ($this->expected_account)");
	     	$this->server->clearAccount();
	     	$this->controller->forward($method, $this->decoded_openid_request, 'serve');
	     	// The forward shouldn't return if everything is ok.
	     	return false;
		}


		// User is authenticated.
  		if ($this->storage->isTrusted($this->account, $this->decoded_openid_request->trust_root)) {
			$this->log->info("User '$this->account' ($this->openid_identity) is authenticated and server '" . $this->decoded_openid_request->trust_root ."' is trusted");
			$response =& $this->decoded_openid_request->answer(true);
			// $this->server->addSregData($this->account, $response, $request);
			$this->controller->handleResponse($response);

			// The $controller->handleResponse() shouldn't return.
			return false;
		} else {
			$this->log->info("User '$this->account' ($this->openid_identity) is authenticated but server '$this->decoded_openid_request->trust_root' isn't trusted");
			$this->controller->saveOpenIDRequestInfo($this->decoded_openid_request);
			$this->controller->forward($method, $request, 'trust');
	
	     	// The forward shouldn't return if everything is ok.
            return false;
    	}
	}
}

?>