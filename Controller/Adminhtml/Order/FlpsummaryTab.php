<?php

namespace Hexasoft\FraudLabsPro\Controller\Adminhtml\Order;

use Psr\Log\LoggerInterface;
use Magento\Backend\App\Action;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\OrderManagementInterface;

// Flpsummarytab class
class FlpsummaryTab extends \Magento\Sales\Controller\Adminhtml\Order
{

    // used for LayoutFactory
    protected $layout_factorys;
    private $layoutFactorys;

    // construct function
    public function __construct(
        Action\Context $contexts,
        \Magento\Framework\Registry $core_registrys,
        \Magento\Framework\App\Response\Http\FileFactory $file_factorys,
        \Magento\Framework\Translate\InlineInterface $translate_inlines,
        \Magento\Framework\View\Result\PageFactory $result_page_factorys,
        \Magento\Framework\Controller\Result\JsonFactory $result_json_factorys,
        \Magento\Framework\View\Result\LayoutFactory $result_layout_factorys,
        \Magento\Framework\Controller\Result\RawFactory $result_raw_factorys,
        OrderManagementInterface $order_managements,
        OrderRepositoryInterface $order_repositorys,
        LoggerInterface $loggers,
        \Magento\Framework\View\LayoutFactory $layout_factorys
    ) {
        $this->layoutFactorys = $layout_factorys;
        parent::__construct(
            $contexts,
            $core_registrys,
            $file_factorys,
            $translate_inlines,
            $result_page_factorys,
            $result_json_factorys,
            $result_layout_factorys,
            $result_raw_factorys,
            $order_managements,
            $order_repositorys,
            $loggers
        );
    }

    // execute function
    public function execute()
    {
        // initiliase order
        $this->_initOrder();

        //create layout
        $layouts = $this->layoutFactorys->create();

        //create block
        $htmls = $layouts->createBlock('Hexasoft\FraudLabsPro\Block\Adminhtml\Order\View\Tab\Flpsummary')->toHtml();

        // process response body of block
        $this->_translateInline->processResponseBody($htmls);

        // create result to be display
        $results = $this->resultRawFactory->create();

        // set content of body
        $results->setContents($htmls);

        return $results;
    }
}