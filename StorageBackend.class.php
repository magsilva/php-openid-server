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
    function siTrusted($account, $site_root) {}
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

	function getRelatedSites($site_root)
	{
		$domains = $this->getRelatedDomains($site_root);
		$sites = array();
		foreach ($domains as $domain) {
			$sites[$domain] = array();
			$result = $this->db->getAll(
				'SELECT DISTINCT site_root, sso_method, sso_arguments FROM domain, site WHERE domain.site_root = site.root AND domain = ?',
				array($domain));
			
			if (PEAR::isError($result)) {
	            trigger_error($result->message, E_USER_ERROR);
        	}
				
			foreach ($result as $site) {
				$sites[$domain][$site['trust_root']] = array();
				$sites[$domain][$site['trust_root']]['method'] = $site['sso_method'];
				$sites[$domain][$site['trust_root']]['arguments'] = $site['sso_arguments'];
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
       	}

		$this->db->query(
				'UPDATE trust_relationship SET trusted = ? WHERE account_username = ? AND site_root = ?',
        		array($trusted, $account, $site_root));

		if (! PEAR::isError($result)) {
            return true;
       	}
       	
		trigger_error($result->message, E_USER_ERROR);
		return false;
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

    function distrust($account, $site_root)
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
			'SELECT site_root, trusted FROM site WHERE account_username = ?',
			array($account));
			
		if (PEAR::isError($result)) {
            trigger_error($result->message, E_USER_ERROR);
        }
		
		return $result;
    }

    function removeAccount($account)
    {
        $result = $this->db->query(
			'DELETE FROM account WHERE username = ?',
			array($account));
		if (PEAR::isError($result)) {
            trigger_error($result->message, E_USER_ERROR);
        }
		
			
        $result = $this->db->query(
			'DELETE FROM trust_relationship WHERE account_username = ?',
			array($account));
		if (PEAR::isError($result)) {
            trigger_error($result->message, E_USER_ERROR);
        }
			
    }
}

?>