<div>
The server you trust, {$trust_root} belongs to one or more domains. Each domain
has several sites as members and the same trust level you attributed {$trust_root}
will be spread over all sites within its domain, as listed below:

{foreach from=$related_sites key="domain_name" item="domain_sites"}
<h2>{$domain_name}</h2>
<table border="1" width="100%">
	{foreach from=$domain_sites key="site_name" item="site_sso_url"}
		{check_sso url="{$site_sso_url}" identity="{$identity}" assign="sso_status"}
		<tr>
			<td>{$site_name}</td>
			<td><img src="{$sso_status}" /></td>
		</tr>
	{/foreach}
</table>
{/foreach}
</div>