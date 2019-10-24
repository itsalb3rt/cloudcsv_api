<?php


use App\plugins\QueryStringPurifier;
use App\plugins\SecureApi;
use App\plugins\Util;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use App\models\emailsNotifications\EmailsNotificationsModel;

class NotificationsemailsController extends Controller
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

    public function notificationsemails($id = null)
    {
        $util = new Util();
        $notifications = new EmailsNotificationsModel();
        switch ($this->request->server->get('REQUEST_METHOD')) {
            case 'GET':
                if ($id === null) {
                    $qString = new QueryStringPurifier();
                    $this->response->setContent(json_encode($notifications->getAll($qString->getFields(),
                        $qString->fieldsToFilter(),
                        $qString->getOrderBy(),
                        $qString->getSorting(),
                        $qString->getOffset(),
                        $qString->getLimit())));
                } else {
                    $notifications = $notifications->getById($id);
                    $this->response->setContent(json_encode($notifications));
                }

                $this->response->setStatusCode(200);
                $this->response->send();
                break;
            case 'POST':
                $newNotification = json_decode(file_get_contents('php://input'), true);
                $util->validateEmail($newNotification['email']);
                $notifications->create($newNotification);

                $this->response->setContent('success');
                $this->response->setStatusCode(201);
                $this->response->send();
                break;
            case 'PATCH':
                $this->response->setContent('Method Not Allowed');
                $this->response->setStatusCode(405);
                $this->response->send();
                break;
            case 'DELETE':
                $notifications->delete($id);
                $this->response->setContent('success');
                $this->response->setStatusCode(200);
                $this->response->send();
                break;
        }
    }
}