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

        $personas = "CREATE TABLE personas (id INTEGER AUTO_INCREMENT ".
            "NOT NULL PRIMARY KEY, ".
            "account VARCHAR(255) NOT NULL, ".implode(", ", $sreg_fields_sql).")";

        // Create tables for OpenID storage backend.
        $tables = array(
                        "CREATE TABLE identities (id INTEGER AUTO_INCREMENT ".
                        "NOT NULL PRIMARY KEY, ".
                        "account VARCHAR(255) NOT NULL, url TEXT NOT NULL, ".
                        "UNIQUE (account, url(255)))",

                        $personas,

                        "CREATE TABLE sites (account VARCHAR(255) NOT NULL, ".
                        "trust_root TEXT, trusted BOOLEAN, ".
                        "UNIQUE (account, trust_root(255)))"
                        );

        foreach ($tables as $t) {
            $result = $this->db->query($t);
        }
    }

    function trustLog($account, $trust_root, $trusted)
    {
        // Try to create a record every time.  This might fail, which
        // is ok.
        $this->db->query("INSERT INTO sites (account, trust_root, trusted) VALUES (?, ?, ?)",
                         array($account, $trust_root, $trusted));

        $this->db->query("UPDATE sites SET trusted = ? WHERE account = ? AND trust_root = ?",
                         array($trusted, $account, $trust_root));
    }

    function removeTrustLog($account, $trust_root)
    {
        $this->db->query("DELETE FROM sites WHERE account = ? AND trust_root = ?",
                         array($account, $trust_root));
    }

    function isTrusted($account, $trust_root)
    {
        $result = $this->db->getOne("SELECT trusted FROM sites WHERE account = ? AND ".
                                    "trust_root = ? AND trusted",
                                    array($account, $trust_root));

        if (PEAR::isError($result)) {
            return false;
        } else {
            return $result;
        }
    }

    function getSites($account)
    {
        return $this->db->getAll("SELECT trust_root, trusted FROM sites WHERE account = ?",
                                 array($account));
    }

    function addIdentifier($account, $identifier)
    {
        $this->db->query("INSERT INTO identities (account, url) VALUES (?, ?)",
                         array($account, $identifier));
    }

    function getAccountForUrl($identifier)
    {
        $result = $this->db->getOne("SELECT account FROM identities WHERE url = ?",
                                    array($identifier));

        if (PEAR::isError($result)) {
            return null;
        } else {
            return $result;
        }
    }

    function getUrlsForAccount($account)
    {
        $result = $this->db->getCol("SELECT url FROM identities WHERE account = ?",
                                    0, array($account));

        if (PEAR::isError($result)) {
            return null;
        } else {
            return $result;
        }
    }

    function removeAccount($account)
    {
        $this->db->query("DELETE FROM identities WHERE account = ?", array($account));
        $this->db->query("DELETE FROM personas WHERE account = ?", array($account));
        $this->db->query("DELETE FROM sites WHERE account = ?", array($account));
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

        // Save profile.  First, get a persona ID.
        $persona_id = $this->_getPersonaId($account);

        // Update the persona record.
        $field_bits = array();
        $values = array();
        foreach ($profile_data as $k => $v) {
            $field_bits[] = "$k = ?";
            $values[] = $v;
        }

        $values[] = $persona_id;

        $result = $this->db->query("UPDATE personas SET ".
                                   implode(", ", $field_bits).
                                   " WHERE id = ?", $values);
    }

    function _getPersonaId($account)
    {
        $pid = $this->db->getOne("SELECT id FROM personas WHERE account = ?",
                                 array($account));

        if (PEAR::isError($pid) || ! $pid) {
            $pid = $this->_createEmptyPersona($account);
        }

        return $pid;
    }

    function _createEmptyPersona($account)
    {
        $pid = $this->db->nextId('personas_id', true);

        $this->db->query("INSERT INTO personas (id, account) VALUES (?, ?)",
                         array($pid, $account));

        return $pid;
    }

    function getPersona($account)
    {
        global $sreg_fields;

        $persona_id = $this->_getPersonaId($account);

        $result = $this->db->getRow("SELECT ".implode(", ", $sreg_fields).
                                    " FROM personas WHERE id = ?",
                                    array($persona_id));

        if (PEAR::isError($result)) {
            return null;
        }

        return $result;
    }
}

?>