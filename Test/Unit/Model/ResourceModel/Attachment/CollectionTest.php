<?php
declare(strict_types=1);

namespace MylSoft\Attachments\Test\Unit\Model\ResourceModel\Attachment;

use MylSoft\Attachments\Model\ResourceModel\Attachment\Collection;
use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
{
    public function testExtendsAbstractCollection(): void
    {
        $this->assertInstanceOf(
            \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection::class,
            $this->getMockBuilder(Collection::class)
                ->disableOriginalConstructor()
                ->getMockForAbstractClass()
        );
    }
}
