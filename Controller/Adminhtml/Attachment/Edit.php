<?php
declare(strict_types=1);

namespace MylSoft\Attachments\Controller\Adminhtml\Attachment;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory;
use MylSoft\Attachments\Api\AttachmentRepositoryInterface;

class Edit extends Action
{
    public const ADMIN_RESOURCE = 'MylSoft_Attachments::attachments_manage';

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param AttachmentRepositoryInterface $attachmentRepository
     */
    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly AttachmentRepositoryInterface $attachmentRepository
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $id = (int) $this->getRequest()->getParam('id');

        try {
            $attachment = $this->attachmentRepository->getById($id);
        } catch (NoSuchEntityException) {
            $this->messageManager->addErrorMessage(__('This attachment no longer exists.'));
            return $this->resultRedirectFactory->create()->setPath('*/*/index');
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('MylSoft_Attachments::attachments');
        $resultPage->getConfig()->getTitle()->prepend(__('Edit Attachment: %1', $attachment->getName()));
        return $resultPage;
    }
}
