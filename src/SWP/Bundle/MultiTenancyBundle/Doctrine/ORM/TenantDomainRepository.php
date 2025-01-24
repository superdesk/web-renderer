<?php

/*
 * This file is part of the Superdesk Web Publisher MultiTenancy Bundle.
 *
 * Copyright 2015 Sourcefabric z.u. and contributors.
 *
 * For the full copyright and license information, please see the
 * AUTHORS and LICENSE files distributed with this source code.
 *
 * @copyright 2015 Sourcefabric z.ú
 * @license http://www.superdesk.org/license
 */

namespace SWP\Bundle\MultiTenancyBundle\Doctrine\ORM;

use SWP\Bundle\StorageBundle\Doctrine\ORM\EntityRepository;
use SWP\Component\MultiTenancy\Model\TenantDomainInterface;
use SWP\Component\MultiTenancy\Repository\TenantDomainRepositoryInterface;

/**
 * Repository interface for tenants.
 */
class TenantDomainRepository extends EntityRepository implements TenantDomainRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function findOneBySubdomainAndDomain(string $subdomain, string $domain): ?TenantDomainInterface
    {
        return $this
            ->createQueryBuilder('td')
            ->select('td', 't',)
            ->leftJoin('td.tenant', 't')
            ->where('td.subdomain = :subdomain')
            ->andWhere('td.domainName = :domainName')
            ->setParameters([
                'subdomain' => $subdomain,
                'domainName' => $domain,
            ])
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * {@inheritdoc}
     */
    public function findOneByDomain(string $domain): ?TenantDomainInterface
    {
        return $this
            ->createQueryBuilder('td')
            ->select('td', 't')
            ->leftJoin('td.tenant', 't')
            ->where('td.domainName = :domainName')
            ->andWhere('td.subdomain IS NULL')
            ->setParameter('domainName', $domain)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
