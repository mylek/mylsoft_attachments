<?php
declare(strict_types=1);

namespace MylSoft\Attachments\Test\Unit\Ui\DataProvider;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use MylSoft\Attachments\Api\AttachmentRepositoryInterface;
use MylSoft\Attachments\Model\Attachment;
use MylSoft\Attachments\Model\ResourceModel\Attachment\Collection;
use MylSoft\Attachments\Model\ResourceModel\Attachment\CollectionFactory;
use MylSoft\Attachments\Ui\DataProvider\AttachmentFormDataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AttachmentFormDataProviderTest extends TestCase
{
    private RequestInterface|MockObject $request;
    private AttachmentRepositoryInterface|MockObject $attachmentRepository;
    private StoreManagerInterface|MockObject $storeManager;
    private AttachmentFormDataProvider $provider;

    protected function setUp(): void
    {
        $this->request              = $this->createMock(RequestInterface::class);
        $this->attachmentRepository = $this->createMock(AttachmentRepositoryInterface::class);

        $store = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();
        $store->method('getBaseUrl')->willReturn('https://example.com/pub/media/');

        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->storeManager->method('getStore')->willReturn($store);

        $collection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $collectionFactory = $this->createMock(CollectionFactory::class);
        $collectionFactory->method('create')->willReturn($collection);

        $this->provider = new AttachmentFormDataProvider(
            'test_form',
            'attachment_id',
            'id',
            $collectionFactory,
            $this->attachmentRepository,
            $this->request,
            $this->storeManager
        );
    }

    public function testGetDataReturnsEmptyArrayWhenNoId(): void
    {
        $this->request->method('getParam')->with('id')->willReturn('0');

        $this->assertSame([], $this->provider->getData());
    }

    public function testGetDataReturnsAttachmentDataWhenFound(): void
    {
        $this->request->method('getParam')->with('id')->willReturn('5');

        $attachment = $this->getMockBuilder(Attachment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $attachment->method('getData')->willReturn([
            'attachment_id' => 5,
            'name'          => 'Invoice',
        ]);
        $attachment->method('getFilepath')->willReturn('');

        $this->attachmentRepository->method('getById')->with(5)->willReturn($attachment);

        $result = $this->provider->getData();

        $this->assertArrayHasKey(5, $result);
        $this->assertEquals('Invoice', $result[5]['name']);
    }

    public function testGetDataIncludesFileArrayWhenFilepathSet(): void
    {
        $this->request->method('getParam')->with('id')->willReturn('5');

        $attachment = $this->getMockBuilder(Attachment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $attachment->method('getData')->willReturn(['attachment_id' => 5, 'name' => 'Invoice']);
        $attachment->method('getFilepath')->willReturn('invoice.pdf');
        $attachment->method('getFilename')->willReturn('invoice.pdf');

        $this->attachmentRepository->method('getById')->willReturn($attachment);

        $result = $this->provider->getData();

        $this->assertArrayHasKey('file', $result[5]);
        $this->assertIsArray($result[5]['file']);
        $this->assertSame('invoice.pdf', $result[5]['file'][0]['name']);
    }

    public function testGetDataReturnsEmptyArrayWhenNotFound(): void
    {
        $this->request->method('getParam')->with('id')->willReturn('99');
        $this->attachmentRepository->method('getById')
            ->willThrowException(new NoSuchEntityException());

        $this->assertSame([], $this->provider->getData());
    }
}
