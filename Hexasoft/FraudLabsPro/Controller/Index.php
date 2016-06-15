<?php

namespace Hexasoft\FraudLabsPro\Controller\Index;

class Index extends \Magento\Framework\App\Action\Action {

    public function execute() {
        $this->loadLayout();
        $this->renderLayout();
    }

}
