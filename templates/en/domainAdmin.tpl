<fieldset>
<legend>Server Administration</legend>

<h3>Domain Search</h3>
<form method="post" action="{$SERVER_URL}">
	<input type="hidden" name="action" value="domainAdmin">
	<input type="text" name="search"> <input type="submit" value="Search domains">
	<input type="submit" name="showall" value="Show All">
</form>

{if $search || $showall}
{if $search_results}
<form method="post" action="{$SERVER_URL}">
<h3>Search Results:</h3>
<input type="hidden" name="action" value="domainAdmin">
<table>
  {foreach from=$search_results item="domain_element"}
  <tr>
    <td><input id="domainelement[{$domain_element.id}]" type="checkbox" name="domainelement[{$domain_element.id}]]"></td>
    <td><label for="domainelement[{$domain_element.id}]">{$domain_element.domain} - {$domain_element.siteroot}</label></td>
  </tr>
  {/foreach}
</table>
<input type="hidden" name="search" value="{$search}">
<input type="submit" name="remove" value="Remove selected sites">
</form>
{else}
  {if $showall}
    No domains found.
  {else}
    No domains found for '{$search}'.
  {/if}
{/if}
{/if}

<h3>Domain configuration</h3>
<form method="post" action="{$SERVER_URL}">
<input type="hidden" name="action" value="domainAdmin">
<table>
  <tr>
    <td>Domain:</td>
    <td><input type="text" name="domainname"></td>
  </tr>
  <tr>
    <td>Site root:</td>
    <td><input type="text" name="domainsiteroot"></td>
  </tr>
</table>
<input type="submit" value="Add site">
</form>

</fieldset>
