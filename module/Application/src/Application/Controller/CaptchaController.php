<?php

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Session\Container;

class CaptchaController extends AbstractActionController
{
    private $commonService = null;

    public function __construct($commonService)
    {
        $this->commonService = $commonService;
    }
    public function indexAction()
    {
        $result = $this->commonService->getCaptcha();
        return new ViewModel($result);
    }
    public function checkCaptchaAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $captchaSession = new Container('captcha');
            $params['challenge_field'] = filter_var($params['challenge_field'], FILTER_SANITIZE_STRING);
            if ($captchaSession->code == $params['challenge_field']) {
                $result = "success";
                $captchaSession->status = 'success';
            } else {
                $result = "fail";
                $captchaSession->status = 'fail';
            }
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('result' => $result));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }
}
