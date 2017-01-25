<?php

/*
 * This file is part of the Superdesk Web Publisher Core Bundle.
 *
 * Copyright 2016 Sourcefabric z.ú. and contributors.
 *
 * For the full copyright and license information, please see the
 * AUTHORS and LICENSE files distributed with this source code.
 *
 * @copyright 2016 Sourcefabric z.ú
 * @license http://www.superdesk.org/license
 */

namespace SWP\Bundle\CoreBundle\Command;

use SWP\Bundle\MultiTenancyBundle\Command\CreateTenantCommand as BaseCreateTenantCommand;
use SWP\Component\Revision\Model\RevisionInterface;

class CreateTenantCommand extends BaseCreateTenantCommand
{
    /**
     * {@inheritdoc}
     */
    public function createTenant($subdomain, $name, $disabled, $organization)
    {
        $tenant = parent::createTenant($subdomain, $name, $disabled, $organization);

        /** @var RevisionManagerInterface $revisionManager */
        $revisionManager = $this->getContainer()->get('swp.manager.revision');
        $revisionManager->setObjectManager($this->getContainer()->get('swp.object_manager.revision'));

        /** @var RevisionInterface $firstTenantPublishedRevision */
        $revision = $revisionManager->create();
        $revision->setTenantCode($tenant->getCode());
        $revisionManager->publish($revision);

        return $tenant;
    }
}
