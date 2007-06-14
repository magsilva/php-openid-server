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

require_once('config.php');
require_once('common.php');
require_once('smarty/libs/Smarty.class.php');


/**
 * The Smarty template class used by this application.
 */
class Template extends Smarty
{
    function Template()
    {
        $this->template_dir = PHP_SERVER_PATH . 'templates';
        $this->compile_dir = PHP_SERVER_PATH . 'templates/templates_c';
        $this->errors = array();
        $this->messages = array();
    }

    function addError($str)
    {
        $this->errors[] = $str;
    }

    function addMessage($str)
    {
        $this->messages[] = $str;
    }

    function display($filename = null, $template_override = false)
    {
        $this->assign('errors', $this->errors);
        $this->assign('messages', $this->messages);
        $this->assign('SITE_TITLE', SITE_TITLE);
        $this->assign('ADMIN', isset($_SESSION['admin']));
        $this->assign('SITE_ADMIN_EMAIL', SITE_ADMIN_EMAIL);
        $this->assign('ALLOW_PUBLIC_REGISTRATION', ALLOW_PUBLIC_REGISTRATION);

        if ($template_override && $filename) {
            return parent::display($filename);
        } else if (!$template_override) {
            if ($filename) {
                $this->assign('body', $this->fetch($filename));
            }
            return parent::display('index.tpl');
        }
    }
}

?>