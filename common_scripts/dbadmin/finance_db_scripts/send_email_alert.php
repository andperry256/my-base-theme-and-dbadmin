<?php
  namespace MyBaseProject;
  use PHPMailer\PHPMailer\PHPMailer;
  $local_site_dir = $_GET['site'];
  require("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php");
  require("$BaseDir/common_scripts/mail_funct.php");
  require("$PrivateScriptsDir/mysql_connect.php");
  $db = finance_db_connect();
  $query_result = mysqli_query($db,"SELECT * FROM email_alerts WHERE rec_id='{$_GET['recid']}'");
  if ($row = mysqli_fetch_assoc($query_result))
  {
    $message_info = array();
    $message_info['subject'] = $row['subject'];
    $message_info['plain_content'] = $row['content'];
    $message_info['plain_content'] = str_replace('{date}',title_date($_GET['dt'],0),$message_info['plain_content']);
    $message_info['plain_content'] = str_replace('{amount}',sprintf("%01.2f",$_GET['amt']),$message_info['plain_content']);
    $message_info['html_content'] = str_replace("\n","<br />",$message_info['plain_content']);
    $message_info['html_content'] = str_replace("[","<b>",$message_info['html_content']);
    $message_info['html_content'] = str_replace("]","</b>",$message_info['html_content']);
    $message_info['from_addr'] = $row['from_address'];
    $message_info['from_name'] = $row['from_name'];
    $message_info['to_addr'] = $row['to_address'];
    output_mail($message_info,$MailHost);
  }
?>