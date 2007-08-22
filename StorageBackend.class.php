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
        $sreg = array('nickname VARCHAR(255)',
                                 'email VARCHAR(255)',
                                 'fullname VARCHAR(255)',
                                 'dob DATE',
                                 'gender CHAR(1)',
                                 'postcode VARCHAR(255)',
                                 'country VARCHAR(32)',
                                 'language VARCHAR(32)',
                                 'timezone VARCHAR(255)');

        $account= 'CREATE TABLE account (' .
        				'username VARCHAR(255) NOT NULL PRIMARY KEY, ' .
        				'password VARCHAR(255) NOT NULL PRIMARY KEY, ' .
		        		implode(', ', $sreg) .
		        		')';

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
        				'PRIMARY KEY (domain, site_root)' .
        				')';

        // Create tables for OpenID storage backend.
        $tables = array(
                      'account' => $account,
                      'trust_relationship' => $trust_relationship,
                      'site' => $site,
                      'domain' => $domain);

		$this->log->debug('Creating tables \'' . implode('\', \'', array_keys($tables)) . '\'');
        foreach ($tables as $key => $value) {
            $result = $this->db->query($value);
            $this->log->info("Created table '$key'");
        }
    }

    function getRelatedDomains($site_root)
    {
        $result = $this->db->getAll(
			'SELECT DISTINCT name FROM domain WHERE site_root = ?',
			array($site_root));
			
        $domains = array();
   		foreach ($result as $domain) {
           	$domains[] = $domain['domain'];
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
				
			foreach ($result as $site) {
				$sites[$domain][$site['trust_root']] = array();
				$sites[$domain][$site['trust_root']]['method'] = $site['sso_method'];
				$sites[$domain][$site['trust_root']]['arguments'] = $site['sso_arguments'];
			}
		}	
		return $sites;	
	}

	function __trustLog($account, $site_root, $trusted)
	{
       	$this->db->query(
				'INSERT INTO trust_relationship (account_username, site_root, trusted) VALUES (?, ?, ?)',
            	array($account, $site_root, $trusted));
	
		$this->db->query(
				'UPDATE trust_relationship SET trusted = ? WHERE account_username = ? AND site_root = ?',
        		array($trusted, $account, $site_root));
        		
		$this->log->info("Changed the trust of '$site_root', for user '$account', to '$trusted'");
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
			'SELECT trusted FROM site WHERE account_username = ? AND site_root = ? AND trusted',
			array($account, $trust_root));

        if (PEAR::isError($result)) {
            return false;
        } else {
            return $result;
        }
    }

    function getSites($account)
    {
        return $this->db->getAll(
			'SELECT site_root, trusted FROM site WHERE account_username = ?',
			array($account));
    }

    function removeAccount($account)
    {
        $this->db->query(
			'DELETE FROM account WHERE username = ?',
			array($account));
        $this->db->query(
			'DELETE FROM trust_relationship WHERE account_username = ?',
			array($account));
    }
}

?>