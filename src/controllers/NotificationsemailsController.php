<?php


use App\plugins\QueryStringPurifier;
use App\plugins\SecureApi;
use App\plugins\Util;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use App\models\emailsNotifications\EmailsNotificationsModel;
use App\models\tables\TablesModel;

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
                    $notifications = $notifications->getAll($qString->getFields(),
                        $qString->fieldsToFilter(),
                        $qString->getOrderBy(),
                        $qString->getSorting(),
                        $qString->getOffset(),
                        $qString->getLimit());
                    $notifications = $this->getTable($notifications);
                    $this->response->setContent(json_encode($notifications));
                } else {
                    $notifications = $notifications->getById($id);
                    $notifications = $this->getTable([$notifications]); //Hack for use same method for search group notification table a simple notification
                    $this->response->setContent(json_encode($notifications[0]));
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

    private function getTable($notifications){
        $tables = new TablesModel();
        foreach ($notifications as $notification){
            $notification->{'table'} = $tables->getById($notification->id_table_storage);
        }
        return $notifications;
    }
}