<?php
declare(strict_types=1);

namespace MylSoft\Attachments\Ui\Component\Listing\Column;

use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class Actions extends Column
{
    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param Escaper $escaper
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly UrlInterface $urlBuilder,
        private readonly Escaper $escaper,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as &$item) {
            $id = $item['attachment_id'];
            $name = $this->escaper->escapeHtml($item['name']);

            $item[$this->getData('name')] = [
                'edit' => [
                    'href'  => $this->urlBuilder->getUrl('mylsoft_attachments/attachment/edit', ['id' => $id]),
                    'label' => __('Edit'),
                ],
                'delete' => [
                    'href'    => $this->urlBuilder->getUrl('mylsoft_attachments/attachment/delete', ['id' => $id]),
                    'label'   => __('Delete'),
                    'confirm' => [
                        'title'   => __('Delete "%1"', $name),
                        'message' => __('Are you sure you want to delete "%1"?', $name),
                    ],
                    'post' => true,
                ],
            ];
        }

        return $dataSource;
    }
}
