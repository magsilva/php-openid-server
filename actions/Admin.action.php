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

class Admin extends Action
{
	function process($method, &$request)
	{
	    
	    if ($this->server->needAuth()) {
	    	$this->controller->redirectWithLogin($request);
	    }
	    if ($this->server->needAdmin()) {
	    	$this->controller->redirectWithAdmin($request);
	    }
	
	    if (array_key_exists('username', $request)) {
	        $username = $request['username'];
	        $pass1 = $request['pass1'];
	        $pass2 = $request['pass2'];
	
	        $success = true;
	
	        $errors = $this->server->accountCheck($username, $pass1, $pass2);
	
	        if ($errors) {
	            foreach ($errors as $e) {
	                $this->template->addError($e);
	            }
	        } else {
	            // Good.
	            if (($username != ADMIN_USERNAME) &&
	                $this->auth->newAccount($username, $pass1, $request)) {
	                // Add an identity URL to storage.
	                $this->storage->addIdentifier($username,
	                                        $this->server->getAccountIdentifier($username));
	                $this->server->addMessage('Account created.');
	                $this->controller->redirect('admin');
	            } else {
	                $this->template->addError('Sorry; the username "' .$username . '" is already taken!');
	            }
	        }
	    } else if (array_key_exists('remove', $request)) {
	
	        foreach ($request['account'] as $account => $on) {
	            $this->auth->removeAccount($account);
	            $this->storage->removeAccount($account);
	        }
	
	        $this->server->addMessage('Account(s) removed.');
	        $this->controller->redirect($this->controller->getServerURL() . '?search=' . $request['search'], 'admin');
	    }
	
	    if (array_key_exists('search', $request) &&
	        ($request['search'] || array_key_exists('showall', $request))) {
	        // Search for accounts.
	
	        if (array_key_exists('showall', $request)) {
	            $results = $auth->search();
	            $this->template->assign('showall', 1);
	        } else {
	            $results = $this->auth->search($request['search']);
	        }
	
	        $this->template->assign('search', $request['search']);
	        $this->template->assign('search_results', $results);
	    }
	
	    $this->template->display('admin.tpl');
    }
}

?>