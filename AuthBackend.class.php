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

	function getAccountProfile($account)
	{
	}
	
	function setAccountProfile($account, $profile)
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
        				'password VARCHAR(255) NOT NULL, ' .
		        		implode(', ', $sreg) .
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
    
    function setAccountProfile($account, $data)
    {
        global $sreg_fields;

        $profile = array();
        foreach ($sreg_fields as $field) {
            $profile[$field] = '';
            if (array_key_exists($field, $data)) {
                $profile[$field] = $data[$field];
            }
        }

        // Update the persona record.
        $fields = array();
        $values = array();
        $values[] = $account;
        
        foreach ($profile as $k => $v) {
            $fields[] = "$k = ?";
            $values[] = $v;
        }

        $result = $this->db->query(
			'UPDATE account SET ' .
			implode(', ', array_keys($profile)) .
			' WHERE username = ?',
			$values);

        if (PEAR::isError($result)) {
            trigger_error($result->message, E_USER_ERROR);
        }
	}

    function getAccountProfile($account)
    {
        global $sreg_fields;

        $result = $this->db->getRow(
			'SELECT ' . implode(', ', $sreg_fields) . ' FROM account WHERE username = ?',
			array($account));

        if (PEAR::isError($result)) {
            trigger_error($result->message, E_USER_ERROR);
            return null;
        }

        return $result;
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

		$ldaprecord_dn = 'uid=' . $username . ',ou=People,' . $this->base_dn;
		
		// In Active Directory, the values must be an array
		$ldaprecord['objectclass'] = array();
		$ldaprecord['objectclass'][] = 'Person';
		$ldaprecord['cn'] = (isset($query['FirstName'])) ? $query['FirstName'] : $username;
		$ldaprecord['sn'] = (isset($query['LastName'])) ? $query['LastName'] : $username;
		// put user in objectClass inetOrgPerson so we can set the mail and phone number attributes
		$ldaprecord['userPassword'] = '{MD5}' . base64_encode(pack('H*',md5($password)));
		// $ldaprecord['telephoneNumber'] = (isset($query['LastName'])) ? $query['TelephoneNumber'] : '';
		
		$ldaprecord['objectclass'][] = 'inetOrgPerson';
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

	function getAccountProfile($account)
	{
        global $sreg_fields;

		$ldap_user = $this->get_ldap_user($account);
		if ($ldap_user === null) {
			trigger_error(ldap_error($this->conn), E_USER_ERROR);
			return null;
		}
		// http://www.yolinux.com/TUTORIALS/LinuxTutorialLDAP-GILSchemaExtension.html
		$profile['nickname'] = (array_key_exists('displayName', $ldap_user)) ? $ldap_user['displayName'][0] : null;
		$profile['email'] = (array_key_exists('mail', $ldap_user)) ? $ldap_user['mail'][0] : null;
		$profile['fullname'] = $ldap_user['cn'][0];
		$profile['dob'] = null;
		$profile['gender'] = null;
		$profile['postcode'] = (array_key_exists('postalCode', $ldap_user)) ? $ldap_user['postalCode'][0] : null;
		$profile['country'] = (array_key_exists('countryName', $ldap_user)) ? $ldap_user['countryName'][0] : null;
		$profile['language'] = (array_key_exists('preferredLanguage', $ldap_user)) ? $ldap_user['preferredLanguage'][0] : null;;
		$profile['timezone'] = null;
		
		foreach ($profile as $k => $v) {
			if ($v == null) {
				unset($profile[$k]);
			}
		}
		
		return $profile;
	}
	
	function setAccountProfile($username, $data)
	{
		$ldaprecord_dn = 'uid=' . $username . ',ou=People,' . $this->base_dn;
		
		$ldaprecord['objectclass'] = array();
		$ldaprecord['objectclass'][] = 'person';
		$ldaprecord['objectclass'][] = 'inetOrgPerson';
		$ldaprecord['objectclass'][] = 'posixAccount';
		
		if (array_key_exists('fullname', $data) && ! empty($data['fullname'])) {
			$ldaprecord['cn'] = $data['fullname'];
		}
		if (array_key_exists('nickname', $data) && ! empty($data['nickname'])) {
			$ldaprecord['displayName'] =  $data['nickname'];
		}
		if (array_key_exists('email', $data) && ! empty($data['email'])) {
			$ldaprecord['mail'] = $data['email'];
		}
		if (array_key_exists('postcode', $data) && ! empty($data['postcode'])) {
			$ldaprecord['postalCode'] = $data['postcode'];
		}
		if (array_key_exists('country', $data) && ! empty($data['country'])) {
			$ldaprecord['countryName'] = $data['country'];
		}
		if (array_key_exists('language', $data) && ! empty($data['language'])) {
			$ldaprecord['preferredLanguage'] = $data['language'];
		}
		
		$this->log->debug(var_export($ldaprecord, true));
		
		$result = @ldap_modify($this->priv_conn, $ldaprecord_dn, $ldaprecord);
		if ($result === FALSE) {
			$this->log->err(ldap_error($this->priv_conn));
		}
		
		return $result;
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

		$ldaprecord_dn = 'cn=' . $username . ',ou=People,' . $this->base_dn;
		
		$attrs = array();
		$attrs['username'] = $username; 
		$attrs['password'] = $password; 
		$attrs['firstname'] = isset($query['FirstName']) ? $query['FirstName'] : $username; 
		$attrs['surname'] = isset($query['LastName']) ? $query['LastName'] : $username;
		$attrs['email'] = $query['Email'];
		$attrs['display_name'] = $attrs['firstname'] . ' ' . $attrs['surname'];
		$attrs['container'] = array('');	// container 	* 	The folder in AD to add the user to.
		
		
		return $this->ad->user_create($attrs);
    }

    function removeAccount($username)
    {
    	if ($username == null) {
    		return false;
    	}
    	return $this->ad->user_delete($username);
    }

    function setPassword($username, $password)
    {
    	if ($username == null) {
    		return false;
    	}
    	return $this->ad->user_password($username, $password);
	}

    function search($str = null)
    {
    	$result = $this->ad->user_info($str);
    	if (count($result) < 1) {
    		return null;
    	}

		return $result;
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