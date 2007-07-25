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

/**
 * Authentication backend interface.
 */
class AuthBackend
{
    function newAccount($username, $password, $query)
    {
        return false;
    }
    
    function removeAccount($username)
    {
    	return false;
    }

	function setPassword($username, $password)
	{
	}

    function search($str = null)
    {
        return array();
    }

    function authenticate($username, $password)
    {
    	return false;
    }
}


/**
 * Authentication backend that stores user's information into a MySQL
 * database.
 */
class AuthBackend_MYSQL extends Backend_MYSQL
{
    function _init()
    {
        // Create tables for OpenID storage backend.
        $tables = array();

        $account = 'CREATE TABLE accounts (' .
        				'username VARCHAR(255) NOT NULL PRIMARY KEY, ' .
                        'password VARCHAR(255)' .
					')';
		$tables[] = $account;

        foreach ($tables as $t) {
            $this->db->query($t);
        }
    }

    function newAccount($username, $password, $query)
    {
        $result = $this->db->query(
			'INSERT INTO accounts (username, password) VALUES (?, ?)',
			array($username, $this->_encodePassword($password)));

        // $query is ignored for this implementation, but you might
        // choose to change the login process to incorporate other
        // user details like an email address.  $query is the HTTP
        // query in which the account registration form was submitted.
        // You'll only need to bother with $query if you've modified
        // the account registration form template and need to access
        // your new fields.

        if (PEAR::isError($result)) {
            return false;
        } else {
            return true;
        }
    }

    function removeAccount($username)
    {
        $this->db->query(
			'DELETE FROM accounts WHERE username = ?',
			array($username));
    }

    function authenticate($username, $password)
    {
        $result = $this->db->getOne(
			'SELECT username FROM accounts WHERE username = ? AND password = ?',
			array($username, $this->_encodePassword($password)));
			
        if (PEAR::isError($result) || (! $result)) {
            return false;
        } else {
            return true;
        }
    }

    function setPassword($username, $password)
    {
        $result = $this->db->query(
			'UPDATE accounts SET password = ? WHERE username = ?',
			array($this->_encodePassword($password), $username));
			
        if (PEAR::isError($result)) {
            return false;
        } else {
            return true;
        }
    }

    function _encodePassword($p)
    {
        return md5($p);
    }

    function search($str = null)
    {
        if ($str != null) {
            $str = "%$str%";

            // Return should be a list of account names; nothing more.
            $result = $this->db->getCol(
				'SELECT username FROM accounts WHERE username LIKE ? ORDER BY username',
				0,
				array($str));
        } else {
            $result = $this->db->getCol(
				'SELECT username FROM accounts ORDER BY username',
				0);
        }

        if (PEAR::isError($result)) {
            return array();
        } else {
            return $result;
        }
    }
}

/**
 * Authentication backend that stores user's information into a LDAP
 * directory.
 * 
 * Copyright (C) 2007 Marco Aurélio Graciotto Silva <magsilva@gmail.com>
 */
class AuthBackend_LDAP extends Backend_LDAP
{
    function newAccount($username, $password, $query = array())
    {
    	if ($username == null) {
    		return false;
    	}

		$ldap_user = $this->get_ldap_user($username);
		if ($ldap_user != null) {
			return $this->authenticate($username, $password);
		}

		$ldaprecord_dn = 'cn=' . $username . ',ou=People,' . $this->base_dn;
		
		// In Active Directory, the values must be an array
		$ldaprecord['objectclass'] = array();
		$ldaprecord['objectclass'][] = "person";
		$ldaprecord['cn'] = (isset($query['FirstName'])) ? $query['FirstName'] : $username;
		$ldaprecord['sn'] = (isset($query['LastName'])) ? $query['LastName'] : $username;
		// put user in objectClass inetOrgPerson so we can set the mail and phone number attributes
		$ldaprecord['userPassword'] = '{MD5}' . base64_encode(pack('H*',md5($password)));
		// $ldaprecord['telephoneNumber'] = (isset($query['LastName'])) ? $query['TelephoneNumber'] : '';
		
		$ldaprecord['objectclass'][] = 'inetOrgPerson';
		// jpegPhoto
		// preferredLanguage
		
		$ldaprecord['objectclass'][] = 'posixAccount';
		$ldaprecord['uid'] = $username;
		$ldaprecord['homeDirectory'] = '/home/' . $username;
		$ldaprecord['loginShell'] = (isset($query['LoginShell'])) ? $query['LoginShell'] : '/bin/bash';
		$ldaprecord['uidNumber'] = $this->get_next_number('uid');
		$ldaprecord['gidNumber'] = $this->get_next_number('gid');

		return @ldap_add($this->priv_conn, $ldaprecord_dn, $ldaprecord);
    }

    function removeAccount($username)
    {
    	if ($username == null) {
    		return false;
    	}
    	$user = $this->get_ldap_user($username);
		return ldap_delete($this->priv_conn, $user);
    }

    function setPassword($username, $password)
    {
    	if ($username == null) {
    		return false;
    	}
    	$user = $this->get_ldap_user($username);
    	$user_dn = $user['dn'];
    	$ldaprecord['userPassword'] = '{MD5}' . base64_encode(pack('H*',md5($password)));
		return ldap_mod_replace($this->priv_conn, $user_dn, $ldaprecord);
	}

    function search($str = null)
    {
    	if ($str != null) {
    		$filter = str_replace("%USERNAME%", $str, $this->user_filter);
    	} else {
    		$filter = str_replace("%USERNAME%", "", $this->user_filter);
    	}

   		$sr = ldap_search($this->conn, $this->base_dn, $filter, array('dn'));
   	
   		if (ldap_count_entries($this->conn, $sr) != 1) {
   			return null;
   		}
   		$users = ldap_get_entries($this->conn, $sr);
   		$result = array();
		foreach ($users as $user) { 
   			$result[] = $user[0];
		}
		
		return $result;
	}

	function get_ldap_user($username)
	{
    	$filter = str_replace("%USERNAME%", $username, $this->user_filter);
    	
    	$sr = ldap_search($this->conn, $this->base_dn, $filter);
    	
    	if (ldap_count_entries($this->conn, $sr) != 1) {
    		return null;
    	}
    	$users = ldap_get_entries($this->conn, $sr);

    	$user = $users[0];
    	return $user;
	}

	/**
	 * Check user login
	 * 
	 * @param $username User's OpenID URL.
	 * @param $password User's password.
	 * 
	 * @return True if the password is correct, False otherwise.
	 */
    function authenticate($username, $password)
    {
    	if ($username == null) {
    		return false;
    	}
    	$user = $this->get_ldap_user($username);
		$result = @ldap_bind($this->conn, $user['dn'], $password);
		ldap_bind($this->conn, $this->bind_username, $this->bind_password);
		
		return $result;
    }
    
    /**
	 * Get the next available uidNumber. It searches all entries that have
	 * uidNumber set, finds the smallest and "fills in the gaps" by
	 * incrementing the smallest uidNumber until an unused value is found.
	 * 
	 * Please, note that both algorithms are susceptible to a race condition.
	 * If two admins are adding users simultaneously, the users may get
	 * identical uidNumbers with this function.
	 * 
	 * This code was based upon work of the The phpLDAPadmin development team
	 * (lib/functions.php).
	 */
	function get_next_number($type='uid')
	{
		if ($type != 'uid' && $type != 'gid') {
			return NULL;
		}
	
		$filter = '(|(uidNumber=*)(gidNumber=*))';
		switch ($type) {
			case 'uid':
				$number = $this->min_uid_number;
				break;
			case 'gid':
				$number = $this->min_gid_number;
				break;
		}
		
		$sr = ldap_search($this->conn, $this->base_dn, $filter, array('uidNumber','gidNumber'), 'sub', false, 'search');
    	$search = ldap_get_entries($this->conn, $sr);
		if (! is_array($search)) {
			return null;
		}
		
		foreach ($search as $dn => $attrs) {
			if (! is_array($attrs)) {
				continue;
			}

			$attrs = array_change_key_case($attrs);
			$entry = array();
			
			switch ($type) {
				case 'uid' :
					if (isset($attrs['uidnumber'])) {
						if (intval($attrs['uidnumber'][0]) > $number) {
							$number = intval($attrs['uidnumber'][0]);
						}
					}
					break;
				case 'gid' :
					if (isset($attrs['gidnumber'])) {
						if (intval($attrs['gidnumber'][0]) > $number) {
							$number = intval($attrs['gidnumber'][0]);
						}
					}
					break;
			}
		}	

		return $number;		
	}
}


/**
 * Authentication backend that stores user's information into a LDAP
 * directory.
 * 
 * Copyright (C) 2007 Marco Aurélio Graciotto Silva <magsilva@gmail.com>
 */
class AuthBackend_ActiveDirectory extends Backend_ActiveDirectory
{
    function newAccount($username, $password, $query = array())
    {
    	if ($username == null) {
    		return false;
    	}

		$ldap_user = $this->get_ldap_user($username);
		if ($ldap_user != null) {
			return $this->authenticate($username, $password);
		}

		$ldaprecord_dn = 'cn=' . $username . ',ou=People,' . $this->base_dn;
		
		// In Active Directory, the values must be an array
		$ldaprecord['objectclass'] = array();
		$ldaprecord['objectclass'][] = "person";
		$ldaprecord['cn'] = (isset($query['FirstName'])) ? $query['FirstName'] : $username;
		$ldaprecord['sn'] = (isset($query['LastName'])) ? $query['LastName'] : $username;
		// put user in objectClass inetOrgPerson so we can set the mail and phone number attributes
		$ldaprecord['userPassword'] = '{MD5}' . base64_encode(pack('H*',md5($password)));
		// $ldaprecord['telephoneNumber'] = (isset($query['LastName'])) ? $query['TelephoneNumber'] : '';
		
		$ldaprecord['objectclass'][] = 'inetOrgPerson';
		// jpegPhoto
		// preferredLanguage
		
		$ldaprecord['objectclass'][] = 'posixAccount';
		$ldaprecord['uid'] = $username;
		$ldaprecord['homeDirectory'] = '/home/' . $username;
		$ldaprecord['loginShell'] = (isset($query['LoginShell'])) ? $query['LoginShell'] : '/bin/bash';
		$ldaprecord['uidNumber'] = $this->get_next_number('uid');
		$ldaprecord['gidNumber'] = $this->get_next_number('gid');

		return @ldap_add($this->priv_conn, $ldaprecord_dn, $ldaprecord);
    }

    function removeAccount($username)
    {
    	if ($username == null) {
    		return false;
    	}
    	$user = $this->get_ldap_user($username);
		return ldap_delete($this->priv_conn, $user);
    }

    function setPassword($username, $password)
    {
    	if ($username == null) {
    		return false;
    	}
    	$user = $this->get_ldap_user($username);
    	$user_dn = $user['dn'];
    	$ldaprecord['userPassword'] = '{MD5}' . base64_encode(pack('H*',md5($password)));
		return ldap_mod_replace($this->priv_conn, $user_dn, $ldaprecord);
	}

    function search($str = null)
    {
    	if ($str != null) {
    		$filter = str_replace("%USERNAME%", $str, $this->user_filter);
    	} else {
    		$filter = str_replace("%USERNAME%", "", $this->user_filter);
    	}

   		$sr = ldap_search($this->conn, $this->base_dn, $filter, array('dn'));
   	
   		if (ldap_count_entries($this->conn, $sr) != 1) {
   			return null;
   		}
   		$users = ldap_get_entries($this->conn, $sr);
   		$result = array();
		foreach ($users as $user) { 
   			$result[] = $user[0];
		}
		
		return $result;
	}

	function get_ldap_user($username)
	{
    	$filter = str_replace("%USERNAME%", $username, $this->user_filter);
    	
    	$sr = ldap_search($this->conn, $this->base_dn, $filter);
    	
    	if (ldap_count_entries($this->conn, $sr) != 1) {
    		return null;
    	}
    	$users = ldap_get_entries($this->conn, $sr);

    	$user = $users[0];
    	return $user;
	}

	/**
	 * Check user login
	 * 
	 * @param $username User's OpenID URL.
	 * @param $password User's password.
	 * 
	 * @return True if the password is correct, False otherwise.
	 */
    function authenticate($username, $password)
    {
    	if ($username == null) {
    		return false;
    	}
		$result = $this->ad->authenticate($username, $password);
		return $result;
    }
    
    /**
	 * Get the next available uidNumber. It searches all entries that have
	 * uidNumber set, finds the smallest and "fills in the gaps" by
	 * incrementing the smallest uidNumber until an unused value is found.
	 * 
	 * Please, note that both algorithms are susceptible to a race condition.
	 * If two admins are adding users simultaneously, the users may get
	 * identical uidNumbers with this function.
	 * 
	 * This code was based upon work of the The phpLDAPadmin development team
	 * (lib/functions.php).
	 */
	function get_next_number($type='uid')
	{
		if ($type != 'uid' && $type != 'gid') {
			return NULL;
		}
	
		$filter = '(|(uidNumber=*)(gidNumber=*))';
		switch ($type) {
			case 'uid':
				$number = $this->min_uid_number;
				break;
			case 'gid':
				$number = $this->min_gid_number;
				break;
		}
		
		$sr = ldap_search($this->conn, $this->base_dn, $filter, array('uidNumber','gidNumber'), 'sub', false, 'search');
    	$search = ldap_get_entries($this->conn, $sr);
		if (! is_array($search)) {
			return null;
		}
		
		foreach ($search as $dn => $attrs) {
			if (! is_array($attrs)) {
				continue;
			}

			$attrs = array_change_key_case($attrs);
			$entry = array();
			
			switch ($type) {
				case 'uid' :
					if (isset($attrs['uidnumber'])) {
						if (intval($attrs['uidnumber'][0]) > $number) {
							$number = intval($attrs['uidnumber'][0]);
						}
					}
					break;
				case 'gid' :
					if (isset($attrs['gidnumber'])) {
						if (intval($attrs['gidnumber'][0]) > $number) {
							$number = intval($attrs['gidnumber'][0]);
						}
					}
					break;
			}
		}	

		return $number;		
	}
}



class AuthBackend_XMPP extends Backend_XMPP
{
    var $jidregex = '/^([^"&\'\/:<>@]+@([a-zA-Z0-9_\-\.]+)\.[a-zA-Z]{2,5}(\/.+)?)$/';
    
    function authenticate($username, $password)
    {
        $this->xep_0070->resource = md5($username);
    
        if (! preg_match($this->jidregex, $username)) {
        	return false;
        }
        
        if ($username == 'true@example.com') {
        	return true;
        }
        if ($username == 'false@example.com') {
        	return false;
        }

        return $this->xep_0070->AuthJID($username, $password, 'OpenID', Controller::getServerRootUrl());
    }
}

?>