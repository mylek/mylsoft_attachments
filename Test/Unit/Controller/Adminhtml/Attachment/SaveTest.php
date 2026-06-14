<?php
declare(strict_types=1);

namespace MylSoft\Attachments\Test\Unit\Controller\Adminhtml\Attachment;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use MylSoft\Attachments\Api\AttachmentRepositoryInterface;
use MylSoft\Attachments\Api\Data\AttachmentInterface;
use MylSoft\Attachments\Controller\Adminhtml\Attachment\Save;
use MylSoft\Attachments\Model\Attachment;
use MylSoft\Attachments\Model\AttachmentFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SaveTest extends TestCase
{
    private HttpRequest|MockObject $request;
    private ManagerInterface|MockObject $messageManager;
    private Redirect|MockObject $redirect;
    private AttachmentRepositoryInterface|MockObject $attachmentRepository;
    private AttachmentFactory|MockObject $attachmentFactory;
    private Save $controller;

    protected function setUp(): void
    {
        $this->request        = $this->getMockBuilder(HttpRequest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->messageManager = $this->createMock(ManagerInterface::class);

        $this->redirect = $this->createMock(Redirect::class);
        $this->redirect->method('setPath')->willReturnSelf();

        $redirectFactory = $this->createMock(RedirectFactory::class);
        $redirectFactory->method('create')->willReturn($this->redirect);

        $this->attachmentRepository = $this->createMock(AttachmentRepositoryInterface::class);
        $this->attachmentFactory    = $this->createMock(AttachmentFactory::class);

        $context = $this->createMock(Context::class);
        $context->method('getRequest')->willReturn($this->request);
        $context->method('getMessageManager')->willReturn($this->messageManager);
        $context->method('getResultRedirectFactory')->willReturn($redirectFactory);

        $objectManager    = new ObjectManager($this);
        $this->controller = $objectManager->getObject(Save::class, [
            'context'              => $context,
            'attachmentRepository' => $this->attachmentRepository,
            'attachmentFactory'    => $this->attachmentFactory,
        ]);
    }

    public function testExecuteRedirectsToIndexWhenNoPostData(): void
    {
        $this->request->method('getPostValue')->willReturn([]);

        $result = $this->controller->execute();

        $this->assertSame($this->redirect, $result);
    }

    public function testExecuteCreatesNewAttachmentWhenNoId(): void
    {
        $attachment = $this->buildAttachmentMock();
        $this->attachmentFactory->method('create')->willReturn($attachment);

        $this->request->method('getPostValue')->willReturn([
            AttachmentInterface::NAME           => 'Invoice',
            AttachmentInterface::EMAIL_TEMPLATE => 'sales_email_order_template',
            AttachmentInterface::IS_ACTIVE      => '1',
        ]);
        $this->request->method('getParam')->with('back')->willReturn(false);

        $this->attachmentRepository->expects($this->once())->method('save')->with($attachment);
        $this->messageManager->expects($this->once())->method('addSuccessMessage');

        $result = $this->controller->execute();
        $this->assertSame($this->redirect, $result);
    }

    public function testExecuteLoadsExistingAttachmentWhenIdProvided(): void
    {
        $attachment = $this->buildAttachmentMock();
        $this->attachmentRepository->method('getById')->with(3)->willReturn($attachment);

        $this->request->method('getPostValue')->willReturn([
            AttachmentInterface::ATTACHMENT_ID  => '3',
            AttachmentInterface::NAME           => 'Invoice',
            AttachmentInterface::EMAIL_TEMPLATE => 'sales_email_order_template',
            AttachmentInterface::IS_ACTIVE      => '1',
        ]);
        $this->request->method('getParam')->with('back')->willReturn(false);

        $this->attachmentRepository->expects($this->once())->method('save');
        $this->attachmentFactory->expects($this->never())->method('create');

        $this->controller->execute();
    }

    public function testExecuteRedirectsToEditOnSaveFailure(): void
    {
        $attachment = $this->buildAttachmentMock();
        $this->attachmentFactory->method('create')->willReturn($attachment);

        $this->request->method('getPostValue')->willReturn([
            AttachmentInterface::NAME           => 'Invoice',
            AttachmentInterface::EMAIL_TEMPLATE => 'tpl',
        ]);

        $this->attachmentRepository->method('save')
            ->willThrowException(new CouldNotSaveException(__('DB error')));

        $this->messageManager->expects($this->once())->method('addErrorMessage');

        $result = $this->controller->execute();
        $this->assertSame($this->redirect, $result);
    }

    public function testExecuteRedirectsToEditWhenBackParamSet(): void
    {
        $attachment = $this->buildAttachmentMock();
        $attachment->method('getId')->willReturn(7);
        $this->attachmentFactory->method('create')->willReturn($attachment);

        $this->request->method('getPostValue')->willReturn([
            AttachmentInterface::NAME           => 'Invoice',
            AttachmentInterface::EMAIL_TEMPLATE => 'tpl',
        ]);
        $this->request->method('getParam')->with('back')->willReturn('1');

        $this->redirect->expects($this->once())
            ->method('setPath')
            ->with('*/*/edit', ['id' => 7])
            ->willReturnSelf();

        $this->controller->execute();
    }

    public function testExecuteRedirectsWithErrorWhenIdNotFound(): void
    {
        $this->attachmentRepository->method('getById')
            ->willThrowException(new NoSuchEntityException());

        $this->request->method('getPostValue')->willReturn([
            AttachmentInterface::ATTACHMENT_ID  => '999',
            AttachmentInterface::NAME           => 'Invoice',
        ]);

        $this->messageManager->expects($this->once())->method('addErrorMessage');

        $result = $this->controller->execute();
        $this->assertSame($this->redirect, $result);
    }

    private function buildAttachmentMock(): Attachment|MockObject
    {
        return $this->getMockBuilder(Attachment::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
