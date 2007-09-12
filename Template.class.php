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
require_once('smarty/Smarty.class.php');

/**
 * The Smarty template class used by this application.
 */
class Template extends Smarty
{
	var $available_languages;
	
	var $language;
	
	var $log;
	
	var $errors;
	
	var $messages;
	
    function Template($language = SITE_LANGUAGE)
    {
    	$this->log = &Logging::instance();
    	
    	$this->available_languages = array(
			'default' => 'default language',
			'en' => 'english',
			'de' => 'deutsch'
		);
       	if (key_exists($language, $this->available_languages)) {
			$this->language = $language;
		} else {
			$this->language = SITE_LANGUAGE;
		}
		$this->log->debug("Language in use: $this->language");
		    
        $this->template_dir = PHP_SERVER_PATH . 'templates/' . $this->language;
        $this->compile_dir = PHP_SERVER_PATH . 'templates/' . $this->language . '/templates_c';
        $this->log->debug("Using templates from '$this->template_dir' and compiling them to '$this->compile_dir'");
        
        $this->errors = array();
        $this->messages = array();
    }

    function addError($str, $stopApp = false)
    {
        if (empty($this->errors)) {
        	$this->errors[] = $str;
        } else if ($this->errors[count($this->errors) - 1] !== $str) {
        	$this->errors[] = $str;
        }        	
        
        if ($stopApp === true) {
        	$this->display('error.tpl', true);
        	exit();
        }
    }

    function addMessage($str)
    {
        $this->messages[] = $str;
    }

    function display($filename = null, $template_override = false)
    {
    	if ($template_override) {
	    	$this->log->debug("Displaying template '$filename'");
    	} else {
    		$this->log->debug("Displaying template '$filename' using 'index.tpl' as container");
    	}
    	
    	if ($template_override && $filename == null) {
    		trigger_error('Cannot override the default template if none is given instead', E_USER_ERROR);
    	}
    	
        $this->assign('errors', $this->errors);
        $this->assign('messages', $this->messages);
        $this->assign('SITE_TITLE', SITE_TITLE);
        $this->assign('ADMIN', isset($_SESSION['php_openidserver_admin']));
        $this->assign('SITE_ADMIN_EMAIL', SITE_ADMIN_EMAIL);
        $this->assign('ALLOW_PUBLIC_REGISTRATION', ALLOW_PUBLIC_REGISTRATION);
        $this->assign('current_language', 'lang=' . $this->language);
        $this->assign('available_languages', $this->available_languages);

        if ($template_override) {
            return parent::display($filename);
        }
        
        if ($filename) {
			$this->assign('body', $this->fetch($filename));
		}
		return parent::display('index.tpl');
    }
}

?>