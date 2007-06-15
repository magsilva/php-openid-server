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

class Controller
{
	var $template_engine;
	
	var $server;
	
	var $auth;
	
	var $storage;
	
	function Controller()
	{
	}

	function setServer($server)
	{
		$this->server = $server;
		$this->auth = $this->server->auth_backend;
		$this->storage = $this->server->storage_backend;
	}
	
	function setTemplateEngine($template_engine)
	{
		$this->template_engine = $template_engine;
		set_error_handler(array($this, 'handleError'));
	}

	function getHandler($action)
	{
		$handler = null;

		if ($action == null || empty($action)) {
		} else {
			$action = ucfirst($action);
			$filename = 'actions/' . $action . '.action.php';
			$filename = realpath($filename);
			if ($filename === FALSE) {
				trigger_error('Action "' . $action . '" not supported.');
			} else {
				$filename = basename($filename);
				$filename =  'actions/' . $filename;
	
				$handler = array();
				$handler[] = $filename;
				$handler[] = $action;
		    }
		}
	
	    return $handler;
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

	function showMessages()
	{
		// If any messages are pending, get them and display them.
		$messages = $this->server->getMessages();
		foreach ($messages as $m) {
    		$this->template_engine->addMessage($m);
		}
		$this->server->clearMessages();
	}
	
	
	function handleError($errno, $errstr, $errfile, $errline)
	{
		// define an assoc array of error string
	    // in reality the only entries we should
	    // consider are E_WARNING, E_NOTICE, E_USER_ERROR,
	    // E_USER_WARNING and E_USER_NOTICE
	    $errortype = array (
                E_ERROR           => 'Error',
                E_WARNING         => 'Warning',
                E_PARSE           => 'Parsing Error',
                E_NOTICE          => 'Notice',
                E_CORE_ERROR      => 'Core Error',
                E_CORE_WARNING    => 'Core Warning',
                E_COMPILE_ERROR   => 'Compile Error',
                E_COMPILE_WARNING => 'Compile Warning',
                E_USER_ERROR      => 'User Error',
                E_USER_WARNING    => 'User Warning',
                E_USER_NOTICE     => 'User Notice',
                E_STRICT          => 'Runtime Notice'
        );
		switch ($errno) {
			case E_NOTICE:
			case E_STRICT:
				break;
			case E_ERROR:
			case E_WARNING:
			case E_PARSE:
			case E_CORE_ERROR:
			case E_CORE_WARNING:
			case E_COMPILE_ERROR:
			case E_COMPILE_WARNING:
				echo 'Error (' . $errortype[$errno] . ') in ' . $errfile . ':' . $errline . ' - ' . $errstr;
				print_r(debug_backtrace());
				$this->template_engine->addError($errstr);
				exit();
				break;
		
			case E_USER_ERROR:
				echo 'Error (' . $errortype[$errno] . ') in ' . $errfile . ':' . $errline . ' - ' . $errstr;
				print_r(debug_backtrace());
			    $this->template_engine->addError($errstr);
			    exit();
				break;
			
			case E_USER_WARNING:
			case E_USER_NOTICE:
				echo 'Error (' . $errortype[$errno] . ') in ' . $errfile . ':' . $errline . ' - ' . $errstr;
				print_r(debug_backtrace());
				$this->template_engine->addError($errstr);
				exit();
			
			default:
				echo 'Error (' . $errortype[$errno] . ') in ' . $errfile . ':' . $errline . ' - ' . $errstr;
				exit();
    	}
	
    	/* Don't execute PHP internal error handler */
    	return true;
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

	function processRequest()
	{
		$this->template_engine->assign('account', $this->server->getAccount());
        $this->template_engine->assign('account_openid_url', $this->server->getAccountIdentifier($this->server->getAccount()));
		$this->template_engine->assign('SERVER_URL', $this->getServerURL());

		// First, get the request data.
		list($method, $request) = $this->getRequest();

		$this->template_engine->assign('account', $this->server->getAccount());
        $this->template_engine->assign('account_openid_url', $this->server->getAccountIdentifier($this->server->getAccount()));
		$this->template_engine->assign('SERVER_URL', $this->getServerURL());

		if (isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO'] == '/serve') {
			$this->forward($method, $request, 'serve');
		// If it's a request for an identity URL, render that.
		} else if (array_key_exists('user', $request) && $request['user']) {
			$this->forward($method, $request, 'identityPage');
		// If it's a request for a user's XRDS, render that.
		} else if (array_key_exists('xrds', $request) && $request['xrds']) {
			$this->forward($method, $request, 'XRDS');
		}
		
		$this->showMessages();
		
		
		if ($request === null) {
			// Error; $method not supported.
			$this->template_engine->addError('Request method ' . $method  . 'not supported.');
			$this->template_engine->display();
		} else {
			// Dispatch request to appropriate handler.
			$action = null;
			if (array_key_exists('action', $request)) {
				$action = $request['action'];
				$action = ucfirst($action);
			}
			
			$this->forward($method, $request, $action);
		}		
	}

	
	function redirect($url = null, $action = null, $next_action = null)
	{
		// If we didn't assigned an URL, the $url actually has the action.		
		if (strpos($url, 'http') === FALSE || strpos($url, 'http') != 0) {
			// And, if our $url is the action, then $action is the $next_action.
			if ($action != null) {
				$next_actions = $action;
			}
			$action = $url;
			$url = null;
		}

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
	    
	    if ($next_actions != null) {
	    	$url .= '&next_action=' . $next_action;
	    }

	    header('Location: ' . $url);
	    exit(0);
	}
	
	function redirectWithLogin($action)
	{
		if (is_array($action)) {
			$action = $action['action'];
		}
		$this->redirect('login', $action);		
	}
	
	function redirectWithAdmin($action) {
		$this->redirect();
	}
	
	function forward($method, $request, $action)
	{
		// Dispatch request to appropriate handler.
		$handler = $this->getHandler($action);
		if ($handler !== null) {
			list($filename, $clsname) = $handler;
			require_once($filename);
			$action = new $clsname($this);
			$action->process($method, $request);
		} else {
			$this->template_engine->display('main.tpl');
		}
		exit();
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
	
	function handleResponse($response)
	{
	    $webresponse =& $this->server->openid_server->encodeResponse($response);
	
		if ($webresponse->headers != null) {
		    foreach ($webresponse->headers as $k => $v) {
		        header("$k: $v");
		    }
		}

	    header('Connection: close');
	    print $webresponse->body;
	    exit(0);
	}
}
?>