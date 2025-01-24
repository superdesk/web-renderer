<?php

/*
 * This file is part of the Superdesk Web Publisher MultiTenancy Component.
 *
 * Copyright 2016 Sourcefabric z.ú. and contributors.
 *
 * For the full copyright and license information, please see the
 * AUTHORS and LICENSE files distributed with this source code.
 *
 * @copyright 2016 Sourcefabric z.ú
 * @license http://www.superdesk.org/license
 */

namespace SWP\Component\MultiTenancy\Model;

use SWP\Component\Common\Model\EnableableTrait;
use SWP\Component\Common\Model\SoftDeletableTrait;
use SWP\Component\Common\Model\TimestampableTrait;

class TenantDomain implements TenantDomainInterface
{
    use SoftDeletableTrait;
    use TimestampableTrait;
    use EnableableTrait;

    /**
     * @var mixed
     */
    protected $id;

    /**
     * @var string
     */
    protected $domainName;

    /**
     * @var string
     */
    protected $subdomain;

    /**
     * @var TenantInterface
     */
    protected $tenant;

    /**
     * TenantDomain constructor.
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * {@inheritdoc}
     */
    public function getDomainName(): string
    {
        return $this->domainName;
    }

    /**
     * {@inheritdoc}
     */
    public function setDomainName(string $domainName)
    {
        $this->domainName = $domainName;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubdomain(): string
    {
        return $this->subdomain;
    }

    /**
     * {@inheritdoc}
     */
    public function setSubdomain(string $subdomain)
    {
        $this->subdomain = $subdomain;
    }

    /**
     * {@inheritdoc}
     */
    public function setTenant(TenantInterface $tenant)
    {
        $this->tenant = $tenant;
    }

    /**
     * {@inheritdoc}
     */
    public function getTenant(): TenantInterface
    {
        return $this->tenant;
    }
}
