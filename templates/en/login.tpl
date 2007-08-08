<div class="login">

<form name="loginform" method="post" action="{$SERVER_URL}">

<input type="hidden" name="action" value="login" />

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
