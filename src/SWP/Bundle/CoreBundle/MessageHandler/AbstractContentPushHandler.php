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

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Exception\NotNullConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Log\LoggerInterface;
use Sentry\Breadcrumb;
use Sentry\State\HubInterface;
use SWP\Bundle\BridgeBundle\Doctrine\ORM\PackageRepository;
use SWP\Bundle\CoreBundle\Hydrator\PackageHydratorInterface;
use SWP\Bundle\CoreBundle\MessageHandler\Exception\LockConflictedException;
use SWP\Bundle\CoreBundle\Model\PackageInterface;
use SWP\Bundle\CoreBundle\Model\Tenant;
use SWP\Component\Bridge\Events;
use SWP\Component\Bridge\Model\ItemInterface;
use SWP\Component\Bridge\Transformer\DataTransformerInterface;
use SWP\Component\MultiTenancy\Context\TenantContextInterface;
use Symfony\Component\Cache\ResettableInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

abstract class AbstractContentPushHandler implements MessageHandlerInterface
{

    protected $logger;

    protected $packageRepository;

    protected $eventDispatcher;

    protected $jsonToPackageTransformer;

    protected $packageObjectManager;

    protected $tenantContext;

    protected $packageHydrator;

    protected $lockFactory;

    public function __construct(
        LoggerInterface $logger,
        PackageRepository $packageRepository,
        EventDispatcherInterface $eventDispatcher,
        DataTransformerInterface $jsonToPackageTransformer,
        EntityManagerInterface $packageObjectManager,
        TenantContextInterface $tenantContext,
        HubInterface $sentryHub,
        PackageHydratorInterface $packageHydrator,
        LockFactory $lockFactory
    ) {
        $this->logger = $logger;
        $this->packageRepository = $packageRepository;
        $this->eventDispatcher = $eventDispatcher;
        $this->jsonToPackageTransformer = $jsonToPackageTransformer;
        $this->packageObjectManager = $packageObjectManager;
        $this->tenantContext = $tenantContext;
        $this->sentryHub = $sentryHub;
        $this->packageHydrator = $packageHydrator;
        $this->lockFactory = $lockFactory;
    }

    public function execute(int $tenantId, PackageInterface $package, array $options = []): void
    {
        $lock = $this->lockFactory->createLock($this->generateLockId($package->getGuid()), 120);

        try {
            if (!$lock->acquire()) {
                throw new LockConflictedException();
            }

            $this->doExecute($tenantId, $package, $options);
        } catch (UniqueConstraintViolationException $e) {
            $this->logException($e, $package, 'UniqueConstraintViolationException exception');

            $cacheDriver = $this->packageObjectManager->getConfiguration()->getMetadataCacheImpl();
            $cacheDriver->flushAll();

            throw $e;

        } catch (NonUniqueResultException | NotNullConstraintViolationException $e) {
            $this->logException($e, $package, 'Unhandled NonUnique or NotNullConstraint exception');

            $cacheDriver = $this->packageObjectManager->getConfiguration()->getMetadataCache();
            $cacheDriver->flushAll();

            throw $e;
        } catch (\Throwable $e) {
            $this->logException($e, $package);

            throw $e;
        } finally {
            $this->reset();
        }
    }


    private function generateLockId(string $guid): string
    {
        return md5(json_encode(['type' => 'package', 'guid' => $guid]));
    }

    private function doExecute(int $tenantId, PackageInterface $package, array $options = []): void
    {
        $packageType = $package->getType();
        if (ItemInterface::TYPE_TEXT !== $packageType && ItemInterface::TYPE_COMPOSITE !== $packageType) {
            return;
        }

        $this->tenantContext->setTenant($this->packageObjectManager->find(Tenant::class, $tenantId));

        /** @var PackageInterface $existingPackage */
        $existingPackage = $this->findExistingPackage($package);
        if (null !== $existingPackage) {
            $existingPackage = $this->packageHydrator->hydrate($package, $existingPackage);

            if (!empty($options)) {
                $this->eventDispatcher->dispatch(
                    new GenericEvent(
                        [
                            'package' => $existingPackage,
                            'options' => $options,
                        ],
                        ['eventName' => Events::PACKAGE_PRE_OPTIONS]
                    ),
                    Events::PACKAGE_PRE_OPTIONS);
            }
            $this->eventDispatcher->dispatch( new GenericEvent($existingPackage, ['eventName' => Events::PACKAGE_PRE_UPDATE]), Events::PACKAGE_PRE_UPDATE);
            $this->packageObjectManager->flush();
            $this->eventDispatcher->dispatch( new GenericEvent($existingPackage, ['eventName' => Events::PACKAGE_POST_UPDATE]), Events::PACKAGE_POST_UPDATE);
            $this->eventDispatcher->dispatch( new GenericEvent($existingPackage, ['eventName' => Events::PACKAGE_PROCESSED]), Events::PACKAGE_PROCESSED);
            $this->packageObjectManager->flush();

            $this->logger->info(sprintf('Package %s was updated', $existingPackage->getGuid()));

            if (!empty($options)) {
                $this->eventDispatcher->dispatch(
                    new GenericEvent(
                        ['package' => $existingPackage, 'options' => $options],
                        ['eventName' => Events::PACKAGE_POST_OPTIONS]
                    ),
                    Events::PACKAGE_POST_OPTIONS
                );
            }

            $this->reset();
            return;
        }

        if (!empty($options)) {
            $this->eventDispatcher->dispatch(
                new GenericEvent(
                    ['package' => $package, 'options' => $options],
                    ['eventName' => Events::PACKAGE_PRE_OPTIONS]
                ),
                Events::PACKAGE_PRE_OPTIONS
            );
        }

        $this->eventDispatcher->dispatch( new GenericEvent($package, ['eventName' => Events::PACKAGE_PRE_CREATE]), Events::PACKAGE_PRE_CREATE);
        $this->packageRepository->add($package);
        $this->eventDispatcher->dispatch( new GenericEvent($package, ['eventName' => Events::PACKAGE_POST_CREATE]), Events::PACKAGE_POST_CREATE);
        $this->eventDispatcher->dispatch( new GenericEvent($package, ['eventName' => Events::PACKAGE_PROCESSED]), Events::PACKAGE_PROCESSED);

        if (!empty($options)) {
            $this->eventDispatcher->dispatch(
                new GenericEvent(
                    ['package' => $package, 'options' => $options],
                    ['eventName' => Events::PACKAGE_POST_OPTIONS]
                ),
                Events::PACKAGE_POST_OPTIONS
            );
        }

        $this->packageObjectManager->flush();

        $this->logger->info(sprintf('Package %s was created', $package->getGuid()));
        $this->reset();
    }



    protected function findExistingPackage(PackageInterface $package)
    {
        $existingPackage = $this->packageRepository->findOneBy(['guid' => $package->getEvolvedFrom() ?? $package->getGuid()]);
        if (null === $existingPackage && null !== $package->getEvolvedFrom()) {
            $existingPackage = $this->packageRepository->findOneBy(['guid' => $package->getGuid()]);
        }

        if (null === $existingPackage) {
            // check for updated items (with evolved from)
            $existingPackage = $this->packageRepository->findOneBy(['evolvedFrom' => $package->getGuid()]);
        }

        return $existingPackage;
    }

    protected function reset(): void
    {
        $this->packageObjectManager->clear();
        if ($this->tenantContext instanceof ResettableInterface) {
            $this->tenantContext->reset();
        }
    }

    protected function logException(\Throwable $e, PackageInterface $package, string $defaultMessage = 'Unhandled exception'): void
    {
        $this->logger->error('' !== $e->getMessage() ? $e->getMessage() : $defaultMessage, ['trace' => $e->getTraceAsString()]);
        $this->sentryHub->addBreadcrumb(new Breadcrumb(
            Breadcrumb::LEVEL_DEBUG,
            Breadcrumb::TYPE_DEFAULT,
            'publishing',
            'Package',
            [
                'guid' => $package->getGuid(),
                'headline' => $package->getHeadline(),
            ]
        ));
        $this->sentryHub->captureException($e);
    }
}
