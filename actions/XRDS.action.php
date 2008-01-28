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


/**
 * Provide an XML document with server data necessary to proceed with the
 * authetication process.
 * 
 * This action is usually reached from the user provided identity URL (the one
 * we get from IdentityPage, at the "<link rel='openid.server' />".
 */
class XRDS extends Action
{
	function process($method, &$request)
	{
	    $username = $request['user'];
	    $this->template->assign('account', $username);
	    $this->template->assign('openid_url', $this->server->getAccountIdentifier($username));
	
	    header('Content-type: application/xrds+xml');
	    $this->template->display('xrds.tpl', true);
	    
	    return true;
	 }
}

?>