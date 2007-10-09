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

require_once('Backend.class.php');
require_once('common.php');

/**
 * StorageBackend stores OpenID related data for each user.
 */
class StorageBackend
{
	function distrust($account, $site_root) {}
	function trust($account, $site_root) {}
	function isTrusted($account, $site_root) {}
	function removeTrust($account, $site_root) {}
	function getSites($account) {}
}

class Storage_MYSQL extends Backend_MYSQL
{
	function _init()
	{
		$trust_relationship = 'CREATE TABLE trust_relationship (' .
						'account_username VARCHAR(255) NOT NULL, '.
                        'site_root VARCHAR(512) NOT NULL, ' .
                        'trusted BOOLEAN, ' .
                        'PRIMARY KEY (account_username, site_root)' .
						')';

		$site =  'CREATE TABLE site (' .
                        'root VARCHAR(512) NOT NULL, ' .
                        'sso_method VARCHAR(512), ' .
                        'sso_arguments VARCHAR(512), ' .
                        'PRIMARY KEY (root)' .
                        ')';
                        
		$domain = 'CREATE TABLE domain (' .
        				'name VARCHAR(255) NOT NULL, ' .
        				'site_root VARCHAR(512) NOT NULL, ' .
        				'PRIMARY KEY (name, site_root)' .
        				')';

		// Create tables for OpenID storage backend.
		$tables = array(
                      'trust_relationship' => $trust_relationship,
                      'site' => $site,
                      'domain' => $domain);

		$this->log->debug('Creating tables \'' . implode('\', \'', array_keys($tables)) . '\'');
	        foreach ($tables as $key => $value) {
        	    $result = $this->db->query($value);
        		if (PEAR::isError($result)) {
        			if ($result->message === 'DB Error: already exists') {
	            		trigger_error($result->message, E_USER_NOTICE);
        		} else {
            			trigger_error($result->message, E_USER_ERROR);
            			return $result;
        		}
			} else {
				$this->log->info("Created table '$key'");
			}
		}
	}


	function addSiteToDomain($domain, $site_root)
	{
       	if ($this->getSite($site_root) === FALSE) {
       		$this->createSite($site_root);
       	}
       	
       	$result = $this->db->query(
				'INSERT INTO domain (name, site_root) VALUES (?, ?)',
            	array($domain, $site_root));
		if (PEAR::isError($result)) {
    		trigger_error($result->message, E_USER_NOTICE);
		}

       	return TRUE;
	}

	function removeSiteFromDomain($domain, $site_root)
	{
       	$result = $this->db->query(
				'DELETE FROM domain WHERE name = ? AND site_root = ?',
            	array($domain, $site_root));
		if (PEAR::isError($result)) {
			trigger_error($result->message, E_USER_NOTICE);
       	}
       	
       	return TRUE;
	}
	
	function removeDomain($domain)
	{
       	$result = $this->db->query(
				'DELETE FROM domain WHERE name = ?',
            	array($domain));
		if (PEAR::isError($result)) {
			trigger_error($result->message, E_USER_ERROR);
			return FALSE;
       	}
       	
       	return TRUE;
	}
	

    function getRelatedDomains($site_root)
    {
        $result = $this->db->getAll(
			'SELECT DISTINCT name FROM domain WHERE site_root = ?',
			array($site_root));
			
        $domains = array();
		if (PEAR::isError($result)) {
            trigger_error($result->message, E_USER_ERROR);
        }
		
   		foreach ($result as $domain) {
           	$domains[] = $domain['name'];
		}
		return $domains;
    }

	function getSitesFromDomain($domain = null)
	{
		$sites = array();
		
		if ($domain == null) {
			$result = $this->db->getAll('SELECT name, site_root FROM domain');
		} else {
			$result = $this->db->getAll('SELECT name, site_root FROM domain WHERE name = ?', array($domain));
		}	
		if (PEAR::isError($result)) {
		   	trigger_error($result->message, E_USER_ERROR);
			return FALSE;
		}
		
		foreach ($result as $site) {
			$domain = $site['name'];
			$siteroot = $site['site_root'];
			$id = $domain . ',' . base64_encode($siteroot);
			$result_element = array();
			$result_element['id'] = $id;
			$result_element['domain'] = $domain;
			$result_element['siteroot'] = $siteroot;
			$sites[] =  $result_element;
		}
		
		return $sites;
	}

 	function getSSOTypeURIs()
	{
		// XML namespace value
		define('SSO_XMLNS_1_0', 'http://numa.sc.usp.br/xmlns/1.0');

	    return array(SSO_XMLNS_1_0);
	}

	function filterMatchAnySSOType(&$service)
	{
	    $uris = $service->getTypes();
	
	    foreach ($uris as $uri) {
	        if (in_array($uri, Storage_MYSQL::getSSOTypeURIs())) {
	            return true;
	        }
	    }
	
	    return false;
	}

	function getSSOService($site_root)
	{
		require_once "Auth/OpenID.php";
		require_once "Auth/OpenID/Parse.php";
		require_once "Auth/OpenID/Message.php";
		require_once "Auth/Yadis/XRIRes.php";
		require_once "Auth/Yadis/Yadis.php";
		require_once('Auth/Yadis/ParanoidHTTPFetcher.php');
		require_once('Auth/Yadis/PlainHTTPFetcher.php');
		require_once('Auth/OpenID/Discover.php');

		
		// Discover OpenID services for a URI. Tries Yadis and falls back
		// on old-style <link rel='...'> discovery if Yadis fails.

	    // Might raise a yadis.discover.DiscoveryFailure if no document
	    // came back for that URI at all.
	    $services = array();
	    $response = Auth_Yadis_Yadis::discover($site_root);
    	if ($response->isFailure()) {
        	return false;
    	}
    	
		$xrds =& Auth_Yadis_XRDS::parseXRDS($response->response_text);
	    if ($xrds) {
    	    $services = $xrds->services(array(array('Storage_MYSQL', 'filterMatchAnySSOType')));
		}

    	if (! $services) {
        	if ($response->isXRDS()) {
            	return Auth_OpenID_discoverWithoutYadis($site_root);
	        }

    	    // Try to parse the response as HTML to get OpenID 1.0/1.1 (<link rel="...">)
        	$sss_services = Auth_OpenID_ServiceEndpoint::fromHTML($site_root, $response->response_text);
    	} else {
        	$sso_services = Auth_OpenID_makeOpenIDEndpoints($site_root, $services);
        }

		$sso_services = Auth_OpenID_getOPOrUserServices($sso_services);
		
		// TODO: Enable many SSO services (for now, we are hardcoding to NUMA's SSO).
		return $sso_services[0]->server_url;
	}

	function getRelatedSites($site_root)
	{
		$domains = $this->getRelatedDomains($site_root);
		$sites = array();
		foreach ($domains as $domain) {
			$sites[$domain] = array();
			$result = $this->db->getAll(
				'SELECT DISTINCT root, sso_method, sso_arguments FROM domain, site WHERE domain.site_root = site.root AND domain.name = ?',
				array($domain));
			if (PEAR::isError($result)) {
	            trigger_error($result->message, E_USER_ERROR);
	            return FALSE;
        	}
			
			foreach ($result as $site) {
				$sites[$domain][$site['root']] = array();
				if (empty($site['sso_method'])) {
					$sso_service = $this->getSSOService($site_root);
					if ($sso_service !== FALSE) {
						$site['sso_method'] = SSO_XMLNS_1_0;
						$site['sso_arguments'] = $sso_service;
						$this->updateSite($site['site_root'], $site['sso_method'], $site['sso_arguments']);
					}  
				}
							
				$sites[$domain][$site['root']]['sso_method'] = $site['sso_method'];
				$sites[$domain][$site['root']]['sso_arguments'] = $site['sso_arguments'];
			}
			
			if (empty($sites[$domain])) {
				unset($sites[$domain]);
			}
		}
		return $sites;	
	}

	function __trustLog($account, $site_root, $trusted)
	{
		$this->log->info("Changing the trust of '$site_root', for user '$account', to '$trusted'");

       	$result = $this->db->query(
				'INSERT INTO trust_relationship (account_username, site_root, trusted) VALUES (?, ?, ?)',
            	array($account, $site_root, $trusted));

		if (! PEAR::isError($result)) {
	        return true;
       	} else {
    		trigger_error($result->message, E_USER_NOTICE);
		}
		
		$result = $this->db->query(
				'UPDATE trust_relationship SET trusted = ? WHERE account_username = ? AND site_root = ?',
        		array($trusted, $account, $site_root));

		if (! PEAR::isError($result)) {
            return true;
       	}
       	
		trigger_error($result->message, E_USER_ERROR);
		return false;
	}

	function removeTrust($account, $site_root)
	{
		$this->log->info("Removing the trust setting of '$site_root' for user '$account'");

       	$result = $this->db->query(
				'DELETE FROM trust_relationship WHERE account_username = ? AND site_root = ?',
            	array($account, $site_root));

		if (PEAR::isError($result)) {
			trigger_error($result->message, E_USER_ERROR);
       	}
	}

	
    function trust($account, $site_root)
    {
    	$this->__trustLog($account, $site_root, true);
    
    	$domains_N_sites = $this->getRelatedSites($site_root); 
    	foreach ($domains_N_sites as $sites) {
	    	foreach ($sites as $site) {
				$this->log->info("Propagating $site_root's trust to $site");
				$this->__trustLog($account, $site, true);
			}
    	}
    }

    function distrust($account, $site_root, $delete = false)
    {
    	$this->__trustLog($account, $site_root, false);
    	   
    	$domains_N_sites = $this->getRelatedSites($site_root); 
    	foreach ($domains_N_sites as $sites) {
	    	foreach ($sites as $site) {
				$this->log->info("Propagating $site_root's distrust to $site");
				$this->__trustLog($account, $site, false);
			}
    	}
    }

    function isTrusted($account, $trust_root)
    {
        $result = $this->db->getOne(
			'SELECT trusted FROM trust_relationship WHERE account_username = ? AND site_root = ? AND trusted',
			array($account, $trust_root));

        if (PEAR::isError($result)) {
        	trigger_error($result->message, E_USER_ERROR);
        }
        
		if (empty($result)) {
			return false;
		} else {
			return true;
		}
    }

    function getSites($account)
    {
        $result = $this->db->getAll(
			'SELECT site_root, trusted FROM trust_relationship WHERE account_username = ?',
			array($account));
			
		if (PEAR::isError($result)) {
            trigger_error($result->message, E_USER_ERROR);
            return FALSE;
        }
		
		return $result;
    }

	function getSite($site_root)
    {
       	$result = $this->db->getOne(
				'SELECT * FROM site WHERE root = ?',
            	array($site_root));
		if (PEAR::isError($result)) {
			trigger_error($result->message, E_USER_ERROR);
			return FALSE;
       	}
       	
		return $result;
    }

    function createSite($site_root, $sso_method, $sso_arguments)
    {
       	$result = $this->db->query(
				'INSERT INTO site (root, sso_method, sso_arguments) VALUES (?, ?, ?)',
            	array($site_root, $sso_method, $sso_arguments));
		if (! PEAR::isError($result)) {
            return true;
       	}
		trigger_error($result->message, E_USER_NOTICE);


		$result = $this->db->query(
				'UPDATE site SET sso_method = ?, sso_arguments = ? WHERE root = ?',
        		array($sso_method, $sso_arguments, $site_root));
		if (! PEAR::isError($result)) {
            return true;
       	}

		trigger_error($result->message, E_USER_ERROR);
		return FALSE;
    }

    function removeSite($site_root)
    {
		$result = $this->db->query(
			'DELETE FROM site WHERE root = ?',
			array($site_root));
		if (PEAR::isError($result)) {
            trigger_error($result->message, E_USER_ERROR);
            return false;
        }
		
		return true;
    }

    function updateSite($site_root, $sso_method, $sso_arguments)
    {
		$result = $this->db->query(
				'UPDATE site SET sso_method = ?, sso_arguments = ? WHERE root = ?',
        		array($sso_method, $sso_arguments, $site_root));
		if (! PEAR::isError($result)) {
            return true;
       	}
		trigger_error($result->message, E_USER_NOTICE);

       	$result = $this->db->query(
				'INSERT INTO site (root, sso_method, sso_arguments) VALUES (?, ?, ?)',
            	array($site_root, $sso_method, $sso_arguments));
		if (! PEAR::isError($result)) {
            return true;
       	}

		trigger_error($result->message, E_USER_ERROR);
		return FALSE;
    }
}

?>
