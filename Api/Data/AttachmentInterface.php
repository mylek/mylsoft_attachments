<?php
declare(strict_types=1);

namespace MylSoft\Attachments\Api\Data;

interface AttachmentInterface
{
    public const ATTACHMENT_ID = 'attachment_id';
    public const NAME = 'name';
    public const FILENAME = 'filename';
    public const DISPLAY_FILENAME = 'display_filename';
    public const FILEPATH = 'filepath';
    public const EMAIL_TEMPLATE = 'email_template';
    public const IS_ACTIVE = 'is_active';
    public const VALID_FROM = 'valid_from';
    public const VALID_TO = 'valid_to';
    public const CREATED_AT = 'created_at';

    public function getAttachmentId(): ?int;

    public function setAttachmentId(int $attachmentId): self;

    public function getName(): string;

    public function setName(string $name): self;

    public function getFilename(): string;

    public function setFilename(string $filename): self;

    public function getDisplayFilename(): ?string;

    public function setDisplayFilename(?string $displayFilename): self;

    public function getFilepath(): string;

    public function setFilepath(string $filepath): self;

    public function getEmailTemplate(): string;

    public function setEmailTemplate(string $emailTemplate): self;

    public function getIsActive(): int;

    public function setIsActive(int $isActive): self;

    public function getValidFrom(): ?string;

    public function setValidFrom(?string $validFrom): self;

    public function getValidTo(): ?string;

    public function setValidTo(?string $validTo): self;

    public function getCreatedAt(): string;
}
