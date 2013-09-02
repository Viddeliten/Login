<?php

function login_mysql_connect()
{
	$conn=@mysql_connect(login_DB_host, login_DB_user,login_DB_pass);
	if (!$conn)
	{
		echo "<p>MySQL-server is not working</p>";
		return NULL;
	}

	$databas=@mysql_select_db(login_DB_name);
	if (!$databas)
	{
		echo "<p>Database is not working</p>";
		return NULL;
	}
	return $conn;

}

function login_display_login_form($login_headline, $login_message)
{
	if((!isset($_SESSION[login_PREFIX.'inloggad']) || $_SESSION[login_PREFIX.'inloggad']<1))
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
	else if(isset($_SESSION[login_PREFIX.'inloggad']) && $_SESSION[login_PREFIX.'inloggad']>0 && isset($_POST["login"]))
	{
		echo "<div id=\"login\"><p>Welcome ".$_SESSION[login_PREFIX.'login_username']."!</p></div>";
	}
}

function login_login()
{
	$_SESSION[login_PREFIX."inloggad"]=0;
	
	$sql="SELECT id,password,level FROM ".login_PREFIX."user WHERE username='".sql_safe($_POST['namn'])."' AND blocked IS NULL;";
	// echo "<br />DEBUG1615: $sql";
	if($uu=@mysql_query($sql))
	{
		if($u=mysql_fetch_array($uu))
		{
			if(!strcmp($u['password'],md5($_POST['password'])))
			{
				$_SESSION[login_PREFIX."Username"]=$_POST['namn'];
				$_SESSION[login_PREFIX."Userid"]=$u['id'];
				$_SESSION[login_PREFIX."HTTP_USER_AGENT"] = md5($_SERVER['HTTP_USER_AGENT']);
				$_SESSION[login_PREFIX.'password']=$_POST['password'];
				setcookie("login", md5($_SESSION[login_PREFIX."Username"]), time()+(60*15));

				$_SESSION[login_PREFIX."inloggad"]=$u['level'];
				
				design_get();
				
				//uppdatera login så att användaren blir aktiv
				user_update_login($u['id']);	
				
				define('MESS', "Welcome, ".$_SESSION[login_PREFIX."Username"]."!");
			}
			else
				$_SESSION[login_PREFIX."inloggad"]=-1;
		}
		else
			$_SESSION[login_PREFIX."inloggad"]=-2;
	}
	else
		$_SESSION[login_PREFIX."inloggad"]=-3;

	if($_SESSION[login_PREFIX."inloggad"]<1)
	{
		define('ERROR', "Log in failed (".$_SESSION[login_PREFIX."inloggad"]."). If you think this is in error, contact <a href=\"mailto:info@storybook.se\">admin</a>. You can try <a href=\"?\">logging in again.</a></p>
		<p><a href=\"?page=forgot\">I forgot my password</a></p>
		<p><a href=\"?page=register\">I want to become a member!</a></p>");
	}
}

function login_logout()
{
	session_unset();
	session_destroy();
	setcookie("login", "", time());
}

?>
