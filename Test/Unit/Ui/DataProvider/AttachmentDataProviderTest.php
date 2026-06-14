<?php
declare(strict_types=1);

namespace MylSoft\Attachments\Test\Unit\Ui\DataProvider;

use Magento\Ui\DataProvider\AbstractDataProvider;
use MylSoft\Attachments\Model\ResourceModel\Attachment\Collection;
use MylSoft\Attachments\Model\ResourceModel\Attachment\CollectionFactory;
use MylSoft\Attachments\Ui\DataProvider\AttachmentDataProvider;
use PHPUnit\Framework\TestCase;

class AttachmentDataProviderTest extends TestCase
{
    public function testExtendsAbstractDataProvider(): void
    {
        $collection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $collectionFactory = $this->createMock(CollectionFactory::class);
        $collectionFactory->method('create')->willReturn($collection);

        $provider = new AttachmentDataProvider(
            'test_provider',
            'attachment_id',
            'id',
            $collectionFactory
        );

        $this->assertInstanceOf(AbstractDataProvider::class, $provider);
    }
}
