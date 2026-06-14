<?php
declare(strict_types=1);

namespace MylSoft\Attachments\Test\Unit\Controller\Adminhtml\Attachment;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\MediaStorage\Model\File\Uploader;
use Magento\MediaStorage\Model\File\UploaderFactory;
use MylSoft\Attachments\Controller\Adminhtml\Attachment\Upload;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UploadTest extends TestCase
{
    private JsonFactory|MockObject $jsonFactory;
    private Json|MockObject $jsonResult;
    private UploaderFactory|MockObject $uploaderFactory;
    private Filesystem|MockObject $filesystem;
    private Upload $controller;

    protected function setUp(): void
    {
        $this->jsonResult = $this->createMock(Json::class);

        $this->jsonFactory = $this->createMock(JsonFactory::class);
        $this->jsonFactory->method('create')->willReturn($this->jsonResult);

        $this->uploaderFactory = $this->createMock(UploaderFactory::class);

        $writeDir = $this->createMock(WriteInterface::class);
        $writeDir->method('getAbsolutePath')->willReturn('/tmp/media/mylsoft/attachments');

        $this->filesystem = $this->createMock(Filesystem::class);
        $this->filesystem->method('getDirectoryWrite')->willReturn($writeDir);

        $urlBuilder = $this->createMock(UrlInterface::class);
        $urlBuilder->method('getBaseUrl')->willReturn('https://example.com/pub/media/');

        $context = $this->createMock(Context::class);
        $context->method('getUrl')->willReturn($urlBuilder);

        $objectManager    = new ObjectManager($this);
        $this->controller = $objectManager->getObject(Upload::class, [
            'context'         => $context,
            'jsonFactory'     => $this->jsonFactory,
            'uploaderFactory' => $this->uploaderFactory,
            'filesystem'      => $this->filesystem,
        ]);
    }

    public function testExecuteSetsUploadDataOnSuccess(): void
    {
        $uploader = $this->createMock(Uploader::class);
        $uploader->method('save')->willReturn([
            'name' => 'invoice.pdf',
            'file' => 'invoice_abc123.pdf',
            'size' => 12345,
            'type' => 'application/pdf',
        ]);

        $this->uploaderFactory->method('create')->willReturn($uploader);

        $this->jsonResult->expects($this->once())
            ->method('setData')
            ->with($this->arrayHasKey('name'));

        $result = $this->controller->execute();
        $this->assertSame($this->jsonResult, $result);
    }

    public function testExecuteSetsErrorDataOnException(): void
    {
        $this->uploaderFactory->method('create')
            ->willThrowException(new \Exception('No file uploaded', 99));

        $this->jsonResult->expects($this->once())
            ->method('setData')
            ->with($this->callback(function (array $data): bool {
                return isset($data['error']) && isset($data['errorcode']);
            }));

        $result = $this->controller->execute();
        $this->assertSame($this->jsonResult, $result);
    }
}
