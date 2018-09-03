<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{


    public function indexAction()
    {

        // $connector = $this->getServiceLocator()->get('GoogleConnector');
        // $connector->connect($_SESSION['accessToken']);

        // // //echo $connector->getEmail();

        // $files = $connector->getFilesFromFolder('0B4Wzoe2WYKb5a2drTlJkUGlCSlk');
        //  echo "<pre>";
        //  var_dump($files);
        //  echo "</pre>";
        // die;
        return new ViewModel();
    }

}
