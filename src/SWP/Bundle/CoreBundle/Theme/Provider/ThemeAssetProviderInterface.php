<?php

declare(strict_types=1);

/*
 * This file is part of the Superdesk Web Publisher Core Bundle.
 *
 * Copyright 2019 Sourcefabric z.u. and contributors.
 *
 * For the full copyright and license information, please see the
 * AUTHORS and LICENSE files distributed with this source code.
 *
 * @copyright 2019 Sourcefabric z.ú
 * @license http://www.superdesk.org/license
 */

namespace SWP\Bundle\CoreBundle\Theme\Provider;

interface ThemeAssetProviderInterface
{
    public function readFile(string $filePath): string;

    public function hasFile(string $filePath): bool;

    public function listContents(string $directory = '', bool $recursive = false);

    public function getTimestamp(string $path);
}
