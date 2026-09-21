<?php
//================================================================================

global $phpmailer;
if ( ! ( $phpmailer instanceof PHPMailer\PHPMailer\PHPMailer ) ) {
    require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
    require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
    require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
}

//================================================================================
