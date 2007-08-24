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
require_once('common/HTTP.class.php');

class IdentityPage extends Action
{	
	function process($method, &$request)
	{
	    $serve_xrds_now = false;
	
	    // If an Accept header is sent, display the XRDS immediately;
	    // otherwise, display the identity page with an XRDS location
	    // header.
	    // TODO: Replace with a generic function (like the one from CoTeia)
	    $headers = HTTPUtil::getRequestHeaders();
	    foreach ($headers as $header => $value) {
	    	$pattern = preg_quote('application/xrds+xml', '/');
	        if ($header == 'Accept' && preg_match('/' . $pattern . '/', $value)) {
	            $serve_xrds_now = true;
	            break;
	        }
	    }
	
	    if ($serve_xrds_now) {
	        $request['xrds'] = $request['user'];
	        $this->controller->forward($method, $request, 'XRDS');
	        
	        // Should return from the forward.
	        assert(FALSE);
	    } else {
	        header('X-XRDS-Location: ' . $this->controller->getServerURL() . '?action=XRDS&user=' . $request['user']);
	        $this->template->assign('openid_url', $this->server->getAccountIdentifier($request['user']));
	        $this->template->assign('user', $request['user']);
	        $this->template->display('idpage.tpl', true);
	    }
	    
	    return true;
	}
}

?>