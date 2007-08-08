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
 
Copyright (C) 2005 JanRain, Inc.
*/

require_once('Action.class.php');

class Serve extends Action
{
	function requireAuth()
	{
		if (array_key_exists('openid_mode', $_REQUEST)) {
			if ($_REQUEST['openid_mode'] == 'associate') {
				return false;
			}
		}  
	    
		// TODO: Support immediate authentication request. Maybe detect an
		// immediate request at Login.action.php and return true there,
		// without authenticating?
		/*
		$request = $this->openid_server->decodeRequest();
		if ($request->immediate) {
			return false;
		}
		*/
		return true;
	}
	
	function process($method, &$request)
	{
	    $http_request = $request;
	    $decoded_openid_request = $this->openid_server->decodeRequest($request);

	    if (! $decoded_openid_request) {
	        trigger_error('Invalid OpenID request');
	        return false;
	    }

	    if (is_a($decoded_openid_request, 'Auth_OpenID_ServerError')) {
	        $this->log->info('Invalid OpenID request');
	        $this->controller->handleResponse($decoded_openid_request);
	    }

	    if (in_array($decoded_openid_request->mode, array('checkid_immediate', 'checkid_setup'))) {
			$this->log->info('OpenID request is for authentication, proceeding');
			
	        $urls = array();
	        
	        $account = $this->server->getAccount();
	        if ($account != null) {
	            $urls = $this->storage->getUrlsForAccount($account);
	        }
	        $openid_identity = $decoded_openid_request->identity;
	        $expected_account = $this->storage->getAccountForUrl($openid_identity);

			// User is not authenticated
			if ($account == null && $decoded_openid_request->immediate) {
				$this->log->info("Immediate authentication for user '$expected_account' ($openid_identity) was denied.");
				$response =& $decoded_openid_request->answer(false, $this->controller->getServerURL());
			}

			// User is authenticated but OpenID doesn't accept it (I don't know how, but...)
	     	if ($account != null && $account != $this->storage->getAccountForUrl($decoded_openid_request->identity)) {
	     		$this->log->info("User '$account' ($openid_identity) is authenticated, but not with the expected account ($expected_account)");
	     		$this->server->clearAccount();
	     		$this->controller->forward($method, $decoded_openid_request, 'serve');
	     		return true;
	     	}

			// User is authenticated.	        
	     	if ($account != null) {
	     		if ($this->storage->isTrusted($account, $decoded_openid_request->trust_root)) {
					$this->log->info("User '$account' ($openid_identity) is authenticated and server '$decoded_openid_request->trust_root' is trusted");
	                $response =& $decoded_openid_request->answer(true);
	                $this->server->addSregData($account, $response, $http_request);
	            } else {
	            	$this->log->info("User '$account' ($openid_identity) is authenticated but server '$request->trust_root' isn't trusted");
	                $this->controller->forward($method, $decoded_openid_request, 'trust');
	            }
	        }
	    } else {
			$this->log->info("OpenID request is for something I don't know (probably an association request).");
	        $response =& $this->openid_server->handleRequest($decoded_openid_request);
	    }
	
		$this->controller->handleResponse($response);
		
		return true;
	}
}

?>