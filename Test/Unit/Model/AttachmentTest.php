<?php
declare(strict_types=1);

namespace MylSoft\Attachments\Test\Unit\Model;

use MylSoft\Attachments\Model\Attachment;
use PHPUnit\Framework\TestCase;

class AttachmentTest extends TestCase
{
    private Attachment $attachment;

    protected function setUp(): void
    {
        $this->attachment = $this->getMockBuilder(Attachment::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
    }

    public function testGetDisplayFilenameReturnsNullWhenNotSet(): void
    {
        $this->assertNull($this->attachment->getDisplayFilename());
    }

    public function testGetDisplayFilenameReturnsNullWhenSetToEmptyString(): void
    {
        $this->attachment->setDisplayFilename('');

        $this->assertNull($this->attachment->getDisplayFilename());
    }

    public function testGetDisplayFilenameReturnsValueWhenSet(): void
    {
        $this->attachment->setDisplayFilename('invoice.pdf');

        $this->assertSame('invoice.pdf', $this->attachment->getDisplayFilename());
    }

    public function testSetDisplayFilenameAcceptsNull(): void
    {
        $this->attachment->setDisplayFilename('previous.pdf');
        $this->attachment->setDisplayFilename(null);

        $this->assertNull($this->attachment->getDisplayFilename());
    }

    public function testGetValidFromReturnsNullWhenNotSet(): void
    {
        $this->assertNull($this->attachment->getValidFrom());
    }

    public function testGetValidFromReturnsNullWhenSetToEmptyString(): void
    {
        $this->attachment->setValidFrom('');

        $this->assertNull($this->attachment->getValidFrom());
    }

    public function testGetValidFromReturnsValueWhenSet(): void
    {
        $this->attachment->setValidFrom('2026-01-01');

        $this->assertSame('2026-01-01', $this->attachment->getValidFrom());
    }

    public function testGetValidToReturnsNullWhenNotSet(): void
    {
        $this->assertNull($this->attachment->getValidTo());
    }

    public function testGetValidToReturnsNullWhenSetToEmptyString(): void
    {
        $this->attachment->setValidTo('');

        $this->assertNull($this->attachment->getValidTo());
    }

    public function testGetValidToReturnsValueWhenSet(): void
    {
        $this->attachment->setValidTo('2026-12-31');

        $this->assertSame('2026-12-31', $this->attachment->getValidTo());
    }

    public function testGetIsActiveDefaultsToZeroWhenNotSet(): void
    {
        $this->assertSame(0, $this->attachment->getIsActive());
    }

    public function testSetIsActiveAndGet(): void
    {
        $this->attachment->setIsActive(1);

        $this->assertSame(1, $this->attachment->getIsActive());
    }
}
