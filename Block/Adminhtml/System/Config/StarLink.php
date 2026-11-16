<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Block\Adminhtml\System\Config;

use GetDropFlow\FreeShippingBar\Model\Upgrade\ProjectLinks;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * One quiet line at the bottom of the settings page: "star the project on GitHub". Download edition only,
 * and only after the repository is public (ProjectLinks::PUBLISHED). No network access.
 */
class StarLink extends Field
{
    /**
     * @inheritDoc
     */
    public function render(AbstractElement $element): string
    {
        if (!ProjectLinks::PUBLISHED) {
            return '';
        }
        return '<tr id="row_' . $this->escapeHtmlAttr($element->getHtmlId()) . '"><td colspan="4"><div class="gdf-fsb-tools">'
            . $this->escapeHtml((string) __('Like this module?')) . ' '
            . '<a href="' . $this->escapeUrl(ProjectLinks::REPO_URL) . '" target="_blank" rel="noopener noreferrer">'
            . $this->escapeHtml((string) __('Star it on GitHub')) . ' &#9733;</a></div></td></tr>';
    }
}
