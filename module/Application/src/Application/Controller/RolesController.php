<?php
namespace Application\Controller;

use Laminas\Config\Factory;
use Laminas\Http\Request;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Session\Container;
use Laminas\Json\Json;


class RolesController extends AbstractActionController
{
    private $roleService = null;

    public function __construct($roleService)
    {
        $this->roleService = $roleService;
    }

    public function indexAction()
    {
        $session = new Container('credo');
        if($session->roleCode == 'user'){
            return $this->redirect()->toRoute('recency');
        }else{
            $request = $this->getRequest();
            if ($request->isPost()) {
                $params = $request->getPost();
                $result = $this->roleService->getAllRole($params);
                return $this->getResponse()->setContent(Json::encode($result));
            }
        }
    }

    public function addAction()
    {
        $session = new Container('credo');
        if($session->roleCode == 'user'){
            return $this->redirect()->toRoute('recency');
        }else{
            /** @var Request $request */
            $request = $this->getRequest();
            if ($request->isPost()) {
                $params = $request->getPost();
                $result = $this->roleService->addRole($params);
                return $this->redirect()->toRoute('roles');
            }else{
                $resourceResult = $this->roleService->getAllResource();
                return new ViewModel(array(
                    'resourceResult' => $resourceResult
                ));
            }
        }
    }

    public function editAction()
    {
        $session = new Container('credo');
        if($session->roleCode == 'user'){
            return $this->redirect()->toRoute('recency');
        }else{
            /** @var Request $request */
            $request = $this->getRequest();
            if ($request->isPost()) {
                $params = $request->getPost();
                $result = $this->roleService->updateRole($params);
                return $this->redirect()->toRoute('roles');
            }else{
                $configFile = CONFIG_PATH . DIRECTORY_SEPARATOR . "acl.config.php";
                $config = Factory::fromFile($configFile, true);
                $resourceResult = $this->roleService->getAllResource();
                $roelId=base64_decode($this->params()->fromRoute('id'));
                $result = $this->roleService->getRole($roelId);
                if ($result) {
                    return new ViewModel(array(
                        'result' => $result,
                        'resourcePrivilegeMap' => $config,
                        'resourceResult' => $resourceResult,
                    ));
                }else {
                    return $this->redirect()->toRoute("roles");
                }
            }
        }
    }

}
