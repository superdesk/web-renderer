<?php

/*
 * This file is part of the Superdesk Web Publisher Core Bundle.
 *
 * Copyright 2016 Sourcefabric z.u. and contributors.
 *
 * For the full copyright and license information, please see the
 * AUTHORS and LICENSE files distributed with this source code.
 *
 * @copyright 2016 Sourcefabric z.ú
 * @license http://www.superdesk.org/license
 */

namespace SWP\Bundle\CoreBundle\Locator;

use SWP\Bundle\CoreBundle\Detection\DeviceDetectionInterface;
//use Sylius\Bundle\ThemeBundle\Locator\ResourceLocatorInterface;
//use Sylius\Bundle\ThemeBundle\Locator\ResourceNotFoundException;
use Sylius\Bundle\ThemeBundle\Model\ThemeInterface;
use Symfony\Component\Filesystem\Filesystem;

class ApplicationResourceLocator //implements ResourceLocatorInterface
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var DeviceDetectionInterface
     */
    private $deviceDetection;

    /**
     * @param Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem, DeviceDetectionInterface $deviceDetection)
    {
        $this->filesystem = $filesystem;
        $this->deviceDetection = $deviceDetection;
    }

    /**
     * {@inheritdoc}
     */
    public function locate(string $resourceName, ThemeInterface $theme): string
    {
        if (null !== $this->deviceDetection->getType()) {
            $path = sprintf('%s/%s/%s', $theme->getPath(), $this->deviceDetection->getType(), $resourceName);
            if ($this->filesystem->exists($path)) {
                return $path;
            }
        }

        $path = sprintf('%s/templates/%s', $theme->getPath(), $resourceName);
        if (!$this->filesystem->exists($path)) {
            //throw new ResourceNotFoundException($resourceName, $theme);
        }

        return $path;
    }

    public function supports(string $template): bool
    {
        return strpos($template, '@') !== 0;
    }
}
