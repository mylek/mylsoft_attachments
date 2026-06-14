<?php
declare(strict_types=1);

namespace MylSoft\Attachments\Model;

use Magento\Framework\Model\AbstractModel;
use MylSoft\Attachments\Api\Data\AttachmentInterface;
use MylSoft\Attachments\Model\ResourceModel\Attachment as AttachmentResource;

class Attachment extends AbstractModel implements AttachmentInterface
{
    protected function _construct(): void
    {
        $this->_init(AttachmentResource::class);
    }

    public function getAttachmentId(): ?int
    {
        $id = $this->getData(self::ATTACHMENT_ID);
        return $id !== null ? (int) $id : null;
    }

    public function setAttachmentId(int $attachmentId): self
    {
        return $this->setData(self::ATTACHMENT_ID, $attachmentId);
    }

    public function getName(): string
    {
        return (string) $this->getData(self::NAME);
    }

    public function setName(string $name): self
    {
        return $this->setData(self::NAME, $name);
    }

    public function getFilename(): string
    {
        return (string) $this->getData(self::FILENAME);
    }

    public function setFilename(string $filename): self
    {
        return $this->setData(self::FILENAME, $filename);
    }

    public function getDisplayFilename(): ?string
    {
        $val = $this->getData(self::DISPLAY_FILENAME);
        return $val !== null && $val !== '' ? (string) $val : null;
    }

    public function setDisplayFilename(?string $displayFilename): self
    {
        return $this->setData(self::DISPLAY_FILENAME, $displayFilename);
    }

    public function getFilepath(): string
    {
        return (string) $this->getData(self::FILEPATH);
    }

    public function setFilepath(string $filepath): self
    {
        return $this->setData(self::FILEPATH, $filepath);
    }

    public function getEmailTemplate(): string
    {
        return (string) $this->getData(self::EMAIL_TEMPLATE);
    }

    public function setEmailTemplate(string $emailTemplate): self
    {
        return $this->setData(self::EMAIL_TEMPLATE, $emailTemplate);
    }

    public function getIsActive(): int
    {
        return (int) $this->getData(self::IS_ACTIVE);
    }

    public function setIsActive(int $isActive): self
    {
        return $this->setData(self::IS_ACTIVE, $isActive);
    }

    public function getValidFrom(): ?string
    {
        $val = $this->getData(self::VALID_FROM);
        return $val !== null && $val !== '' ? (string) $val : null;
    }

    public function setValidFrom(?string $validFrom): self
    {
        return $this->setData(self::VALID_FROM, $validFrom);
    }

    public function getValidTo(): ?string
    {
        $val = $this->getData(self::VALID_TO);
        return $val !== null && $val !== '' ? (string) $val : null;
    }

    public function setValidTo(?string $validTo): self
    {
        return $this->setData(self::VALID_TO, $validTo);
    }

    public function getCreatedAt(): string
    {
        return (string) $this->getData(self::CREATED_AT);
    }
}
