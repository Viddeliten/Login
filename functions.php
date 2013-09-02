<?php

/********************************/
/*	Handles requests from forms	*/
/********************************/
function login_receive()
{	
	$message=NULL;
	
	if(isset($_POST['login']))
		login_login();

	if(isset($_POST['register']))
	{
		login_display_register_form();
	}
	if(isset($_POST['userregister']))
	{
		$message=login_register_user();
		echo "mess: $message";
	}
		
	if(isset($_POST['forgot']))
		login_display_password_recover_form();
		
	if(isset($_POST['passwordreset']))
		login_reset_password();
		
	return $message;
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

		<p>Username: <input type=\"text\" name=\"username\"></p>
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
	if(isset($_POST['username']))
		echo "<input type=\"text\" name=\"username\" value=\"".$_POST['username']."\"></p>";
	else
		echo "<input type=\"text\" name=\"username\"></p>";
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
		<p>Reset your password? Enter your e-mail or your username.</p>
		<p>E-mail: <input type=\"text\" name=\"email\"></p>
		<p>Username:<input type=\"text\" name=\"username\"></p>
		<input type=\"submit\" name=\"passwordreset\" value=\"reset\">
	</form>";
}

function login_login()
{
	$_SESSION[login_PREFIX."inloggad"]=0;
	
	$sql="SELECT id,password,level FROM ".login_PREFIX."user WHERE username='".sql_safe($_POST['username'])."' AND blocked IS NULL;";
	// echo "<br />DEBUG1615: $sql";
	if($uu=@mysql_query($sql))
	{
		if($u=mysql_fetch_array($uu))
		{
			if(!strcmp($u['password'], crypt($_POST['password'], login_CONFUSER)))
			{
				$_SESSION[login_PREFIX."Username"]=$_POST['username'];
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
	
	if(isset($_POST['username']) && $_POST['username']!="")
	{
		if(isset($_POST['email']) && $_POST['email']!="")
		{
			//Kolla om nicket redan finns
			//Kolla om emailen redan finns
			$sql="SELECT username, email FROM ".login_PREFIX."user WHERE (username='".sql_safe($_POST['username'])."' OR email='".sql_safe($_POST['email'])."');";
			echo "<br />DEBUG1652: $sql";
			if($uu=@mysql_query($sql))
			{
				if(mysql_affected_rows()>0)
				{
					return "Username or email already registered.";
				}
				else
				{
					$pass=password_generate(8);
					$went_fine=mysql_query("INSERT INTO ".login_PREFIX."user SET username='".sql_safe($_POST['username'])."', email='".sql_safe($_POST['email'])."', password='".crypt($_POST['password'], login_CONFUSER)."';");
					if($went_fine)
					{
						//Skicka ett email
						$to = $_POST['email'];
						$subject = "Your new registration";
						$body="Thankyou for signing up!

					Your new password is: $pass

Hope to see you soon!
					
you recieve this email because your email was used to register at ".login_SITE_URL." If this was not done by you, simply ignore this message, and we apologize for the inconvenience.
";
						$headers = 'From: '.login_CONTACT_EMAIL . "\r\n" .
		'Reply-To: '.login_CONTACT_EMAIL. "\r\n" .
		'X-Mailer: PHP/' . phpversion();
						
						//Send mail
						if (mail($to, $subject, $body, $headers))
						{
							echo("<p>Message successfully sent!</p>");
							return "<h2>Congratulations!<h2><p>Your registration went fine. You will be notified by email at $_POST[email] soon. This email will contain your new password.</p>";
						}
						else
						{
							echo("<p>Message delivery failed.</p>");
						}
					}
				}
			}
		}
		else
			return "You need to enter an email adress!";		
	}
	else
		return "You need to have a username!";
}

function login_reset_password()
{
}

?>
