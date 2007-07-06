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

class Trust extends Action
{
	function process($method, &$request)
	{
	    if ($this->server->needAuth($request)) {
	    	$this->controller->redirectWithLogin($request);
	    }
	    
	    $account = $this->server->getAccount();
	    list($request_info, $sreg) = $this->controller->getRequestInfo();
	
	    if ($request_info === FALSE) {
	        $this->controller->redirect();
	    }
	
	    $urls = $this->storage->getUrlsForAccount($account);
	    $openid_identity = $request->identity;
	
	    if (! in_array($request_info->identity, $urls)){
	        $this->server->clearAccount();
	        $this->controller->setRequestInfo($request_info, $sreg);
		    if ($this->server->needAuth($request)) {
		    	$this->controller->redirectWithLogin($request);
		    }
	    }
	
	    if ($method == 'POST') {
	        $trusted = false;
	        if (isset($request['trust_forever'])) {
	            $this->storage->trustLog($account, $request_info->trust_root, true);
	            $this->log->info("User $account trusts $request_info->trust_root forever");
	            $trusted = true;
	        } else if (isset($request['trust_once'])) {
	            $this->storage->trustLog($account, $request_info->trust_root, false);
	            $this->log->info("User $account trusts $request_info->trust_root just this time");
	            $trusted = true;
	        } else {
	            $this->storage->trustLog($account, $request_info->trust_root, false);
	            $this->log->info("User $account doesn't trust $request_info->trust_root");
	        }
	
	        if ($trusted) {
	        	// Get requested user data.
	            $allowed_fields = array();
	            if (array_key_exists('sreg', $request)) {
	                $allowed_fields = array_keys($request['sreg']);
	            }
	            $response = $request_info->answer(true);
	            $this->server->addSregData($account, $response, $this->controller->getRequestInfo(), $allowed_fields);
	            
	            // Propagate the cookies
	            // TODO: Check if the user agent has changed (so that we don't have to issue
	            // a cookie
	            $sites = $this->storage->getRelatedSites($request_info->trust_root);
			    $this->template->assign('trust_root', $request_info->trust_root);
		    	$this->template->assign('identity', $request_info->identity);
	            $this->template->assign('related_sites', $sites);
	            $this->template->assign('action', 'redirect');
	            $this->template->assign('redirect_html', $this->controller->getServerURL() . '?action=redirect');
	            	            
			    $this->template->display('redirect.tpl');
	            $_SESSION['response'] = $response;
	            return;
	            
	        } else {
	            $response = $request_info->answer(false);
	            $this->controller->setRequestInfo();
	        	$this->controller->handleResponse($response);
	        }
	    }
	
	    if ($sreg != FALSE) {
	        // Get the profile data and mark it up so it's easy to tell
	        // what's required and what's optional.
	        $profile = $this->storage->getPersona($account);
	
	        list($optional, $required, $policy_url) = $sreg;
	
	        $sreg_labels = array('nickname' => 'Nickname',
	                             'fullname' => 'Full name',
	                             'email' => 'E-mail address',
	                             'dob' => 'Birth date',
	                             'postcode' => 'Postal code',
	                             'gender' => 'Gender',
	                             'country' => 'Country',
	                             'timezone' => 'Time zone',
	                             'language' => 'Language');
	
	        $profile['country'] = getCountryName($profile['country']);
	        $profile['language'] = getLanguage($profile['language']);
	
	        $new_profile = array();
	        foreach ($profile as $k => $v) {
	            if (in_array($k, $optional) || in_array($k, $required)) {
	                $new_profile[] = array('name' => $sreg_labels[$k],
	                                       'real_name' => $k,
	                                       'value' => $v,
	                                       'optional' => in_array($k, $optional),
	                                       'required' => in_array($k, $required));
	            }
	        }
	
	        $this->template->assign('profile', $new_profile);
	        $this->template->assign('policy_url', $policy_url);
	    }
	
	    $this->template->assign('trust_root', $request_info->trust_root);
	    $this->template->assign('identity', $request_info->identity);
	    $this->template->display('trust.tpl');
	 }
}

?>