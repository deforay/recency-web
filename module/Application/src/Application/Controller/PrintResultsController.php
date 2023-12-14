<?php
namespace Application\Controller;

use Laminas\Http\Request;
use Laminas\Session\Container;
use Laminas\View\Model\ViewModel;
use Laminas\Json\Json;
use Laminas\Mvc\Controller\AbstractActionController;

class PrintResultsController extends AbstractActionController
{

    private $recencyService = null;

    public function __construct($recencyService)
    {
        $this->recencyService = $recencyService;
    }

    public function indexAction()
    {
        /** @var Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $parameters = $request->getPost();
            $result = $this->recencyService->getPrintResultsDetails($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }
        return new ViewModel();
    }
}
