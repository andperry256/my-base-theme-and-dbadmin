<?php
//==============================================================================
if (!function_exists('db_connect'))
{
	require_once("$PrivateScriptsDir/mysql_connect.php");
}
$db = db_connect($AuthDBID);

// Determine whether access is internal to the local network
$remote_ip = $_SERVER['REMOTE_ADDR'];
if (($Location == 'local') && (isset($IP_Subnet_Addr)) && (substr($_SERVER['REMOTE_ADDR'],0,strlen($IP_Subnet_Addr)) == $IP_Subnet_Addr))
{
	$local_access = true;
}
elseif (($Location == 'local') && (isset($home_remote_ip_addr)) && ($_SERVER['REMOTE_ADDR'] == $home_remote_ip_addr))
{
	$local_access = true;
}
else
{
	$local_access = false;
}

if (($Location == 'local') && ($local_access) && (!session_var_is_set('user')))
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
	update_session_var('user',$DefaultLocalUser);
}

if ((session_var_is_set('user')) && (!empty(get_session_var('user'))))
{
	// User is logged on
	$UserAuthenticated = true;
}
else
{
	$UserAuthenticated = false;
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
		update_session_var('user',$username);
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
