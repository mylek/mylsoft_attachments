<?php
declare(strict_types=1);

namespace MylSoft\Attachments\Controller\Adminhtml\Attachment;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem\DirectoryList;
use MylSoft\Attachments\Api\AttachmentRepositoryInterface;

class Delete extends Action
{
    public const ADMIN_RESOURCE = 'MylSoft_Attachments::attachments_manage';

    /**
     * @param Context $context
     * @param AttachmentRepositoryInterface $attachmentRepository
     * @param DirectoryList $directoryList
     */
    public function __construct(
        Context $context,
        private readonly AttachmentRepositoryInterface $attachmentRepository,
        private readonly DirectoryList $directoryList
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $redirect = $this->resultRedirectFactory->create()->setPath('*/*/index');
        $id = (int) $this->getRequest()->getParam('id');

        if (!$id) {
            $this->messageManager->addErrorMessage(__('Invalid attachment ID.'));
            return $redirect;
        }

        try {
            $attachment = $this->attachmentRepository->getById($id);
            $this->deleteFile($attachment->getFilepath());
            $this->attachmentRepository->delete($attachment);
            $this->messageManager->addSuccessMessage(__('The attachment has been deleted.'));
        } catch (NoSuchEntityException) {
            $this->messageManager->addErrorMessage(__('This attachment no longer exists.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Could not delete attachment: %1', $e->getMessage()));
        }

        return $redirect;
    }

    private function deleteFile(string $filepath): void
    {
        if (!$filepath) {
            return;
        }

        $fullPath = $this->directoryList->getPath('media') . '/mylsoft/attachments/' . $filepath;
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }
}
