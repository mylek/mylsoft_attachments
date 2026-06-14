<?php
declare(strict_types=1);

namespace MylSoft\Attachments\Controller\Adminhtml\Attachment;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use MylSoft\Attachments\Api\AttachmentRepositoryInterface;
use MylSoft\Attachments\Api\Data\AttachmentInterface;
use MylSoft\Attachments\Model\AttachmentFactory;

class Save extends Action
{
    public const ADMIN_RESOURCE = 'MylSoft_Attachments::attachments_manage';

    /**
     * @param Context $context
     * @param AttachmentRepositoryInterface $attachmentRepository
     * @param AttachmentFactory $attachmentFactory
     */
    public function __construct(
        Context $context,
        private readonly AttachmentRepositoryInterface $attachmentRepository,
        private readonly AttachmentFactory $attachmentFactory
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $redirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();

        if (!$data) {
            return $redirect->setPath('*/*/index');
        }

        $id = (int) ($data['attachment_id'] ?? 0);

        try {
            $attachment = $id
                ? $this->attachmentRepository->getById($id)
                : $this->attachmentFactory->create();
        } catch (NoSuchEntityException) {
            $this->messageManager->addErrorMessage(__('This attachment no longer exists.'));
            return $redirect->setPath('*/*/index');
        }

        $attachment->setName((string) ($data[AttachmentInterface::NAME] ?? ''));
        $attachment->setEmailTemplate((string) ($data[AttachmentInterface::EMAIL_TEMPLATE] ?? ''));
        $attachment->setIsActive((int) ($data[AttachmentInterface::IS_ACTIVE] ?? 1));
        $displayFilename = trim((string) ($data[AttachmentInterface::DISPLAY_FILENAME] ?? ''));
        $attachment->setDisplayFilename($displayFilename !== '' ? $displayFilename : null);

        $validFrom = trim((string) ($data[AttachmentInterface::VALID_FROM] ?? ''));
        $attachment->setValidFrom($validFrom !== '' ? $validFrom : null);

        $validTo = trim((string) ($data[AttachmentInterface::VALID_TO] ?? ''));
        $attachment->setValidTo($validTo !== '' ? $validTo : null);

        $fileData = $data['file'] ?? [];
        if (is_array($fileData) && !empty($fileData)) {
            $firstFile = reset($fileData);
            if (isset($firstFile['file'])) {
                $attachment->setFilepath($firstFile['file']);
                $attachment->setFilename($firstFile['name'] ?? $firstFile['file']);
            }
        }

        try {
            $this->attachmentRepository->save($attachment);
            $this->messageManager->addSuccessMessage(__('The attachment has been saved.'));
        } catch (CouldNotSaveException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $redirect->setPath('*/*/edit', ['id' => $id ?: $attachment->getId()]);
        }

        if ($this->getRequest()->getParam('back')) {
            return $redirect->setPath('*/*/edit', ['id' => $attachment->getId()]);
        }

        return $redirect->setPath('*/*/index');
    }
}
