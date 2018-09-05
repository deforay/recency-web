<?php
namespace Application\Controller;

use Zend\View\Model\ViewModel;
use Zend\Json\Json;
use Zend\Mvc\Controller\AbstractActionController;

class RecencyController extends AbstractActionController
{

    public function indexAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $recencyService = $this->getServiceLocator()->get('RecencyService');
            $result = $recencyService->getRecencyDetails($params);
            return $this->getResponse()->setContent(Json::encode($result));
        }
    }

    public function addAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $recencyService = $this->getServiceLocator()->get('RecencyService');
            $result = $recencyService->addRecencyDetails($params);
            return $this->_redirect()->toRoute('recency');
        }else{
            $recencyId=base64_decode( $this->params()->fromRoute('id') );
            $facilityService = $this->getServiceLocator()->get('FacilitiesService');
            $facilityResult=$facilityService->getFacilitiesAllDetails();
            return new ViewModel(array(
                'facilityResult' => $facilityResult
            ));
        }
    }

    public function editAction()
    {
        $recencyService = $this->getServiceLocator()->get('RecencyService');
        if($this->getRequest()->isPost())
        {
            $params=$this->getRequest()->getPost();
            $result=$recencyService->updateRecencyDetails($params);
            return $this->redirect()->toRoute('recency');
        }
        else
        {
            $recencyId=base64_decode( $this->params()->fromRoute('id') );
            $facilityService = $this->getServiceLocator()->get('FacilitiesService');
            $facilityResult=$facilityService->getFacilitiesAllDetails();
            $result=$recencyService->getRecencyDetailsById($recencyId);
            return new ViewModel(array(
                'facilityResult' => $facilityResult,
                'result' => $result
            ));
        }
    }
}
