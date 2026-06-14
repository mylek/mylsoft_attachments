<?php
declare(strict_types=1);

namespace MylSoft\Attachments\Controller\Adminhtml\Attachment;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\MediaStorage\Model\File\UploaderFactory;

class Upload extends Action
{
    public const ADMIN_RESOURCE = 'MylSoft_Attachments::attachments_manage';

    private const UPLOAD_DIR = 'mylsoft/attachments';

    /**
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param UploaderFactory $uploaderFactory
     * @param Filesystem $filesystem
     */
    public function __construct(
        Context $context,
        private readonly JsonFactory $jsonFactory,
        private readonly UploaderFactory $uploaderFactory,
        private readonly Filesystem $filesystem
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();

        try {
            $uploader = $this->uploaderFactory->create(['fileId' => 'file']);
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(false);
            $uploader->setAllowCreateFolders(true);

            $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
            $targetPath = $mediaDirectory->getAbsolutePath(self::UPLOAD_DIR);

            $uploadResult = $uploader->save($targetPath);

            $result->setData([
                'name' => $uploadResult['name'],
                'file' => $uploadResult['file'],
                'size' => $uploadResult['size'],
                'type' => $uploadResult['type'] ?? '',
                'url'  => $this->_url->getBaseUrl(['_type' => \Magento\Framework\UrlInterface::URL_TYPE_MEDIA])
                    . self::UPLOAD_DIR . '/' . $uploadResult['file'],
            ]);
        } catch (\Exception $e) {
            $result->setData(['error' => $e->getMessage(), 'errorcode' => $e->getCode()]);
        }

        return $result;
    }
}
