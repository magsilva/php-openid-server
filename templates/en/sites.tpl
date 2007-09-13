<fieldset>
<legend>Sites</legend>

This page lists past trust decisions you have made when logging into
sites using your OpenID.

<p>
<form method="post" action="{$SERVER_URL}">
{if $sites}
<input type="hidden" name="action" value="sites">
<table class="sites">
  <tr>
    <th></th>
    <th>Site</th>
    <th>Status</th>
  </tr>
  {foreach from=$sites item="site"}
  <tr>
    <td><input type="checkbox" name="site[{$site.site_root}]" id="site[{$site.site_root}]"></td>
    <td width="100%"><code><label for="site[{$site.site_root}]">
      {if $site.site_root_full}
        <html:abbr title="{$site.site_root}">{$site.site_root}</html:abbr>
      {else}
        {$site.site_root}
      {/if}
    </code></code></td>
    <td>{if $site.trusted}<span class="trusted">Trusted</span>{else}
      <nobr><span class="untrusted">Not trusted</span></nobr>{/if}</td>
  </tr>
  {/foreach}
</table>
<br/>
<input type="submit" name="trust_selected" value="Allow">
<input type="submit" name="untrust_selected" value="Deny">
<input type="submit" name="remove" value="Remove from list">
{else}
You have not yet logged into any sites with your OpenID.
{/if}
</form>
</p>

</fieldset>
