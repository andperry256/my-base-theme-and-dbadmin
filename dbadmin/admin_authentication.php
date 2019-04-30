<?php
//==============================================================================
if (!function_exists('db_connect'))
{
	require_once("$PrivateScriptsDir/mysql_connect.php");
}
//==============================================================================

$db = db_connect($AuthDBID);
$session_id = session_id();
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

/*
If access is internal to the local network and no login record exists
for the current session then create a record with the username set to
default.
*/
if (($Location == 'local') && ($local_access))
{
	$query_result = mysqli_query($db,"SELECT * FROM login_sessions WHERE session_id='$session_id'");
	if (mysqli_num_rows($query_result) == 0)
	{
		if (!isset($DefaultLocalUser))
		{
			// Logged in by default
			$DefaultLocalUser = 'local';
		}
		mysqli_query($db,"INSERT into login_sessions (session_id,username,access_time) VALUES ('$session_id','$DefaultLocalUser',$time)");
	}
}

$query_result = mysqli_query($db,"SELECT * FROM login_sessions WHERE session_id='$session_id'");
if ($row = mysqli_fetch_assoc($query_result))
{
	if (empty($row['username']))
	{
		// Session record with an empty username indicates no user logged on.
		$UserAuthenticated = false;
	}
	else
	{
		// There is a logged on user.
		mysqli_query($db,"UPDATE login_sessions SET access_time=$time WHERE session_id='$session_id'");
		$UserAuthenticated = true;
	}
}
else
{
	// No record found for current session.
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
			$authenticated = true;
	}
	if ($authenticated)
	{
		$session_id = session_id();
		$time = time();
		$query_result = mysqli_query($db,"SELECT * FROM login_sessions WHERE session_id='$session_id'");
		if ($row = mysqli_fetch_assoc($query_result))
		{
			// Session record already exists.
			if (empty($row['username']))
			{
				mysqli_query($db,"UPDATE login_sessions SET username='$username',access_time=$time WHERE session_id='$session_id'");
			}
		}
		else
		{
			// Create session record
			mysqli_query($db,"INSERT into login_sessions (session_id,username,access_time) VALUES ('$session_id','$username',$time)");
		}
		$UserAuthenticated = true;
	}
	else
		print("<p><b>Invalid login - please try again.</b></p>");
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
