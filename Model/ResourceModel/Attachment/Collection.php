<?php
declare(strict_types=1);

namespace MylSoft\Attachments\Model\ResourceModel\Attachment;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use MylSoft\Attachments\Model\Attachment;
use MylSoft\Attachments\Model\ResourceModel\Attachment as AttachmentResource;

class Collection extends AbstractCollection
{
    protected function _construct(): void
    {
        $this->_init(Attachment::class, AttachmentResource::class);
    }
}
