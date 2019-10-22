<?php

use App\plugins\SecureApi;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;
use App\models\users\UsersModel;
use Ingenerator\Tokenista;
use App\plugins\QueryStringPurifier;

class UsersController extends Controller
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

    public function users($id = null): void
    {
        switch ($this->request->server->get('REQUEST_METHOD')) {
            case 'GET':

                if ($id === null) {
                    $qString = new QueryStringPurifier();

                    $users = new UsersModel();
                    $this->response->setContent(json_encode($users->getAll($qString->getFields(),
                        $qString->fieldsToFilter(),
                        $qString->getOrderBy(),
                        $qString->getSorting(),
                        $qString->getOffset(),
                        $qString->getLimit())));
                } else {
                    $users = new UsersModel();
                    $user = $users->getById($id);
                    if (!empty($user))
                        unset($user->password);
                    $this->response->setContent(json_encode($user));
                }

                $this->response->setStatusCode(200);
                $this->response->send();
                break;
            case 'POST':
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

                $newUser = new UsersModel();
                $newUser->create($user);

                $this->response->setContent('success');
                $this->response->setStatusCode(201);
                $this->response->send();
                break;
            case 'PATCH':
                $user = json_decode(file_get_contents('php://input'), true);
                if (!empty($user) && $id !== null) {
                    $users = new UsersModel();

                    if(isset($user['email']))
                        $this->validateEmail($user['email']);

                    if(isset($user['password'])){
                        $this->passwordMatch($user['password'],$user['confirm_password']);
                        $this->isPasswordSecure($user['password']);
                        $user['password'] = $this->passwordHasing($user['password']);
                    }

                    $users->update($id, $user);
                    $this->response->setContent('success');
                    $this->response->setStatusCode(201);
                    $this->response->send();
                }
                break;
            case 'DELETE':
                if($id !== null){
                    $users = new UsersModel();
                    $users->delete($id);
                    $this->response->setContent('success');
                    $this->response->setStatusCode(201);
                    $this->response->send();
                }
                break;
        }
    }

    private function passwordHasing(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2I);
    }

    private function passwordMatch(string $password1, string $password2): void
    {
        if ($password1 != $password2) {
            $this->response->setContent('the password not match');
            $this->response->setStatusCode(409);
            $this->response->send();
            die();
        }
    }

    private function isPasswordSecure(string $password): void
    {
        if (strlen($password) < 8) {
            $this->response->setContent('the password is not secure');
            $this->response->setStatusCode(409);
            $this->response->send();
            die();
        }
    }

    private function defineUserRole($user)
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

    private function validateEmail($email)
    {
        $validator = new EmailValidator();
        if ($validator->isValid($email, new RFCValidation()) === false) {
            $this->response->setContent('the email is not valid');
            $this->response->setStatusCode(409);
            $this->response->send();
            die();
        }
    }
}