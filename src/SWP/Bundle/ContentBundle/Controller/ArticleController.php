<?php

/*
 * This file is part of the Superdesk Web Publisher Content Bundle.
 *
 * Copyright 2015 Sourcefabric z.u. and contributors.
 *
 * For the full copyright and license information, please see the
 * AUTHORS and LICENSE files distributed with this source code.
 *
 * @copyright 2015 Sourcefabric z.ú
 * @license http://www.superdesk.org/license
 */

namespace SWP\Bundle\ContentBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use SWP\Bundle\ContentBundle\Doctrine\ArticleRepositoryInterface;
use SWP\Bundle\ContentBundle\Form\Type\ArticleType;
use SWP\Bundle\ContentBundle\Provider\ArticleProviderInterface;
use SWP\Bundle\ContentBundle\Provider\RouteProviderInterface;
use SWP\Bundle\ContentBundle\Service\ArticleServiceInterface;
use SWP\Component\Common\Criteria\Criteria;
use SWP\Component\Common\Pagination\PaginationData;
use SWP\Component\Common\Response\ResourcesListResponse;
use SWP\Component\Common\Response\ResourcesListResponseInterface;
use SWP\Component\Common\Response\ResponseContext;
use SWP\Component\Common\Response\SingleResourceResponse;
use SWP\Component\Common\Response\SingleResourceResponseInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use FOS\RestBundle\Controller\Annotations\Route;

class ArticleController extends AbstractController {

  private FormFactoryInterface $formFactory;
  private RouteProviderInterface $routeProvider; // swp.provider.route
  private ArticleRepositoryInterface $articleRepository; //swp.repository.article
  private ArticleProviderInterface $articleProvider; //swp.provider.article
  private EventDispatcherInterface  $eventDispatcher;
  private EntityManagerInterface $entityManager; // swp.object_manager.article
  private ArticleServiceInterface $articleService; // swp.service.article

  /**
   * @param FormFactoryInterface $formFactory
   * @param RouteProviderInterface $routeProvider
   * @param ArticleRepositoryInterface $articleRepository
   * @param ArticleProviderInterface $articleProvider
   * @param EventDispatcherInterface $eventDispatcher
   * @param EntityManagerInterface $entityManager
   * @param ArticleServiceInterface $articleService
   */
  public function __construct(FormFactoryInterface       $formFactory, RouteProviderInterface $routeProvider,
                              ArticleRepositoryInterface $articleRepository, ArticleProviderInterface $articleProvider,
                              EventDispatcherInterface   $eventDispatcher, EntityManagerInterface $entityManager,
                              ArticleServiceInterface    $articleService) {
    $this->formFactory = $formFactory;
    $this->routeProvider = $routeProvider;
    $this->articleRepository = $articleRepository;
    $this->articleProvider = $articleProvider;
    $this->eventDispatcher = $eventDispatcher;
    $this->entityManager = $entityManager;
    $this->articleService = $articleService;
  }


  /**
   * @Route("/api/{version}/content/articles/", methods={"GET"}, options={"expose"=true}, defaults={"version"="v2"}, name="swp_api_content_list_articles")
   *
   * @throws \Exception
   */
  public function listAction(Request $request): ResourcesListResponseInterface {
    $authors = '';
    if (null !== $request->query->get('author', null)) {
      $authors = explode(', ', $request->query->get('author'));
    }

    if ($request->query->get('route', false) && $request->query->get('includeSubRoutes', false)) {
      $routeObject = $this->routeProvider->getOneById($request->query->get('route'));

      if (null !== $routeObject) {
        $ids = [$routeObject->getId()];
        foreach ($routeObject->getChildren() as $child) {
          $ids[] = $child->getId();
        }
        $request->query->set('route', $ids);
      }
    }

    $articles = $this->articleRepository
        ->getPaginatedByCriteria($this->eventDispatcher, new Criteria([
            'status' => $request->query->get('status', ''),
            'route' => $request->query->get('route', ''),
            'publishedBefore' => $request->query->has('publishedBefore') ? new \DateTime($request->query->get('publishedBefore')) : null,
            'publishedAfter' => $request->query->has('publishedAfter') ? new \DateTime($request->query->get('publishedAfter')) : null,
            'author' => $authors,
            'query' => $request->query->get('query', ''),
            'source' => $request->query->get('source'),
        ]), $request->query->all('sorting'), new PaginationData($request));

    return new ResourcesListResponse($articles);
  }

  /**
   * @Route("/api/{version}/content/articles/{id}", methods={"GET"}, options={"expose"=true}, defaults={"version"="v2"}, name="swp_api_content_show_articles", requirements={"id"=".+"})
   */
  public function getAction($id): SingleResourceResponseInterface {
    $article = $this->articleProvider->getOneById($id);

    if (null === $article) {
      throw new NotFoundHttpException('Article was not found');
    }

    return new SingleResourceResponse($article);
  }

  /**
   * @Route("/api/{version}/content/article/search-code", methods={"GET"}, options={"expose"=true}, defaults={"version"="v2"}, name="swp_api_content_show_articles_by_code", requirements={"code"=".+"})
   */
  public function getByCodeAction(Request $request): SingleResourceResponseInterface {
    // Extract parameters from the request
    $code = $request->query->get('code', '');

    $article = $this->entityManager->createQuery('SELECT a.id FROM SWP\Bundle\ContentBundle\Model\Article a where a.code = :code')
        ->setParameter('code', $code)
        //->setParameter('tenant_code', $tenantCode)
        ->setMaxResults(1)
        ->getOneOrNullResult();

    if (null === $article) {
      throw new NotFoundHttpException('Article was not found');
    }

    $article->setStatus('new');
    $article->setRouteId(null);
    $article->setPublishedAt(null);
    $article->setIsPublishable(false);
    $this->entityManager->flush();

    // Find the swp_package associated with the article
    $swpPackage = $article->getPackage();

    if (null !== $swpPackage) {
        // Update the swp_package fields
        $swpPackage->setStatus('new');
        $swpPackage->setServices([]);
        $swpPackage->setFirstPublishedAt(null);
        $this->entityManager->flush();
    }

    return new SingleResourceResponse($article);
  }

  /**
   * @Route("/api/{version}/content/articles/{id}", methods={"PATCH"}, options={"expose"=true}, defaults={"version"="v2"}, name="swp_api_content_update_articles", requirements={"id"=".+"})
   */
  public function updateAction(Request $request, $id): SingleResourceResponseInterface {
    $objectManager = $this->entityManager;
    $article = $this->findOr404($id);
    $originalArticleStatus = $article->getStatus();

    $form = $this->formFactory->createNamed('', ArticleType::class, $article, ['method' => $request->getMethod()]);

    $form->handleRequest($request);
    if ($form->isSubmitted() && $form->isValid()) {
      $this->articleService->reactOnStatusChange($originalArticleStatus, $article);
      $objectManager->flush();
      $objectManager->refresh($article);

      return new SingleResourceResponse($article);
    }

    return new SingleResourceResponse($form, new ResponseContext(500));
  }

  /**
   * @Route("/api/{version}/content/articles/{id}", methods={"DELETE"}, options={"expose"=true}, defaults={"version"="v2"}, name="swp_api_content_delete_articles", requirements={"id"=".+"})
   */
  public function deleteAction($id): SingleResourceResponseInterface {
    $objectManager = $this->entityManager;
    $objectManager->remove($this->findOr404($id));
    $objectManager->flush();

    return new SingleResourceResponse(null, new ResponseContext(204));
  }

  private function findOr404($id) {
    if (null === $article = $this->articleProvider->getOneById($id)) {
      throw new NotFoundHttpException('Article was not found.');
    }

    return $article;
  }
}
