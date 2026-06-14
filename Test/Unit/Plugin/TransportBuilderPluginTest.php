<?php
declare(strict_types=1);

namespace MylSoft\Attachments\Test\Unit\Plugin;

use Laminas\Mail\Message as LaminasMessage;
use Laminas\Mime\Message as LaminasMimeMessage;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Framework\Mail\Message as MagentoMessage;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Mail\TransportInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use MylSoft\Attachments\Api\AttachmentRepositoryInterface;
use MylSoft\Attachments\Api\Data\AttachmentInterface;
use MylSoft\Attachments\Plugin\TransportBuilderPlugin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class TransportBuilderPluginTest extends TestCase
{
    private AttachmentRepositoryInterface|MockObject $repository;
    private Filesystem|MockObject $filesystem;
    private TimezoneInterface|MockObject $timezone;
    private LoggerInterface|MockObject $logger;
    private TransportBuilderPlugin $plugin;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(AttachmentRepositoryInterface::class);
        $this->filesystem = $this->createMock(Filesystem::class);
        $this->timezone   = $this->createMock(TimezoneInterface::class);
        $this->logger     = $this->createMock(LoggerInterface::class);

        $this->plugin = new TransportBuilderPlugin(
            $this->repository,
            $this->filesystem,
            $this->timezone,
            $this->logger
        );

        $this->timezone->method('date')->willReturn(new \DateTime('2026-06-14'));
    }

    public function testReturnsTransportImmediatelyWhenTemplateIdIsEmpty(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $builder   = $this->buildBuilderMock(null);

        $this->repository->expects($this->never())->method('getActiveByEmailTemplate');

        $result = $this->plugin->aroundGetTransport($builder, fn() => $transport);

        $this->assertSame($transport, $result);
    }

    public function testReturnsTransportWhenNoAttachmentsFound(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $builder   = $this->buildBuilderMock('sales_email_order_guest_template');

        $this->repository->method('getActiveByEmailTemplate')->willReturn([]);

        $result = $this->plugin->aroundGetTransport($builder, fn() => $transport);

        $this->assertSame($transport, $result);
    }

    public function testRepositoryIsCalledWithCorrectTemplateId(): void
    {
        $builder = $this->buildBuilderMock('sales_email_order_guest_template');

        $this->repository->expects($this->once())
            ->method('getActiveByEmailTemplate')
            ->with('sales_email_order_guest_template')
            ->willReturn([]);

        $this->plugin->aroundGetTransport(
            $builder,
            fn() => $this->createMock(TransportInterface::class)
        );
    }

    public function testLogsErrorAndReturnsTransportWhenRepositoryThrows(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $builder   = $this->buildBuilderMock('sales_email_order_guest_template');

        $this->repository->method('getActiveByEmailTemplate')
            ->willThrowException(new \RuntimeException('DB connection lost'));

        $this->logger->expects($this->once())->method('error');

        $result = $this->plugin->aroundGetTransport($builder, fn() => $transport);

        $this->assertSame($transport, $result);
    }

    public function testAttachmentSkippedWhenTodayIsBeforeValidFrom(): void
    {
        // Today: 2026-06-14, valid_from: 2026-07-01 (future)
        $attachment = $this->makeAttachment(validFrom: '2026-07-01', validTo: null);

        [, $transport, $mediaDir] = $this->buildMessageChain([$attachment]);

        $mediaDir->expects($this->never())->method('isFile');

        $this->plugin->aroundGetTransport(
            $this->buildBuilderMock('sales_email_order_guest_template'),
            fn() => $transport
        );
    }

    public function testAttachmentSkippedWhenTodayIsAfterValidTo(): void
    {
        // Today: 2026-06-14, valid_to: 2026-06-01 (past)
        $attachment = $this->makeAttachment(validFrom: null, validTo: '2026-06-01');

        [, $transport, $mediaDir] = $this->buildMessageChain([$attachment]);

        $mediaDir->expects($this->never())->method('isFile');

        $this->plugin->aroundGetTransport(
            $this->buildBuilderMock('sales_email_order_guest_template'),
            fn() => $transport
        );
    }

    public function testAttachmentIncludedWhenTodayMatchesValidFrom(): void
    {
        // Today: 2026-06-14, valid_from: 2026-06-14 (same day = included)
        $attachment = $this->makeAttachment(validFrom: '2026-06-14', validTo: null);

        [, $transport, $mediaDir] = $this->buildMessageChain([$attachment]);

        $mediaDir->expects($this->once())->method('isFile')->willReturn(false);

        $this->plugin->aroundGetTransport(
            $this->buildBuilderMock('sales_email_order_guest_template'),
            fn() => $transport
        );
    }

    public function testAttachmentIncludedWhenTodayMatchesValidTo(): void
    {
        // Today: 2026-06-14, valid_to: 2026-06-14 (same day = included)
        $attachment = $this->makeAttachment(validFrom: null, validTo: '2026-06-14');

        [, $transport, $mediaDir] = $this->buildMessageChain([$attachment]);

        $mediaDir->expects($this->once())->method('isFile')->willReturn(false);

        $this->plugin->aroundGetTransport(
            $this->buildBuilderMock('sales_email_order_guest_template'),
            fn() => $transport
        );
    }

    public function testAttachmentIncludedWhenWithinDateRange(): void
    {
        // Today: 2026-06-14, range: 2026-06-01 – 2026-06-30
        $attachment = $this->makeAttachment(validFrom: '2026-06-01', validTo: '2026-06-30');

        [, $transport, $mediaDir] = $this->buildMessageChain([$attachment]);

        $mediaDir->expects($this->once())->method('isFile')->willReturn(false);

        $this->plugin->aroundGetTransport(
            $this->buildBuilderMock('sales_email_order_guest_template'),
            fn() => $transport
        );
    }

    public function testAttachmentAlwaysIncludedWhenNoDatesSet(): void
    {
        $attachment = $this->makeAttachment(validFrom: null, validTo: null);

        [, $transport, $mediaDir] = $this->buildMessageChain([$attachment]);

        $mediaDir->expects($this->once())->method('isFile')->willReturn(false);

        $this->plugin->aroundGetTransport(
            $this->buildBuilderMock('sales_email_order_guest_template'),
            fn() => $transport
        );
    }

    public function testDisplayFilenameIsUsedInsteadOfFilenameWhenSet(): void
    {
        $attachment = $this->makeAttachment(validFrom: null, validTo: null);
        $attachment->method('getFilepath')->willReturn('file.pdf');
        $attachment->method('getDisplayFilename')->willReturn('custom-name.pdf');
        $attachment->method('getFilename')->willReturn('original.pdf');

        [, $transport, $mediaDir, , $mimeMessage] = $this->buildMessageChain([$attachment]);

        $mediaDir->method('isFile')->willReturn(true);
        $mediaDir->method('readFile')->willReturn('%PDF-content');
        $mediaDir->method('stat')->willReturn(['mimetype' => 'application/pdf']);

        $mimeMessage->expects($this->once())
            ->method('setParts')
            ->with($this->callback(function (array $parts): bool {
                $last = end($parts);
                return $last->getFileName() === 'custom-name.pdf';
            }));

        $this->plugin->aroundGetTransport(
            $this->buildBuilderMock('sales_email_order_guest_template'),
            fn() => $transport
        );
    }

    public function testOriginalFilenameUsedWhenDisplayFilenameIsNull(): void
    {
        $attachment = $this->makeAttachment(validFrom: null, validTo: null);
        $attachment->method('getFilepath')->willReturn('file.pdf');
        $attachment->method('getDisplayFilename')->willReturn(null);
        $attachment->method('getFilename')->willReturn('original.pdf');

        [, $transport, $mediaDir, , $mimeMessage] = $this->buildMessageChain([$attachment]);

        $mediaDir->method('isFile')->willReturn(true);
        $mediaDir->method('readFile')->willReturn('%PDF-content');
        $mediaDir->method('stat')->willReturn(['mimetype' => 'application/pdf']);

        $mimeMessage->expects($this->once())
            ->method('setParts')
            ->with($this->callback(function (array $parts): bool {
                $last = end($parts);
                return $last->getFileName() === 'original.pdf';
            }));

        $this->plugin->aroundGetTransport(
            $this->buildBuilderMock('sales_email_order_guest_template'),
            fn() => $transport
        );
    }

    public function testLogsWarningAndSkipsWhenFileNotFound(): void
    {
        $attachment = $this->makeAttachment(validFrom: null, validTo: null);
        $attachment->method('getFilepath')->willReturn('missing.pdf');

        [, $transport, $mediaDir] = $this->buildMessageChain([$attachment]);

        $mediaDir->method('isFile')->willReturn(false);

        $this->logger->expects($this->once())->method('warning');

        $this->plugin->aroundGetTransport(
            $this->buildBuilderMock('sales_email_order_guest_template'),
            fn() => $transport
        );
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function buildBuilderMock(?string $templateIdentifier): TransportBuilder|MockObject
    {
        $builder = $this->getMockBuilder(TransportBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $prop = new \ReflectionProperty(TransportBuilder::class, 'templateIdentifier');
        $prop->setAccessible(true);
        $prop->setValue($builder, $templateIdentifier);

        return $builder;
    }

    private function makeAttachment(
        ?string $validFrom,
        ?string $validTo
    ): AttachmentInterface|MockObject {
        $attachment = $this->createMock(AttachmentInterface::class);
        $attachment->method('getValidFrom')->willReturn($validFrom);
        $attachment->method('getValidTo')->willReturn($validTo);
        return $attachment;
    }

    /**
     * Builds the full Transport → MagentoMessage → zendMessage → MimeMessage mock chain
     * and wires the repository to return the given attachments.
     *
     * @param  AttachmentInterface[] $attachments
     * @return array{TransportBuilder, TransportInterface, ReadInterface, LaminasMessage, LaminasMimeMessage}
     */
    private function buildMessageChain(array $attachments): array
    {
        $this->repository->method('getActiveByEmailTemplate')->willReturn($attachments);

        $mimeMessage = $this->createMock(LaminasMimeMessage::class);
        $mimeMessage->method('getParts')->willReturn([]);

        $laminasMessage = $this->createMock(LaminasMessage::class);
        $laminasMessage->method('getBody')->willReturn($mimeMessage);

        $magentoMessage = $this->getMockBuilder(MagentoMessage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $prop = new \ReflectionProperty(MagentoMessage::class, 'zendMessage');
        $prop->setAccessible(true);
        $prop->setValue($magentoMessage, $laminasMessage);

        $transport = $this->createMock(TransportInterface::class);
        $transport->method('getMessage')->willReturn($magentoMessage);

        $mediaDir = $this->createMock(ReadInterface::class);
        $this->filesystem->method('getDirectoryRead')
            ->with(DirectoryList::MEDIA)
            ->willReturn($mediaDir);

        return [
            $this->buildBuilderMock('sales_email_order_guest_template'),
            $transport,
            $mediaDir,
            $laminasMessage,
            $mimeMessage,
        ];
    }
}
