<?php
declare(strict_types=1);

namespace MylSoft\Attachments\Test\Unit\Model\ResourceModel;

use MylSoft\Attachments\Model\ResourceModel\Attachment;
use PHPUnit\Framework\TestCase;

class AttachmentTest extends TestCase
{
    public function testExtendsAbstractDb(): void
    {
        $this->assertInstanceOf(
            \Magento\Framework\Model\ResourceModel\Db\AbstractDb::class,
            $this->getMockBuilder(Attachment::class)
                ->disableOriginalConstructor()
                ->getMockForAbstractClass()
        );
    }
}
