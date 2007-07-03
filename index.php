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


require_once('common.php');
require_once('config.php');

require_once('Logging.class.php');
require_once('Template.class.php');
require_once('OpenIDServer.class.php');
require_once('Controller.class.php');

$log = &Logging::instance();

$controller = new Controller();
$log->info('Controller initialized');

// Initialize backends.
$server = new OpenIDServer($controller->getServerURL(), AUTH_BACKEND, $auth_parameters, STORAGE_BACKEND, $storage_parameters);
$controller->setServer($server);
$log->info('OpenID server initialized');

// Create a page template.
$language = SITE_LANGUAGE;
if (isset($_GET['lang'])) {
	if (key_exists($_GET['lang'], $valid_lang) ) {
		$language = $_GET['lang'];
	}
}
$template = new Template();
$controller->setTemplateEngine($template);
$log->info('Template engine initialized');

set_error_handler(array($controller, 'handleError'));
$log->info('Handed over the error handling to the application controller');

$log->info('Handing over the request processment to the controller.');
$controller->processRequest();

?>
