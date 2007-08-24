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

require_once('Action.class.php');

class Account extends Action
{
	function requireAuth()
	{
		return true;
	}  

	function process($method, &$request)
	{
		global $timezone_strings, $country_codes, $language_codes;

	    $account = $this->server->getAccount();
		$profile = $this->auth->getAccountProfile($account);

	    if ($method == 'POST') {
	        $profile_form = $request['profile'];
	
	        // Adjust DOB value.
	        $dob = $profile_form['dob'];
	
	        if (! $dob['Date_Year']) {
	            $dob['Date_Year'] = '0000';
	        }
	        if (! $dob['Date_Month']) {
	            $dob['Date_Month'] = '00';
	        }
	        if (! $dob['Date_Day']) {
	            $dob['Date_Day'] = '00';
	        }
	
	        $profile_form['dob'] = sprintf('%d-%d-%d',
	                                       $dob['Date_Year'],
	                                       $dob['Date_Month'],
	                                       $dob['Date_Day']);
	
	        $profile = array();
	        foreach ($sreg_fields as $field) {
	            $profile[$field] = $profile_form[$field];
	        }
	
	        // TODO: Save profile.
	        $this->auth->setAccountProfile($account, $profile);
	
	        // Add a message to the session so it'll get displayed after
	        // the redirect.
	        $this->server->addMessage('Changes saved.');
	
	        // Redirect to account screen to make reloading easy.
	        $this->controller->forward($method, $request, 'index');
	    }
	
	    // TODO: $profile = $this->storage->getPersona($account);
	
	    if ($profile['dob'] === null) {
	        $profile['dob'] = '0000-00-00';
	    }
	
	    // Stuff profile data and choices into template.
	    $this->template->assign('profile', $profile);
	    $this->template->assign('timezones', $timezone_strings);
	    $this->template->assign('countries', $country_codes);
	    $this->template->assign('languages', $language_codes);
	
	    $this->template->display('account.tpl');
	}
}

?>