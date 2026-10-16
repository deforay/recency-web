<?php

namespace Application\Controller;

use Laminas\Http\Request;
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
        /** @var Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $captchaSession = new Container('captcha');
            $code = (string) ($captchaSession->code ?? '');
            $answer = htmlspecialchars((string) ($params['challenge_field'] ?? ''));
            // One guess per image. Without this, one image took unlimited guesses,
            // and an empty answer matched when no image had been loaded.
            unset($captchaSession->code);
            if ($code !== '' && hash_equals($code, $answer)) {
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
