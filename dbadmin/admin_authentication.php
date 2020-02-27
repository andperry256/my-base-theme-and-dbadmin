<?php
//==============================================================================
if (!function_exists('db_connect'))
{
	require_once("$PrivateScriptsDir/mysql_connect.php");
}
$db = db_connect($AuthDBID);

define ('SESSION_TIMEOUT',3600);  // 1 hour
$time = time();

// Determine whether access is internal to the local network
$remote_ip = $_SERVER['REMOTE_ADDR'];
if ((isset($IP_Subnet_Addr)) && (substr($_SERVER['REMOTE_ADDR'],0,strlen($IP_Subnet_Addr)) == $IP_Subnet_Addr))
{
	$local_access = true;
}
elseif (($Location == 'local') && ($_SERVER['REMOTE_ADDR'] == $home_remote_ip_addr))
{
	$local_access = true;
}
else
{
	$local_access = false;
}

if (($Location == 'local') && ($local_access) && (!isset($_SESSION['user'])))
{
	/*
	Access is internal to the local network and there is no logged on user
	but with no active logout (i.e. where $_SESSION['user'] is set but empty).
	Automatically log on as the default user.
	*/
	if (!isset($DefaultLocalUser))
	{
		$DefaultLocalUser = 'local';
	}
	$_SESSION['user'] = $DefaultLocalUser;
	$_SESSION['access_time'] = $time;
}

if ((isset($_SESSION['user'])) && (!empty($_SESSION['user'])) && (($time - $_SESSION['access_time']) < SESSION_TIMEOUT))
{
	// User is logged on
	$UserAuthenticated = true;
	$_SESSION['access_time'] = $time;
}
elseif ((isset($_SESSION['user'])) && (empty($_SESSION['user'])))
{
	// Actively logged off from previous session
	$UserAuthenticated = false;
	if (($time - $_SESSION['access_time']) > SESSION_TIMEOUT)
	{
		// Session timeout has expired - remove empty username
		unset($_SESSION['user']);
	}
}
else
{
	$UserAuthenticated = false;
	unset($_SESSION['user']);
}

// Process result of login form
if ((!$UserAuthenticated) && (isset($_POST['submitted'])))
{
	// Authenticate using any site name and password from the site admin database
	$username = mysqli_real_escape_string($db,$_POST['username']);
	$password = mysqli_real_escape_string($db,$_POST['password']);
	$query_result = mysqli_query($db,"SELECT * FROM admin_passwords WHERE username='$username'");
	if ($row = mysqli_fetch_assoc($query_result))
	{
		if ((!empty($password)) && (crypt($password,$row['enc_passwd']) == $row['enc_passwd']))
		{
			$UserAuthenticated = true;
		}
	}
	if ($UserAuthenticated)
	{
		$_SESSION['user'] = $username;
		$_SESSION['access_time'] = $time;
	}
	else
	{
		print("<p><b>Invalid login - please try again.</b></p>");
	}
}

// Output login form is no user authenicated
if (!$UserAuthenticated):
?>
	<style>
		p,td {
			font-family: Verdana, Arial, Helvetica, sans-serif;
			font-size: 12pt;
		}
	</style>
	<form method="post">
		<fieldset>
			<br />
			<table width="500" cellpadding=5><tr>
				<td width="100" class="small"><span class="bold">Username:</span></td>
				<td><input type="text" size=40 name="username" value="<?php if (isset($_POST['username'])) echo $_POST['username']; ?>"></td>
			</tr><tr>
				<td class="small"><span class="bold">Password:</span></td>
				<td><input type="password" size=40 name="password" value="<?php if (isset($_POST['password'])) echo $_POST['password']; ?>"></td>
			</tr><tr>
				<td><input value="Submit" type="submit"></td>
			</tr></table>
			<input type="hidden" name="submitted" value="TRUE" />
		</fieldset>
	</form>
	</body>
	</html>
<?php
endif;

//==============================================================================
?>
