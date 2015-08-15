<?php

/**
 * @file
 *
 * see @https://github.com/PHPMailer/PHPMailer
 */

namespace Pyramid\Vendor\Mailer;

require_once __DIR__ . '/class.phpmailer.php';
require_once __DIR__ . '/class.pop3.php';
require_once __DIR__ . '/class.smtp.php';

class Mailer {

    /**
     * @ $mail
     */
    public $mail;

    /**
     * 析构函数
     * $sendmail = new Pyramid\Vendor\Mailer\Mailer(array());
     */
    public function __construct($configs = array()) {
        $this->mail = new \PHPMailer(true);
        $this->mail->isSMTP();                                                // Set mailer to use SMTP
        $this->mail->Host = $configs['host'];                                 // Specify main and backup SMTP servers
        $this->mail->SMTPAuth = true;                                         // Enable SMTP authentication
        $this->mail->Username = $configs['username'];                         // SMTP username
        $this->mail->Password = $configs['password'];                         // SMTP password
        $this->mail->SMTPSecure = isset($configs['smtpsecure'])?$configs['smtpsecure']:'tls'; // Enable TLS encryption, `ssl` also accepted
        $this->mail->Port = isset($configs['port'])? $configs['port'] : 25;   // TCP port to connect to
        $this->mail->From = $configs['from'];
        $this->mail->FromName =  $configs['fromname'];
    }

    /**
     * @检查账号是否可用
     *
     */
    public function check(){
        return $this->mail->smtpConnect();
    }

    /**
     * @ 发送邮件
     * 
     */
    public function send($param) {
        /*
        if ($param['is_html']) {
            $this->mail->isHTML(true);
        }
        */
        $this->mail->isHTML(true);
        
        //发送到
        $sendTo = explode(";",$param['address']);
        foreach($sendTo as $add) {
            if (preg_match("/^([_a-z0-9-]+)(\.[_a-z0-9-]+)*@([a-z0-9-]+)(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/", $add)) {
                $this->mail->addAddress($add);
            }
        }
        //回复给
        if (isset($param['replyto'])) {
            $replytos = explode(";",$param['replyto']);
            foreach($replytos as $replyto) {
                if (preg_match("/^([_a-z0-9-]+)(\.[_a-z0-9-]+)*@([a-z0-9-]+)(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/", $replyto)) {
                    $this->mail->addReplyTo($replyto);
                }
            }
        }
        //抄送
        if (isset($param['cc'])) {
            $ccs = explode(";",$param['cc']);
            foreach($ccs as $cc) {
                if (preg_match("/^([_a-z0-9-]+)(\.[_a-z0-9-]+)*@([a-z0-9-]+)(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/", $cc)) {
                    $this->mail->addCC($cc);
                }
            }
        }
        //密送
        if (isset($param['bcc'])) {
            $bccs = explode(";",$param['bcc']);
            foreach($bccs as $bcc) {
                if (preg_match("/^([_a-z0-9-]+)(\.[_a-z0-9-]+)*@([a-z0-9-]+)(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/", $bcc)) {
                    $this->mail->addBCC($bcc);
                }
            }
        }
        //附件
        if (isset($param['attachments'])) {
            foreach($param['attachments'] as $attachment) {
                if(empty($attachment['path'])) continue;
                $filename = isset($attachment['name']) ? $attachment['name'] : substr(strrchr($attachment['path'], "/"), 1);
                $this->mail->addAttachment($attachment['path'], $filename);
            }
        }
        $this->mail->Subject = $param['subject'];
        $this->mail->Body    = $param['body'];       
        if(!$this->mail->Send()) {
            logger()->debug("Error send mail fail: ".$mail->ErrorInfo);
            $return = false;
        } else {
            $return = true;
        }
        return $return;
    }
    
    public function __destruct(){
        $this->mail->SmtpClose();
        $this->mail = null;
    }
}