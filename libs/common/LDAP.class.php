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
 
Copyright (C) 2007 Marco Aurélio Graciotto Silva <magsilva@gmail.com>
*/

class LDAPUtil
{
	// see: RFC2254
	function quote($str) {
        return str_replace(
                array( '\\', ' ', '*', '(', ')' ),
                array( '\\5c', '\\20', '\\2a', '\\28', '\\29' ),
                $str
        );
	}
	
	/**
	 * Take an LDAP and make an associative array from it.
	 * (http://br.php.net/manual/en/function.ldap-get-entries.php#62145).
	 * 
	 * This function takes an LDAP entry in the ldap_get_entries() style and
	 * converts itto an associative array like ldap_add() needs.
	 *
	 * @param array $entry is the entry that should be converted.
	 *
	 * @return array is the converted entry.
	 */
	function cleanUpEntry($entry)
	{
	    $retEntry = array();
	    for ($i = 0; $i < $entry['count']; $i++) {
	        $attribute = $entry[$i];
	        if ($entry[$attribute]['count'] == 1) {
	            $retEntry[$attribute] = $entry[$attribute][0];
	        } else {
	            for ($j = 0; $j < $entry[$attribute]['count']; $j++) {
	                $retEntry[$attribute][] = $entry[$attribute][$j];
	            }
	        }
	    }
	    return $retEntry;
	}
	
}

?>