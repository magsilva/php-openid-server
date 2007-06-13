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

require_once('config.php');
require_once('common.php');

require_once('AuthBackend.class.php');
require_once('StorageBackend.class.php');

require_once('Auth/OpenID/Server.php');
require_once('Auth/OpenID/MySQLStore.php');


class OpenIDServer
{
	var $auth_backend;
	
	var $storage_backend;
	
	var $openid_server;
	
	static $openid_store;

	function OpenIDServer($auth_backend, $auth_parameters, $storage_backend, $storage_parameters)
	{
		session_start();
		
		// Initialize backends.
		$this->startAuthBackend($auth_backend, $auth_parameters);
		$this->startStorageBackend($storage_backend, $storage_parameters);
		$this->startOpenIDServer();
	}
	
	function startAuthBackend($auth_backend, $auth_parameters)
	{
        $cls = 'AuthBackend_' . $auth_backend;
        if (! class_exists($cls)) {
        	trigger_error('Could not start authencitation class');
        }
        
        $auth_backend = new $cls();
        if (! $auth_backend->connect($auth_parameters)) {
            trigger_error('Could not start authentication engine');
        }
        $this->auth_backend = $auth_backend;
	}
	
	function startStorageBackend($storage_backend, $storage_parameters)
	{
        $cls = 'Storage_' . $storage_backend;
        if (! class_exists($cls)) {
        	trigger_error('Could not start storage class');
        }
        
        $storage_backend = new $cls();
        if (! $storage_backend->connect($storage_parameters)) {
            trigger_error('Cannot start storage engine');
	    }
	    $this->storage_backend = $storage_backend;
	}
	
	function startOpenIDServer()
	{
		$this->openid_server = new Auth_OpenID_Server($this->getOpenIDStore());
	}
	
	function getOpenIDStore()
	{
		if (! $this->openid_store) {
	        $this->openid_store =& new Auth_OpenID_MySQLStore($this->storage_backend->db);
	        $this->openid_store->createTables();
		}
	    return $this->openid_store;
	}
	
	function setAccount($account_name, $admin = false)
	{
		$_SESSION['account'] = $account_name;
		if ($admin) {
			$_SESSION['admin'] = 1;
		}
	}

	function clearAccount()
	{
	    unset($_SESSION['account']);
	    unset($_SESSION['admin']);
	    unset($_SESSION['request']);
	    unset($_SESSION['sreg_request']);
	}

	function getAccount()
	{
	    if (array_key_exists('account', $_SESSION)) {
	        return $_SESSION['account'];
	    }
	
	    return null;
	}
	
	
	function getAccountIdentifier($account)
	{
		global $controller;
		
    	return sprintf('%s?user=%s', $controller->getServerURL(), $account);
	}
	
	function needAuth(&$request)
	{
		global $controller;
	
	    if (! $this->getAccount()) {
	        $destination = $controller->getServerURL() . '?action=login';
	        if (array_key_exists('action', $request)) {
	            $destination .= '&next_action=' . $request['action'];
	        }
	
			$controller->redirect($destination);
	    }
	}
	
	function needAdmin()
	{
	    if (!isset($_SESSION['admin'])) {
	        $controller->redirect($controller->getServerURL());
	    }
	}
	
	function addMessage($str)
	{
    	if (!array_key_exists('messages', $_SESSION)) {
        	$_SESSION['messages'] = array();
    	}

    	$_SESSION['messages'][] = $str;
	}

	function requestSregData($request)
	{
	    $optional = array();
	    $required = array();
	    $policy_url = null;
	
	    if (array_key_exists('openid.sreg.required', $request)) {
	        $required = explode(",", $request['openid.sreg.required']);
	    }
	
	    if (array_key_exists('openid.sreg.optional', $request)) {
	        $optional = explode(",", $request['openid.sreg.optional']);
	    }
	
	    if (array_key_exists('openid.sreg.policy_url', $request)) {
	        $policy_url = $request['openid.sreg.policy_url'];
	    }
	
	    return array($optional, $required, $policy_url);
	}
	
	function addSregData($account, &$response, $allowed_fields = null)
	{
		global $controller;
		
	
	    $profile = $this->storage_backend->getPersona($account);
	
	    list($r, $sreg) = $controller->getRequestInfo();
	    list($optional, $required, $policy_url) = $sreg;
	
	    if ($allowed_fields === null) {
	        $allowed_fields = array_merge($optional, $required);
	    }
	
	    $data = array();
	    foreach ($optional as $field) {
	        if (array_key_exists($field, $profile) && in_array($field, $allowed_fields)) {
	            $data[$field] = $profile[$field];
	        }
	    }
	    foreach ($required as $field) {
	        if (array_key_exists($field, $profile) && in_array($field, $allowed_fields)) {
	            $data[$field] = $profile[$field];
	        }
	    }
	
	    // $response->addFields('sreg', $data);    
	}


	function handleResponse($response)
	{
	    $webresponse =& $this->openid_server->encodeResponse($response);
	
	    foreach ($webresponse->headers as $k => $v) {
	        header("$k: $v");
	    }
	
	    header('Connection: close');
	    print $webresponse->body;
	    exit(0);
	}
	
	function accountCheck($username, $pass1, $pass2)
	{
	    $errors = array();
	
	    if ($pass1 != $pass2) {
	        $errors[] = 'Passwords must match.';
	    } else if (strlen($pass1) < MIN_PASSWORD_LENGTH) {
	        $errors[] = 'Password must be at least '.
	            MIN_PASSWORD_LENGTH.' characters long.';
	    }
	
	    if (strlen($username) < MIN_USERNAME_LENGTH) {
	        $errors[] = 'Username must be at least '.
	            MIN_USERNAME_LENGTH.' characters long.';
	    }
	
	    return $errors;
	}



}

?>