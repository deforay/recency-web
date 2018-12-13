<?php

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;

class CaptchaController extends AbstractActionController
{
    public function indexAction()
    {
        $commonService=$this->getServiceLocator()->get('CommonService');
        $result = $commonService->getCaptcha();
        return new ViewModel($result);
    }
    public function checkCaptchaAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $captchaSession = new Container('captcha');
            if ($captchaSession->code == $params['challenge_field']) {
                $result = "success";
            } else {
                 $result = "fail";
            }
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('result'=>$result));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }
}

