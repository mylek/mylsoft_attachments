<?php
declare(strict_types=1);

namespace MylSoft\Attachments\Controller\Adminhtml\Attachment;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Ui\Component\MassAction\Filter;
use MylSoft\Attachments\Api\AttachmentRepositoryInterface;
use MylSoft\Attachments\Model\ResourceModel\Attachment\CollectionFactory;

class MassDelete extends Action
{
    public const ADMIN_RESOURCE = 'MylSoft_Attachments::attachments_manage';

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param AttachmentRepositoryInterface $attachmentRepository
     * @param DirectoryList $directoryList
     */
    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly CollectionFactory $collectionFactory,
        private readonly AttachmentRepositoryInterface $attachmentRepository,
        private readonly DirectoryList $directoryList
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $deleted = 0;
        $mediaDir = $this->directoryList->getPath('media');

        foreach ($collection as $attachment) {
            $filepath = $mediaDir . '/mylsoft/attachments/' . $attachment->getFilepath();
            if (file_exists($filepath)) {
                unlink($filepath);
            }
            $this->attachmentRepository->delete($attachment);
            $deleted++;
        }

        $this->messageManager->addSuccessMessage(__('A total of %1 attachment(s) have been deleted.', $deleted));
        return $this->resultRedirectFactory->create()->setPath('*/*/index');
    }
}
