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

class CheckId extends Action
{
	var $decoded_openid_request;
	
	var $account;
	
	var $openid_identity;
	
	var $expected_account; 
	
	function process($method, &$request)
	{
	    $this->decoded_openid_request = $this->openid_server->decodeRequest();

	    if (! $this->decoded_openid_request) {
	        trigger_error('Invalid OpenID request: ' . $this->decoded_openid_request->text);
	        return false;
	    }

	    if (is_a($this->decoded_openid_request, 'Auth_OpenID_ServerError')) {
	        trigger_error('Invalid OpenID request: ' . $this->decoded_openid_request->text);
	        $this->controller->handleResponse($this->decoded_openid_request);
	    }

        $this->account = $this->server->getAccount();
        $this->openid_identity = $this->decoded_openid_request->identity;
        $this->expected_account = $this->storage->getAccountForUrl($this->openid_identity);
	}
}

?>