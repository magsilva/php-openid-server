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

require_once('openid/Auth/OpenID/Server.php');
require_once('openid/Auth/OpenID/MySQLStore.php');


class OpenIDServer
{
	var $domain;
	
	var $auth_backend;
	
	var $storage_backend;
	
	var $openid_store;
	
	static $openid_server = null;
	
	function OpenIDServer($domain, $auth_backend, $auth_parameters, $storage_backend, $storage_parameters)
	{
		session_start();
		
		$this->domain = $domain;
		// Initialize backends.
		$this->startAuthBackend($auth_backend, $auth_parameters);
		$this->startStorageBackend($storage_backend, $storage_parameters);
		$this->startOpenIDStore($storage_backend, $storage_parameters);
		$this->startOpenIDServer($storage_backend, $storage_parameters);
	}
	
	function startAuthBackend($auth_backend, $auth_parameters)
	{
        $cls = 'AuthBackend_' . $auth_backend;
        if (! class_exists($cls)) {
        	trigger_error('Could not start authencitation class', E_USER_ERROR);
        }
        
        $auth_backend = new $cls();
        if (! $auth_backend->connect($auth_parameters)) {
            trigger_error('Could not start authentication engine', E_USER_ERROR);
        }
        $this->auth_backend =& $auth_backend;
	}
	
	function startStorageBackend($storage_backend, $storage_parameters)
	{
        $cls = 'Storage_' . $storage_backend;
        if (! class_exists($cls)) {
        	trigger_error('Could not start storage class', E_USER_ERROR);
        }
        
        $storage_backend = new $cls();
        if (! $storage_backend->connect($storage_parameters)) {
            trigger_error('Cannot start storage engine', E_USER_ERROR);
	    }
	    $this->storage_backend =& $storage_backend;
	}

	function startOpenIDStore($storage_backend, $storage_parameters)
	{
		$parameters = $storage_parameters;
       	$parameters['phptype'] = 'mysql';
       	$db =& DB::connect($parameters);
       	if (!PEAR::isError($db)) {
       		$openid_store =& new Auth_OpenID_MySQLStore($db);
       		$openid_store->createTables();
       		$this->openid_store =& $openid_store;
		}
	}

	function startOpenIDServer()
	{
/*
 		static $server = null;
    	if (! isset($server)) {
    		$server =& new Auth_OpenID_Server(Server_getOpenIDStore());
    	}
    	return $server;
*/
    	if (! isset($this->openid_server) || $this->openid_server == null) {
    		$this->openid_server =& new Auth_OpenID_Server($this->openid_store);
    	}
	}
	
	
	function setAccount($account_name, $admin = false)
	{
		$_SESSION['php_openidserver_account'] = $account_name;
		if ($admin) {
			$_SESSION['php_openidserver_admin'] = 1;
		}
	}

	function clearAccount()
	{
	    unset($_SESSION['php_openidserver_account']);
	    unset($_SESSION['php_openidserver_admin']);
	    unset($_SESSION['php_openidserver_request']);
	}

	function getAccount()
	{
	    if (array_key_exists('php_openidserver_account', $_SESSION)) {
	        return $_SESSION['php_openidserver_account'];
	    }
	
	    return null;
	}
	
	
	function getAccountForUrl($identifier)
	{
		// Remove malformed identifiers (escape any strange char)
		if (defined('IDENTIFIER_PATTERN')) {
			$pattern = IDENTIFIER_PATTERN;	
		} else {
			$pattern = '';
		}
		
		if (empty($pattern)) {
			$pattern = sprintf('%s?action=identityPage&user=', $this->domain);
			$pattern = preg_quote($pattern, '/');
			$pattern = '/^' . $pattern . '(.*)/';
		}
		
		$result = preg_match($pattern, $identifier, $matches);
		
		if ($result ===  0 || $result === FALSE) {
			return false;
		}
		return $matches[1];
		
	}
	
	function getAccountIdentifier($account)
	{
		$identifier = sprintf('%s?action=identityPage&user=%s', $this->domain, $account);
		return $identifier;
	}
	
	function needAuth()
	{
	    if (! $this->getAccount()) {
	    	return true;
	    }
	    return false;
	}
	
	function needAdmin()
	{
	    if (!isset($_SESSION['php_openidserver_admin'])) {
	        return true;
	    }
	    return false;
	}
	
	function addMessage($str)
	{
    	if (!array_key_exists('php_openidserver_messages', $_SESSION)) {
        	$_SESSION['php_openidserver_messages'] = array();
    	}

    	$_SESSION['php_openidserver_messages'][] = $str;
	}

	function getMessages()
	{
    	if (array_key_exists('php_openidserver_messages', $_SESSION)) {
        	return $_SESSION['php_openidserver_messages'];
    	} else {
        	return array();
    	}
	}

	function clearMessages()
	{
    	unset($_SESSION['php_openidserver_messages']);
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
	
	function addSregData($account, &$response, $request_info, $allowed_fields = null)
	{
	    // TODO: $profile = $this->storage_backend->getPersona($account);
	
	    list($r, $sreg) = $request_info;
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