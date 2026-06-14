<?php
declare(strict_types=1);

namespace MylSoft\Attachments\Plugin;

use Laminas\Mail\Message as LaminasMessage;
use Laminas\Mime\Message as LaminasMimeMessage;
use Laminas\Mime\Mime;
use Laminas\Mime\Part as MimePart;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\Mail\Message as MagentoMessage;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Mail\TransportInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use MylSoft\Attachments\Api\AttachmentRepositoryInterface;
use Psr\Log\LoggerInterface;

class TransportBuilderPlugin
{
    /**
     * @param AttachmentRepositoryInterface $attachmentRepository
     * @param Filesystem $filesystem
     * @param TimezoneInterface $timezone
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly AttachmentRepositoryInterface $attachmentRepository,
        private readonly Filesystem $filesystem,
        private readonly TimezoneInterface $timezone,
        private readonly LoggerInterface $logger
    ) {}

    public function aroundGetTransport(TransportBuilder $subject, callable $proceed): TransportInterface
    {
        $templateId = $this->readTemplateIdentifier($subject);

        $transport = $proceed();

        if ($templateId === '') {
            return $transport;
        }

        try {
            $attachments = $this->attachmentRepository->getActiveByEmailTemplate($templateId);
        } catch (\Throwable $e) {
            $this->logger->error('MylSoft_Attachments: cannot load attachments — ' . $e->getMessage());
            return $transport;
        }

        if (empty($attachments)) {
            return $transport;
        }

        try {
            $this->attachFilesToTransport($transport, $attachments);
        } catch (\Throwable $e) {
            $this->logger->error('MylSoft_Attachments: cannot attach files — ' . $e->getMessage());
        }

        return $transport;
    }

    /**
     * @param \MylSoft\Attachments\Api\Data\AttachmentInterface[] $attachments
     */
    private function attachFilesToTransport(TransportInterface $transport, array $attachments): void
    {
        $message = $transport->getMessage();
        if (!$message instanceof MagentoMessage) {
            return;
        }

        $laminasMessage = $this->readZendMessage($message);
        $body = $laminasMessage->getBody();
        if (!$body instanceof LaminasMimeMessage) {
            return;
        }

        $mediaDir = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
        $parts = $body->getParts();

        $today = $this->timezone->date()->format('Y-m-d');

        foreach ($attachments as $attachment) {
            $validFrom = $attachment->getValidFrom();
            $validTo   = $attachment->getValidTo();

            if ($validFrom !== null && $today < $validFrom) {
                continue;
            }
            if ($validTo !== null && $today > $validTo) {
                continue;
            }

            $relative = 'mylsoft/attachments/' . $attachment->getFilepath();

            if (!$mediaDir->isFile($relative)) {
                $this->logger->warning('MylSoft_Attachments: file not found — ' . $relative);
                continue;
            }

            try {
                $content = $mediaDir->readFile($relative);
            } catch (FileSystemException $e) {
                $this->logger->warning('MylSoft_Attachments: cannot read file — ' . $e->getMessage());
                continue;
            }

            $part = new MimePart($content);
            $part->setType(
                $mediaDir->stat($relative)['mimetype'] ?? 'application/octet-stream'
            );
            $part->setDisposition(Mime::DISPOSITION_ATTACHMENT);
            $part->setEncoding(Mime::ENCODING_BASE64);
            $part->setFileName($attachment->getDisplayFilename() ?? $attachment->getFilename());

            $parts[] = $part;
        }

        $body->setParts($parts);
        $laminasMessage->setBody($body);
    }

    private function readTemplateIdentifier(TransportBuilder $builder): string
    {
        try {
            $ref = new \ReflectionObject($builder);
            while ($ref !== false) {
                if ($ref->hasProperty('templateIdentifier')) {
                    $prop = $ref->getProperty('templateIdentifier');
                    $prop->setAccessible(true);
                    return (string) ($prop->getValue($builder) ?? '');
                }
                $ref = $ref->getParentClass();
            }
        } catch (\ReflectionException) {
        }
        return '';
    }

    private function readZendMessage(MagentoMessage $message): LaminasMessage
    {
        $prop = new \ReflectionProperty(MagentoMessage::class, 'zendMessage');
        $prop->setAccessible(true);
        return $prop->getValue($message);
    }
}
