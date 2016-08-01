<?php

/**
 * This file is part of the Superdesk Web Publisher MultiTenancy Component.
 *
 * Copyright 2016 Sourcefabric z.u. and contributors.
 *
 * For the full copyright and license information, please see the
 * AUTHORS and LICENSE files distributed with this source code.
 *
 * @copyright 2016 Sourcefabric z.ú.
 * @license http://www.superdesk.org/license
 */
namespace spec\SWP\Component\MultiTenancy\Factory;

use PhpSpec\ObjectBehavior;
use SWP\Component\Common\Generator\GeneratorInterface;
use SWP\Component\MultiTenancy\Factory\TenantFactory;
use SWP\Component\MultiTenancy\Model\Tenant;

/**
 * @mixin TenantFactory
 */
class TenantFactorySpec extends ObjectBehavior
{
    function let(GeneratorInterface $generator)
    {
        $this->beConstructedWith('SWP\Component\MultiTenancy\Model\Tenant', $generator);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('SWP\Component\MultiTenancy\Factory\TenantFactory');
    }

    function it_implements_tenant_factory_interface()
    {
        $this->shouldImplement('SWP\Component\MultiTenancy\Factory\TenantFactoryInterface');
    }

    function it_creates_a_new_tenant_with_code(GeneratorInterface $generator)
    {
        $generator->generate(6)->shouldBeCalled();

        $this->create()->shouldHaveType(new Tenant());
    }
}
