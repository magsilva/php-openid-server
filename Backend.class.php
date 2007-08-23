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


/**
 * Storage backend implementations.
 */
 
require_once('DB.php');
require_once('xmpp/xep-0070.php');
require_once('adldap/adLDAP.php');

/**
 * Data storage area. We don't worry, for now, about data creation and 
 * retrieval. Our aim is just to define a standard way to connect to a
 * data storage.
 */
class Backend
{
	var $log;
	
	function Backend()
	{
		$this->log = &Logging::instance();
	}
	
	/**
	 * Connect to the data storage area.
	 * 
	 * @param $parameters Array Parameters used to connect to the storage
	 * area.
	 */
	function connect($parameters) {}
}

/**
 * A data storage area implemented as a MySQL database.
 */
class Backend_MYSQL extends Backend
{
	var $database;
	
	var $db;
	
	/*
	 * Connect to a MySQL database using PEAR DB.
	 * 
	 * @param $parameters Array Parameters used to connect to the storage
	 * area. The expected array's keys are 'username' (name of the user
	 * required to connect to the database), 'password' (the database user's
	 * password), 'database' (the database name) and 'hostspec' (the
	 * hostname or IP address of the database server).
	 */
    function connect($parameters)
    {
        $this->database = $parameters['database'];
        $parameters['phptype'] = 'mysql';
        $this->db =& DB::connect($parameters);

        if (PEAR::isError($this->db)) {
        	trigger_error($this->db, E_USER_ERROR);
        	return false;
        }
        
        $this->db->setFetchMode(DB_FETCHMODE_ASSOC);
        $this->db->autoCommit(true);
        if (PEAR::isError($this->db)) {
        	trigger_error($this->db, E_USER_ERROR);
        	return false;
        }

       	if (CREATE_DATABASE) {
       		$result = $this->_init();
       		if (PEAR::isError($result)) {
        		trigger_error($this->db, E_USER_ERROR);
        		return false;
       		}
        }
        
        return true;
    }
}

/**
 * A storage area implemented using a LDAP directory.
 * 
 * Copyright (C) 2007 Marco Aurélio Graciotto Silva <magsilva@gmail.com>
 */
class Backend_LDAP extends Backend
{
	var $server_name;
	
	var $base_dn;
	
	var $min_uid_number;
	
	var $min_gid_number;
	
	var $user_filter;
	
	var $bind_username;
	
	var $bind_password;
	
	var $conn;
	
	var $priv_conn;	
	

	/**
	 * Connect to a LDAP server.
	 * 
	 * @param $arguments Array Parameters used to connect to the storage area.
	 * The expected array's keys are 'server_name' (the LDAP server hostname
	 * or IP address), 'base_dn' (the root directory), 'bind_username' (user
	 * to use when binding, the default is null), 'bind_password' (binding
	 * user's password, the default is null), 'admin_username' (user to use
	 * when creating or modifying user information), 'admin_password' (admin
	 * user's password), 'user_filter' (format string used for searching users
	 * within the LDAP repository, the default is '(uid=% USERNAME%)').
	 */
	function connect($arguments)
	{				
    	$this->server_name = $arguments['server_name'];
    	$this->base_dn = $arguments['base_dn'];
    	$this->min_uid_number = 1000;
    	$this->min_gid_number = 1000;
    	
    	$this->user_filter = (isset($arguments['user_filter'])) ?
    		$arguments['user_filter'] : '(uid=%USERNAME%)';
		$this->bind_username = (isset($arguments['bind_username'])) ?
			$arguments['bind_username'] : null;
		$this->bind_password = (isset($arguments['bind_password'])) ?
			$arguments['bind_password'] : null;

		$admin_username = (isset($arguments['admin_username'])) ?
			$arguments['admin_username'] : $this->bind_username;
		$admin_password = (isset($arguments['admin_password'])) ?
			$arguments['admin_password'] : $this->bind_password;

		if ($this->server_name == null || $this->base_dn == null) {
			return false;
		}

		$this->conn = ldap_connect($this->server_name);
		if (ldap_errno($this->conn) !== 0 | $this->conn === FALSE) {
			trigger_error(ldap_error($this->conn), E_USER_ERROR);
		}
		
		$result = ldap_set_option($this->conn, LDAP_OPT_PROTOCOL_VERSION, 3);
		if ($result === FALSE) {
			trigger_error(ldap_error($this->conn), E_USER_ERROR);
		}
    	$result = ldap_set_option($this->conn, LDAP_OPT_DEREF, LDAP_DEREF_ALWAYS);
    	if ($result === FALSE) {
			trigger_error(ldap_error($this->conn), E_USER_ERROR);
		}
    	
    	// @ldap_start_tls($this->conn);
    	$result = ldap_bind($this->conn, $this->bind_username, $this->bind_password);
    	if ($result == FALSE) {
    		return false;
    	}
    	
		if ($this->bind_username == $admin_username) {
			$this->priv_conn =& $this->conn;
		} else {
			$this->priv_conn = ldap_connect('ldap://' .$this->server_name, 389);
			ldap_set_option($this->priv_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
	    	ldap_set_option($this->priv_conn, LDAP_OPT_DEREF, LDAP_DEREF_ALWAYS);
	    	// @ldap_start_tls($this->priv_conn);
	    	$result = ldap_bind($this->priv_conn, $admin_username, $admin_password);
	    	if ($result == FALSE) {
	    		return false;
	    	}
		}
		
    	return true;
    }
}


/**
 * A storage area implemented using an ActiveDirectory.
 * 
 * Copyright (C) 2007 Marco Aurélio Graciotto Silva <magsilva@gmail.com>
 */
class Backend_ActiveDirectory extends Backend
{
	var $ad;	

	/**
	 * Connect to a LDAP server.
	 * 
	 * @param $arguments Array Parameters used to connect to the storage area.
	 * The expected array's keys are 'server_name' (the LDAP server hostname
	 * or IP address), 'base_dn' (the root directory), 'bind_username' (user
	 * to use when binding, the default is null), 'bind_password' (binding
	 * user's password, the default is null), 'admin_username' (user to use
	 * when creating or modifying user information), 'admin_password' (admin
	 * user's password), 'user_filter' (format string used for searching users
	 * within the LDAP repository, the default is '(uid=% USERNAME%)').
	 */
	function connect($arguments)
	{
		$options = array();
		// $options['account_suffix'] = '';
		$options['base_dn'] = $arguments['base_dn'];
		$options['domain_controllers'] = array($arguments['server_name']);
		 
		if (isset($arguments['admin_username'])) {
			$options['ad_username'] = $arguments['admin_username'];
		}
		if (isset($arguments['admin_password'])) {
			$options['ad_password'] = $arguments['admin_password'];
		}
		$options['use_ssl'] = true;
		$this->ad = new adLDAP($options);

    	if ($this->ad == NULL) {
    		return FALSE;
    	} else {
    		return TRUE;
    	}
    }
}



class Backend_XMPP
{
    var $xep_0070;
    
	/**
	 * Connect to a XMPP server.
	 * 
	 * @param $parameters Array Parameters used to connect to the storage
	 * area. The expected array's keys are 'server' (name of the server),
	 * 'port' (the server's port), 'username' (the username required to
	 * connect) and 'password' (the user's password).
	 */
	function connect($parameters)
	{
        $this->xep_0070 = new XEP_0070();
        $this->xep_0070->server = $parameters['server'];
        $this->xep_0070->port = $parameters['port'];
        $this->xep_0070->username = $parameters['username'];
        $this->xep_0070->password = $parameters['password'];
	}
}

?>