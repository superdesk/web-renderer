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

namespace SWP\Component\MultiTenancy\Repository;

use SWP\Component\MultiTenancy\Model\TenantDomainInterface;
use SWP\Component\Storage\Repository\RepositoryInterface;

/**
 * Repository interface for tenant domains.
 */
interface TenantDomainRepositoryInterface extends RepositoryInterface
{
    /**
     * Finds the tenant by subdomain.
     *
     * @param string $subdomain The subdomain
     * @param string $domain    The domain
     *
     * @return TenantDomainInterface|null The instance of TenantInterface or null
     */
    public function findOneBySubdomainAndDomain(string $subdomain, string $domain): ?TenantDomainInterface;

    /**
     * Finds the tenant by domain.
     *
     * @param string $domain The domain
     *
     * @return TenantDomainInterface|null The instance of TenantInterface or null
     */
    public function findOneByDomain(string $domain): ?TenantDomainInterface;
}
