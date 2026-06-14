<?php
declare(strict_types=1);

namespace MylSoft\Attachments\Test\Unit\Model\Config\Source;

use MylSoft\Attachments\Model\Config\Source\IsActive;
use PHPUnit\Framework\TestCase;

class IsActiveTest extends TestCase
{
    private IsActive $source;

    protected function setUp(): void
    {
        $this->source = new IsActive();
    }

    public function testToOptionArrayReturnsTwoOptions(): void
    {
        $this->assertCount(2, $this->source->toOptionArray());
    }

    public function testToOptionArrayFirstOptionIsYes(): void
    {
        $options = $this->source->toOptionArray();

        $this->assertSame(1, $options[0]['value']);
        $this->assertEquals('Yes', $options[0]['label']);
    }

    public function testToOptionArraySecondOptionIsNo(): void
    {
        $options = $this->source->toOptionArray();

        $this->assertSame(0, $options[1]['value']);
        $this->assertEquals('No', $options[1]['label']);
    }

    public function testToOptionArrayEveryEntryHasValueAndLabelKeys(): void
    {
        foreach ($this->source->toOptionArray() as $option) {
            $this->assertArrayHasKey('value', $option);
            $this->assertArrayHasKey('label', $option);
        }
    }
}
