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

use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;

class ThemeAssetProvider implements ThemeAssetProviderInterface
{
    private $filesystem;

    public function __construct(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function readFile(string $filePath): string
    {
        try {
            $fileContent = $this->filesystem->read($filePath);
            if (false !== $fileContent) {
                return $fileContent;
            }
        } catch (FileNotFoundException $e) {
            // allow to return null
        }

        return '';
    }

    public function hasFile(string $filePath): bool
    {
        return $this->filesystem->has($filePath);
    }

    public function listContents(string $directory = '', bool $recursive = false): array
    {
        return $this->filesystem->listContents($directory, $recursive);
    }

    public function getTimestamp(string $path)
    {
        $this->filesystem->getTimestamp($path);
    }
}
