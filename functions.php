<?php

/********************************/
/*	Handles requests from forms	*/
/********************************/
function login_receive()
{	
	if(isset($_POST['login']))
		login_login();

	if(isset($_POST['register']))
	{
		login_display_register_form();
	}
	if(isset($_POST['userregister']))
		login_register_user();
		
	if(isset($_POST['forgot']))
		login_display_password_recover_form();
		
	if(isset($_POST['passwordreset']))
		login_reset_password();
}

function login_mysql_connect()
{
	$conn=@mysql_connect(login_DB_host, login_DB_user,login_DB_pass);
	if (!$conn)
	{
		echo "<p>Login: MySQL-server is not working</p>";
		return NULL;
	}

	$databas=@mysql_select_db(login_DB_name);
	if (!$databas)
	{
		echo "<p>Login: Database is not working</p>";
		return NULL;
	}
	return $conn;

}

function login_display_login_form($login_headline="Login in", $login_message="", $forgot_message="I forgot my password", $register_message="Register")
{
	if((!isset($_SESSION[login_PREFIX.'inloggad']) || $_SESSION[login_PREFIX.'inloggad']<1))
	{
		echo "<div class=\"login_form\">
		<form method=\"post\">
		<h3>$login_headline</h3>
		<p>$login_message</p>

		<p>Username: <input type=\"text\" name=\"namn\"></p>
		<p>Password: <input type=\"password\" name=\"password\"></p>
		<p><input type=\"submit\" name=\"login\" value=\"Log me in!\"></p>
		<p><input type=\"submit\" name=\"forgot\" value=\"$forgot_message\"></p>
		<p><input type=\"submit\" name=\"register\" value=\"$register_message\"></p>
		</form></div>
		";
	}
	else if(isset($_SESSION[login_PREFIX.'inloggad']) && $_SESSION[login_PREFIX.'inloggad']>0 && isset($_POST["login"]))
	{
		echo "<div id=\"login\"><p>Welcome ".$_SESSION[login_PREFIX.'Username']."!</p></div>";
	}
}

function login_display_register_form()
{
	echo "<h2>Register</h2>";
	echo "<form method=\"post\">
		<p>Username<br />";
	if(isset($_POST['nick']))
		echo "<input type=\"text\" name=\"nick\" value=\"".$_POST['nick']."\"></p>";
	else
		echo "<input type=\"text\" name=\"nick\"></p>";
	echo "
	<p>email<br />";
	if(isset($_POST['email']))
		echo "<input type=\"text\" name=\"email\" value=\"$_POST[email]\">";
	else
		echo "<input type=\"text\" name=\"email\">";
	echo "<span class=\"smalltext\">Needs to be a correct one as your password will be sent here!</span></p>
		<p><input type=\"submit\" name=\"userregister\" value=\"Sign up\"></p>
	</form>
	";
}

function login_display_password_recover_form()
{
		echo "
	<form method=\"post\">
		<p>Reset your password? Enter your e-mail or your nickname.</p>
		<p>E-mail: <input type=\"text\" name=\"email\"></p>
		<p>Nickname:<input type=\"text\" name=\"nickname\"></p>
		<input type=\"submit\" name=\"passwordreset\" value=\"reset\">
	</form>";
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
			if(!strcmp($u['password'], crypt($_POST['password'], login_CONFUSER)))
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
		<p><a href=\"?login=forgot\">I forgot my password</a></p>
		<p><a href=\"?login=register\">I want to become a member!</a></p>");
	}
}

function login_logout()
{
	session_unset();
	session_destroy();
	setcookie("login", "", time());
}

function login_register_user()
{
	if($_POST['nick']!="")
	{
		if(isset($_POST['email']) && $_POST['email']!="")
		{
			//Kolla om nicket redan finns
			//Kolla om emailen redan finns
			$sql="SELECT nick, email, blocked FROM ".$_SESSION["IS_prefix"]."user WHERE (nick='".sql_safe($_POST['nick'])."' OR email='".sql_safe($_POST['email'])."') AND blocked IS NULL;";
			//echo "<br />DEBUG: $sql";
			if($uu=@mysql_query($sql))
			{
				if(mysql_affected_rows()>0)
				{
					DEFINE('ERROR',"Nickname or email already registered.");
				}
				else
				{
					$pass=password_generate(8);
					$mess="Thankyou for signing up to Storybook.se!
					Your info:
					Username: $_POST[nick]
					Password: $pass

Hope to see you soon!
					
you recieve this email because your email was used to register at http://storybook.se If this was not done by you, simply ignore this message, and we apologize for the inconvenience.

Regards,
The Storybook Team";
					mail($_POST['email'], "[Storybook.se] - Your password", $mess);
					//echo "<p>$mess</p>";
					mysql_query("INSERT INTO ".$_SESSION["IS_prefix"]."user SET nick='".sql_safe($_POST['nick'])."', email='".sql_safe($_POST['email'])."', password='".md5($pass)."', member_since='".date("YmdHis")."';");
					DEFINE('REGISTERED',1);
					DEFINE('MESS',"<h2>Congratulations!<h2><p>Your registration went fine. You will be notified by email at $_POST[email] soon. This email will contain your user information.</p>");
				}
			}
		}
		else
			DEFINE('ERROR', "You need to enter an email adress!");		
	}
	else
		DEFINE('ERROR', "You need to have a nickname!");
}

function login_reset_password()
{
}

?>
