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
 
Copyright (C) 2007 Marco Aurelio Graciotto Silva <magsilva@gmail.com>
*/

require_once('Action.class.php');

class Index extends Action
{
	function process($method, &$request)
	{
		$this->template->assign('account', $this->server->getAccount());
        $this->template->assign('account_openid_url', $this->server->getAccountIdentifier($this->server->getAccount()));
	    $this->template->display('main.tpl');
	    return true;
	}
}

?>