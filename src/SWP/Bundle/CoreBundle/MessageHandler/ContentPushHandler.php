<?php

declare(strict_types=1);

/*
 * This file is part of the Superdesk Web Publisher Core Bundle.
 *
 * Copyright 2020 Sourcefabric z.ú. and contributors.
 *
 * For the full copyright and license information, please see the
 * AUTHORS and LICENSE files distributed with this source code.
 *
 * @copyright 2020 Sourcefabric z.ú
 * @license http://www.superdesk.org/license
 */

namespace SWP\Bundle\CoreBundle\MessageHandler;

use SWP\Bundle\CoreBundle\MessageHandler\Message\ContentPushMessage;

class ContentPushHandler extends AbstractContentPushHandler
{
    public function __invoke(ContentPushMessage $contentPushMessage)
    {
        $content = $contentPushMessage->getContent();
        $tenantId = $contentPushMessage->getTenantId();
        $package = $this->jsonToPackageTransformer->transform($content);
        $options = $contentPushMessage->getOptions();

        $this->execute($tenantId, $package, $options);
    }
}
