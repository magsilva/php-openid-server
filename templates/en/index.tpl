{include file="header.tpl"}

{include file="nav.tpl"}
{include file="errors.tpl"}
{include file="messages.tpl"}
	
<div class="body">
{if $body}
	{$body}
{else}
	{include file="main.tpl"}
{/if}
</div>

{include file="footer.tpl"}
