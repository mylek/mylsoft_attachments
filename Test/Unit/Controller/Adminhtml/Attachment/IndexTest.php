<?php
declare(strict_types=1);

namespace MylSoft\Attachments\Test\Unit\Controller\Adminhtml\Attachment;

use Magento\Backend\App\Action\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Page\Config as PageConfig;
use Magento\Framework\View\Page\Title;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use MylSoft\Attachments\Controller\Adminhtml\Attachment\Index;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class IndexTest extends TestCase
{
    private PageFactory|MockObject $pageFactory;
    private Index $controller;

    protected function setUp(): void
    {
        $this->pageFactory = $this->createMock(PageFactory::class);

        $objectManager  = new ObjectManager($this);
        $this->controller = $objectManager->getObject(Index::class, [
            'resultPageFactory' => $this->pageFactory,
        ]);
    }

    public function testExecuteReturnsPageResult(): void
    {
        $page = $this->buildPageMock();
        $this->pageFactory->method('create')->willReturn($page);

        $result = $this->controller->execute();

        $this->assertSame($page, $result);
    }

    public function testExecuteSetsActiveMenu(): void
    {
        $page = $this->buildPageMock();
        $this->pageFactory->method('create')->willReturn($page);

        $page->expects($this->once())
            ->method('setActiveMenu')
            ->with('MylSoft_Attachments::attachments');

        $this->controller->execute();
    }

    public function testExecutePreparesPageTitle(): void
    {
        $title = $this->createMock(Title::class);
        $title->expects($this->once())->method('prepend')->with(__('Email Attachments'));

        $config = $this->createMock(PageConfig::class);
        $config->method('getTitle')->willReturn($title);

        $page = $this->buildPageMock($config);
        $this->pageFactory->method('create')->willReturn($page);

        $this->controller->execute();
    }

    private function buildPageMock(?PageConfig $config = null): Page|MockObject
    {
        if ($config === null) {
            $title  = $this->createMock(Title::class);
            $config = $this->createMock(PageConfig::class);
            $config->method('getTitle')->willReturn($title);
        }

        $page = $this->getMockBuilder(Page::class)
            ->disableOriginalConstructor()
            ->getMock();
        $page->method('setActiveMenu')->willReturnSelf();
        $page->method('getConfig')->willReturn($config);

        return $page;
    }
}
