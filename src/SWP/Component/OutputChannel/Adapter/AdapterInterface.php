<?php

declare(strict_types=1);

/*
 * This file is part of the Superdesk Web Publisher Output Channel Component.
 *
 * Copyright 2018 Sourcefabric z.ú. and contributors.
 *
 * For the full copyright and license information, please see the
 * AUTHORS and LICENSE files distributed with this source code.
 *
 * @copyright 2018 Sourcefabric z.ú
 * @license http://www.superdesk.org/license
 */

namespace SWP\Component\OutputChannel\Adapter;

use SWP\Bundle\CoreBundle\Model\ArticleInterface;

interface AdapterInterface
{
    /**
     * @param array $config
     */
    public function setConfig(array $config): void;

    /**
     * @param ArticleInterface $article
     */
    public function send(ArticleInterface $article): void;
}
