<?php
namespace Application\View\Helper;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Helper\AbstractHelper;
 
class CategoryHelper extends AbstractHelper implements ServiceLocatorAwareInterface
{
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator){
        $this->serviceLocator = $serviceLocator;  
        return $this;  
    }
    /** 
     * Get the service locator. 
     * 
     * @return ServiceLocatorInterface 
     */  
    public function getServiceLocator()
    {  
        return $this->serviceLocator;  
    } 

    
    public function __invoke()
    {
        $sm = $this->getServiceLocator()->getServiceLocator();
        $model = $sm->get("ProductCategoriesTable");
        return $model->fetchAllActiveProductCategoryForMenu();
    }
}
?>