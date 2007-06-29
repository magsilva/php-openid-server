<!-- BEGIN CUSTOMIZATIONS HERE -->
  <h3>Welcome!</h3>

  <p>
  You are now running an OpenID server.  You should edit this page and
  put some site-specific content here; see
  <code>templates/main.tpl</code>.  Enjoy!
  </p>
<!-- END CUSTOMIZATIONS HERE -->

{if $account && !$ADMIN}
<h3>Using Your Own OpenID URL</h3>

<p>
Your OpenID is:
</p>

<pre>
<a href="{$account_openid_url}">{$account_openid_url}</a>
</pre>

<p>
You can use your own URL as your OpenID.  Just edit the
<code>&lt;HEAD&gt;</code> section of your URL's web page and add this
content:

<pre>
&lt;link rel="openid.server" href="<a href="{$RAW_SERVER_URL}index.php/serve">"{$RAW_SERVER_URL}index.php/serve</a>"&gt;
&lt;link rel="openid.delegate" href="<a href="{$account_openid_url}">{$account_openid_url}</a>"&gt;
</pre>

Then you can use your URL to authenticate to this server.

</p>
{/if}
