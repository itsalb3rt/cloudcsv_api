<?php


namespace App\plugins;


use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;
use Symfony\Component\HttpFoundation\Response;

class Util
{
    public function validateEmail($email)
    {
        $validator = new EmailValidator();
        if ($validator->isValid($email, new RFCValidation()) === false) {
            $respose = new Response();
            $respose->setContent('the email is not valid');
            $respose->setStatusCode(409);
            $respose->send();
            die();
        }
    }


    /**
     * It takes a string, trims it, strips slashes, converts special characters to HTML entities, replaces
     * spaces with underscores, and removes all characters that aren't letters, numbers, or underscores
     * 
     * for example this is use on table name and columns names
     * 
     * @param string The string to be sanitized.
     * 
     * @return The sanitized string.
     */
    public function sanitizeString($string): string
    {
        $string = trim($string);
        $string = stripslashes($string);
        $string = htmlspecialchars($string);
        $string = preg_replace('/\s+/', '_', $string);
        $string = preg_replace('/[^A-Za-z0-9_]/', '', $string);
        return $string;
    }
}
