<?php

/*
 * This file is part of the Superdesk Web Publisher MultiTenancy Component.
 *
 * Copyright 2015 Sourcefabric z.u. and contributors.
 *
 * For the full copyright and license information, please see the
 * AUTHORS and LICENSE files distributed with this source code.
 *
 * @copyright 2015 Sourcefabric z.ú
 * @license http://www.superdesk.org/license
 */

namespace SWP\Component\MultiTenancy\Model;

use SWP\Bundle\SettingsBundle\Model\SettingsOwnerInterface;
use SWP\Component\Common\Model\EnableableInterface;
use SWP\Component\Common\Model\SoftDeletableInterface;
use SWP\Component\Common\Model\TimestampableInterface;
use SWP\Component\Storage\Model\PersistableInterface;

interface TenantDomainInterface extends
    TimestampableInterface,
    EnableableInterface,
    SoftDeletableInterface,
    PersistableInterface,
    SettingsOwnerInterface
{

    /**
     * @return string
     */
    public function getDomainName(): string;

    /**
     * @param string $domainName
     */
    public function setDomainName(string $domainName);

    /**
     * Gets the tenant subdomain.
     *
     * @return string The tenant subdomain
     */
    public function getSubdomain(): string;

    /**
     * Sets the tenant identifier.
     *
     * @param string $subdomain The tenant subdomain
     */
    public function setSubdomain(string $subdomain);

    /**
     * @return TenantInterface
     */
    public function getTenant(): TenantInterface;

    public function setTenant(TenantInterface $tenant);
}
