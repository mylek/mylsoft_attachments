<?php
declare(strict_types=1);

namespace MylSoft\Attachments\Model;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use MylSoft\Attachments\Api\AttachmentRepositoryInterface;
use MylSoft\Attachments\Api\Data\AttachmentInterface;
use MylSoft\Attachments\Model\ResourceModel\Attachment as AttachmentResource;
use MylSoft\Attachments\Model\ResourceModel\Attachment\CollectionFactory;

class AttachmentRepository implements AttachmentRepositoryInterface
{
    /**
     * @param AttachmentResource $resource
     * @param AttachmentFactory $attachmentFactory
     * @param CollectionFactory $collectionFactory
     * @param SearchResultsInterfaceFactory $searchResultsFactory
     */
    public function __construct(
        private readonly AttachmentResource $resource,
        private readonly AttachmentFactory $attachmentFactory,
        private readonly CollectionFactory $collectionFactory,
        private readonly SearchResultsInterfaceFactory $searchResultsFactory
    ) {}

    public function save(AttachmentInterface $attachment): AttachmentInterface
    {
        try {
            $this->resource->save($attachment);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()));
        }
        return $attachment;
    }

    public function getById(int $attachmentId): AttachmentInterface
    {
        $attachment = $this->attachmentFactory->create();
        $this->resource->load($attachment, $attachmentId);
        if (!$attachment->getId()) {
            throw new NoSuchEntityException(__('Attachment with ID "%1" does not exist.', $attachmentId));
        }
        return $attachment;
    }

    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface
    {
        $collection = $this->collectionFactory->create();
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    public function delete(AttachmentInterface $attachment): bool
    {
        try {
            $this->resource->delete($attachment);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__($e->getMessage()));
        }
        return true;
    }

    public function deleteById(int $attachmentId): bool
    {
        return $this->delete($this->getById($attachmentId));
    }

    public function getActiveByEmailTemplate(string $emailTemplate): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(AttachmentInterface::EMAIL_TEMPLATE, $emailTemplate);
        $collection->addFieldToFilter(AttachmentInterface::IS_ACTIVE, 1);
        return array_values($collection->getItems());
    }
}
