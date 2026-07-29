<?php
//==============================================================================

include(__DIR__.'/session_start.php');
if (is_file('/Config/linux_pathdefs.php')) {
    // Local server
    $elements = explode('/',ltrim($_SERVER['REQUEST_URI'],'/'));
    $local_site_dir = $elements[0];
}
require("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php");
require("$base_dir/wp-content/themes/my-base-theme/shared_functions.php");
require("$base_dir/common_scripts/totp_funct.php");
if (is_file("$private_scripts_dir/totp_secret_key.php")) {
    include("$private_scripts_dir/totp_secret_key.php");
}
$db = db_connect($auth_dbid);
$totp_enabled = mysqli_num_rows(mysqli_query($db,"SHOW FULL COLUMNS FROM $auth_db_table WHERE Field='is_totp_user'"));

if (isset($_POST['submitted'])) {
    require_once("$base_dir/mysql_connect.php");
    $username = $_POST['username'];
    $password = $_POST['password'];
    $auth_code = $_POST['auth_code'] ?? '';
    $user_authenticated = false;

    // Authenticate with username & password
    $where_clause = "$auth_db_username_field=?";
    $where_values = ['s',$username];
    if ((preg_match("/^[A-Z0-9.]*$/i", $username)) &&
        ($row = mysqli_fetch_assoc(mysqli_select_query($db,$auth_db_table,'*',$where_clause,$where_values,'')))) {
        if ((!empty($password)) && (crypt($password,$row['enc_passwd']) == $row['enc_passwd'])) {
            $user_authenticated = true;
        }
    }

    // Authenticate with OTP
    $where_clause = 'is_totp_user=1';
    if ((!$user_authenticated) && ($totp_enabled) && (preg_match("/^[0-9]{6}$/i", $auth_code)) &&
        (defined('TOTP_SECRET_KEY')) && (verify_totp_code(TOTP_SECRET_KEY,$auth_code)) &&
        ($row = mysqli_fetch_assoc(mysqli_select_query($db,$auth_db_table,'*',$where_clause,[],'')))) {
        $username = $row[$auth_db_username_field];
        $user_authenticated = true;
    }

    if ($user_authenticated) {
        if (isset($_COOKIE[LOGIN_COOKIE_ID])) {
            $where_clause = 'id=?';
            $where_values = ['s',$_COOKIE[LOGIN_COOKIE_ID]];
            if ($row = mysqli_fetch_assoc(mysqli_select_query($db,'login_sessions','*',$where_clause,$where_values,''))) {
                $where_clause = 'username=?';
                $where_values = ['s',$username];
                if ($row2 = mysqli_fetch_assoc(mysqli_select_query($db,'admin_passwords','*',$where_clause,$where_values,''))) {
                    $fields = 'username,access_level,remote_addr';
                    $values = ['s',$row2['username'],'s',$row2['access_level'],'s',$_SERVER['REMOTE_ADDR']];
                    $where_clause = 'id=?';
                    $where_values = ['s',$row['id']];
                    mysqli_update_query($db,'login_sessions',$fields,$values,$where_clause,$where_values);
                }
            }
        }
        put_user($username);
        header("Location: $base_url{$_POST['return_path']}");
        exit;
    }
    else {
        $error_message = "<p><b>Invalid login - please try again.</b></p>";
    }
}

//==============================================================================
?>
<html>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <style>
            html {
                color: #444;
                font-size: 115%;
                font-family: Verdana, Arial,'Noto Sans', Roboto, Helvetica, sans-serif;
            }
            table {
                border-collapse: collapse;
            }
            h1 {
                font-size: 1.5em;
            }
            td {
                border: solid 1px #ccc;
                padding: 0.5em;
            }
            input {
                padding: 0.5em;
            }
        </style>
    </head>
    <body>
        <?php print($error_message ?? ''); ?>
        <form method="post">
            <fieldset>
                <?php
                if (is_file("$base_dir/login_header.php")) {
                    include ("$base_dir/login_header.php");
                }
                if ($totp_enabled) {
                    print("<p>Please enter [username + password] OR authenticator code</p>\n");
                }
                ?>
                <table><tr>
                    <td>Username:</td>
                    <td><input type="text" size=24 name="username" value="<?php if (isset($_POST['username'])) echo $_POST['username']; ?>"></td>
                </tr><tr>
                    <td>Password:</td>
                    <td><input type="password" size=24 name="password" value="<?php if (isset($_POST['password'])) echo $_POST['password']; ?>"></td>
                </tr><tr>
                <?php if ($totp_enabled): ?>
                    <td>Auth Code:</td>
                    <td><input type="text" size=24 name="auth_code" value=""></td>
                </tr><tr>
                <?php endif; ?>
                    <td><input value="Submit" type="submit"></td>
                </tr></table>
                <input type="hidden" name="return_path" value="<?php echo $_GET['return_path']; ?>" />
                <input type="hidden" name="submitted" value="TRUE" />
            </fieldset>
        </form>
    </body>
<html>
