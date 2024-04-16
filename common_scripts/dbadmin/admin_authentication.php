<?php
//==============================================================================

if (!function_exists('db_connect'))
{
    require_once("$private_scripts_dir/mysql_connect.php");
}
require_once("$db_admin_dir/common_funct.php");
$db = db_connect($auth_dbid);

// Determine whether access is internal to the local network
$remote_ip = $_SERVER['REMOTE_ADDR'];
if (($location == 'local') && (isset($ip_subnet_addr)) && (substr($_SERVER['REMOTE_ADDR'],0,strlen($ip_subnet_addr)) == $ip_subnet_addr))
{
    $local_access = true;
}
elseif (($location == 'local') && (isset($home_remote_ip_addr)) && ($_SERVER['REMOTE_ADDR'] == $home_remote_ip_addr))
{
    $local_access = true;
}
else
{
    $local_access = false;
}

if (($location == 'local') && ($local_access) && (!session_var_is_set(SV_USER)))
{
    /*
    Access is internal to the local network and there is no logged on user
    but with no active logout (i.e. where $_SESSION[SV_USER] is set but empty).
    Automatically log on as the default user.
    */
    if (!isset($default_local_user))
    {
        $default_local_user = 'local';
    }
    update_session_var(SV_USER,$default_local_user);
    if (defined('SV_ACCESS_LEVEL'))
    {
        update_session_var(SV_ACCESS_LEVEL,9);
    }
}

if ((session_var_is_set(SV_USER)) && (!empty(get_session_var(SV_USER))))
{
    // User is logged on
    $user_authenticated = true;
}
else
{
    $user_authenticated = false;
}

// Process result of login form
if (isset($_GET['noauth']))
{
    print("<p><b>Invalid login - please try again.</b></p>");
}

// Output login form is no user authenicated
if (!$user_authenticated):
?>
  <style>
    p,td {
      font-family: Verdana, Arial, Helvetica, Roboto, sans-serif;
      font-size: 12pt;
    }
  </style>
  <form method="post" action="<?php echo "$db_admin_url/login_action.php?site=$local_site_dir&returnurl=".cur_url_par(); ?>">
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
