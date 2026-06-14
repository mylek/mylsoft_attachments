<?php
declare(strict_types=1);

namespace MylSoft\Attachments\Test\Unit\Controller\Adminhtml\Attachment;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Ui\Component\MassAction\Filter;
use MylSoft\Attachments\Api\AttachmentRepositoryInterface;
use MylSoft\Attachments\Api\Data\AttachmentInterface;
use MylSoft\Attachments\Controller\Adminhtml\Attachment\MassDelete;
use MylSoft\Attachments\Model\ResourceModel\Attachment\Collection;
use MylSoft\Attachments\Model\ResourceModel\Attachment\CollectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MassDeleteTest extends TestCase
{
    private ManagerInterface|MockObject $messageManager;
    private Redirect|MockObject $redirect;
    private Filter|MockObject $filter;
    private CollectionFactory|MockObject $collectionFactory;
    private AttachmentRepositoryInterface|MockObject $attachmentRepository;
    private DirectoryList|MockObject $directoryList;
    private MassDelete $controller;

    protected function setUp(): void
    {
        $this->messageManager = $this->createMock(ManagerInterface::class);

        $this->redirect = $this->createMock(Redirect::class);
        $this->redirect->method('setPath')->willReturnSelf();

        $redirectFactory = $this->createMock(RedirectFactory::class);
        $redirectFactory->method('create')->willReturn($this->redirect);

        $this->filter             = $this->createMock(Filter::class);
        $this->collectionFactory  = $this->createMock(CollectionFactory::class);
        $this->attachmentRepository = $this->createMock(AttachmentRepositoryInterface::class);

        $this->directoryList = $this->createMock(DirectoryList::class);
        $this->directoryList->method('getPath')->with('media')->willReturn('/tmp/media');

        $context = $this->createMock(Context::class);
        $context->method('getMessageManager')->willReturn($this->messageManager);
        $context->method('getResultRedirectFactory')->willReturn($redirectFactory);

        $objectManager    = new ObjectManager($this);
        $this->controller = $objectManager->getObject(MassDelete::class, [
            'context'              => $context,
            'filter'               => $this->filter,
            'collectionFactory'    => $this->collectionFactory,
            'attachmentRepository' => $this->attachmentRepository,
            'directoryList'        => $this->directoryList,
        ]);
    }

    public function testExecuteDeletesAllSelectedAndShowsCount(): void
    {
        $attachment1 = $this->createMock(AttachmentInterface::class);
        $attachment1->method('getFilepath')->willReturn('');

        $attachment2 = $this->createMock(AttachmentInterface::class);
        $attachment2->method('getFilepath')->willReturn('');

        $baseCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $filteredCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $filteredCollection->method('getIterator')
            ->willReturn(new \ArrayIterator([$attachment1, $attachment2]));

        $this->collectionFactory->method('create')->willReturn($baseCollection);
        $this->filter->method('getCollection')->with($baseCollection)->willReturn($filteredCollection);

        $this->attachmentRepository->expects($this->exactly(2))->method('delete');

        $this->messageManager->expects($this->once())
            ->method('addSuccessMessage')
            ->with(__('A total of %1 attachment(s) have been deleted.', 2));

        $result = $this->controller->execute();
        $this->assertSame($this->redirect, $result);
    }

    public function testExecuteHandlesEmptySelection(): void
    {
        $baseCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $filteredCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $filteredCollection->method('getIterator')->willReturn(new \ArrayIterator([]));

        $this->collectionFactory->method('create')->willReturn($baseCollection);
        $this->filter->method('getCollection')->willReturn($filteredCollection);

        $this->attachmentRepository->expects($this->never())->method('delete');

        $this->messageManager->expects($this->once())
            ->method('addSuccessMessage')
            ->with(__('A total of %1 attachment(s) have been deleted.', 0));

        $this->controller->execute();
    }
}
