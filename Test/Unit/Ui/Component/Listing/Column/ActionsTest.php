<?php
declare(strict_types=1);

namespace MylSoft\Attachments\Test\Unit\Ui\Component\Listing\Column;

use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use MylSoft\Attachments\Ui\Component\Listing\Column\Actions;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ActionsTest extends TestCase
{
    private UrlInterface|MockObject $urlBuilder;
    private Escaper|MockObject $escaper;
    private Actions $actions;

    protected function setUp(): void
    {
        $this->urlBuilder = $this->createMock(UrlInterface::class);
        $this->escaper    = $this->createMock(Escaper::class);

        $this->actions = new Actions(
            $this->createMock(ContextInterface::class),
            $this->createMock(UiComponentFactory::class),
            $this->urlBuilder,
            $this->escaper,
            [],
            ['name' => 'actions']
        );
    }

    public function testPrepareDataSourceReturnsUnchangedWhenNoItemsKey(): void
    {
        $dataSource = ['data' => []];

        $result = $this->actions->prepareDataSource($dataSource);

        $this->assertSame($dataSource, $result);
    }

    public function testPrepareDataSourceAddsEditAndDeleteLinksPerItem(): void
    {
        $this->urlBuilder->method('getUrl')
            ->willReturnCallback(function (string $route, array $params): string {
                return 'https://example.com/' . $route . '/id/' . ($params['id'] ?? '');
            });

        $this->escaper->method('escapeHtml')->willReturnArgument(0);

        $dataSource = [
            'data' => [
                'items' => [
                    ['attachment_id' => 1, 'name' => 'Invoice'],
                    ['attachment_id' => 2, 'name' => 'Terms'],
                ],
            ],
        ];

        $result = $this->actions->prepareDataSource($dataSource);
        $items  = $result['data']['items'];

        $this->assertArrayHasKey('actions', $items[0]);
        $this->assertArrayHasKey('edit', $items[0]['actions']);
        $this->assertArrayHasKey('delete', $items[0]['actions']);

        $this->assertArrayHasKey('actions', $items[1]);
        $this->assertArrayHasKey('href', $items[0]['actions']['edit']);
        $this->assertArrayHasKey('href', $items[0]['actions']['delete']);
    }

    public function testPrepareDataSourceDeleteActionHasPostFlag(): void
    {
        $this->urlBuilder->method('getUrl')->willReturn('https://example.com/url');
        $this->escaper->method('escapeHtml')->willReturnArgument(0);

        $dataSource = [
            'data' => [
                'items' => [
                    ['attachment_id' => 1, 'name' => 'Invoice'],
                ],
            ],
        ];

        $result = $this->actions->prepareDataSource($dataSource);
        $delete = $result['data']['items'][0]['actions']['delete'];

        $this->assertTrue($delete['post']);
        $this->assertArrayHasKey('confirm', $delete);
    }
}
