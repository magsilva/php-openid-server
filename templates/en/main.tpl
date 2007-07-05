<h3>Welcome!</h3>

<p>Welcome to NUMA's OpenID server.</p>


<p>If this is the first time you listened (or read) about OpenID, it's advisable
to read the text below. It explains what OpenID is and how to use it.</p>


<h4>What is OpenID</h4>

OpenID is a new identification technique, specially created for Internet.
Instead of storing user sensitive information in every application (what is a
burden for the vendors, that have to assure those information are securelly
stored) and requiring, for every user, a new username and password (something
pretty annoying), OpenID provides an unique username and password and delegate
all the identification work to the OpenID server. In other words, it's a
complete and high quality solution for both users and developers.</p>


<h4>But how does it work?</h4>

For you, user, it works this way:
<ol>
	<li>You access an OpenID enabled application. If it requires the authentication
	of your identity, you'll have the usual login prompt. Nothing unusual... till
	now.</li>
	
	<li>Instead of entering your username and password, you enter just your OpenID
	URL. Yes, no password needed (yet).</li>
	
	<li>If it's the first time you authenticated to OpenID, you'll be redirected
	to the OpenID server. There you'll required the password.</li>
	
	<li>Finally, if the authentication runs fine, you'll return to the
	first application, now logged in.</li>
</ol>


{if $account && !$ADMIN}
<h3>Using Your Own OpenID URL</h3>

<p>Your OpenID is:</p>

<pre>
<a href="{$account_openid_url}">{$account_openid_url}</a>
</pre>

<p>You can use your home-page's URL as your OpenID. Just edit the
<code>&lt;HEAD&gt;</code> section of your URL's web page and add this
content:</p>

<pre>
&lt;link rel="openid.server" href="<a href="{$RAW_SERVER_URL}index.php/serve">"{$RAW_SERVER_URL}index.php/serve</a>"&gt;
&lt;link rel="openid.delegate" href="<a href="{$account_openid_url}">{$account_openid_url}</a>"&gt;
</pre>

<p>Then you can use your home-page's URL to authenticate to this server.</p>
{/if}
