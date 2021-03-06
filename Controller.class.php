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

require_once('common/Debug.class.php');
require_once('common/PHPCompatHack.php');

class Controller
{
	var $template_engine;
	
	var $server;
	
	var $auth;
	
	var $storage;
	
	var $sso;
	
	var $log;
	
	var $error_backlog;
	
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
		
		$this->error_backlog = array();
		
		$this->log->debug('Taking over the PHP error handler');
		error_reporting(E_ALL);
		ini_set('html_errors', false);  
		set_error_handler(array($this, 'handleError'));
		
		// Active assert and make it quiet
		assert_options(ASSERT_ACTIVE, 1);
		assert_options(ASSERT_WARNING, 0);
		assert_options(ASSERT_BAIL, 0);
		assert_options(ASSERT_QUIET_EVAL, 1);
		assert_options(ASSERT_CALLBACK, array($this, 'handleAssertion'));
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
		if (! empty($this->error_backlog)) {
			foreach ($this->error_backlog as $errstr) { 
				$this->template_engine->addError($errstr);
			}
		}
	}

	function saveOpenIDRequestInfo($request)
	{
        $_SESSION['php_openidserver_openid_request'] = serialize($request);
	}
	
	function getOpenIDRequestInfo()
	{
		if (isset($_SESSION['php_openidserver_openid_request'])) {
	       return unserialize($_SESSION['php_openidserver_openid_request']);
	    } else {
	        return false;
	    }
	}

	function clearOpenIDRequestInfo()
	{
		if (isset($_SESSION['php_openidserver_openid_request'])) {
	        unset($_SESSION['php_openidserver_openid_request']);
	    }
	}

	function saveRequestInfo()
	{
		$this->log->debug('Saving HTTP request info');
		// http://br.php.net/manual/en/language.variables.predefined.php#72571
		// PHP automatically replace dots ('.') AND spaces (' ') with underscores ('_')
		// in any incoming POST or GET (or REQUEST) variables ('.' and ' ' are not valid
		// characters to use in a variable name).
		
		// Vars in $_REQUEST are *not* a reference to the respective $_POST and $_GET and
		// $_COOKIE ones.
		if (array_key_exists('php_openidserver_request', $_SESSION)) {
			$this->log->err("Cannot save HTTP request info (there is one stored already).");
			return;
		}
		
		$_SESSION['php_openidserver_request'] = array();
		$_SESSION['php_openidserver_request']['get'] = array();
		$_SESSION['php_openidserver_request']['post'] = array();
		$_SESSION['php_openidserver_request']['request'] = array();
		$_SESSION['php_openidserver_request']['request_method'] = $_SERVER['REQUEST_METHOD'];
		$_SESSION['php_openidserver_request']['query_string'] = $_SERVER['QUERY_STRING'];
		
		$raw_post = file_get_contents('php://input');
		if ($raw_post === false) {
			$raw_post = $GLOBALS['HTTP_RAW_POST_DATA'];	
		}
		$_SESSION['php_openidserver_request']['raw_post'] = $raw_post;
		
		
		foreach ($_GET as $key => $value) {
			$_SESSION['php_openidserver_request']['get'][$key] = $value;
		}
		foreach ($_POST as $key => $value) {
			$_SESSION['php_openidserver_request']['post'][$key] = $value;
		}
		foreach ($_REQUEST as $key => $value) {
			$_SESSION['php_openidserver_request']['request'][$key] = $value;
		}
		
	}
	
	function restoreRequestInfo()
	{	
		if (array_key_exists('php_openidserver_request', $_SESSION)) {
			$this->log->debug('Restoring HTTP request info');
			
			foreach ($_GET as $key => $value) {
				unset($_GET[$key]);
			}
			foreach ($_POST as $key => $value) {
				unset($_POST[$key]);
			}
			foreach ($_REQUEST as $key => $value) {
				unset($_REQUEST[$key]);
			}
			
			$_SERVER['REQUEST_METHOD'] = $_SESSION['php_openidserver_request']['request_method'];
			$_SERVER['QUERY_STRING'] = $_SESSION['php_openidserver_request']['query_string'];
			$GLOBALS['HTTP_RAW_POST_DATA'] = $_SESSION['php_openidserver_request']['raw_post']; 	

			foreach ($_SESSION['php_openidserver_request']['get'] as $key => $value) {
				$_GET[$key] = $value;
			}
			foreach ($_SESSION['php_openidserver_request']['post'] as $key => $value) {
				$_POST[$key] = $value;
			}
			foreach ($_SESSION['php_openidserver_request']['request'] as $key => $value) {
				$_REQUEST[$key] = $value;
			}
			
			$this->clearRequestInfo();
		}
	}

	function clearRequestInfo()
	{
		if ($this->hasRequestInfo()) {
			$this->log->debug('Clearing saved HTTP request info');
			unset($_SESSION['php_openidserver_request']);
		}
	}

	function hasRequestInfo()
	{
		if (array_key_exists('php_openidserver_request', $_SESSION)) {
			return true;
		}
		return false;
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

	function handleAssertion($file, $line, $message)
	{
		$this->log->notice('Failed assertion in ' . $file . ':' . $line . ' - ' . $message .
			"\nTrace:\n" . DebugUtil::exportTrace());
    	
    	/* Don't execute PHP internal assertion handler */
    	return true;
	}

	
	
	function handleError($errno, $errstr, $errfile, $errline)
	{
		/*
		$trace[1]['function']
		$trace[1]['line']
		$trace[1]['file']
		$trace[1]['class']
		$trace[1]['object']
		$trace[1]['type']
		$trace[1]['args']
		*/
		
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
                E_STRICT          => 'Runtime Notice',
                E_RECOVERABLE_ERROR     => 'Recoverable Fatal Error'
        );
        
		switch ($errno) {
			case E_STRICT:
				break;
			case E_NOTICE:
				// Ignore messages generated the templates (usually undefined variables).
				if ($this->template_engine !== null && strpos($this->template_engine->template_dir, $errfile) == 0) {
					break;
				}
				$this->log->notice('System error (' . $errortype[$errno] . ') in ' . $errfile . ':' . $errline . ' - ' .
					$errstr . "\nTrace:\n" . DebugUtil::exportTrace());
				break;
			case E_WARNING:
				// Ignore messages generated the templates (usually undefined variables).
				if ($this->template_engine !== null && strpos($this->template_engine->template_dir, $errfile) == 0) {
					break;
				}
				$this->log->warning('System error (' . $errortype[$errno] . ') in ' . $errfile . ':' . $errline . ' - ' .
					$errstr . "\nTrace:\n" . DebugUtil::exportTrace());
				break;
			case E_ERROR:
			case E_PARSE:
			case E_CORE_ERROR:
			case E_CORE_WARNING:
			case E_COMPILE_ERROR:
			case E_COMPILE_WARNING:
			case E_RECOVERABLE_ERROR:
				$this->log->warning('System error (' . $errortype[$errno] . ') in ' . $errfile . ':' . $errline . ' - ' .
					$errstr . "\nTrace:\n" . DebugUtil::exportTrace());
				$errstr = 'A severe internal application error has just ocurred. This site\'s administrator has' .
						' been notified about it. Please, try to access this site later.';
				if (ob_get_contents() !== FALSE) {
					ob_end_clean();
				}
				echo $errstr;
				exit();
				break;
		
			case E_USER_ERROR:
				$this->log->err('Error (' . $errortype[$errno] . ') in ' . $errfile . ':' . $errline . ' - ' .
					$errstr . "\nTrace:\n" . DebugUtil::exportTrace());
				$errstr = 'An internal application error has just ocurred. This site\'s administrator has been ' .
						'notified about this error. Please, try to access this site later.';
				if ($this->template_engine != null) {
			    	  $this->template_engine->addError($errstr, true);
				} else {
					echo $errstr;
					exit();
				}
				break;	
			
			case E_USER_WARNING:
				$this->log->warning('Error (' . $errortype[$errno] . ') in ' . $errfile . ':' . $errline . ' - ' . $errstr);
				if ($this->template_engine != null) {
					$this->template_engine->addError($errstr);
				} else {
					$this->error_backlog[] = $errstr;
				}
				break;

			case E_USER_NOTICE:
				$this->log->notice('Error (' . $errortype[$errno] . ') in ' . $errfile . ':' . $errline . ' - ' .
					$errstr . "\nTrace:\n" . DebugUtil::exportTrace());
				break;
			
			
			default:
				// We handle all the forseable errors. If something escaped us, it's a serious problem!
				assert(FALSE);
    	}
	
    	/* Don't execute PHP internal error handler */
    	return true;
	}
	

	/**
	 * Get the URL of the current script
	 */
	function getServerRootURL()
	{
	    $host = $_SERVER['SERVER_NAME'];
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
		
		if ($action == 'serve' && array_key_exists('openid_mode', $request)) {
			switch ($_REQUEST['openid_mode']) {
				case 'associate':
					$action = 'associate';
					break;
				case 'check_authentication':
					$action = 'checkAuthentication';
					break;
				case 'checkid_setup':
					$action = 'checkIdSetup';
					break;
				case 'checkid_immediate':
					$action = 'checkIdImmediate';
					break;
			}
		}  
		
		$this->forward($method, $request, $action);
		exit();
	}

	
	function redirect($url = null, $action = null, $next_action = null, $return_to = null)
	{
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

	    if (isset($_GET['lang'])) {
	        if (strpos($url, '?') === false) {
	            $url .= '?lang=' . $this->template_engine->language;
	        } else {
	            $url .= '&lang=' . $this->template_engine->language;
	        }
	    }
	
		if (headers_sent($file, $line)){
    		$this->log->warn("Headers were already sent in $file on line $line, redirection may fail");
		}
		
		$this->log->info("Redirecting to '$url'");
	 	header('Content-Lenght: 0');
	 	header('Location: ' . $url);
	    exit();
	}
	
	function redirectWithLogin()
	{
		$this->log->debug('Redirecting to login');
		$this->saveRequestInfo();
		$this->redirect(null, 'login');		
	}
	
	function redirectWithAdmin($action)
	{
		$this->log->debug('Redirecting to login as admin');
		$this->saveRequestInfo();
		$this->redirect(null, 'login');		
	}
	
	function forward($method, $request, $action)
	{
		$this->log->debug("Forwarding to action '$action'");
		
		// Dispatch request to appropriate handler.
		$handler = $this->getHandler($action);
		if ($handler->requireAuth() && $this->server->getAccount() == null) {
			$this->log->debug('Action requires authentication and user is not authenticated, so forwarding him to login');
			$this->redirectWithLogin();
		}
		
		if ($handler->requireAdmin() && ! $this->server->isAdmin()) {
			$this->log->debug('Action requires administrator privileges and user is not authenticated as a administrator, so forwarding him to login as admin');
			trigger_error('Incorrect authentication information for admin user.', E_USER_WARNING);
			$this->server->clearAccount();
			$this->redirectWithAdmin();
		}
		

		$this->log->debug("Handing over the job to $action action's handler");
		$result = $handler->process($method, $request);
		if ($result === false) {
			$this->template_engine->display('main.tpl');
		}
		exit();
	}

	function handleResponse($response)
	{
	    $webresponse =& $this->server->openid_server->encodeResponse($response);
	    if (! isset($webresponse) || empty($webresponse)) {
	    	exit();
	    }
	
		// TODO: if $webresponse == Auth_OpenID_EncodingError
	    if (isset($webresponse->headers)) {
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
