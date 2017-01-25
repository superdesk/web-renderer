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

namespace SWP\Bundle\CoreBundle\EventSubscriber;

use SWP\Bundle\RevisionBundle\Event\RevisionPublishedEvent;
use SWP\Bundle\RevisionBundle\Events;
use SWP\Bundle\TemplatesSystemBundle\Repository\ContainerRepository;
use SWP\Component\Common\Criteria\Criteria;
use SWP\Component\Revision\Model\RevisionInterface;
use SWP\Component\Revision\Model\RevisionLogInterface;
use SWP\Component\Revision\RevisionAwareInterface;
use SWP\Component\Storage\Factory\FactoryInterface;
use SWP\Component\Storage\Model\PersistableInterface;
use SWP\Component\TemplatesSystem\Gimme\Model\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RevisionsSubscriber implements EventSubscriberInterface
{
    /**
     * @var ContainerRepository
     */
    protected $containerRepository;

    /**
     * @var FactoryInterface
     */
    protected $revisionLogFactory;

    /**
     * RevisionsSubscriber constructor.
     *
     * @param ContainerRepository $containerRepository
     * @param FactoryInterface    $revisionLogFactory
     */
    public function __construct(ContainerRepository $containerRepository, FactoryInterface $revisionLogFactory)
    {
        $this->containerRepository = $containerRepository;
        $this->revisionLogFactory = $revisionLogFactory;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::REVISION_PUBLISH => 'publish',
        ];
    }

    /**
     * @param RevisionPublishedEvent $event
     */
    public function publish(RevisionPublishedEvent $event)
    {
        /** @var RevisionInterface $revision */
        $revision = $event->getRevision();
        if (null === $revision->getPrevious()) {
            return;
        }

        // new revision containers id's
        $queryBuilder = $this->containerRepository->createQueryBuilder('c');
        $newRevisionContainers = $queryBuilder->select('c.uuid')
            ->where('c.revision = :revision')
            ->setParameter('revision', $revision)
            ->getQuery()
            ->getResult();

        $ids = [];
        foreach ($newRevisionContainers as $container) {
            $ids[] = $container['uuid'];
        }

        // published revisions containers
        $criteria = new Criteria();
        $criteria->set('revision', $revision->getPrevious());
        $queryBuilder = $this->containerRepository->getQueryByCriteria($criteria, [], 'c');
        if (count($ids) > 0) {
            $queryBuilder->andWhere('c.uuid NOT IN (:ids)')->setParameter('ids', $ids);
        }

        $containers = $queryBuilder->getQuery()->getResult();
        /** @var ContainerInterface|RevisionAwareInterface $container */
        foreach ($containers as $container) {
            $container->setRevision($revision);
            $this->log($container, $revision);
        }

        $this->containerRepository->flush();
    }

    /**
     * @param $object
     * @param RevisionInterface $revision
     */
    private function log($object, $revision)
    {
        if (!$object instanceof PersistableInterface || !$object instanceof RevisionAwareInterface) {
            return;
        }

        /** @var RevisionLogInterface|PersistableInterface $revisionLog */
        $revisionLog = $this->revisionLogFactory->create();
        $revisionLog->setEvent(RevisionLogInterface::EVENT_UPDATE);
        $revisionLog->setObjectType(get_class($object));
        $revisionLog->setObjectId($object->getId());
        $revisionLog->setSourceRevision($revision->getPrevious());
        $revisionLog->setTargetRevision($revision);

        $this->containerRepository->persist($revisionLog);
    }
}
