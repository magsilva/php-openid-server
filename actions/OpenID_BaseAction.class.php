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

class OpenID_BaseAction extends Action
{
	var $openid_request;
	
	function process($method, &$request)
	{
	    if (! isset($this->openid_request)) {
	    	$this->log->debug('Decoding OpenID request');
	    	$this->openid_request = $this->openid_server->decodeRequest();
	    } else {
	    	$this->log->debug('Reusing OpenID request');
	    }
	    
	    if (! $this->openid_request) {
	        trigger_error('Invalid OpenID request: ' . $this->openid_request->text, E_USER_NOTICE);
	        trigger_error('An internal error has occurred. It wasn\'t your fault, but from '. 
	        'either this application or the application that is requesting the user ' .
	        'authentication. The error has been reported and, hopefully, will be fixed soon.', E_USER_ERROR);

	        // Shouldn't return.
	        assert(FALSE);
	    }

	    if (is_a($this->openid_request, 'Auth_OpenID_ServerError')) {
	        trigger_error('OpenID request couldn\'t be handled: ' . $this->openid_request->text, E_USER_NOTICE);
	        $this->controller->handleResponse($this->openid_request);

	        // Shouldn't return.
	        assert(FALSE);
	    }
	}
}

?>


?>