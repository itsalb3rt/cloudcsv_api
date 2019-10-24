<?php

/**
 * Created by PhpStorm.
 * User: destroid
 * Date: 19/7/2019
 * Time: 1:13 PM
 */

namespace App\plugins;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SecureApi
{
    /**
     * Controller constructor.
     *
     * Valida la seguridad del API
     */
    public function __construct()
    {
        if (ENVIROMENT == 'dev') {
            $origin = "http://localhost:8080";
        } else {
            $origin = "https://gibucket.a2hosted.com";
        }

        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            header("Access-Control-Allow-Credentials", "true");
            header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
            header("Access-Control-Allow-Origin:$origin");
            header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
            die();
        }


        header("Access-Control-Allow-Credentials", "true");
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
        header("Access-Control-Allow-Origin:$origin");
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    }

    private function isTokenValid()
    {
        $request = Request::createFromGlobals();
        if (strlen($request->server->filter('HTTP_X_AUTH_APP_KEY')) < 5) {
            return true;
        } else {
            return false;
        }
    }
}
