<?php
declare(strict_types=1);

namespace MylSoft\Attachments\Ui\DataProvider;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use MylSoft\Attachments\Api\AttachmentRepositoryInterface;
use MylSoft\Attachments\Model\ResourceModel\Attachment\CollectionFactory;

class AttachmentFormDataProvider extends AbstractDataProvider
{
    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param AttachmentRepositoryInterface $attachmentRepository
     * @param RequestInterface $request
     * @param StoreManagerInterface $storeManager
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        CollectionFactory $collectionFactory,
        private readonly AttachmentRepositoryInterface $attachmentRepository,
        private readonly RequestInterface $request,
        private readonly StoreManagerInterface $storeManager,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData(): array
    {
        $id = (int) $this->request->getParam('id');
        if (!$id) {
            return [];
        }

        try {
            $attachment = $this->attachmentRepository->getById($id);
        } catch (NoSuchEntityException) {
            return [];
        }

        $data = $attachment->getData();

        if ($attachment->getFilepath()) {
            $baseUrl = $this->storeManager->getStore()->getBaseUrl(
                \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
            );
            $data['file'] = [
                [
                    'name' => $attachment->getFilename(),
                    'file' => $attachment->getFilepath(),
                    'url'  => $baseUrl . 'mylsoft/attachments/' . $attachment->getFilepath(),
                    'size' => file_exists(
                        BP . '/pub/media/mylsoft/attachments/' . $attachment->getFilepath()
                    ) ? filesize(BP . '/pub/media/mylsoft/attachments/' . $attachment->getFilepath()) : 0,
                    'type' => 'file',
                ],
            ];
        }

        return [$id => $data];
    }
}
