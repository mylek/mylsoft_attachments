<?php
declare(strict_types=1);

namespace MylSoft\Attachments\Test\Unit\Controller\Adminhtml\Attachment;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use MylSoft\Attachments\Api\AttachmentRepositoryInterface;
use MylSoft\Attachments\Api\Data\AttachmentInterface;
use MylSoft\Attachments\Controller\Adminhtml\Attachment\Delete;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeleteTest extends TestCase
{
    private RequestInterface|MockObject $request;
    private ManagerInterface|MockObject $messageManager;
    private Redirect|MockObject $redirect;
    private AttachmentRepositoryInterface|MockObject $attachmentRepository;
    private DirectoryList|MockObject $directoryList;
    private Delete $controller;

    protected function setUp(): void
    {
        $this->request        = $this->createMock(RequestInterface::class);
        $this->messageManager = $this->createMock(ManagerInterface::class);

        $this->redirect = $this->createMock(Redirect::class);
        $this->redirect->method('setPath')->willReturnSelf();

        $redirectFactory = $this->createMock(RedirectFactory::class);
        $redirectFactory->method('create')->willReturn($this->redirect);

        $this->attachmentRepository = $this->createMock(AttachmentRepositoryInterface::class);
        $this->directoryList        = $this->createMock(DirectoryList::class);
        $this->directoryList->method('getPath')->with('media')->willReturn('/tmp/media');

        $context = $this->createMock(Context::class);
        $context->method('getRequest')->willReturn($this->request);
        $context->method('getMessageManager')->willReturn($this->messageManager);
        $context->method('getResultRedirectFactory')->willReturn($redirectFactory);

        $objectManager    = new ObjectManager($this);
        $this->controller = $objectManager->getObject(Delete::class, [
            'context'              => $context,
            'attachmentRepository' => $this->attachmentRepository,
            'directoryList'        => $this->directoryList,
        ]);
    }

    public function testExecuteAddsErrorWhenNoId(): void
    {
        $this->request->method('getParam')->with('id')->willReturn('0');

        $this->messageManager->expects($this->once())->method('addErrorMessage');
        $this->attachmentRepository->expects($this->never())->method('getById');

        $result = $this->controller->execute();
        $this->assertSame($this->redirect, $result);
    }

    public function testExecuteDeletesAttachmentSuccessfully(): void
    {
        $this->request->method('getParam')->with('id')->willReturn('5');

        $attachment = $this->createMock(AttachmentInterface::class);
        $attachment->method('getFilepath')->willReturn('');
        $this->attachmentRepository->method('getById')->with(5)->willReturn($attachment);

        $this->attachmentRepository->expects($this->once())->method('delete')->with($attachment);
        $this->messageManager->expects($this->once())->method('addSuccessMessage');

        $result = $this->controller->execute();
        $this->assertSame($this->redirect, $result);
    }

    public function testExecuteAddsErrorWhenNotFound(): void
    {
        $this->request->method('getParam')->with('id')->willReturn('5');
        $this->attachmentRepository->method('getById')
            ->willThrowException(new NoSuchEntityException());

        $this->messageManager->expects($this->once())->method('addErrorMessage');
        $this->attachmentRepository->expects($this->never())->method('delete');

        $result = $this->controller->execute();
        $this->assertSame($this->redirect, $result);
    }

    public function testExecuteAddsErrorOnGeneralException(): void
    {
        $this->request->method('getParam')->with('id')->willReturn('5');

        $attachment = $this->createMock(AttachmentInterface::class);
        $attachment->method('getFilepath')->willReturn('');
        $this->attachmentRepository->method('getById')->willReturn($attachment);
        $this->attachmentRepository->method('delete')
            ->willThrowException(new \RuntimeException('Unexpected error'));

        $this->messageManager->expects($this->once())->method('addErrorMessage');

        $result = $this->controller->execute();
        $this->assertSame($this->redirect, $result);
    }
}
