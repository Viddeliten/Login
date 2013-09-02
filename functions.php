<?php

//testar

function login_display_login_form($login_headline, $login_message)
{
	if((!isset($_SESSION[login_PREFIX.'login_inloggad']) || $_SESSION[login_PREFIX.'login_inloggad']<1))
	{
		echo "<div class=\"login_form\">
		<form action=\"?page=login\" method=\"post\">
		<h3>$login_headline</h3>
		<p>$login_message</p>

		<p>Username: <input type=\"text\" name=\"namn\"></p>
		<p>Password: <input type=\"password\" name=\"password\"></p>
		<p><input type=\"submit\" name=\"login\" value=\"Log me in!\"></p>
		<p><a href=\"?page=forgot\">I forgot my password</a></p>
		<p><a href=\"?page=register\">Register</a></p>
		</form></div>
		";
	}
	else if(isset($_SESSION[login_PREFIX.'login_inloggad']) && $_SESSION[login_PREFIX.'login_inloggad']>0 && isset($_POST["login"]))
	{
		echo "<div id=\"login\"><p>Welcome ".$_SESSION[login_PREFIX.'login_username']."!</p></div>";
	}
}

function login_logout()
{
	session_unset();
	session_destroy();
	setcookie("login", "", time());
}

?>
