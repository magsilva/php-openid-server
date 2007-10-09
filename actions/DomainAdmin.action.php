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

class DomainAdmin extends Action
{
	function requireAuth()
	{
		return true;
	}
	
	function requireAdmin()
	{
		return true;
	}

	function process($method, &$request)
	{
	    if (array_key_exists('domainname', $request)) {
	    	$this->log->debug('Creating domain');
	        $domain = $request['domainname'];
	        $siteroot = $request['domainsiteroot'];
	        
	     	$this->storage->addSiteToDomain($domain, $siteroot);
            $this->template->addMessage('Site added to domain ' . $domain);
            $this->controller->redirect('domainAdmin');
	    } else if (array_key_exists('remove', $request)) {
	    	$this->log->debug('Removing domain');
	        foreach ($request['domainelement'] as $id => $on) {
	        	$domain_element = explode(',', $id);
	        	$domain = $domain_element[0];
	        	$site_root = base64_decode($domain_element[1]);
	        	$this->storage->removeSiteFromDomain($domain, $site_root);
	        }
	        $this->server->addMessage('Sites removed from domains.');
	    } else if (array_key_exists('search', $request) || array_key_exists('showall', $request)) {
	    	$this->log->debug('Searching for domain\'s');
	    	
	        if (array_key_exists('showall', $request)) {
	            $results = $this->storage->getSitesFromDomain();
	            $this->template->assign('showall', 1);
	        } else {
	            $results = $this->storage->getSitesFromDomain($request['search']);
	        }
	
	        $this->template->assign('search', $request['search']);
	        $this->template->assign('search_results', $results);
	    }
	
	    $this->template->display('domainAdmin.tpl');
    }
}

?>