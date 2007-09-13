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
	function requireAuth()
	{
		return true;
	}

	function process($method, &$request)
	{
	    $account = $this->server->getAccount();
	    $max_trustroot_length = 50;
	
	    if ($method == 'POST' && $request['site']) {
	    	if (isset($request['trust_selected'])) {
                foreach ($request['site'] as $site => $on) {
                    $this->storage->trust($account, (string) $site);
                }
            } else if (isset($request['untrust_selected'])) {
                foreach ($request['site'] as $site => $on) {
                    $this->storage->distrust($account, (string) $site);
                }
            } else if (isset($request['remove'])) {
                foreach ($request['site'] as $site => $on) {
                    $this->storage->removeTrust($account, (string) $site);
                }
            }
            $this->template->addMessage('Settings saved.');
	    }
	
	    $sites = $this->storage->getSites($account);
	    foreach ($sites as $site) {
	        $site['site_root_full'] = $site['site_root'];
	        if (strlen($site['site_root']) > $max_trustroot_length) {
	            $site['site_root'] = substr($site['site_root'], 0, $max_trustroot_length) . "...";
	        }
	        $site['site_root'] = preg_replace('/\*/', '<span class="anything">anything</span>',
	                                                $site['site_root']);
	    }
	
	    $this->template->assign('sites', $sites);
	    $this->template->display('sites.tpl');
	}
}

?>