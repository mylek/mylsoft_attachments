<?php
declare(strict_types=1);

namespace MylSoft\Attachments\Test\Unit\Controller\Adminhtml\Attachment;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Page\Config as PageConfig;
use Magento\Framework\View\Page\Title;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use MylSoft\Attachments\Api\AttachmentRepositoryInterface;
use MylSoft\Attachments\Api\Data\AttachmentInterface;
use MylSoft\Attachments\Controller\Adminhtml\Attachment\Edit;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EditTest extends TestCase
{
    private RequestInterface|MockObject $request;
    private ManagerInterface|MockObject $messageManager;
    private Redirect|MockObject $redirect;
    private PageFactory|MockObject $pageFactory;
    private AttachmentRepositoryInterface|MockObject $attachmentRepository;
    private Edit $controller;

    protected function setUp(): void
    {
        $this->request        = $this->createMock(RequestInterface::class);
        $this->messageManager = $this->createMock(ManagerInterface::class);

        $this->redirect = $this->createMock(Redirect::class);
        $this->redirect->method('setPath')->willReturnSelf();

        $redirectFactory = $this->createMock(RedirectFactory::class);
        $redirectFactory->method('create')->willReturn($this->redirect);

        $this->pageFactory          = $this->createMock(PageFactory::class);
        $this->attachmentRepository = $this->createMock(AttachmentRepositoryInterface::class);

        $context = $this->createMock(Context::class);
        $context->method('getRequest')->willReturn($this->request);
        $context->method('getMessageManager')->willReturn($this->messageManager);
        $context->method('getResultRedirectFactory')->willReturn($redirectFactory);

        $objectManager    = new ObjectManager($this);
        $this->controller = $objectManager->getObject(Edit::class, [
            'context'              => $context,
            'resultPageFactory'    => $this->pageFactory,
            'attachmentRepository' => $this->attachmentRepository,
        ]);
    }

    public function testExecuteReturnsPageForValidId(): void
    {
        $this->request->method('getParam')->with('id')->willReturn('5');

        $attachment = $this->createMock(AttachmentInterface::class);
        $attachment->method('getName')->willReturn('Test Attachment');
        $this->attachmentRepository->method('getById')->with(5)->willReturn($attachment);

        $page = $this->buildPageMock();
        $this->pageFactory->method('create')->willReturn($page);

        $this->assertSame($page, $this->controller->execute());
    }

    public function testExecuteSetsActiveMenuForValidId(): void
    {
        $this->request->method('getParam')->with('id')->willReturn('5');

        $attachment = $this->createMock(AttachmentInterface::class);
        $attachment->method('getName')->willReturn('Test');
        $this->attachmentRepository->method('getById')->willReturn($attachment);

        $page = $this->buildPageMock();
        $page->expects($this->once())->method('setActiveMenu')->with('MylSoft_Attachments::attachments');
        $this->pageFactory->method('create')->willReturn($page);

        $this->controller->execute();
    }

    public function testExecuteRedirectsWithErrorWhenNotFound(): void
    {
        $this->request->method('getParam')->with('id')->willReturn('99');
        $this->attachmentRepository->method('getById')
            ->willThrowException(new NoSuchEntityException());

        $this->messageManager->expects($this->once())->method('addErrorMessage');

        $result = $this->controller->execute();
        $this->assertSame($this->redirect, $result);
    }

    private function buildPageMock(): Page|MockObject
    {
        $title  = $this->createMock(Title::class);
        $config = $this->createMock(PageConfig::class);
        $config->method('getTitle')->willReturn($title);

        $page = $this->getMockBuilder(Page::class)
            ->disableOriginalConstructor()
            ->getMock();
        $page->method('setActiveMenu')->willReturnSelf();
        $page->method('getConfig')->willReturn($config);

        return $page;
    }
}
