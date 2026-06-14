<?php
declare(strict_types=1);

namespace MylSoft\Attachments\Test\Unit\Controller\Adminhtml\Attachment;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Page\Config as PageConfig;
use Magento\Framework\View\Page\Title;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use MylSoft\Attachments\Controller\Adminhtml\Attachment\NewAction;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class NewActionTest extends TestCase
{
    private PageFactory|MockObject $pageFactory;
    private NewAction $controller;

    protected function setUp(): void
    {
        $this->pageFactory  = $this->createMock(PageFactory::class);
        $objectManager      = new ObjectManager($this);
        $this->controller   = $objectManager->getObject(NewAction::class, [
            'resultPageFactory' => $this->pageFactory,
        ]);
    }

    public function testExecuteReturnsPageResult(): void
    {
        $page = $this->buildPageMock();
        $this->pageFactory->method('create')->willReturn($page);

        $this->assertSame($page, $this->controller->execute());
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
        $title->expects($this->once())->method('prepend')->with(__('New Email Attachment'));

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
