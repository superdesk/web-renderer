<?php

declare(strict_types=1);

/*
 * This file is part of the Superdesk Web Publisher Bridge Component.
 *
 * Copyright 2017 Sourcefabric z.ú. and contributors.
 *
 * For the full copyright and license information, please see the
 * AUTHORS and LICENSE files distributed with this source code.
 *
 * @copyright 2017 Sourcefabric z.ú
 * @license http://www.superdesk.org/license
 */

namespace SWP\Component\Bridge;

final class Events
{
    /**
     * The SWP_VALIDATION event occurs on an object validation.
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     *
     * @var string
     */
    const SWP_VALIDATION = 'swp.validation';

    /**
     * The PACKAGE_PRE_CREATE event occurs before package is created.
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     *
     * @var string
     */
    const PACKAGE_PRE_CREATE = 'swp.bridge.package_pre_create';

    /**
     * The PACKAGE_POST_CREATE event occurs after package is created.
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     *
     * @var string
     */
    const PACKAGE_POST_CREATE = 'swp.bridge.package_post_create';

    /**
     * The PACKAGE_PRE_UPDATE event occurs before package update.
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     *
     * @var string
     */
    const PACKAGE_PRE_UPDATE = 'swp.bridge.package_pre_update';

    /**
     * The PACKAGE_POST_UPDATE event occurs after package update.
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     *
     * @var string
     */
    const PACKAGE_POST_UPDATE = 'swp.bridge.package_post_update';

    /**
     * The PACKAGE_PROCESSED event occurs after package processing.
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     *
     * @var string
     */
    const PACKAGE_PROCESSED = 'swp.bridge.package_processed';

    /**
     * The PACKAGE_PRE_OPTIONS event occurs before package update/insert.
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     *
     * @var string
     */
    const PACKAGE_PRE_OPTIONS = 'swp.bridge.package_pre_options';

    /**
     * The PACKAGE_POST_OPTIONS event occurs after package update/insert.
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     *
     * @var string
     */
    const PACKAGE_POST_OPTIONS = 'swp.bridge.package_post_options';

    private function __construct()
    {
    }
}
