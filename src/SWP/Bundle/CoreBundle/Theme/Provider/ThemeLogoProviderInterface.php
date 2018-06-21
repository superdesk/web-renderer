<?php

declare(strict_types=1);

/*
 * This file is part of the Superdesk Web Publisher Core Bundle.
 *
 * Copyright 2018 Sourcefabric z.u. and contributors.
 *
 * For the full copyright and license information, please see the
 * AUTHORS and LICENSE files distributed with this source code.
 *
 * @copyright 2018 Sourcefabric z.ú
 * @license http://www.superdesk.org/license
 */

namespace SWP\Bundle\CoreBundle\Theme\Provider;

interface ThemeLogoProviderInterface
{
    public const DEFAULT = 'theme_logo';

    /**
     * @param string $settingName
     *
     * @return string
     */
    public function getLogoLink(string $settingName = self::DEFAULT): string;
}
