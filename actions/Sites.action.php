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

require_once('Action.class.php');

class Sites extends Action
{
	function process($method, &$request)
	{
	    if ($this->server->needAuth()) {
	    	$this->controller->redirectWithLogin($request);
	    }
	    
	    $account = $this->server->getAccount();
	    $sites = $this->storage->getSites($account);
	
	    if ($method == 'POST' && $request['site']) {
	    	if (isset($request['trust_selected'])) {
                foreach ($request['site'] as $site => $on) {
                    $this->storage->trustLog($account, $site, true);
                }
            } else if (isset($request['untrust_selected'])) {
                foreach ($request['site'] as $site => $on) {
                    $this->storage->trustLog($account, $site, false);
                }
            } else if (isset($request['remove'])) {
                foreach ($request['site'] as $site => $on) {
                    $this->storage->removeTrustLog($account, $site);
                }
            }
            $this->template->addMessage('Settings saved.');
	    }
	
	    $sites = $this->storage->getSites($account);
	    $max_trustroot_length = 50;
	
	    foreach ($sites as $site) {
	        $site['trust_root_full'] = $site['trust_root'];
	        if (strlen($site['trust_root']) > $max_trustroot_length) {
	            $site['trust_root'] = substr($site['trust_root'], 0, $max_trustroot_length) . "...";
	        }
	        $site['trust_root'] = preg_replace('/\*/', '<span class="anything">anything</span>',
	                                                $site['trust_root']);
	    }
	
	    $this->template->assign('sites', $sites);
	    $this->template->display('sites.tpl');
	}
}

?>