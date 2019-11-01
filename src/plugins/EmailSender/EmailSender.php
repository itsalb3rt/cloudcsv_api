<?php


namespace App\plugins\EmailSender;


use ConfigFileManager\ConfigFileManager;
use PHPMailer\PHPMailer\PHPMailer;

class EmailSender
{
    private $phpMailer;
    private $subject;
    private $body;

    public function __construct()
    {
        $this->phpMailer = new PHPMailer();

        $this->phpMailer->IsSMTP(); // enable SMTP
        $config = $systemConfigIni = new ConfigFileManager(__ROOT__DIR__ . 'system/config/config.php.ini');

        $this->phpMailer->SMTPDebug = 0; //debug off = 0, debugging: 1 = errors and messages, 2 = messages only
        $this->phpMailer->SMTPAuth = true; // authentication enabled
        $this->phpMailer->SMTPSecure = 'ssl'; // secure transfer enabled REQUIRED for Gmail
        $this->phpMailer->Host = $config->system_email_host;
        $this->phpMailer->Port = 465; // or 587
        $this->phpMailer->IsHTML(true);
        $this->phpMailer->Username = $config->system_email;
        $this->phpMailer->Password = str_replace('"', '', $config->system_email_password);
        $this->phpMailer->SetFrom($config->system_email);

    }

    public function setSubject(string $subject): void
    {
        $this->subject = $subject;
    }

    public function setBody(string $body): void
    {
        $this->body = $body;
    }

    public function setAddress(string $address): void
    {
        $this->phpMailer->AddAddress($address);
    }

    public function send()
    {
        $this->phpMailer->Subject = $this->subject;
        $this->phpMailer->Body = $this->body;

        return $this->phpMailer->Send();
    }
}