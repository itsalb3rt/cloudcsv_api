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
}
