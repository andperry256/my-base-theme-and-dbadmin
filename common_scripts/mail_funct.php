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

namespace MyBaseProject;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if (!is_dir($PHPMailerDir))
{
	exit("PHPMailer directory not defined");
}
else
{
	require_once("$PHPMailerDir/src/PHPMailer.php");
	require_once("$PHPMailerDir/src/SMTP.php");
	require_once("$PHPMailerDir/src/Exception.php");
}

//================================================================================
/*
Function get_imap_message (and sub-function get_message_part)

This function is for the processing of a message read from a mailbox via IMAP.

The function returns an array with the following elements:-
  header (structure as returned by imap_headerinfo)
	plain_content (as UTF-8)
	html_content (as UTF-8)
	charset

In order to handle attachments, the following global variables need to be
declared and used by the calling software:-
	$AttachmentsDir
	$TotalAttachmentSize
*/
//================================================================================

function get_imap_message($mbox,$mid,$noattach=false)
{
	global $TotalAttachmentSize;
	global $htmlmsg,$plainmsg,$charset,$attachments;
	$htmlmsg = $plainmsg = $charset = '';
	$attachments = array();
	$TotalAttachmentSize = 0;

	// Header
	$header = imap_headerinfo($mbox,$mid);

	// Body
	$struct = imap_fetchstructure($mbox,$mid);
	if ((!isset($struct->parts)) || (!$struct->parts))
	{
		// Not multipart
		get_message_part($mbox,$mid,$struct,0,$noattach);
	}
	else
	{
		// Multipart: iterate through each part
		foreach ($struct->parts as $partno0=>$part)
		{
			get_message_part($mbox,$mid,$part,$partno0+1,$noattach);
		}
	}

	$return_info = array();
	$return_info['header'] = $header;
	if (strtolower($charset) == 'iso-8859-1')
	{
		$plainmsg = utf8_encode($plainmsg);
		$htmlmsg = utf8_encode($htmlmsg);
	}
	$return_info['html_content'] = $htmlmsg;
	$return_info['plain_content'] = $plainmsg;
	$return_info['charset'] = $charset;
	return $return_info;
}

function get_message_part($mbox,$mid,$part,$partno,$noattach=false)
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
		$TotalAttachmentSize += filesize("$AttachmentsDir/$filename");
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
			get_message_part($mbox,$mid,$subpart,$partno.'.'.($partno0+1));
		}
	}
}

//==============================================================================
/*
Function output_mail

This function sends an email via SMTP.

The following parameters are provided:-

1. $mail_info - Array containing all the various data relating to the message.

  Mandatory fields:-
	Originator name.
	Originator address.
	Destination address.
	Subject.
	HTML and/or plain content.

  Optional fields:-
	Message ID - defaults to 0.
	Destination name - defaults to destination address.
	Reply address.

2  $host - Mail host domain to be used to look up the required mail route.

3. $attachments - Array containing path references to required attachment files.
   Optional parameter defaulting to an empty array.

Returns an array as follows:-

Offset 0 = Error code
   0 = Success
   1 = Unable to connect to database
   2 = Failed to send message
  11 = Originator name not specifed
  12 = Originator address not specified
  13 = Destination address not specified
  14 = Entry not found in mail route table
  15 = No subject
  16 = No content
  21 = Not used here but reserved for SMTP2GO event logged

Offset 1 = Additional error information
*/
//==============================================================================

function output_mail($mail_info,$host,$attachments=array())
{
	global $DefaultSenderEmail;
	global $AltSenderEmail;
	foreach ($mail_info as $key => $value)
	{
		$mail_info[$key] = trim($value);
	}

	// Check for mandatory data
	if ((!isset($mail_info['from_name'])) || (empty($mail_info['from_name'])))
	{
		// No originator name
		return array(11,'');
	}
	elseif ((!isset($mail_info['from_addr'])) || (empty($mail_info['from_addr'])))
	{
		// No originator address
		return array(12,'');
	}
	elseif ((!isset($mail_info['to_addr'])) || (empty($mail_info['to_addr'])))
	{
		// No destination address
		return array(13,'');
	}
	elseif ((!isset($mail_info['subject'])) || (empty($mail_info['subject'])))
	{
		// No subject
		return array(15,'');
	}
	elseif (((!isset($mail_info['html_content'])) || (empty($mail_info['html_content']))) &&
	        ((!isset($mail_info['plain_content'])) || (empty($mail_info['plain_content']))))
	{
		// No content
		return array(16,'');
	}

	// Process any default values
	if ((!isset($mail_info['to_name'])) || (empty($mail_info['to_name'])))
	{
		$mail_info['to_name'] = $mail_info['to_addr'];
	}
	if (!isset($mail_info['reply_addr']))
	{
		$mail_info['reply_addr'] = '';
	}
	if (!isset($mail_info['message_id']))
	{
		$mail_info['message_id'] = 0;
	}

	// Connect to database and request routing information
	if ($db = mail_db_connect())
	{
		$where_clause = 'orig_domain=?';
		$where_values = array('s',$host);
		if ($row = mysqli_fetch_assoc(mysqli_select_query($db,'mail_routes','*',$where_clause,$where_values,'')))
		{
			// Create PHPMailer object
			$mail = new PHPMailer();
			$mail->CharSet = 'UTF-8';

			// Process message content
			if ((isset($mail_info['html_content'])) && (!empty($mail_info['html_content'])))
			{
				// HTML content present
				$mail->IsHTML(true);
				$mail->Body = $mail_info['html_content'];
				if ((isset($mail_info['plain_content'])) && (!empty($mail_info['plain_content'])))
				{
					$mail->AltBody = $mail_info['plain_content'];
				}
				else
				{
					$mail->AltBody = 'This e-mail must be viewed in an HTML compatible application.';
				}
			}
			else
			{
				// No HTML content
				$mail->IsHTML(false);
				$mail->Body = $mail_info['plain_content'];
			}

			// Process any attachments
			foreach($attachments as $key => $value)
			{
				$mail->AddAttachment($key);
			}

			// Process remaining info
			$mail->Subject = $mail_info['subject'];
			if (!empty($mail_info['reply_addr']))
			{
				$mail->AddReplyTo($mail_info['reply_addr'],$mail_info['from_name']);
			}
			$mail->AddAddress($mail_info['to_addr'],$mail_info['to_name']);
			$mail->SetFrom($mail_info['from_addr'],$mail_info['from_name']);
			$mail->IsSMTP();
			$mail->SMTPDebug = false;
			$mail->SMTPAuth = true;
			$mail->Mailer = 'smtp';
			$mail->SMTPSecure = 'tls';
			$mail->Host = $row['mail_server'];
			$mail->Username = $row['username'];
			$mail->Password = $row['passwd'];
			$mail->Port = $row['port_no'];

			// Send message and process result
			$send_retval = $mail->Send();
			$error_info = '';
			if ($send_retval === false)
			{
				// Make a 2nd attempt to send
				$send_retval = $mail->Send();
				if ($send_retval !== false)
				{
					// Success
					log_message_info_to_file(0,$mail->Host,$mail_info,$error_info);
					return array(0,'');
				}

				// Send failure
				if (isset($mail->ErrorInfo))
				{
					$error_info = $mail->ErrorInfo;
				}
				else
				{
					$error_info = '';
				}
				log_message_info_to_file(2,$mail->Host,$mail_info,$error_info);
				return array(2,$error_info);
			}
			else
			{
				// Success
				log_message_info_to_file(0,$mail->Host,$mail_info,$error_info);
				return array(0,'');
			}
		}
		else
		{
			// Mail route not found
			log_message_info_to_file(14,'',$mail_info,'');
			return array(14,'');
		}
	}
	else
	{
		// Cannot connect to remote DB
		log_message_info_to_file(1,'',$mail_info,'');
		return array(1,'');
	}
}

//================================================================================
/*
Function log_message_info_to_file
*/
//================================================================================

function log_message_info_to_file($error_code,$host,$mail_info,$error_info)
{
	global $MailLogDir;

	if (!is_dir("$MailLogDir"))
		return;
	if (isset($mail_info['message_id']))
		$id = $mail_info['message_id'];
	else
		$id = 0;
	if (isset($mail_info['from_name']))
		$from_name = trim($mail_info['from_name']);
	else
		$from_name = '';
	if (isset($mail_info['from_addr']))
		$from_addr = trim($mail_info['from_addr']);
	else
		$from_addr = '';
	if (isset($mail_info['to_name']))
		$to_name = trim($mail_info['to_name']);
	else
		$to_name = '';
	if (isset($mail_info['to_addr']))
		$to_addr = trim($mail_info['to_addr']);
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
Function email_previous_day_log
*/
//================================================================================

require_once("$BaseDir/common_scripts/date_funct.php");
function email_previous_day_mail_log($station_id,$from_addr)
{
	global $MailLogDir;
	$yesterday_date = PreviousDate(TODAY_DATE);
	$log_file = "$MailLogDir/mail-"."$yesterday_date.log";
	if (is_file($log_file))
	{
		$message_info = array();
		$message_info['subject'] = "Mail Log File for $station_id on $yesterday_date";
		$message_info['plain_content'] = "See attachment.\n";
		$message_info['from_addr'] = $from_addr;
		$message_info['from_name'] = $station_id;
		$message_info['to_addr'] = 'domains@andperry.com';
		$message_info['to_name'] = '';
		$attachments = array();
		$attachments[$log_file] = true;
		$error_info = output_mail($message_info,$station_id,$attachments);
		return $error_info[0];
	}
	else
	{
		return -1;
	}
}

//================================================================================
?>
