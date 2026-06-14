<?php
declare(strict_types=1);

namespace MylSoft\Attachments\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use MylSoft\Attachments\Api\Data\AttachmentInterface;

interface AttachmentRepositoryInterface
{
    public function save(AttachmentInterface $attachment): AttachmentInterface;

    public function getById(int $attachmentId): AttachmentInterface;

    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface;

    public function delete(AttachmentInterface $attachment): bool;

    public function deleteById(int $attachmentId): bool;

    /**
     * @return AttachmentInterface[]
     */
    public function getActiveByEmailTemplate(string $emailTemplate): array;
}
