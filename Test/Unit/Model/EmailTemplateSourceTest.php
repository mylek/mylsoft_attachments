<?php
declare(strict_types=1);

namespace MylSoft\Attachments\Test\Unit\Model;

use Magento\Email\Model\ResourceModel\Template\Collection as TemplateCollection;
use Magento\Email\Model\ResourceModel\Template\CollectionFactory;
use Magento\Email\Model\Template\Config as TemplateConfig;
use MylSoft\Attachments\Model\EmailTemplateSource;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EmailTemplateSourceTest extends TestCase
{
    private CollectionFactory|MockObject $collectionFactory;
    private TemplateConfig|MockObject $templateConfig;
    private EmailTemplateSource $source;

    protected function setUp(): void
    {
        $this->collectionFactory = $this->createMock(CollectionFactory::class);
        $this->templateConfig    = $this->createMock(TemplateConfig::class);

        $this->source = new EmailTemplateSource(
            $this->collectionFactory,
            $this->templateConfig
        );
    }

    public function testFirstOptionIsEmptyPlaceholder(): void
    {
        $this->stubEmptyCollection();
        $this->templateConfig->method('getAvailableTemplates')->willReturn([]);

        $options = $this->source->toOptionArray();

        $this->assertSame('', $options[0]['value']);
    }

    public function testCustomTemplatesGroupAddedWhenDbTemplatesExist(): void
    {
        $this->stubCollectionWithTemplates([
            ['id' => '1', 'code' => 'My Custom Template'],
        ]);
        $this->templateConfig->method('getAvailableTemplates')->willReturn([]);

        $options = $this->source->toOptionArray();

        $groups = array_filter($options, fn($o) => is_array($o['value'] ?? null));
        $this->assertNotEmpty($groups);

        $customGroup = array_values($groups)[0];
        $this->assertEquals('Custom Templates', $customGroup['label']);
        $this->assertSame('1', $customGroup['value'][0]['value']);
        $this->assertSame('My Custom Template', $customGroup['value'][0]['label']);
    }

    public function testCustomTemplatesGroupNotAddedWhenNoDbTemplates(): void
    {
        $this->stubEmptyCollection();
        $this->templateConfig->method('getAvailableTemplates')->willReturn([]);

        $options = $this->source->toOptionArray();

        $groups = array_filter($options, fn($o) => is_array($o['value'] ?? null));
        $this->assertEmpty($groups);
    }

    public function testDefaultTemplatesGroupAddedFromConfig(): void
    {
        $this->stubEmptyCollection();
        $this->templateConfig->method('getAvailableTemplates')->willReturn([
            ['value' => 'sales_email_order_guest_template', 'label' => 'New Order for Guest'],
            ['value' => 'sales_email_order_template',       'label' => 'New Order'],
        ]);

        $options = $this->source->toOptionArray();

        $groups = array_filter($options, fn($o) => is_array($o['value'] ?? null));
        $this->assertNotEmpty($groups);

        $defaultGroup = array_values($groups)[0];
        $this->assertEquals('Default Templates', $defaultGroup['label']);
        $this->assertCount(2, $defaultGroup['value']);
    }

    public function testBaseTemplateIdentifierGetsActiveSuffix(): void
    {
        $this->stubEmptyCollection();
        $this->templateConfig->method('getAvailableTemplates')->willReturn([
            ['value' => 'sales_email_order_guest_template', 'label' => 'New Order for Guest'],
        ]);

        $options = $this->source->toOptionArray();

        $groups  = array_values(array_filter($options, fn($o) => is_array($o['value'] ?? null)));
        $entries = $groups[0]['value'];

        $base = array_filter($entries, fn($e) => $e['value'] === 'sales_email_order_guest_template');
        $this->assertStringContainsString('[active]', array_values($base)[0]['label']);
    }

    public function testThemeVariantDoesNotGetActiveSuffix(): void
    {
        $this->stubEmptyCollection();
        $this->templateConfig->method('getAvailableTemplates')->willReturn([
            ['value' => 'sales_email_order_guest_template/GateSoftware/helikon', 'label' => 'New Order for Guest (GateSoftware/helikon)'],
        ]);

        $options = $this->source->toOptionArray();

        $groups  = array_values(array_filter($options, fn($o) => is_array($o['value'] ?? null)));
        $entries = $groups[0]['value'];

        $this->assertStringNotContainsString('[active]', $entries[0]['label']);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function stubEmptyCollection(): void
    {
        $collection = $this->createMock(TemplateCollection::class);
        $collection->method('getIterator')->willReturn(new \ArrayIterator([]));
        $this->collectionFactory->method('create')->willReturn($collection);
    }

    /** @param array<int, array{id: string, code: string}> $templates */
    private function stubCollectionWithTemplates(array $templates): void
    {
        $items = array_map(function (array $t) {
            $mock = $this->getMockBuilder(\Magento\Email\Model\Template::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['getId'])
                ->addMethods(['getTemplateCode'])
                ->getMock();
            $mock->method('getId')->willReturn($t['id']);
            $mock->method('getTemplateCode')->willReturn($t['code']);
            return $mock;
        }, $templates);

        $collection = $this->createMock(TemplateCollection::class);
        $collection->method('getIterator')->willReturn(new \ArrayIterator($items));
        $this->collectionFactory->method('create')->willReturn($collection);
    }
}
