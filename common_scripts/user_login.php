<?php
//==============================================================================

if (session_status() ==  PHP_SESSION_NONE) {
    session_start();
}
if (is_file('/Config/linux_pathdefs.php')) {
    // Local server
    $elements = explode('/',ltrim($_SERVER['REQUEST_URI'],'/'));
    $local_site_dir = $elements[0];
}
require("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php");
if (isset($_POST['submitted'])) {
    require_once("$base_dir/mysql_connect.php");
    $db = db_connect($auth_dbid);
    $username = $_POST['username'];
    $password = $_POST['password'];
    $user_authenticated = false;
    $where_clause = "$auth_db_username_field=?";
    $where_values = ['s',$username];
    if ((preg_match("/^[A-Z0-9.]*$/i", $username)) &&
        ($row = mysqli_fetch_assoc(mysqli_select_query($db,$auth_db_table,'*',$where_clause,$where_values,'')))) {
        if ((!empty($password)) && (crypt($password,$row['enc_passwd']) == $row['enc_passwd'])) {
            // User authenticated
            $_SESSION[SV_USER] = $username;
            if ((isset($row['access_level'])) && (defined('SV_ACCESS_LEVEL'))) {
                $_SESSION[SV_ACCESS_LEVEL] = $row['access_level'];
            }
            header("Location: $base_url{$_POST['return_path']}");
            exit;
        }
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
                ?>
                <table><tr>
                    <td>Username:</td>
                    <td><input type="text" size=24 name="username" value="<?php if (isset($_POST['username'])) echo $_POST['username']; ?>"></td>
                </tr><tr>
                    <td>Password:</td>
                    <td><input type="password" size=24 name="password" value="<?php if (isset($_POST['password'])) echo $_POST['password']; ?>"></td>
                </tr><tr>
                    <td><input value="Submit" type="submit"></td>
                </tr></table>
                <input type="hidden" name="return_path" value="<?php echo $_GET['return_path']; ?>" />
                <input type="hidden" name="submitted" value="TRUE" />
            </fieldset>
        </form>
    </body>
<html>
