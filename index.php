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

require_once('Template.class.php');
require_once('OpenIDServer.class.php');
require_once('Controller.class.php');

$controller = new Controller();

// Initialize backends.
$server = new OpenIDServer();
$auth = $server->auth_backend;
$storage = $server->storage_backend;

// Create a page template.
$template = new Template();

set_error_handler(array($controller, 'handleError'));



$controller->processRequest();

?>
