<?php
namespace AppBundle\Controller;

use AppBundle\Entity\User;
use AppBundle\Entity\UserSecurity;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class InstallerController extends Controller {
    /**
     * @Route("/ix338/dbx", name="dbmainentrieinstall")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function DBMainEntriesAction(Request $request) {
        //echo phpinfo(); die();
        $assetsDir = $this->getParameter('asset_dir') ?? FALSE;
        $dir       = realpath($this->getParameter('kernel.project_dir')) . DIRECTORY_SEPARATOR . $assetsDir . DIRECTORY_SEPARATOR . 'public';
        if ($assetsDir !== FALSE && !is_link(realpath($this->getParameter('kernel.project_dir')) . DIRECTORY_SEPARATOR . 'web' . DIRECTORY_SEPARATOR . 'public') && !file_exists($dir)) {
            echo var_dump(symlink(realpath($this->getParameter('kernel.project_dir')) . DIRECTORY_SEPARATOR . 'web' . DIRECTORY_SEPARATOR . 'public', '' . $dir));
        }
        if(!file_exists('/home/lsws/LOGS/'.$_SERVER['SERVER_NAME'])){
            mkdir('/home/lsws/LOGS/'.$_SERVER['SERVER_NAME'],0775);
            @fclose(fopen('/home/lsws/LOGS/'.$_SERVER['SERVER_NAME'].'/virtualHost.log','w+'));
        }
        // check credentials
        if($request->get('k') != 'djskldjasdjksajdksajkdjaskdjk')
            throw new AccessDeniedHttpException('invalid');

        $em = $this->getDoctrine()->getManager();
        /** @var $em EntityManagerInterface */
        $find = $em->createQueryBuilder()->select('u')->from('AppBundle:User','u')
            ->where('u.username = :uparam')->setParameter('uparam', 'apiuser')->getQuery()->getResult();

        if($find) {
            return new JsonResponse([
                                        'base_dir' => realpath($this->getParameter('kernel.project_dir')) . DIRECTORY_SEPARATOR,
                                        'e' => 'Installation done - DB USER skipped'
                                    ]);
        }
        // standard user
        $user = new User();
        $user->setUsername('apiuser');
        $user->setActiveName( 'apiOne' );
        $user->setIdentifier( 'api_1' );
        $user->setTimestamp( time() );

        $em->persist( $user );
        $em->flush();

        /** @var $em EntityManagerInterface */
        $find = $em->createQueryBuilder()->select('u')->from('AppBundle:UserSecurity','u')
                   ->where('u.apiKey = :uparam')->setParameter('uparam', 'xeroxmm')->getQuery()->getResult();

        if($find) {
            return new JsonResponse([
                                        'base_dir' => realpath($this->getParameter('kernel.project_dir')) . DIRECTORY_SEPARATOR,
                                        'e' => 'Installation done - DB API skipped'
                                    ]);
        }

        // standard api user
        $api = new UserSecurity();
        $api->setUserObj( $user );
        $api->setPwHash( '123' );
        $api->setApiKey( 'xeroxmm' );
        $api->setApiPassword( '123456dfgdfgdfgdfg653656tegh' );

        $em->persist( $api );
        $em->flush();

        return new JsonResponse([
            'base_dir' => realpath($this->getParameter('kernel.project_dir')) . DIRECTORY_SEPARATOR,
            'e' => 'Installation done'
                                ]);
    }
}