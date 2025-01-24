<?php

/*
 * This file is part of the Superdesk Web Publisher MultiTenancy Bundle.
 *
 * Copyright 2016 Sourcefabric z.ú. and contributors.
 *
 * For the full copyright and license information, please see the
 * AUTHORS and LICENSE files distributed with this source code.
 *
 * @copyright 2016 Sourcefabric z.ú
 * @license http://www.superdesk.org/license
 */

namespace SWP\Bundle\MultiTenancyBundle\Doctrine\PHPCR;

use SWP\Bundle\StorageBundle\Doctrine\ODM\PHPCR\DocumentRepository;
use SWP\Component\MultiTenancy\Repository\TenantDomainRepositoryInterface;

class TenantDomainRepository extends DocumentRepository implements TenantDomainRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function findOneBySubdomainAndDomain($subdomain, $domain)
    {
        $qb = $this->createQueryBuilder('t');
        $qb->where()->eq()->field('t.subdomain')->literal($subdomain);
        $qb->andWhere()->eq()->field('t.domainName')->literal($domain);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * {@inheritdoc}
     */
    public function findOneByDomain($domain)
    {
        $qb = $this->createQueryBuilder('t');
        $qb->where()->eq()->field('t.domainName')->literal($domain);
        $qb->andWhere()->eq()->field('t.subdomain')->literal(null);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
