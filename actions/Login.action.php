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
			$this->log->err('Trying to login when already is authenticated.');
		}
		
		// Do the authentication.
	    if ($method == 'POST' && array_key_exists('username', $request) && array_key_exists('passwd', $request)) {
	    	$this->log->debug('Starting authentication process');
	    
	        // Process login.
	        $u = $request['username'];
	        $p = $request['passwd'];
	        
	        if ($u && $p) {
	        	// Special case: admin authentication
	        	if ($u == ADMIN_USERNAME) {
	    			if (md5($p) == ADMIN_PASSWORD_MD5) {
		                // Log in as admin.
		                $this->server->setAccount($u, true);                
                    	$this->controller->forward($method, $request, 'index');
	    			} else {
	    				trigger_error('Incorrect authentication information.');
	    			}
	            }
	            
	            if ($this->auth->authenticate($u, $p)) {
	            	$this->server->setAccount($u);
		            $this->log->info("User $u has been authenticated");
	            	if ($this->controller->hasRequestInfo()) {
		            	$this->controller->restoreRequestInfo();
	                	$this->controller->processRequest();
	            	} else {
		            	$this->controller->clearRequestInfo();
                    	$this->controller->forward($method, $request, 'index'); 
	            	}
	            } else {
	                trigger_error('The confirmation request was rejected, or timed out.');
	            }
			} else {
				trigger_error('Please fill in all the available fields.');
			}
	    }
		
	    $this->template->display('login.tpl');
	}
}

?>