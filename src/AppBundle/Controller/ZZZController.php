<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use AppBundle\Entity\UserSecurity;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ZZZController extends Controller {
    /**
     * @Route("/_/restart", name="server_restart")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function restartServerAction( ) {
        if($_SERVER['SERVER_ADDR'] !== '192.168.0.250')
            throw $this->createNotFoundException('Site not found');;

        if (($ssh = ssh2_connect('localhost')) && ssh2_auth_password($ssh, 'root', file_get_contents('/passwd'))) {
            $stream = ssh2_exec($ssh, 'shutdown -r now');
            fclose($stream);
            exit;
        }

        return new JsonResponse('Connection failed');
    }
    /**
     * @Route("/_/shutdown", name="server_shutdown")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function shutdownServerAction( ) {
        if($_SERVER['SERVER_ADDR'] !== '192.168.0.250')
            throw $this->createNotFoundException('Site not found');;

        if (($ssh = ssh2_connect('localhost')) && ssh2_auth_password($ssh, 'root', file_get_contents('/passwd'))) {
            $stream = ssh2_exec($ssh, 'shutdown -h now');
            fclose($stream);
            exit;
        }

        return new JsonResponse('Connection failed');
    }
    /**
     * @Route("/_/info", name="server_info")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function infoServerAction( ) {
        phpinfo();
        return new Response("");
    }
}