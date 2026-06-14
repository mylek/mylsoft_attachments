<?php
declare(strict_types=1);

namespace MylSoft\Attachments\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Attachment extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('mylsoft_attachment', 'attachment_id');
    }
}
