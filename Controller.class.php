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
	
	var $sso;
	
	var $log;
	
	function Controller()
	{
		$this->log = &Logging::instance();
		
		// Force SSL.
		if (! isset($_SERVER['HTTPS']) OR $_SERVER['HTTPS'] != 'on') {
			$this->log->debug('Using unsecure HTTP connection');
			if (FORCE_HTTPS) {
				$this->log->debug('Switching to secure HTTP connection');
				// $this->redirect('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']);
			}
		}
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
	}

	function saveRequestInfo()
	{
		// http://br.php.net/manual/en/language.variables.predefined.php#72571
		// PHP automatically replace dots ('.') AND spaces (' ') with underscores ('_')
		// in any incoming POST or GET (or REQUEST) variables ('.' and ' ' are not valid
		// characters to use in a variable name).
		
		// Vars in $_REQUEST are *not* a reference to the respective $_POST and $_GET and
		// $_COOKIE ones.

		$_SESSION['request'] = $_REQUEST;
	}
	
	function restoreRequestInfo()
	{
		if (isset($_SESSION['request'])) {
			// $_REQUEST = $_SESSION['request'];
			// Preserve previous request values (if they do not conflict).
			foreach ($_SESSION['request'] as $key => $val) {
				$_REQUEST['$key'] = $val;
			}
			$this->clearRequestInfo();
		}
	}

	function clearRequestInfo()
	{
		unset($_SESSION['request']);
	}

	function getHandler($action)
	{
		$handler = null;
		
		if ($action == null || empty($action)) {
			trigger_error('No action was given', E_USER_ERROR);
		}
		
		$action = ucfirst($action);
		$filename = dirname(__FILE__) . '/actions/' . $action . '.action.php';
		$filename = realpath($filename);
		if ($filename === FALSE) {
			trigger_error("Action '$action' not supported.", E_USER_ERROR);
		}
		
		$this->log->debug("Found a handler for action '$action' ('$filename')");
		$filename = basename($filename);
		$filename =  'actions/' . $filename;
		require_once($filename);
		$handler = new $action($this);

	    return $handler;
	}

	
	function getRequest()
	{
    	$method = $_SERVER['REQUEST_METHOD'];

    	switch ($method) {
    		case 'GET':
        		return array($method, $_REQUEST);
        		break;
    		case 'POST':
        		return array($method, $_REQUEST);
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
			case E_STRICT:
				break;
			case E_NOTICE:
				$this->log->warning('Error (' . $errortype[$errno] . ') in ' . $errfile . ':' . $errline . ' - ' . $errstr);
				break;
			case E_ERROR:
			case E_WARNING:
			case E_PARSE:
			case E_CORE_ERROR:
			case E_CORE_WARNING:
			case E_COMPILE_ERROR:
			case E_COMPILE_WARNING:
				$this->log->err('Error (' . $errortype[$errno] . ') in ' . $errfile . ':' . $errline . ' - ' . $errstr);
				exit();
				break;
		
			case E_USER_ERROR:
				$this->log->err('Error (' . $errortype[$errno] . ') in ' . $errfile . ':' . $errline . ' - ' . $errstr);
			    $this->template_engine->addError($errstr);
				break;	
			
			case E_USER_WARNING:
			case E_USER_NOTICE:
				$this->log->notice('Error (' . $errortype[$errno] . ') in ' . $errfile . ':' . $errline . ' - ' . $errstr);
				$this->template_engine->addError($errstr);
				break;
			
			default:
				$this->log->err('Error (' . $errortype[$errno] . ') in ' . $errfile . ':' . $errline . ' - ' . $errstr);
				exit();
    	}
	
    	/* Don't execute PHP internal error handler */
    	return true;
	}
	

	/**
	 * Get the URL of the current script
	 */
	function getServerRootURL()
	{
	    $host = $_SERVER['HTTP_HOST'];
	    $port = $_SERVER['SERVER_PORT'];
	    $s = isset($_SERVER['HTTPS']) ? 's' : '';
	    if (($s && $port == '443') || (!$s && $port == '80')) {
	        $p = '';
	    } else {
	        $p = ':' . $port;
	    }
	    
	    return "http$s://$host$p";
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
	
	    return $this->getServerRootURL() . $path;
	}

	/**
	 * Get the URL of the current script
	 */
	function getServerURLWithLanguage()
	{
		$url = $this->getServerURL();

		if (isset($_GET['lang'])) {
	        if (strpos($url, '?') === false) {
	            $url .= '?lang=' . $this->template_engine->language;
	        } else {
	            $url .= '&lang=' . $this->template_engine->language;
	        }
	    }
	    
	    return $url;
	}


	function processRequest()
	{
		$this->log->debug('Request being processed: ' . $_SERVER['REQUEST_URI']);
		
		$this->template_engine->assign('account', $this->server->getAccount());
        $this->template_engine->assign('account_openid_url', $this->server->getAccountIdentifier($this->server->getAccount()));
		$this->template_engine->assign('RAW_SERVER_URL', $this->getServerURL());
		$this->template_engine->assign('SERVER_URL', $this->getServerURLWithLanguage());

		// Restore previous request content (if any)
		$this->restoreRequestInfo();
	
		// First, get the request data.
		list($method, $request) = $this->getRequest();
		
		$this->showMessages();
		
		if ($request === null) {
			// Error; $method not supported.
			trigger_error('Request method ' . $method  . 'not supported.', E_USER_ERROR);
		}
		
		// Dispatch request to appropriate handler.
		$action = 'index';
		if (array_key_exists('action', $request)) {
			$action = $request['action'];
		}
		$this->forward($method, $request, $action);
		exit();
	}

	
	function redirect($url = null, $action = null, $next_action = null, $return_to = null)
	{
		$this->saveRequestInfo();

		// If we didn't assigned an URL, the $url actually has the action.		
		if ($url != null && (strpos($url, 'http') === FALSE || strpos($url, 'http') != 0)) {
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
	    
	    if ($next_action != null) {
	    	$url .= '&next_action=' . $next_action;
	    }

		if ($return_to != null) {
			$url .= '&return_to=' . htmlentities($return_to);
		}

	    if ($action != null && isset($_GET['lang'])) {
	        if (strpos($url, '?') === false) {
	            $url .= '?lang=' . $this->template_engine->language;
	        } else {
	            $url .= '&lang=' . $this->template_engine->language;
	        }
	    }

		$this->log->info("Redirecting to action '$action', next action is '$next_action', and return URL is '$return_to' ($url)");
	    header('Location: ' . $url);
	    exit(0);
	}
	
	function redirectWithLogin($request)
	{
		$this->log->debug("Redirecting with login to '$action'");
		$this->redirect(null, 'login');		
	}
	
	function redirectWithAdmin($action)
	{
		$this->log->debug("Redirecting requiring admin privileges to '$action'");
		$this->redirect();
	}
	
	function forward($method, $request, $action)
	{
		$this->log->debug("Forwarding to action '$action' ($method, \n" . var_export($request, true) . ")");
		
		$this->restoreRequestInfo();

		// Dispatch request to appropriate handler.
		$handler = $this->getHandler($action);
		if ($handler->requireAuth() && $this->server->getAccount() == null) {
			$this->log->debug('Action requires authentication and user is not authenticated, so forwarding him to login');
			$this->redirectWithLogin();
		}

		$this->log->debug("Handing over the job to $action action's handler");
		$result = $handler->process($method, $request);
		if ($result === false) {
			$this->template_engine->display('main.tpl');
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