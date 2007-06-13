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

require_once('Views.class.php');


class Controller
{
	function getHandlers()
	{
		$request_handlers = array(
			'login'		=> array('Views.class.php', 'render_login'),
			'logout'	=> array('Views.class.php', 'render_logout'),
			'sites'		=> array('Views.class.php', 'render_sites'),
			'account'	=> array('Views.class.php', 'render_account'),
			'register'	=> array('Views.class.php', 'render_register'),
			'captcha'	=> array('Views.class.php', 'render_captcha'),
			'serve'		=> array('Views.class.php', 'render_serve'),
			'trust'		=> array('Views.class.php', 'render_trust'),
			'admin'		=> array('Views.class.php', 'render_admin'),
		);
		
		return $request_handlers;
	}
	
	function getRequest()
	{
    	$method = $_SERVER['REQUEST_METHOD'];

    	switch ($method) {
    		case 'GET':
        		return array($method, $_GET);
        		break;
    		case 'POST':
        		return array($method, $_POST);
        		break;
    	}

    	return array($method, null);
	}

	function getRequestInfo()
	{
	    if (isset($_SESSION['request'])) {
	        return array(unserialize($_SESSION['request']),
	                     unserialize($_SESSION['sreg_request']));
	    } else {
	        return false;
	    }
	}

	function setRequestInfo($info=null, $sreg=null)
	{
	    if (!isset($info)) {
	        unset($_SESSION['request']);
	    } else {
	        $_SESSION['request'] = serialize($info);
	        $_SESSION['sreg_request'] = serialize($sreg);
	    }
	}

	function getMessages()
	{
    	if (array_key_exists('messages', $_SESSION)) {
        	return $_SESSION['messages'];
    	} else {
        	return array();
    	}
	}

	function clearMessages()
	{
    	unset($_SESSION['messages']);
	}

	function getHandler(&$request)
	{
		$handlers = $this->getHandlers();
	
	    if (array_key_exists('action', $request) && array_key_exists($request['action'], $handlers)) {
	        // The handler is array($filename, $function_name).
	        return $handlers[$request['action']];
	    }
	
	    return null;
	}


	function showMessages()
	{
		global $template;
	
		// If any messages are pending, get them and display them.
		$messages = $this->getMessages();
		foreach ($messages as $m) {
    		$template->addMessage($m);
		}
		$this->clearMessages();
	}
	
	function processRequest()
	{
		global $template;
	
		// First, get the request data.
		list($method, $request) = $this->getRequest();

		if (isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO'] == '/serve') {
			render_serve($method, $request, $template);
			exit(0);
		// If it's a request for an identity URL, render that.
		} else if (array_key_exists('user', $request) && $request['user']) {
			render_identityPage($method, $request, $template);
			exit(0);
		// If it's a request for a user's XRDS, render that.
		} else if (array_key_exists('xrds', $request) && $request['xrds']) {
			render_XRDS($method, $request, $template);
    		exit(0);
		}
		
		
		$this->showMessages();
		
		
		if ($request === null) {
			// Error; $method not supported.
			$template->addError("Request method $method not supported.");
			$template->display();
		} else {
			// Dispatch request to appropriate handler.
			$handler = $this->getHandler($request);
			if ($handler !== null) {
				list($filename, $handler_function) = $handler;
				require_once $filename;
				call_user_func_array($handler_function, array($method, $request, $template));
			} else {
				$template->display('main.tpl');
			}
		}		
	}
	
	
	/**
	 * Get the URL of the current script
	 */
	function getServerURL()
	{
	    $path = dirname($_SERVER['SCRIPT_NAME']);
	    if ($path[strlen($path) - 1] != '/') {
	        $path .= '/';
	    }
	
	    $host = $_SERVER['HTTP_HOST'];
	    $port = $_SERVER['SERVER_PORT'];
	    $s = isset($_SERVER['HTTPS']) ? 's' : '';
	    if (($s && $port == '443') || (!$s && $port == '80')) {
	        $p = '';
	    } else {
	        $p = ':' . $port;
	    }
	    
	    return "http$s://$host$p$path";
	}
	
	
	function redirect($url, $action = null)
	{
		if ($url == null) {
			$url = $this->getServerURL();
		}
		
	    if ($action != null) {
	        if (strpos($url, '?') === false) {
	            $url .= '?action=' . $action;
	        } else {
	            $url .= '&action=' . $action;
	        }
	    }
	
	    header('Location: ' . $url);
	    exit(0);
	}


	function handleError($errno, $errstr, $errfile, $errline)
	{
		global $template;
	

		switch ($errno) {
			case E_ERROR:
			case E_WARNING:
			case E_PARSE:
			case E_NOTICE:
			case E_CORE_ERROR:
			case E_CORE_WARNING:
			case E_COMPILE_ERROR:
			case E_COMPILE_WARNING:
				$template->addError($errstr);
				break;
		
			case E_USER_ERROR:
			    $template->addError($errstr);
				break;
			
			case E_USER_WARNING:
			case E_USER_NOTICE:
				echo 'Error in ' . $errfile . ':' . $errline . ' - ' . $errstr;
				exit();
			
			default:
    	}
	
    	/* Don't execute PHP internal error handler */
    	return true;
	}
}
?>