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
	        $this->controller->redirect();
	    }

	    if (is_a($request, 'Auth_OpenID_ServerError')) {
	        $this->controller->handleResponse($request);
	    }
	
	    $this->controller->setRequestInfo($request, $this->server->requestSregData($http_request));
	
	    if (in_array($request->mode, array('checkid_immediate', 'checkid_setup'))) {
	
	        $urls = array();
	        $account = $this->server->getAccount();
	
	        if ($account) {
	            $urls = $this->storage->getUrlsForAccount($account);
	        }
	
	        if ($request->immediate && ! $account) {
	            $response =& $request->answer(false, $this->controller->getServerURL());
	        } else if ($account &&
	                   $this->storage->isTrusted($account, $request->trust_root) &&
	                   in_array($request->identity, $urls)) {
	             $response =& $request->answer(true);
	             $this->server->addSregData($account, $response, $this->controller->getRequestInfo());
	        } else if ($account != $this->storage->getAccountForUrl($request->identity)) {
	            $this->server->clearAccount();
	            $this->controller->setRequestInfo($request, $this->server->requestSregData($http_request));
	            $http_request['action'] = 'trust';
			    if ($this->server->needAuth($http_request)) {
			    	$this->controller->redirectWithLogin($http_request);
			    }
	        } else {
	            if ($this->storage->isTrusted($account, $request->trust_root)) {
	                $response =& $request->answer(true);
	                $this->server->addSregData($account, $response, $this->controller->getRequestInfo());
	            } else {
	                $this->controller->redirect('trust');
	            }
	        }
	    } else {
	        $response =& $this->openid_server->handleRequest($request);
	    }
	
	    $this->controller->setRequestInfo();
		$this->controller->handleResponse($response);
	}
}

?>