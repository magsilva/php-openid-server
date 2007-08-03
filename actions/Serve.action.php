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
	function process($method, &$request)
	{
	    $http_request = $request;
	    $request = $this->openid_server->decodeRequest();

	    if (! $request) {
	        trigger_error('Invalid OpenID request');
	        return false;
	    }

	    if (is_a($request, 'Auth_OpenID_ServerError')) {
	        $this->log->info('Invalid OpenID request');
	        $this->controller->handleResponse($request);
	    }
	
	    $this->controller->setRequestInfo($request, $this->server->requestSregData($http_request));
	
	    if (in_array($request->mode, array('checkid_immediate', 'checkid_setup'))) {
			$this->log->info('OpenID request is for authentication, proceeding');
			
	        $urls = array();
	        
	        $account = $this->server->getAccount();
	        if ($account != null) {
	            $urls = $this->storage->getUrlsForAccount($account);
	        }
	        $openid_identity = $request->identity;
	        $expected_account = $this->storage->getAccountForUrl($request->identity);

	        if ($request->immediate && $account == null) {
				$this->log->info("User '$expected_account' ($openid_identity) isn't authenticated (and, as an immediate authentication was requested, it completely failed)");
	            $response =& $request->answer(false, $this->controller->getServerURL());
	        } else if ($account != null &&
	                   $this->storage->isTrusted($account, $request->trust_root) &&
	                   in_array($request->identity, $urls)) {
				$this->log->info("User '$account' ($openid_identity) is authenticated and server '$request->trust_root' is trusted");
				$response =& $request->answer(true);
				$this->server->addSregData($account, $response, $this->controller->getRequestInfo());
	        } else if ($account != $this->storage->getAccountForUrl($request->identity)) {
	        	$this->log->info("User '$expected_account' ($openid_identity) isn't authenticated");
	            $this->server->clearAccount();
	            $this->controller->setRequestInfo($request, $this->server->requestSregData($http_request));
	            $http_request['next_action'] = 'trust';
		    	$this->controller->forward($method, $http_request, 'login');
	        } else {
	            if ($this->storage->isTrusted($account, $request->trust_root)) {
					$this->log->info("User '$account' ($openid_identity) is authenticated and server '$request->trust_root' is trusted");
	                $response =& $request->answer(true);
	                $this->server->addSregData($account, $response, $this->controller->getRequestInfo());
	            } else {
	            	$this->log->info("User '$account' ($openid_identity) is authenticated and server '$request->trust_root' isn't trusted");
	                $this->controller->forward($method, $request, 'trust');
	            }
	        }
	    } else {
			$this->log->info("OpenID request is for something I don't know");
	        $response =& $this->openid_server->handleRequest($request);
	    }
	
	    $this->controller->setRequestInfo();
		$this->controller->handleResponse($response);
		
		return true;
	}
}

?>