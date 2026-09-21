<?php
//================================================================================

namespace MyBaseProject;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
if (!is_dir($php_mailer_dir)) {
    exit("PHPMailer directory not defined\n");
}
else {
    require_once("$php_mailer_dir/src/PHPMailer.php");
    require_once("$php_mailer_dir/src/SMTP.php");
    require_once("$php_mailer_dir/src/Exception.php");
}

//================================================================================
