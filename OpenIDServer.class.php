<?php

require_once "config.php";
require_once "common.php";

class OpenIDServer
{
	var $auth_backend;
	
	var $storage_backend;

	function OpenIDServer()
	{
		session_start();
		
		// Initialize backends.
		$this->auth_backend =& $this->getAuthBackend();
		$this->storage_backend =& $this->getStorageBackend();
	}
	
	function getAuthBackend($auth_backend, $auth_parameters)
	{
	    if (! $auth_backend) {
	        // Try to instantiate auth backend class from settings.
	        $cls = 'AuthBackend_' . AUTH_BACKEND;
	        $auth_backend = new $cls();
	        if (! $auth_backend->connect($auth_parameters)) {
	            return null;
	        }
	    }
	    return $auth_backend;
	}
	
	function getStorageBackend($storage_backend, $storage_parameters)
	{
	    if (! $storage_backend) {
	        // Try to instantiate storage backend class from settings.
	        $cls = 'Storage_' . STORAGE_BACKEND;
	        $storage_backend = new $cls();
	        if (! $storage_backend->connect($storage_parameters)) {
	            return null;
	        }
	    }
	    return $storage_backend;
	}
	
}

?>