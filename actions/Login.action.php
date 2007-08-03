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

class Login extends Action
{
	function process($method, &$request)
	{
	    if ($this->server->getAccount()) {
	        $this->controller->forward($method, $request, 'index');
	    }
	
	    if ($method == 'POST') {
	        // Process login.
	        $u = $request['username'];
	        $p = $request['passwd'];
	
	        if ($u && $p) {
	        	if ($u == ADMIN_USERNAME && md5($p) == ADMIN_PASSWORD_MD5) {
	                // Log in as admin.
	                $this->server->setAccount($u, true);
	                $this->controller->forward($method, $request, 'index');
	            } else if (($u != ADMIN_USERNAME) &&  $this->auth->authenticate($u, $p)) {
	           
	                $this->server->setAccount($u);
	
					$return_to = null;
					if (array_key_exists('return_to', $request)) {
				        $return_to = html_entity_decode($request['return_to']);
				    }
					if (array_key_exists('openid_return_to', $request)) {
				        $return_to = html_entity_decode($request['openid_return_to']);
				    }
	                
                	$action = 'index';
                	if (array_key_exists('next_action', $request)) {
                    	$request['action'] = $request['next_action'];
                    	$action = $request['action'];
                    	unset($request['next_action']);
                	}
                    $this->controller->forward($method, $request, $action);
	            } else {
	                $this->template->addError('The confirmation request was rejected, or timed out.');
	            }
			} else {
				$this->template->addError('Please fill in all the available fields.');
			}
	    }
	
	    if (array_key_exists('next_action', $request)) {
	        $this->template->assign('next_action', $request['next_action']);
	    }
		if (array_key_exists('return_to', $request)) {
	        $this->template->assign('return_to', $request['return_to']);
	    }
		if (array_key_exists('openid_return_to', $request)) {
	        $this->template->assign('return_to', $request['openid_return_to']);
	    }
	
	    list($info, $sreg) = $this->controller->getRequestInfo();
	
	    if ($info) {
	        // Reverse lookup from URL to account name.
	        $username = $this->storage->getAccountForUrl($info->identity);
	
	        if ($username !== null) {
	            $this->template->assign('required_user', $username);
	            $this->template->assign('identity_url', $info->identity);
	        } else {
	            // Return an OpenID error because this server does not
	            // know about that URL.
	            $this->server->clearAccount();
	            $this->controller->setRequestInfo();
	            $this->template->addError('You\'ve tried to authenticate using a URL this '.
	                                'server does not manage (<code>' . $info->identity . '</code>). ' .
	                                'If you are using your own identity page, there may be a typo ' .
	                                'in the URL.');
	        }
	    }
	
	    $this->template->display('login.tpl');
	}
}

?>