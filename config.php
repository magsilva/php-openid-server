<?php

require_once('Log.php');

/**
 * The configuration settings for the PHP OpenID Server.
 */
define('CREATE_DATABASE', false);

/**
 * The site title; this will appear at the top and bottom of each
 * page, as well as in the browser title bar.
 */
define('SITE_TITLE', 'Núcleo de Manufatura Avançada (NUMA)');

/**
 * Site default language.
 */
define('SITE_LANGUAGE', 'en');

/**
 * The administrator's email address.  You may leave this empty if you
 * wish.  If empty, the "Contact (email address)" message will not
 * appear on every page footer.
 */
define('SITE_ADMIN_EMAIL', 'magsilva@gmail.com');

/**
 * Minimum username length for account registration.
 */
define('MIN_USERNAME_LENGTH', 2);

/**
 * Minimum password length for account registration.
 */
define('MIN_PASSWORD_LENGTH', 6);

/**
 * Set this to true if you want to allow public OpenID registration.
 * In either case, the ADMIN_USERNAME account specified below will be
 * able to log in to create and remove accounts.
 */
define('ALLOW_PUBLIC_REGISTRATION', true);

/**
 * Set these values for administrative access.  This account will be
 * able to create and remove accounts from the auth backend.  This
 * username will not be permitted to use an OpenID.  The password MUST
 * be an MD5 hexadecimal hash of the password you want to use.
 * Example:
 *
 * define('ADMIN_PASSWORD_MD5', '21232f297a57a5a743894a0e4a801fc3');
 *
 */
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD_MD5', '456b7016a916a4b178dd72b947c152b7');

/**
 * Storage backend to use.  Currently the only choice is "MYSQL".  See
 * storage.php for storage backend implementations.  Parameters for
 * connecting to the storage backend.  See storage.php if you want to
 * create your own backend.
 */
define('STORAGE_BACKEND', 'MYSQL');
global $storage_parameters;
$storage_parameters = array('username' => 'test',
                            'password' => 'test',
                            'database' => 'php-openid-server-dev',
                            'hostspec' => 'localhost');

/**
 * Authentication backend for authentication queries.  Default choice
 * is "MYSQL" ("LDAP" is also available).  See auth.php for backend
 * implementations if you want to create your own.  This default setting just
 * puts the authentication data in the same database with the storage data
 * (above), so you probably don't need to adjust this.
 */
define('AUTH_BACKEND', 'LDAP');
global $auth_parameters;
$auth_parameters = array('server_name' => 'localhost',
                         'base_dn' => 'dc=m242,dc=numa,dc=intranet',
                         'bind_username' => null,
                         'bind_password' => null,
                         'admin_username' => 'cn=admin,dc=m242,dc=numa,dc=intranet',
                         'admin_password' => 'admin',
                         'user_filter' => '(uid=%USERNAME%)');

/**
 * Cookie name.
 */                         
define('COOKIE_NAME', 'secret');

/**
 * Default cookie timeout.
 */
define('COOKIE_TIMEOUT', time() + (60 * 60 * 24 * 7));		

/**
 * Force use of secure HTTP connections.
 */
define('FORCE_HTTPS', false);

/**
 * Filename to output the logging information.
 */
define('LOG_FILENAME', dirname(__FILE__) . '/out.log');

/**
 * Kind of logging message to be recorded.
 * 
 * PEAR_LOG_EMERG  System is unusable
 * PEAR_LOG_ALERT Immediate action required
 * PEAR_LOG_CRIT Critical conditions
 * PEAR_LOG_ERR Error conditions
 * PEAR_LOG_WARNING Warning conditions
 * PEAR_LOG_NOTICE Normal but significant
 * PEAR_LOG_INFO Informational
 * PEAR_LOG_DEBUG Debug-level messages
 */
define('LOG_ERROR_LEVEL', PEAR_LOG_INFO);

?>
