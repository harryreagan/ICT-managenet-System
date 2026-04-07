<?php
/**
 * Lightweight SMTP Client for ICT Management System
 * Allows sending emails without relying on server-side mail() configuration.
 */
class SMTPClient
{
    private $host;
    private $port;
    private $user;
    private $pass;
    private $error = '';

    public function __construct($host, $port, $user, $pass)
    {
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->pass = $pass;
    }

    public function send($to, $subject, $message, $fromName, $fromEmail)
    {
        $header = "To: $to\r\n";
        $header .= "From: $fromName <$fromEmail>\r\n";
        $header .= "Subject: $subject\r\n";
        $header .= "Content-Type: text/plain; charset=utf-8\r\n";
        $header .= "Date: " . date("r") . "\r\n";
        $header .= "Message-ID: <" . md5(uniqid(time())) . "@" . $_SERVER['SERVER_NAME'] . ">\r\n";

        $content = $header . "\r\n" . $message;

        try {
            $socket = fsockopen($this->host, $this->port, $errno, $errstr, 30);
            if (!$socket)
                throw new Exception("Could not connect to SMTP host: $errstr ($errno)");

            $this->getResponse($socket); // 220

            fwrite($socket, "EHLO " . $_SERVER['SERVER_NAME'] . "\r\n");
            $this->getResponse($socket);

            fwrite($socket, "STARTTLS\r\n");
            $this->getResponse($socket);
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

            fwrite($socket, "EHLO " . $_SERVER['SERVER_NAME'] . "\r\n");
            $this->getResponse($socket);

            fwrite($socket, "AUTH LOGIN\r\n");
            $this->getResponse($socket);

            fwrite($socket, base64_encode($this->user) . "\r\n");
            $this->getResponse($socket);

            fwrite($socket, base64_encode($this->pass) . "\r\n");
            $this->getResponse($socket);

            fwrite($socket, "MAIL FROM: <$fromEmail>\r\n");
            $this->getResponse($socket);

            fwrite($socket, "RCPT TO: <$to>\r\n");
            $this->getResponse($socket);

            fwrite($socket, "DATA\r\n");
            $this->getResponse($socket);

            fwrite($socket, $content . "\r\n.\r\n");
            $this->getResponse($socket);

            fwrite($socket, "QUIT\r\n");
            fclose($socket);
            return true;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    private function getResponse($socket)
    {
        $res = "";
        while ($str = fgets($socket, 515)) {
            $res .= $str;
            if (substr($str, 3, 1) == " ")
                break;
        }
        return $res;
    }

    public function getLastError()
    {
        return $this->error;
    }
}
