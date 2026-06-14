<?php
declare(strict_types=1);

namespace MylSoft\Attachments\Model;

use Magento\Email\Model\ResourceModel\Template\CollectionFactory as TemplateCollectionFactory;
use Magento\Email\Model\Template\Config as TemplateConfig;
use Magento\Framework\Data\OptionSourceInterface;

class EmailTemplateSource implements OptionSourceInterface
{
    /**
     * @param TemplateCollectionFactory $templateCollectionFactory
     * @param TemplateConfig $templateConfig
     */
    public function __construct(
        private readonly TemplateCollectionFactory $templateCollectionFactory,
        private readonly TemplateConfig $templateConfig
    ) {}

    public function toOptionArray(): array
    {
        $options = [['value' => '', 'label' => __('-- Please Select --')]];

        $customGroup = ['label' => __('Custom Templates'), 'value' => []];
        $collection = $this->templateCollectionFactory->create();
        foreach ($collection as $template) {
            $customGroup['value'][] = [
                'value' => (string) $template->getId(),
                'label' => $template->getTemplateCode(),
            ];
        }
        if (!empty($customGroup['value'])) {
            $options[] = $customGroup;
        }

        $defaultGroup = ['label' => __('Default Templates'), 'value' => []];
        foreach ($this->templateConfig->getAvailableTemplates() as $template) {
            $value = $template['value'];
            $label = (string) $template['label'];

            // Base identifier (no theme suffix) — this is what Magento uses when sending
            if (strpos($value, '/') === false) {
                $label .= ' [active]';
            }

            $defaultGroup['value'][] = ['value' => $value, 'label' => $label];
        }
        if (!empty($defaultGroup['value'])) {
            $options[] = $defaultGroup;
        }

        return $options;
    }
}
