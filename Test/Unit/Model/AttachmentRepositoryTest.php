<?php
declare(strict_types=1);

namespace MylSoft\Attachments\Test\Unit\Model;

use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use MylSoft\Attachments\Api\Data\AttachmentInterface;
use MylSoft\Attachments\Model\Attachment;
use MylSoft\Attachments\Model\AttachmentFactory;
use MylSoft\Attachments\Model\AttachmentRepository;
use MylSoft\Attachments\Model\ResourceModel\Attachment as AttachmentResource;
use MylSoft\Attachments\Model\ResourceModel\Attachment\Collection;
use MylSoft\Attachments\Model\ResourceModel\Attachment\CollectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AttachmentRepositoryTest extends TestCase
{
    private AttachmentResource|MockObject $resource;
    private AttachmentFactory|MockObject $attachmentFactory;
    private CollectionFactory|MockObject $collectionFactory;
    private SearchResultsInterfaceFactory|MockObject $searchResultsFactory;
    private AttachmentRepository $repository;

    protected function setUp(): void
    {
        $this->resource             = $this->createMock(AttachmentResource::class);
        $this->attachmentFactory    = $this->createMock(AttachmentFactory::class);
        $this->collectionFactory    = $this->createMock(CollectionFactory::class);
        $this->searchResultsFactory = $this->createMock(SearchResultsInterfaceFactory::class);

        $this->repository = new AttachmentRepository(
            $this->resource,
            $this->attachmentFactory,
            $this->collectionFactory,
            $this->searchResultsFactory
        );
    }

    public function testSavePersistsAttachmentAndReturnsIt(): void
    {
        $attachment = $this->createMock(Attachment::class);
        $this->resource->expects($this->once())->method('save')->with($attachment);

        $result = $this->repository->save($attachment);

        $this->assertSame($attachment, $result);
    }

    public function testSaveWrapsExceptionAsCouldNotSaveException(): void
    {
        $attachment = $this->createMock(Attachment::class);
        $this->resource->method('save')->willThrowException(new \RuntimeException('DB write error'));

        $this->expectException(CouldNotSaveException::class);

        $this->repository->save($attachment);
    }

    public function testGetByIdReturnsLoadedAttachment(): void
    {
        $attachment = $this->createMock(Attachment::class);
        $attachment->method('getId')->willReturn(5);
        $this->attachmentFactory->method('create')->willReturn($attachment);

        $result = $this->repository->getById(5);

        $this->assertSame($attachment, $result);
        $this->resource->verify();
    }

    public function testGetByIdThrowsNoSuchEntityExceptionWhenNotFound(): void
    {
        $attachment = $this->createMock(Attachment::class);
        $attachment->method('getId')->willReturn(null);
        $this->attachmentFactory->method('create')->willReturn($attachment);

        $this->expectException(NoSuchEntityException::class);

        $this->repository->getById(999);
    }

    public function testDeleteRemovesAttachmentAndReturnsTrue(): void
    {
        $attachment = $this->createMock(Attachment::class);
        $this->resource->expects($this->once())->method('delete')->with($attachment);

        $result = $this->repository->delete($attachment);

        $this->assertTrue($result);
    }

    public function testDeleteWrapsExceptionAsCouldNotDeleteException(): void
    {
        $attachment = $this->createMock(Attachment::class);
        $this->resource->method('delete')->willThrowException(new \RuntimeException('DB error'));

        $this->expectException(CouldNotDeleteException::class);

        $this->repository->delete($attachment);
    }

    public function testDeleteByIdLoadsAttachmentAndDeletesIt(): void
    {
        $attachment = $this->createMock(Attachment::class);
        $attachment->method('getId')->willReturn(3);
        $this->attachmentFactory->method('create')->willReturn($attachment);
        $this->resource->expects($this->once())->method('delete')->with($attachment);

        $result = $this->repository->deleteById(3);

        $this->assertTrue($result);
    }

    public function testGetActiveByEmailTemplateAppliesBothFilters(): void
    {
        $expected = [$this->createMock(Attachment::class)];

        $collection = $this->createMock(Collection::class);
        $collection->expects($this->exactly(2))
            ->method('addFieldToFilter')
            ->withConsecutive(
                [AttachmentInterface::EMAIL_TEMPLATE, 'sales_email_order_guest_template'],
                [AttachmentInterface::IS_ACTIVE, 1]
            )
            ->willReturnSelf();
        $collection->method('getItems')->willReturn($expected);

        $this->collectionFactory->method('create')->willReturn($collection);

        $result = $this->repository->getActiveByEmailTemplate('sales_email_order_guest_template');

        $this->assertSame($expected, $result);
    }

    public function testGetActiveByEmailTemplateReturnsEmptyArrayWhenNoneFound(): void
    {
        $collection = $this->createMock(Collection::class);
        $collection->method('addFieldToFilter')->willReturnSelf();
        $collection->method('getItems')->willReturn([]);

        $this->collectionFactory->method('create')->willReturn($collection);

        $this->assertSame([], $this->repository->getActiveByEmailTemplate('nonexistent'));
    }
}
