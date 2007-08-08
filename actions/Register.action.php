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

class Register extends Action
{
	function process($method, &$request)
	{
	    if (! ALLOW_PUBLIC_REGISTRATION) {
	        $this->controller->redirect();
	    }
	
	    if ($method == 'POST') {
	        $hash = null;
	        if (array_key_exists('php_openidserver_hash', $_SESSION)) {
	            $hash = $_SESSION['php_openidserver_hash'];
	        }
	
	        $success = true;
	
	        if ($hash !== md5($request['captcha_text'])) {
	            $this->template->addError('Security code does not match image.  Please try again.');
	            $success = false;
	        }
	
	        $errors = $this->server->accountCheck($request['username'],
	                                      $request['pass1'],
	                                      $request['pass2']);
	
	        if ($errors) {
	            foreach ($errors as $e) {
	                $this->template->addError($e);
	            }
	        } else {
	            // Good.
	            if (($request['username'] != ADMIN_USERNAME) &&
	                $this->auth->newAccount($request['username'], $request['pass1'], $request)) {
	
	                // Add an identity URL to storage.
	                $this->storage->addIdentifier($request['username'],
	                                        $this->server->getAccountIdentifier($request['username']));
	
	                $this->server->setAccount($request['username']);
	                $this->server->addMessage('Registration successful; welcome, ' . $request['username'] . '!');
	                $this->controller->redirect();
	            } else {
	                $this->template->addError('Sorry; that username is already taken!');
	            }
	        }
	
	        $this->template->assign('username', $request['username']);
	    }
	
	    $this->template->display('register.tpl');
	}
}

?>