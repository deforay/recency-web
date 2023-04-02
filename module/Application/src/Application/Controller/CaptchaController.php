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
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $captchaSession = new Container('captcha');
            $params['challenge_field'] = htmlspecialchars($params['challenge_field']);
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
