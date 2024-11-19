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

namespace SWP\Bundle\CoreBundle\MessageHandler\Message;

class ContentPushMessage implements MessageInterface
{
    /** @var int */
    private $tenantId;

    /** @var string */
    private $content;
    protected array $options = [];

    public function __construct(int $tenantId, string $content, array $options = [])
    {
        $this->tenantId = $tenantId;
        $this->content = $content;
        $this->options = $options;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function toArray(): array
    {
        return [
            'tenant' => $this->tenantId,
            'content' => $this->content,
            'options' => $this->options
        ];
    }
}
