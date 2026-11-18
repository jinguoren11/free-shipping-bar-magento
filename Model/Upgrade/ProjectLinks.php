<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model\Upgrade;

/**
 * Public project links used by the download edition only.
 * PUBLISHED must stay false until the owner has made the repository public: until then no link is shown anywhere.
 */
class ProjectLinks
{
    public const PUBLISHED = false;
    public const REPO_URL = 'https://github.com/jinguoren11/free-shipping-bar-magento';
}
