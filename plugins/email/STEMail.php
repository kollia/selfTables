<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

class STEMail
{
    /**
     * use exception handling
     * @var bool
     */
    private $exceptions= false;
    /**
     * whether STEmail was initialed correctly
     * @var bool
     */
    private $initialed= false;
    /**
     * sources of PHPMailer
     * object when available
     * @var PHPMailer
     */
    private $phpmailer= null;
    /**
     * email address from which sending
     * @var string
     */
    private $fromSending= "";
    /**
     * error string from email process
     * @var string
     */
    private $sErrorStr= "";

    public function __construct(bool $exceptions= false)
    {
        $this->exceptions= $exceptions;
    }
    /**
     * check whether PHPMailer config file exist,
     * otherwise mailing over standard php mail() function
     * 
     * @param string $from address of email from which the email was sending
     * @param string $lang language of two characters for error messages
     * @return bool whether email was correct configured
     */
    public function init(string $from, string $lang= "en") : bool
    {
        STCheck::param($from, 0, "string");

        $this->fromSending= $from;
        if( !$this->initialPHPMailer(__DIR__."/st_email.inc.json", $lang) &&
            $this->getErrorString() !== ""                                      )
        {
            return false;
        }
        $this->initialed= true;// if PHPMailer cannot configured mail over standard php function
        return true;
    }
    /**
     * configure PHPMailer
     * 
     * @param string $json path to json config file
     * @param string $lang language of two characters for error messages
     * @return bool whether PHPMailer was correct configured
     */
    protected function initialPHPMailer(string $json, string $lang) : bool
    {
        $content= @file_get_contents($json);
        if($content == false)
        {// no json file available, mail over standard php mailer
            return false;
        }
        $config_data= json_decode($content, true);

        $mailer= new PHPMailer(/*exceptions*/true);// intern exception handling should be always true
        try{
            if(STCheck::isDebug("email"))
                $mailer->SMTPDebug= $config_data['PHPMailer']['SMTPDebug'];
            elseif(STCheck::isDebug())
                $mailer->SMTPDebug= 1;
            else
                $mailer->SMTPDebug= 0;
            $mailer->CharSet= $config_data['PHPMailer']['CharSet'];
            $mailer->isSMTP();
            $mailer->Host= $config_data['PHPMailer']['Host'];
            $mailer->Username= $config_data['PHPMailer']['Username'];
            $mailer->Password= $config_data['PHPMailer']['Password'];
            $mailer->SMTPAuth= $config_data['PHPMailer']['SMTPAuth'];
            $mailer->SMTPSecure= $config_data['PHPMailer']['SMTPSecure'];
            $mailer->Port= $config_data['PHPMailer']['SMTPPort'];
            $mailer->setFrom($this->fromSending);
            $mailer->addReplyTo($this->fromSending);
            $mailer->setLanguage($lang, 'PHPMailer/language/');
            $this->phpmailer= &$mailer;

        }catch(Exception $ex)
        {
            $msg= $ex->getMessage();
            if(!$msg)
                $msg= "incorrect PHPMailer config file '$json'";
            else
                $msg=  "incorrect PHPMailer initial: ".$msg;
            $this->sErrorStr= $msg;
            return false;
        }
        return true;
    }
    /**
     * returning error string getting from email process
     * 
     * @return string error string
     */
    public function getErrorString() : string
    {
        return $this->sErrorStr;
    }
    /**
     * sending email over SMTP-port when PHPMailer was configured
     * or sending by standard PHP mail routine
     * 
     * @param string|array $to string or array of email addresses
     * @param string $subject subject of email
     * @param string $ASCIIbody email body as clear text for non-html clients
     * @param string $HTMLbody email body as html
     * @param array $Cc array of second Cc email addresses
     * @param array $Bcc array of blind-copy email addresses
     * @return bool whether sending was correct
     */
    function sendmail(string|array $to, string $subject, string $ASCIIbody, string $HTMLbody= null, array $Cc= null, array $Bcc= null) : bool
    {
        if(!$this->initialed)
        {
            $this->sErrorStr= "before sending email initial first";
            if($this->exceptions)
                throw(new Exception($this->sErrorStr));
            return false;
        }
        if(isset($this->phpmailer))
        { // mailing over PHPMailer
            try
            {
                if(isset($Cc))
                {
                    foreach($Cc as $email)
                        $this->phpmailer->addCC($email);
                }
                if(isset($Bcc))
                {
                    foreach($Bcc as $email)
                        $this->phpmailer->addBCC($email);
                }
            
                //Attachments
                //$this->phpmailer->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
                //$this->phpmailer->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
            

                //Content
                if(is_array($to))
                {
                    foreach($to as $email)
                        $this->phpmailer->addAddress($email);
                }else
                    $this->phpmailer->addAddress($to);
                $this->phpmailer->Subject = $subject;
                if(isset($HTMLbody))
                {
                    $this->phpmailer->isHTML(true); 
                    $this->phpmailer->Body    = $HTMLbody;//'This is the HTML message body <b>in bold!</b>';
                    $this->phpmailer->AltBody = $ASCIIbody;//'This is the body in plain text for non-HTML mail clients';
                }else
                {
                    $this->phpmailer->isHTML(false);
                    $this->phpmailer->Body= $ASCIIbody;
                }
        
                $this->phpmailer->send();
                
            } catch (Exception $e)
            {               
                $this->sErrorStr= $this->phpmailer->ErrorInfo;
                return false;
            }
        }else // PHPMailer not configured
        {// mail over standard php mailer 
            $toAdr= array();
            if(is_array($to))
            {
                $tostr= "";
                foreach($to as $email)
                    $tostr.= "$email,";
                $tostr= substr($tostr, 0, strlen($tostr)-1);
                $toAdr['To']= $tostr;
            }else
                $toAdr['To']= $to;
            if(isset($Cc))
            {
                foreach($Cc as $email)
                    $ccstr.= "$email,";
                $ccstr= substr($ccstr, 0, strlen($ccstr)-1);
                $toAdr['Cc']= $ccstr;
            }
            if(isset($Bcc))
            {
                foreach($Bcc as $email)
                    $bccstr.= "$email,";
                $bccstr= substr($bccstr, 0, strlen($bccstr)-1);
                $toAdr['Bcc']= $bccstr;
            }
            
            $body['txt']= $ASCIIbody;
            $body['html']= $HTMLbody;
            if(!$this->send_email($toAdr, $this->fromSending, $subject, $body))
            {
                $this->sErrorStr= "ERROR: standard PHP email client doesn't send correct email";
                return false;
            }
        }
        return true;
    }
    /**
     * Send standard PHP email
     * 
     * @param string|array $to sending email to address(es)
     * @param string|array $from sending email from address with key from and reply
     * @param string $subject subject of email
     * @param array $message array of messages with key txt and or html
     * @return bool boolean whether sending was OK
     */
    protected function send_email($to, $from, $subject, $message) : bool
    {
        // Unique boundary
        $boundary = md5( uniqid('', true) . microtime() );

        // Add From: header
        if(is_array($from))
            $fromAdr= $from['from'];
        else
            $fromAdr= $from;
        $headers['From']= $fromAdr;
        //$headers = "From: {$fromAdr}\r\n";
        if(is_array($from) && isset($from['reply']))
            $reply= $from['reply'];
        else
            $reply= $fromAdr;
        $headers['Reply-To']= $reply;
        //$headers.= "Reply-To: {$reply}\r\n";

        // Specify MIME version 1.0
        //$headers['MIME-Version']= "1.0";

        $headers['X-Mailer']= "PHP/".phpversion();

        // Tell e-mail client this e-mail contains alternate versions
        $headers['Content-Type']= "multipart/alternative; boundary=\"$boundary\""; 


        // Plain text version of message
        $body = "--$boundary\r\n" .
        "Content-Type: text/plain; charset=UTF-8\r\n" .
        "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split( base64_encode( strip_tags($message['txt']) ) );

        // HTML version of message
        $body .= "--$boundary\r\n" .
        "Content-Type: text/html; charset=UTF-8\r\n" .
        "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split( base64_encode( $message['html'] ) );

        $body .= "--$boundary--";

        // Send Email
        if(is_array($to))
        {
            if (is_array($to['To']))
            {

                foreach ($to['To'] as $e)
                {
                    $bOK= mail($e, $subject, $body, $headers);
                    if(!$bOK)
                        return false;
                }
                return true;
            }
            $to= $to['To'];
        }
        return mail($to, $subject, $body, $headers);
    }
    /**
     * validate correctness of email address in writing
     * and also whether domain exist
     * 
     * @param string $email address of email
     * @return bool whether validation was correct
     */
    public function validateEmail($email) : bool
    {
        $isValid = true;
        $atIndex = strrpos($email, "@");
        if (is_bool($atIndex) && !$atIndex) {
            $isValid = false;
        } else {
            $domain = substr($email, $atIndex + 1);
            $local = substr($email, 0, $atIndex);
            $localLen = strlen($local);
            $domainLen = strlen($domain);
            if ($localLen < 1 || $localLen > 64) {
                // local part length exceeded
                $isValid = false;
            } else if ($domainLen < 1 || $domainLen > 255) {
                // domain part length exceeded
                $isValid = false;
            } else if ($local[0] == '.' || $local[$localLen - 1] == '.') {
                // local part starts or ends with '.'
                $isValid = false;
            } else if (preg_match('/\\.\\./', $local)) {
                // local part has two consecutive dots
                $isValid = false;
            } else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) {
                // character not valid in domain part
                $isValid = false;
            } else if (preg_match('/\\.\\./', $domain)) {
                // domain part has two consecutive dots
                $isValid = false;
            } else if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', str_replace("\\\\", "", $local))) {
                // character not valid in local part unless
                // local part is quoted
                if (!preg_match('/^"(\\\\"|[^"])+"$/', str_replace("\\\\", "", $local))) {
                    $isValid = false;
                }
            }
            
            if(function_exists('checkdnsrr')){
                if ($isValid && !(checkdnsrr($domain, "MX") || checkdnsrr($domain, "A"))) {
                    // domain not found in DNS
                    $isValid = false;
                }
            }
    
        }
        return $isValid;
    }
    
}

?>