<?php

use App\plugins\SecureApi;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use ConfigFileManager\ConfigFileManager;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;

class SystememailController extends Controller
{
    private $request;
    private $response;

    public function __construct()
    {
        new SecureApi();
        $this->request = Request::createFromGlobals();
        $this->response = new Response();
        $this->response->headers->set('content-type', 'application/json');
    }

    public function systememail()
    {
        $config = new ConfigFileManager(__ROOT__DIR__ . 'system/config/config.php.ini');

        switch ($this->request->server->get('REQUEST_METHOD')) {
            case 'GET':
                $this->sendRespose($config->system_email, 200);
                break;
            case 'POST':
                $newEmail = json_decode(file_get_contents('php://input'), true);

                $this->validateEmail($newEmail['email']);
                $config->system_email = $newEmail['email'];

                $config->save();
                $this->sendRespose('success', 201);
                break;
            case 'PATCH':
            case 'DELETE':
                $this->sendRespose('Method Not Allowed', 405);
                break;
        }
    }

    private function sendRespose($content, $statusCode)
    {
        $this->response->setContent($content);
        $this->response->setStatusCode($statusCode);
        $this->response->send();
    }

    private function validateEmail($email)
    {
        $validator = new EmailValidator();
        if ($validator->isValid($email, new RFCValidation()) === false) {
            $this->sendRespose('The email is not valid', 409);
            die();
        }
    }
}