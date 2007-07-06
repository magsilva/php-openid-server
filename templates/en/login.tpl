<div class="login">

<form name="loginform" method="post" action="{$SERVER_URL}">

<input type="hidden" name="action" value="login" />
{if $next_action}
<input type="hidden" name="next_action" value="{$next_action}" />
{/if}
{if $return_to}
<input type="hidden" name="return_to" value="{$return_to}" />
{/if}

<table class="login">
<tr>
	<td>Username:</td>
	<td>
		<input class="disabled_bold" type="text" name="username" value="{$required_user}" {if $required_user} readonly{/if} />
	</td>
</tr>
<tr>
	<td>Password:</td>
	<td><input type="password" name="passwd" /></td>
</tr>
<tr>
	<td align="center" colspan="2"><input type="submit" value="Log in"></td>
<tr>
</table>

</form>
</div>
