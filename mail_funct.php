<?php
//================================================================================
//
//    Mail Handling Functions.
//
//================================================================================
//
//    N.B. Certain functions in this module depend upon the following site
//    specific function being defined elsewhere (normally in mysql_connect.php):-
//
//	  mail_db_connect()
//
//	  returning a database link variable.
//
//================================================================================

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
global $PHPMailerMainVersion;

if (!is_dir($PHPMailerDir))
{
	die("PHPMailer directory not defined");
}
elseif (is_file("$PHPMailerDir/class.phpmailer.php"))
{
	$PHPMailerMainVersion = 5;
	require_once("$PHPMailerDir/class.phpmailer.php");
	require_once("$PHPMailerDir/class.smtp.php");
}
elseif (is_file("$PHPMailerDir/PHPMailer.php"))
{
	$PHPMailerMainVersion = 6;
	require_once("$PHPMailerDir/PHPMailer.php");
	require_once("$PHPMailerDir/SMTP.php");
	require_once("$PHPMailerDir/Exception.php");
}

//================================================================================

function getmsg($mbox,$mid,$noattach=false)
{
	// Global data to be populated with message information.
	// The main message may be plain text, HTML or both.
	global $header,$htmlmsg,$plainmsg,$charset,$attachments;
	$htmlmsg = $plainmsg = $charset = '';
	$attachments = array();

	// Header
	$header = imap_header($mbox,$mid);

	// Body
	$struct = imap_fetchstructure($mbox,$mid);
	if ((!isset($struct->parts)) || (!$struct->parts))
	{
		// Not multipart
		getpart($mbox,$mid,$struct,0,$noattach);
	}
	else
	{
		// Multipart: iterate through each part
		foreach ($struct->parts as $partno0=>$part)
		{
			getpart($mbox,$mid,$part,$partno0+1,$noattach);
		}
	}
}

//================================================================================

function getpart($mbox,$mid,$part,$partno,$noattach=false)
{
	global $AttachmentsDir;
	global $TotalAttachmentSize;
	global $htmlmsg,$plainmsg,$charset,$attachments;

	// Extract decode data
	if($partno)
	{
		$data = imap_fetchbody($mbox,$mid,$partno);  // Multipart
	}
	else
	{
		$data = imap_body($mbox,$mid);  // Not multipart
	}

	// Any part may be encoded, even plain text messages, so check everything.
	// No need to decode 7-bit, 8-bit, or binary.
	if ($part->encoding==4)
	{
		$data = quoted_printable_decode($data);
	}
	elseif ($part->encoding==3)
	{
		$data = base64_decode($data);
	}

	// Get all parameters, like charset, filenames of attachments, etc.
	$params = array();
	if ((isset($part->parameters)) && ($part->parameters))
	{
		foreach ($part->parameters as $x)
		{
			$params[ strtolower( $x->attribute ) ] = $x->value;
		}
	}
	if ((isset($part->dparameters)) && ($part->dparameters))
	{
		foreach ($part->dparameters as $x)
		{
			$params[ strtolower( $x->attribute ) ] = $x->value;
		}
	}

	// Check for attachment
	if ((!$noattach) &&
	    (((isset($params['filename'])) && ($params['filename'])) ||
	     ((isset($params['name'])) && ($params['name']))))
	{
		// Filename may be given as 'filename', 'name' or both.
		if ($params['filename'])
		{
			$filename = $params['filename'];
		}
		else
		{
			$filename = $params['name'];
		}

		// Deal with the possibility of two files having the same name
		$path_parts = pathinfo($filename);
		$ext = $path_parts['extension'];
		$filename_base = substr($filename,0,strlen($filename)-strlen($ext)-1);
		$fileno = 0;
		while (isset($attachments[$filename]))
		{
			$fileno++;
			$filename = $filename_base.'-'."$fileno.$ext";
		}

		// filename may be encoded, so see imap_mime_header_decode()
		set_time_limit(300);
		$attachments[$filename] = $data;
		$ofp = fopen("$AttachmentsDir/$filename","w");
		fwrite($ofp,$data);

		// Add missing file extensions to image files where possible
		if (empty($ext))
		{
			$image_type = exif_imagetype("$AttachmentsDir/$filename");
		 	if ($image_type == IMAGETYPE_JPEG)
			{
				rename("$AttachmentsDir/$filename","$AttachmentsDir/$filename.jpg");
			}
			elseif ($image_type == IMAGETYPE_PNG)
			{
				rename("$AttachmentsDir/$filename","$AttachmentsDir/$filename.png");
			}
			elseif ($image_type == IMAGETYPE_GIF)
			{
				rename("$AttachmentsDir/$filename","$AttachmentsDir/$filename.gif");
			}
			elseif ($image_type == IMAGETYPE_BMP)
			{
				rename("$AttachmentsDir/$filename","$AttachmentsDir/$filename.bmp");
			}
		}

		if (isset($TotalAttachmentSize))
		{
			$TotalAttachmentSize += filesize("$AttachmentsDir/$filename");
		}
		fclose($ofp);
	}

	// Check for text.
	elseif ($part->type==0 && $data)
	{
		// Messages may be split in different parts because of inline attachments,
		// so append parts together with blank row.
		if (strtolower($part->subtype)=='plain')
		{
			$plainmsg .= trim($data) ."\n\n";
		}
		else
		{
			$htmlmsg .= $data ."<br><br>";
		}
		$charset = $params['charset'];  // assume all parts are same charset
	}

	// Check for embedded message.
	elseif ($part->type==2 && $data)
	{
		// Append raw source to main message.
		$plainmsg .= trim($data) ."\n\n";
	}

	// Subpart recursion
	if ((isset($part->parts)) && ($part->parts))
	{
		foreach ($part->parts as $partno0=>$subpart)
		{
			getpart($mbox,$mid,$subpart,$partno.'.'.($partno0+1));
		}
	}
}

//================================================================================

function date_and_time_now()
{
	return date("Y-m-d H:i:s");
}

//================================================================================
/*
Function log_message_details_to_file
*/
//================================================================================

function log_message_details_to_file($error_code,$host,$details,$error_info)
{
	global $MailLogDir;

	if (!is_dir("$MailLogDir"))
		return;
	if (isset($details['message_id']))
		$id = $details['message_id'];
	else
		$id = 0;
	if (isset($details['from_name']))
		$from_name = trim($details['from_name']);
	else
		$from_name = '';
	if (isset($details['from_addr']))
		$from_addr = trim($details['from_addr']);
	else
		$from_addr = '';
	if (isset($details['to_name']))
		$to_name = trim($details['to_name']);
	else
		$to_name = '';
	if (isset($details['to_addr']))
		$to_addr = trim($details['to_addr']);
	else
		$to_addr = '';
	$log_file_name = 'mail-'.date('Y-m-d').'.log';
	$ofp = fopen("$MailLogDir/$log_file_name",'a');
	if ($ofp !== false)
	{
		$line = date('H:i:s');
		$line .= " EC=$error_code ID=$id";
		$line .= "  MH=$host";
		$line .= "  FN=$from_name";
		$line .= "  FA=$from_addr";
		$line .= "  TN=$to_name";
		$line .= "  TA=$to_addr";
		$line = str_replace("\n",'',$line);
		$line = str_replace("\r",'',$line);
		$line .= "\n";
		fprintf($ofp,$line);
		if (!empty($error_info))
		{
			$line = "   Error Info: $error_info\n";
			fprintf($ofp,$line);
		}
		fclose($ofp);
	}
}

//================================================================================
/*
Function deliver_mail

Parameters:-
$mail    = PHPMailer object
$details = Array containing originator and destination details
$host    = Mail host domain to be used to look up the required mail route

Return values:-
 0 = Success
 1 = Unable to connect to database
 2 = Failed to send message
11 = Message ID not specified
12 = Originator address not specified
13 = Destination address not specified
14 = Entry not found in mail route table
21 = Not used here but reserved for SMTP2GO event logged
*/
//================================================================================

function deliver_mail($mail,$details,$host)
{
	global $DefaultSenderEmail;
	global $AltSenderEmail;
	global $PHPMailerMainVersion;
	$dummy_details = array();

	if (!isset($details['from_addr']))
	{
		// No originator address
		log_message_details_to_file(12,'',$details,'');
		return 12;
	}
	elseif (!isset($details['to_addr']))
	{
		// No addressee
		log_message_details_to_file(13,'',$details,'');
		return 13;
	}

	if (!isset($details['from_name']))
	{
		$details['from_name'] = $details['from_addr'];
	}
	if (!isset($details['to_name']))
	{
		$details['to_name'] = $details['to_addr'];
	}
	if (!isset($details['reply_addr']))
	{
		$details['reply_addr'] = '';
	}
	$from_addr = trim($details['from_addr']);
	if ((isset($DefaultSenderEmail)) && (isset($AltSenderEmail)) && ($from_addr == $DefaultSenderEmail))
	{
		$from_addr = $AltSenderEmail;
	}
	$from_name = trim($details['from_name']);
	$tok = strtok($from_addr,'@');
	$from_domain = strtok('@');
	$to_addr = trim($details['to_addr']);
	$to_name = trim($details['to_name']);
	$reply_addr = trim($details['reply_addr']);

	if ($db = mail_db_connect())
	{
		$query_result = mysqli_query($db,"SELECT * FROM mail_routes WHERE orig_domain='$host'");
		if ($row = mysqli_fetch_assoc($query_result))
		{
			$mail2 = clone $mail;
			$mail2->ClearAllRecipients();
			if (!empty($reply_addr))
			{
				$mail2->AddReplyTo($reply_addr,$from_name);
			}
			if ($to_addr != '*')
			{
				$mail2->AddAddress($to_addr,$to_name);
			}
			$mail2->SetFrom($from_addr,$from_name);
			$mail2->IsSMTP();
			$mail2->SMTPDebug = false;
			$mail2->SMTPAuth = true;
			$mail2->Mailer = 'smtp';
			$mail2->SMTPSecure = 'tls';
			$mail2->Host = $row['mail_server'];
			$mail2->Username = $row['username'];
			$mail2->Password = $row['passwd'];
			$mail2->Port = $row['port_no'];

			$send_retval = $mail2->Send();

			$error_info = '';
			if ($send_retval === false)
			{
				// Make a 2nd attempt to send
				$send_retval = $mail2->Send();
				if ($send_retval !== false)
				{
					// Success
					log_message_details_to_file(0,$mail2->Host,$details,$error_info);
					return 0;
				}

				// Send failure
				if (isset($mail2->ErrorInfo))
				{
					$error_info = $mail2->ErrorInfo;
				}
				else
				{
					$error_info = '';
				}
				log_message_details_to_file(2,$mail2->Host,$details,$error_info);
				return 2;
			}
			else
			{
				// Success
				log_message_details_to_file(0,$mail2->Host,$details,$error_info);
				return 0;
			}
		}
		else
		{
			// Mail route not found
			log_message_details_to_file(14,'',$details,'');
			return 14;
		}
	}
	else
	{
		// Cannot connect to remote DB
		log_message_details_to_file(1,'',$details,'');
		return 1;
	}
}

//================================================================================
/*
Function email_previous_day_log
*/
//================================================================================

require_once("$BaseDir/_link_to_common/date_funct.php");
function email_previous_day_mail_log($station_id,$from_addr)
{
	global $MailLogDir;
	$yesterday_date = PreviousDate(TODAY_DATE);
	$log_file = "$MailLogDir/mail-"."$yesterday_date.log";
	if (is_file($log_file))
	{
		$mail = new PHPMailer;
		$mail->Subject = "Mail Log File for $station_id on $yesterday_date";
		$message_text = "See attachment.\n";
		$mail->IsHTML(false);
		$mail->Body = $message_text;
		$mail->AddAttachment($log_file);
		$message_details = array();
		$message_details['message_id'] = 0;
		$message_details['from_addr'] = $from_addr;
		$message_details['from_name'] = $station_id;
		$message_details['to_addr'] = 'domains@andperry.com';
		$message_details['to_name'] = '';
		$error_code = deliver_mail($mail,$message_details,$station_id);
		unset($mail);
		return $error_code;
	}
	return -1;
}

//==============================================================================
/*
Function handle_replaceable_utf8_characters

This function is used to process UTF8 characters that can be replaced with a
simplified alternative, thus avoiding potential problems that may occur with
character set conversion.

N.B. This function should be viewed as a workaround and not as a permanent
substitute for the correct handling of character sets.
*/
//==============================================================================

function handle_replaceable_utf8_characters($text)
{
	$text = str_replace(
		array("\xe2\x80\x98",  // Left single smart quote
		      "\xe2\x80\x99",  // Right single smart quote
					"\xe2\x80\x9c",  // Left double smart quote
					"\xe2\x80\x9d",  // Right double smart quote
					"\xe2\x80\x93",  // EN-dash
					"\xe2\x80\x94",  // EM-dash
					"\xe2\x80\xa6",  // horizontal elipsis
					"\xe2\x82\xac",  // Euro sign
					"\xc2\xa0",      // non-breaking space
					"\xc2\xa9",      // copyright symbol
				),
		array("'",
		      "'",
					'"',
					'"',
					'-',
					'--',
					'...',
					'&euro;',
					'&nbsp;',
					'&copy;',
				 ),
		$text
	);
	return $text;
}

//==============================================================================
/*
Function convert_html_codes_to_plain_text

This function is used to convert any HTML codes returned from calling
handle_replaceable_utf8_characters into plain text equivalents.
*/
//================================================================================

function convert_html_codes_to_plain_text($text)
{
	$text = str_replace(
		array('&euro;','&nbsp;','&copy;'),
		array('EUR',' ','(c)'),
		$text
	);
	return $text;
}

//================================================================================
?>
