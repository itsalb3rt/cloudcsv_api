<?php

use App\models\AccountRecovery\AccountRecovery;
use App\plugins\SecureApi;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;
use App\models\users\UsersModel;
use Ingenerator\Tokenista;
use App\plugins\EmailSender\EmailSender;

class AuthController extends Controller
{
    private $request;
    private $response;

    public function __construct()
    {
        new SecureApi(true);
        $this->request = Request::createFromGlobals();
        $this->response = new Response();
        $this->response->headers->set('content-type', 'application/json');
    }

    public function login()
    {
        if ($this->request->server->get('REQUEST_METHOD') === 'POST') {
            $requestUser = json_decode(file_get_contents('php://input'), true);
            $user = new UsersModel();
            $userName = $requestUser['user_name'];

            if (filter_var($userName, FILTER_VALIDATE_EMAIL)) {
                $user = $user->getByEmail($userName);
            } else {
                $user = $user->getByUserName($userName);
            }

            $this->isUserNotFound($user);

            if (password_verify($requestUser['password'], $user->password)) {
                unset($user->password);
                $this->response->setContent(json_encode($user));
                $this->response->setStatusCode(201);
                $this->response->send();
            } else {
                $this->response->setContent(json_encode(['Unauthorized']));
                $this->response->setStatusCode(401);
                $this->response->send();
            }
        }
    }

    public function isUserNotFound($user)
    {
        if (empty($user)) {
            $this->response->setContent(json_encode(["User Not Found"]));
            $this->response->setStatusCode(404);
            $this->response->send();
            die();
        }
    }

    public function register(): void
    {
        if ($this->request->server->get('REQUEST_METHOD') === 'POST') {

            $user = json_decode(file_get_contents('php://input'), true);
            $this->isPasswordSecure($user['password']);
            $this->passwordMatch($user['password'], $user['confirm_password']);
            $this->validateEmail($user['email']);

            $user['create_at'] = date('Y-m-d H:i:s');
            $user['password'] = $this->passwordHasing($user['password']);
            unset($user['confirm_password']);

            $user['role'] = $this->defineUserRole($user);
            $token = new Tokenista('cloudcsv');
            $user['token'] = $token->generate();

            $checkUser = new UsersModel();
            $userByEmail = $checkUser->getByEmail($user['email']);
            $user['user_name'] = explode('@', $user['email'])[0];

            $userByUserName = $checkUser->getByUserName($user['user_name']);

            if (empty($userByEmail) && empty($userByUserName)) {
                $newUser = new UsersModel();
                $newUser->create($user);

                $this->response->setContent(json_encode([
                    "message" => "success",
                ]));
                $this->response->setStatusCode(201);
                $this->response->send();
            } else {
                $this->response->setContent(json_encode(['message' => 'User Already Exist']));
                $this->response->setStatusCode(409);
                $this->response->send();
            }
        }
    }

    public function recovery()
    {

        if ($this->request->server->get('REQUEST_METHOD') === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $email = $data['email'];
            $users = new UsersModel();
            $currentUser = $users->getByEmail($email);

            if (!empty($currentUser)) {
                $token = 'cloudcsv';
                $tokenista = new Tokenista($token, ["lifetime" => 7200]);
                $token = $tokenista->generate();

                $accountRecovery = new AccountRecovery();
                $accountRecovery->removeAccountRecoveryInformation($currentUser->id_user);

                $accountRecovery->setAccountRecoveryInformation([
                    'id_user' => $currentUser->id_user,
                    "token" => $token
                ]);

                $emailSender = new EmailSender();
                $emailSender->setSubject('CloudCsv account recovery');
                $emailSender->setBody($this->getBodyMessageForRecoveryEmail($token));
                $emailSender->setAddress($email);
                $emailSender->send();

                $this->response->setContent(json_encode([
                    "message" => "success",
                ]));
                $this->response->setStatusCode(200);

            } else {
                $this->response->setContent(json_encode('The email no exits'));
                $this->response->setStatusCode(409);
            }
            $this->response->send();
        }
    }

    public function resetPassword()
    {

        if ($this->request->server->get('REQUEST_METHOD') === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $token = $data['token'];

            $tokenista = new Tokenista('cloudcsv', ["lifetime" => 7200]);
            $accountRecovery = new AccountRecovery();
            $accountData = $accountRecovery->getByToken($token);

            if ($tokenista->isValid($token) === true && $tokenista->isExpired($token) === false && !empty($accountData)) {
                $password = $data['password'];
                $confirmPassword = $data['confirm_password'];
                $user = new UsersModel();
                $this->passwordMatch($password, $confirmPassword);

                $user->update($accountData->id_user, [
                    'password' => $this->passwordHasing($password)
                ]);
                $accountRecovery->removeAccountRecoveryInformation($accountData->id_user);
                $this->response->setContent(json_encode([
                    "message" => "success",
                ]));
                $this->response->setStatusCode(200);
            } else {
                $this->response->setContent('the token is not valid');
                $this->response->setStatusCode(401);
            }
            $this->response->send();
        }
    }

    private function passwordHasing(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2I);
    }

    private function passwordMatch(string $password1, string $password2): void
    {
        if ($password1 != $password2) {
            $this->response->setContent(json_encode('the password not match'));
            $this->response->setStatusCode(409);
            $this->response->send();
            die();
        }
    }

    private function isPasswordSecure(string $password): void
    {
        if (strlen($password) < 8) {
            $this->response->setContent(json_encode('the password is not secure'));
            $this->response->setStatusCode(409);
            $this->response->send();
            die();
        }
    }

    private function defineUserRole($user): string
    {
        if (isset($user['role'])) {
            if ($user['role'] === 'admin' || $user['role'] === 'user') {
                return $user['role'];
            } else {
                return 'user';
            }
        } else {
            return 'user';
        }
    }

    private function validateEmail($email): void
    {
        $validator = new EmailValidator();
        if ($validator->isValid($email, new RFCValidation()) === false) {
            $this->response->setContent(json_encode('the email is not valid'));
            $this->response->setStatusCode(409);
            $this->response->send();
            die();
        }
    }

    private function getBodyMessageForRecoveryEmail($token){
        $bodyMessage = '<div>The following link will take you to an external page to recover your password;</div>';
        $url = BASE_URL_PRODUCTION_FRONTEND."/auth/reset-password/?token=$token";
        $bodyMessage .= '<div><a href=" '. $url .' "> ' . $url . '</a></div>';
        $bodyMessage .= '<div>This email expires in 2 hours.</div>';
        return $bodyMessage;
    }

}