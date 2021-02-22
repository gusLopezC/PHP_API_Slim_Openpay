<?php

// Import PHPMailer classes into the global namespace
// These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer
{

	/**
	 * This method sends a email
	 *
	 * @param	string	$fro	 	Email address origin 
	 * @param	string	$to			Email addess destination
	 * @param	string	$name		Name of addressee
	 * @param	string	$subject	Topic of message
	 * @param	string	$html		Message HTML
	 * @param	string	$text		Message simple
	 *
	 * @return	boolean
	 */
	public static function send($to, $name, $subject)
	{
		$mail = new PHPMailer(true);						// Passing `true` enables exceptions
			
		//Server settings
		$mail->SMTPDebug	=	0;							// Enable verbose debug output
		$mail->isSMTP();									// Set mailer to use SMTP
		$mail->Host			=	MAIL_HOST;					// Specify main and backup SMTP servers
		$mail->Username		=	MAIL_USER;					// SMTP username
		$mail->Password		=	MAIL_PASS;					// SMTP password
		$mail->SMTPAuth		=	true;						// Enable SMTP authentication
		$mail->SMTPSecure	=	'tls';						// Enable TLS encryption, `ssl` also accepted
		$mail->Port			=	587;						// TCP port to connect to

		//Recipients
		$mail->AddReplyTo(MAIL_USER, MAIL_NAME);			// Add a "Reply-To" address (Optional)
		$mail->SetFrom(MAIL_USER, MAIL_NAME);
		$mail->AddAddress($to, $name);						// Add a recipient
		$mail->addBCC(MAIL_USER);							// Add a "BCC" address (Optional)


		$message  = "<html><body>";
   
$message .= "<table width='100%' bgcolor='#e0e0e0' cellpadding='0' cellspacing='0' border='0'>";
   
$message .= "<tr><td>";
   
$message .= "<table align='center' width='100%' border='0' cellpadding='0' cellspacing='0' style='max-width:650px; background-color:#fff; font-family:Verdana, Geneva, sans-serif;'>";
    
$message .= "<thead>
  <tr height='80'>
  <th colspan='4' style='background-color:#f5f5f5; border-bottom:solid 1px #bdbdbd; font-family:Verdana, Geneva, sans-serif; color:#333; font-size:34px;' >Programacion.net</th>
  </tr>
             </thead>";
    
$message .= "<tbody>
             <tr align='center' height='50' style='font-family:Verdana, Geneva, sans-serif;'>
       <td style='background-color:#00a2d1; text-align:center;'><a href='http://www.programacion.net/articulos/c' style='color:#fff; text-decoration:none;'>C</a></td>
       <td style='background-color:#00a2d1; text-align:center;'><a href='http://www.programacion.net/articulos/php' style='color:#fff; text-decoration:none;'>PHP</a></td>
       <td style='background-color:#00a2d1; text-align:center;'><a href='http://www.programacion.net/articulos/asp' style='color:#fff; text-decoration:none;' >ASP</a></td>
       <td style='background-color:#00a2d1; text-align:center;'><a href='http://www.programacion.net/articulos/java' style='color:#fff; text-decoration:none;' >Java</a></td>
       </tr>
      
       <tr>
       <td colspan='4' style='padding:15px;'>
       <p style='font-size:20px;'>Hi' ".$to.",</p>
       <hr />
       <p style='font-size:25px;'>Sending HTML eMaile using PHPMailer</p>
       <img src='https://4.bp.blogspot.com/-rt_1MYMOzTs/VrXIUlYgaqI/AAAAAAAAAaI/c0zaPtl060I/s1600/Image-Upload-Insert-Update-Delete-PHP-MySQL.png' alt='Sending HTML eMail using PHPMailer in PHP' title='Sending HTML eMail using PHPMailer in PHP' style='height:auto; width:100%; max-width:100%;' />
       <p style='font-size:15px; font-family:Verdana, Geneva, sans-serif;'>".$to.".</p>
       </td>
       </tr>
      
              </tbody>";
    
$message .= "</table>";
   
$message .= "</td></tr>";
$message .= "</table>";
   
$message .= "</body></html>";
		//Content
		$mail->isHTML(true);								// Set email format to HTML
		$mail->Subject		=	$subject;
		$mail->Body			=	$message;
		$mail->AltBody		=	$subject;
		$mail->CharSet		=	'UTF-8';

		if (filter_var($to, FILTER_VALIDATE_EMAIL) !== false) {
			$result = $mail->send();
		} else {
			return false;
		}

		if ($result) {
			return true;
		} else {
			return false;
		}
	}

}

?>