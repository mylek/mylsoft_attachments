<?php
declare(strict_types=1);

namespace MylSoft\Attachments\Controller\Adminhtml\Attachment;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class NewAction extends Action
{
    public const ADMIN_RESOURCE = 'MylSoft_Attachments::attachments_manage';

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('MylSoft_Attachments::attachments');
        $resultPage->getConfig()->getTitle()->prepend(__('New Email Attachment'));
        return $resultPage;
    }
}
