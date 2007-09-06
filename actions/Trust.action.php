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

require_once('OpenID_BaseAction.class.php');

/**
 * Set the trust level for a site and it's related domains.
 */
class Trust extends OpenID_BaseAction
{
	function requireAuth()
	{
		return true;
	}
	
	function process($method, &$request)
	{
		$this->openid_request = $this->controller->getOpenIDRequestInfo();
		parent::process($method, $request);
        
		// It will be post if it's an CheckId_Setup and GET if CheckId_Immediate?	
	    if ($method == 'POST' && (isset($request['trust_forever']) || $request['trust_once'])) {
	    	$trust_forever = isset($request['trust_forever']);
	    	$trust_once = isset($request['trust_once']);
	    	
	        $trusted = false;
	        if ($trust_forever) {
	            $this->storage->trust($this->account, $this->openid_request->trust_root);
	            $this->log->debug("User $this->account trusts '" . $this->openid_request->trust_root . "' forever");
	            $trusted = true;
	        } else if ($trust_once) {
	            $this->log->debug("User $this->account trusts '" . $this->openid_request->trust_root . "' just for this time");
	            $trusted = true;
	        } else {
	            $this->storage->distrust($this->account, $this->openid_request->trust_root);
	            $this->log->debug("User $this->account doesn't trust '" . $this->openid_request->trust_root . "'");
	        }
	
	        if ($trusted) {
	            // Start the SSO protocol
	            $response = $this->openid_request->answer(true);

	            // TODO: Check if the user agent has changed (so that we don't have to issue a cookie
	            $sites = $this->storage->getRelatedSites($this->openid_request->trust_root);
	            if (empty($sites)) {
	            	$this->log->debug("Site '" . $this->openid_request->trust_root . "' doesn't belong to a domain, so no need to propagate the trust");
		            $this->controller->clearOpenIDRequestInfo();
	            	$this->controller->handleResponse($response);
	            	
	            	// Shouldn't return.
	            	assert(FALSE);
	            }
	            
			    $this->template->assign('trust_root', $this->openid_request->trust_root);
		    	$this->template->assign('identity', $this->openid_request->identity);
	            $this->template->assign('related_sites', $sites);
	            $this->template->assign('action', 'redirect');
	            $this->template->assign('redirect_html', $this->controller->getServerURL() . '?action=redirect');
	            	            
			    $this->template->display('redirect.tpl');
	            $_SESSION['php_openidserver_response'] = $response;
	            $this->controller->clearOpenIDRequestInfo();

            	$this->log->debug("Site '" . $this->openid_request->trust_root . "' belongs to a domain, so we need to propagate the trust");
	            return true;
	        } else {
	            $response = $this->openid_request->answer(false);
	            $this->controller->clearOpenIDRequestInfo();
	        	$this->controller->handleResponse($response);
	        	
	        	// The Controller->handleResponse shouldn't return.
	        	assert(FALSE);
	        }
	    }
	
	    $this->template->assign('trust_root', $this->openid_request->trust_root);
	    $this->template->assign('identity', $this->openid_request->identity);
	    $this->template->display('trust.tpl');
	    
	    return true;
	 }
}

?>