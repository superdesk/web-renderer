<?php

namespace SWP\Bundle\CoreBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use SWP\Bundle\ContentBundle\ArticleEvents;
use SWP\Bundle\ContentBundle\Doctrine\ArticleRepositoryInterface;
use SWP\Bundle\ContentBundle\Event\ArticleEvent;
use SWP\Bundle\CoreBundle\Model\Package;
use SWP\Bundle\CoreBundle\Model\PackageInterface;
use SWP\Bundle\CoreBundle\Model\PublishDestination;
use SWP\Bundle\CoreBundle\Provider\PublishDestinationProvider;
use SWP\Bundle\MultiTenancyBundle\MultiTenancyEvents;
use SWP\Component\Common\Exception\UnexpectedTypeException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

final class PackageOptionsListener
{
    protected $publishDestinationManager;
    protected $packageManager;
    protected $articleManager;

    protected EntityManagerInterface $entityManager;
    protected PublishDestinationProvider $publishDestinationProvider;

    private ArticleRepositoryInterface $articleRepository;

    private EventDispatcherInterface $eventDispatcher;


    protected ?LoggerInterface $logger;

    public function __construct(
        $publishDestinationManager,
        $packageManager,
        ArticleRepositoryInterface $articleRepository,
        $articleManager,
        PublishDestinationProvider $publishDestinationProvider,
        EventDispatcherInterface $eventDispatcher,
    ) {
        $this->publishDestinationManager = $publishDestinationManager;
        $this->packageManager = $packageManager;
        $this->articleManager = $articleManager;

        $this->publishDestinationProvider = $publishDestinationProvider;
        $this->articleRepository = $articleRepository;
        $this->eventDispatcher = $eventDispatcher;
    }


    private function getPackage(GenericEvent $event): ?PackageInterface
    {
        return $event->getSubject()['package'] ?? null;
    }

    private function getOptions(GenericEvent $event): array
    {
        return $event->getSubject()['options'] ?? [];
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function postPackageOptions(GenericEvent $event)
    {
        $package = $this->getPackage($event);
        $options = $this->getOptions($event);

        $status = $options['status'] ?? '';
        if (!empty($status)) {
            $this->setArticlesStatus($package, $status);
            $this->setPackageStatus($package, $status);
        }

    }

    public function prePackageOptions(GenericEvent $event)
    {
        $package = $this->getPackage($event);

        /**
         * Delete all destinations
         */
        $this->deleteDestinations($package);
        /**
         * Remove service part from package
         */
        $this->clearPackageServices($package);
    }
    public function deleteDestinations($package): void
    {
        $this->eventDispatcher->dispatch(new GenericEvent(), MultiTenancyEvents::TENANTABLE_DISABLE);
        $destinations = $this->publishDestinationProvider->getDestinations($package);

        /**
         * @var PublishDestination $destination
         */
        foreach ($destinations as $destination) {
            $this->publishDestinationManager->remove($destination);
        }
        $this->publishDestinationManager->flush();
        $this->eventDispatcher->dispatch(new GenericEvent(), MultiTenancyEvents::TENANTABLE_ENABLE);
    }

    private function clearPackageServices(Package $package)
    {
        $this->eventDispatcher->dispatch(new GenericEvent(), MultiTenancyEvents::TENANTABLE_DISABLE);
        $package->setServices();
        $this->packageManager->persist($package);
        $this->packageManager->flush();
        $this->eventDispatcher->dispatch(new GenericEvent(), MultiTenancyEvents::TENANTABLE_ENABLE);
    }

    private function setArticlesStatus(PackageInterface $package, string $status)
    {
        $this->eventDispatcher->dispatch(new GenericEvent(), MultiTenancyEvents::TENANTABLE_DISABLE);
        $articles = $this->articleRepository
            ->getArticlesByPackage($package)
            ->getQuery()
            ->getResult();

        foreach ($articles as $article) {
            $article->setStatus($status);
            $this->articleManager->flush();
        }

        $this->eventDispatcher->dispatch(new GenericEvent(), MultiTenancyEvents::TENANTABLE_ENABLE);
    }

    private function setPackageStatus(Package $package, string $status)
    {
        $this->eventDispatcher->dispatch(new GenericEvent(), MultiTenancyEvents::TENANTABLE_DISABLE);
        $package->setStatus($status);
        $this->packageManager->persist($package);
        $this->packageManager->flush();
        $this->eventDispatcher->dispatch(new GenericEvent(), MultiTenancyEvents::TENANTABLE_ENABLE);
    }
}
