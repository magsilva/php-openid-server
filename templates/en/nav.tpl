<div class="nav">
<ul>
	{if $account}
	<li class="right nohover">Logged in as <span class="openid">{$account}</span></li>
	{/if}
	
    <li><a href="{$SERVER_URL}">Home</a></li>
    {if $account}
		{if $ADMIN}
			<li><a href="{$SERVER_URL}?action=accountAdmin">Accounts</a></li>
			<li><a href="{$SERVER_URL}?action=domainAdmin">Domains</a></li>
		{else}
			<li><a href="{$SERVER_URL}?action=account">My Profile</a></li>
			<li><a href="{$SERVER_URL}?action=sites">Sites</a></li>
		{/if}
		<li><a href="{$SERVER_URL}?action=logout">Log out</a></li>
	{else}
		<li><a href="{$SERVER_URL}?action=login">Log in</a></li>
		
		{if $ALLOW_PUBLIC_REGISTRATION}
		<li><a href="{$SERVER_URL}?action=register">Register</a></li>
		{/if}
	{/if}
    
	<li style="align:right">
	|
    {foreach from=$available_languages key=key item=value}
    	{if $key <> 'default'}
			<a href="{$SERVER_URL}?lang={$key}"><img src="images/{$key}.png" border="0" alt="{$value}"></a> | 
    	{/if}
    {/foreach}
	</li>
</ul>
</div>
