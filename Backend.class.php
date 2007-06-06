<?php
/**
 * Storage backend implementations.  PEAR DB is required to use these
 * storage backends.
 */


require_once('DB.php');


class Backend
{
	function connect() {}
}

class Backend_MYSQL extends Backend
{
    function connect($parameters)
    {
        $this->database = $parameters['database'];
        $parameters['phptype'] = 'mysql';
        $this->db =& DB::connect($parameters);

        if (!PEAR::isError($this->db)) {
            $this->db->setFetchMode(DB_FETCHMODE_ASSOC);
            $this->db->autoCommit(true);

            if (PEAR::isError($this->db)) {
                /*
                 trigger_error("Could not connect to database '".
                 $parameters['database'].
                 "': " .
                 $this->db->getMessage(),
                 E_USER_ERROR);
                */
                return false;
            }

            $this->_init();
        } else {
            return false;
        }

        return true;
    }
}


class Backend_LDAP extends Backend
{
		/**
	 * @param $principal_format String format for the credentials this
	 * principal accepts.
	 * $principal_format, $server_name, $base_dn, $bind_username = null,
	 * $bind_password = null, $user_filter = '(uid=%USERNAME%)'
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
		ldap_set_option($this->conn, LDAP_OPT_PROTOCOL_VERSION, 3);
    	ldap_set_option($this->conn, LDAP_OPT_DEREF, LDAP_DEREF_ALWAYS);
    	@ldap_start_tls($this->conn);
    	$result = ldap_bind($this->conn, $this->bind_username,
    		$this->bind_password);
    	if ($result == FALSE) {
    		return false;
    	}
    	
		if ($this->bind_username == $admin_username) {
			$this->priv_conn =& $this->conn;
		} else {
			$this->priv_conn = ldap_connect($this->server_name);
			ldap_set_option($this->priv_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
	    	ldap_set_option($this->priv_conn, LDAP_OPT_DEREF, LDAP_DEREF_ALWAYS);
	    	@ldap_start_tls($this->priv_conn);
	    	$result = ldap_bind($this->priv_conn, $admin_username, $admin_password);
	    	if ($result == FALSE) {
	    		return false;
	    	}
		}
		
    	return true;
    }
}

?>