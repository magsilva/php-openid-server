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
 
Copyright (C) 2007 Marco Aurélio Graciotto Silva <magsilva@gmail.com>
*/

class Action
{
	var $auth;

	var $controller;
	
	var $openid_server;
	
	var $server;

	var $storage;
	
	var $template;
	
	
	function Action(&$controller)
	{
		$this->controller =& $controller;
		$this->server =& $this->controller->server;
		$this->auth =& $this->server->auth_backend;
		$this->openid_server =& $this->server->openid_server;
		$this->storage =& $this->server->storage_backend;
		$this->template =& $this->controller->template_engine;
		
		$this->log = &Logging::instance();
	}
	
	function process($method, &$request) {}
}

?>