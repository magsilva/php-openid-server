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
    function trustLog($account, $trust_root, $trusted) {}
    function removeTrustLog($account, $trust_root) {}
    function isTrusted($account, $trust_root) {}
    function getSites($account) {}
    function addIdentifier($account, $identifier) {}
    function getAccountForUrl($identifier) {}
    function getUrlsForAccount($account) {}
    function removeAccount($account) {}
    function savePersona($account, $profile_data) {}
    function getPersona($account) {}
}

class Storage_MYSQL extends Backend_MYSQL
{
    function _init()
    {
        $sreg_fields_sql = array('nickname VARCHAR(255)',
                                 'email VARCHAR(255)',
                                 'fullname VARCHAR(255)',
                                 'dob DATE',
                                 'gender CHAR(1)',
                                 'postcode VARCHAR(255)',
                                 'country VARCHAR(32)',
                                 'language VARCHAR(32)',
                                 'timezone VARCHAR(255)');

        $personas = 'CREATE TABLE personas (' .
        				'account VARCHAR(255) NOT NULL PRIMARY KEY, ' .
		        		implode(', ', $sreg_fields_sql) .
		        		')';

		$identities = 'CREATE TABLE identities (' .
						'account VARCHAR(255) NOT NULL, ' .
                        'url VARCHAR(512) NOT NULL, '.
                        'PRIMARY KEY (account, url)' .
                        ')';

		$sites =  'CREATE TABLE sites (' .
						'account VARCHAR(255) NOT NULL, '.
                        'trust_root VARCHAR(512) NOT NULL, ' .
                        'sso_url VARCHAR(512), ' .
                        'trust_level INTEGER, ' .
                        'PRIMARY KEY (account, trust_root)' .
                        ')';
                        
        $domains = 'CREATE TABLE domains (' .
        				'domain VARCHAR(255) NOT NULL, ' .
        				'element_trust_root VARCHAR(512) NOT NULL, ' .
        				'PRIMARY KEY (domain, element_trust_root)' .
        				')';

        // Create tables for OpenID storage backend.
        $tables = array(
                      $identities,
                      $personas,
                      $sites,
                      $domains);

		$this->log->debug('Creating tables ' . implode(', ', $tables));
        foreach ($tables as $t) {
            $result = $this->db->query($t);
            $this->log->debug("Created table $t");
        }
    }

	function __trustLog($account, $trust_root, $trusted)
	{
		$trust_level = 0;
		$current_level = $this->db->getOne(
			'SELECT trust_level FROM sites WHERE account = ? AND trust_root = ?',
    		array($account, $trust_root));
    	
    	if (! empty($current_level)) {
    		$trust_level = $current_level;
    	}
    	
    	if ($trusted) {
    		$trust_level++;
    	} else {
    		$trust_level--;
    	}
    	if ($trust_level < 0) {
    		$trust_level = 0;
    	}

       	$this->db->query(
				'INSERT INTO sites (account, trust_root, trust_level) VALUES (?, ?, ?)',
            	array($account, $trust_root, $trust_level));
	
		$this->db->query('UPDATE sites SET trust_level = ? WHERE account = ? AND trust_root = ?',
        		array($trusted, $trust_root, $trust_level));
        		
		$this->log->info("Changed the trust level of $trust_root, for user $account, to $trust_level");
	}

    function getRelatedDomains($trust_root)
    {
        $result = $this->db->getAll('SELECT DISTINCT domain FROM domains WHERE element_trust_root = ?', array($trust_root));
        $domains = array();
   		foreach ($result as $domain) {
           	$domains[] = $domain['domain'];
		}
		return $domains;
    }

	function getRelatedSites($trust_root)
	{
		$domains = $this->getRelatedDomains($trust_root);
		$sites = array();
		foreach ($domains as $domain) {
			$sites[$domain] = array();
			$result = $this->db->getAll('SELECT DISTINCT trust_root, sso_url FROM domains,sites WHERE element_trust_root = trust_root AND domain = ?', array($domain));
			foreach ($result as $site) {
				$sites[$domain][$site['trust_root']] = $site['sso_url'];
			}
		}	
		return $sites;	
	}
	
    function trustLog($account, $trust_root, $trusted)
    {
    	$this->__trustLog($account, $trust_root, $trusted);
    
    	$domains_N_sites = $this->getRelatedSites($trust_root); 
    	foreach ($domains_N_sites as $sites) {
	    	foreach ($sites as $site) {
				$this->log->info("Propagating $trust_root's trust change to $site");
				$this->__trustLog($account, $site, $trusted);
			}
    	}
    }

    function removeTrustLog($account, $trust_root)
    {
    	$this->__trustLog($account, $trust_root, false);
    	   
        $result = $this->db->getAll('SELECT DISTINCT domain FROM domains WHERE element_trust_root = ?', array($trust_root));
		if (! empty($result)) {
           	$this->log->info("Propagating $trust_root's trust change to " . implode(', ', $result));
    		foreach ($result as $site) {
    			$this->__trustLog($account, $site, false);
	   		}
		}
    }

    function isTrusted($account, $trust_root)
    {
        $result = $this->db->getOne('SELECT trusted FROM sites WHERE account = ? AND '.
                                    'trust_root = ? AND trusted',
                                    array($account, $trust_root));

        if (PEAR::isError($result)) {
            return false;
        } else {
            return $result;
        }
    }

    function getSites($account)
    {
        return $this->db->getAll('SELECT trust_root, trusted FROM sites WHERE account = ?',
                                 array($account));
    }

    function addIdentifier($account, $identifier)
    {
        $this->db->query('INSERT INTO identities (account, url) VALUES (?, ?)',
                         array($account, $identifier));
    }

    function getAccountForUrl($identifier)
    {
        $result = $this->db->getOne('SELECT account FROM identities WHERE url = ?',
                                    array($identifier));

        if (PEAR::isError($result)) {
            return null;
        } else {
            return $result;
        }
    }

    function getUrlsForAccount($account)
    {
        $result = $this->db->getCol('SELECT url FROM identities WHERE account = ?',
                                    0, array($account));

        if (PEAR::isError($result)) {
            return null;
        } else {
            return $result;
        }
    }

    function removeAccount($account)
    {
        $this->db->query('DELETE FROM identities WHERE account = ?', array($account));
        $this->db->query('DELETE FROM personas WHERE account = ?', array($account));
        $this->db->query('DELETE FROM sites WHERE account = ?', array($account));
    }

    function savePersona($account, $profile_data)
    {
        global $sreg_fields;

        $profile = array();
        foreach ($sreg_fields as $field) {
            $profile[$field] = '';
            if (array_key_exists($field, $profile_data)) {
                $profile[$field] = $profile_data[$field];
            }
        }

        // Update the persona record.
        $field_bits = array();
        $values = array();
        foreach ($profile_data as $k => $v) {
            $field_bits[] = "$k = ?";
            $values[] = $v;
        }

		$values[] = $account;
        $result = $this->db->query('UPDATE personas SET '.
                                   implode(', ', $field_bits).
                                   ' WHERE account = ?', $values);
    }

    function getPersona($account)
    {
        global $sreg_fields;

        $result = $this->db->getRow('SELECT ' . implode(', ', $sreg_fields).
                                    ' FROM personas WHERE account = ?',
                                    array($account));

        if (PEAR::isError($result)) {
            return null;
        }

        return $result;
    }
}

?>