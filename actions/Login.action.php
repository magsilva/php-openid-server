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
		if ($this->server->getAccount() != null) {
			trigger_error('Trying to login when already is authenticated.', E_ERROR_NOTICE);
		}
		
		// Do the authentication.
	    if ($method == 'POST' && array_key_exists('username', $request) && array_key_exists('passwd', $request)) {
	    	$this->log->debug('Starting authentication process');
	    
	        // Process login.
	        $u = $request['username'];
	        $p = $request['passwd'];
	        
	        if (! empty($u)) {
	        	// Special case: admin authentication
	        	if ($u == ADMIN_USERNAME) {
	    			if (md5($p) == ADMIN_PASSWORD_MD5) {
			            $this->log->debug('Admin user has been authenticated');
		                $this->server->setAccount($u, true);                
                    	$this->controller->forward($method, $request, 'index');
	    			} else {
	    				trigger_error('Incorrect authentication information for admin user.', E_USER_WARNING);
	    			}
	            }
	            
	            if ($this->auth->authenticate($u, $p)) {
	            	$this->server->setAccount($u);
		            $this->log->debug("User $u has been authenticated");
	            	if ($this->controller->hasRequestInfo()) {
		            	$this->controller->restoreRequestInfo();
	                	$this->controller->processRequest();
	            	} else {
                    	$this->controller->forward($method, $request, 'index'); 
	            	}
	            } else {
	                trigger_error('The authentication has failed due to incorrect username or password. ' .
	                		'Please, try again.', E_USER_WARNING);
	            }
			} else {
				trigger_error('Please, fill in all the available fields.', E_USER_WARNING);
			}
	    }
		
	    $this->template->display('login.tpl');
	}
}

?>