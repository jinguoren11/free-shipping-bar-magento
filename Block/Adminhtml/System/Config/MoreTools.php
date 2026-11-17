<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Block\Adminhtml\System\Config;

use GetDropFlow\FreeShippingBar\Model\Config;
use GetDropFlow\FreeShippingBar\Model\MoreTools\Catalog;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Small static "More tools" panel at the bottom of the settings page.
 * No network access, no pop-up, no admin notification, no data collected. Can be switched off in the settings.
 */
class MoreTools extends Field
{
    /**
     * @var Catalog
     */
    private $catalog;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param Context $context
     * @param Catalog $catalog
     * @param Config $config
     * @param array $data
     */
    public function __construct(Context $context, Catalog $catalog, Config $config, array $data = [])
    {
        parent::__construct($context, $data);
        $this->catalog = $catalog;
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public function render(AbstractElement $element): string
    {
        if (!$this->config->flag('about/show_more_tools')) {
            return '';
        }
        $items = '';
        foreach ($this->catalog->getItems() as $item) {
            $items .= '<li class="gdf-fsb-tools__item"><strong>' . $this->escapeHtml($item['name'])
                . '</strong> &mdash; '
                . $this->escapeHtml((string) __($item['description'])) . ' '
                . '<a href="' . $this->escapeUrl($item['url']) . '" target="_blank" rel="noopener noreferrer">'
                . $this->escapeHtml((string) __('Learn more at %1', $item['host']))
                . '</a></li>';
        }
        if ($items === '') {
            return '';
        }
        $note = (string) __('Optional suggestions from the author. This module works the same without them.');
        return '<tr id="row_' . $this->escapeHtmlAttr($element->getHtmlId()) . '"><td colspan="4">'
            . '<div class="gdf-fsb-tools"><div class="gdf-fsb-tools__title">'
            . $this->escapeHtml((string) __('More tools'))
            . '</div><p class="gdf-fsb-tools__note">' . $this->escapeHtml($note)
            . '</p><ul class="gdf-fsb-tools__list">' . $items . '</ul></div></td></tr>';
    }
}
