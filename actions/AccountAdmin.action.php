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

class AccountAdmin extends Action
{
	function requireAuth()
	{
		return true;
	}
	
	function requireAdmin()
	{
		return true;
	}

	function process($method, &$request)
	{
	    if (array_key_exists('username', $request)) {
	    	$this->log->debug('Creating account');
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
	            if ($username != ADMIN_USERNAME && $this->auth->newAccount($username, $pass1, $request)) {
	                // Add an identity URL to storage.
	                $this->template->addMessage('Account created.');
	                $this->controller->redirect('accountAdmin');
	            } else {
	                $this->template->addError('Sorry; the username "' .$username . '" is already taken!');
	            }
	        }
	    } else if (array_key_exists('remove', $request)) {
	    	$this->log->debug('Removing account');
	        foreach ($request['account'] as $account => $on) {
	            $this->auth->removeAccount($account);
	        }
	        $this->server->addMessage('Account(s) removed.');
	    } else if (array_key_exists('search', $request) || array_key_exists('showall', $request)) {
	    	$this->log->debug('Searching for an account');
	    	
	        if (array_key_exists('showall', $request)) {
	            $results = $this->auth->search();
	            $this->template->assign('showall', 1);
	        } else {
	            $results = $this->auth->search($request['search']);
	        }
	
	        $this->template->assign('search', $request['search']);
	        $this->template->assign('search_results', $results);
	    }
	
	    $this->template->display('accountAdmin.tpl');
    }
}

?>