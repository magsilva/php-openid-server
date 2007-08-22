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

define('PHP_SERVER_PATH', dirname(__FILE__) . '/');
set_include_path(get_include_path() . PATH_SEPARATOR . '.');
set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__));
set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . '/libs/');
set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . '/libs/openid/');


require_once('common.php');
require_once('config.php');

require_once('Logging.class.php');
require_once('Template.class.php');
require_once('OpenIDServer.class.php');
require_once('Controller.class.php');

$log = &Logging::instance();

$log->debug('Initializing application');
$controller = new Controller();

// Initialize OpenID backend.
$server = new OpenIDServer($controller->getServerURL(), AUTH_BACKEND, $auth_parameters, STORAGE_BACKEND, $storage_parameters);
$controller->setServer($server);

// Hack to circunvent a PHP strict notice.
date_default_timezone_set(date_default_timezone_get());

// Create a template engine.
$language = SITE_LANGUAGE;
if (isset($_GET['lang'])) {
	if (key_exists($_GET['lang'], $valid_lang) ) {
		$language = $_GET['lang'];
	}
}
$template = new Template($language);
$controller->setTemplateEngine($template);

// $log->debug('Taking over the PHP error handler');
// set_error_handler(array($controller, 'handleError'));

$log->debug('System ready! Handing over the request process to the controller.');
$controller->processRequest();

?>
