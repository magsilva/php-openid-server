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

class CheckId extends OpenID_BaseAction
{
	var $account;
	
	var $openid_identity;
	
	var $expected_account; 
	
	function process($method, &$request)
	{
	    parent::process($method, $request);

        $this->account = $this->server->getAccount();
        $this->openid_identity = $this->openid_request->identity;
        $this->expected_account = $this->server->getAccountForUrl($this->openid_identity);
	}
}

?>