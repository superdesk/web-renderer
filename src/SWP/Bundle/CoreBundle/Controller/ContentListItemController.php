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

namespace SWP\Bundle\CoreBundle\Controller;

use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use SWP\Bundle\ContentBundle\ArticleEvents;
use SWP\Bundle\ContentBundle\Event\ArticleEvent;
use SWP\Bundle\ContentListBundle\Form\Type\ContentListItemsType;
use SWP\Bundle\ContentListBundle\Services\ContentListServiceInterface;
use SWP\Bundle\CoreBundle\Controller\ContentListController;
use SWP\Bundle\CoreBundle\Form\Type\ContentListItemType;
use SWP\Bundle\CoreBundle\Model\ContentListInterface;
use SWP\Bundle\CoreBundle\Model\ContentListItemInterface;
use SWP\Bundle\CoreBundle\Repository\ArticleRepositoryInterface;
use SWP\Bundle\CoreBundle\Repository\ContentListItemRepositoryInterface;
use SWP\Component\Common\Criteria\Criteria;
use SWP\Component\Common\Pagination\PaginationData;
use SWP\Component\Common\Response\ResourcesListResponse;
use SWP\Component\Common\Response\ResourcesListResponseInterface;
use SWP\Component\Common\Response\ResponseContext;
use SWP\Component\Common\Response\SingleResourceResponse;
use SWP\Component\Common\Response\SingleResourceResponseInterface;
use SWP\Component\ContentList\Model\ContentListAction;
use SWP\Component\ContentList\Repository\ContentListRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use FOS\RestBundle\Controller\Annotations\Route;

class ContentListItemController extends AbstractController {
  private ContentListItemRepositoryInterface $contentListItemRepository;
  private EntityManagerInterface $entityManager;
  private ContentListServiceInterface $contentListService;
  private EventDispatcherInterface $eventDispatcher;

  /**
   * @param ContentListItemRepositoryInterface $contentListItemRepository
   * @param EntityManagerInterface $entityManager
   * @param ContentListServiceInterface $contentListService
   * @param EventDispatcherInterface $eventDispatcher
   */
  public function __construct(
      ContentListItemRepositoryInterface $contentListItemRepository,
      EntityManagerInterface $entityManager,
      ContentListServiceInterface $contentListService,
      EventDispatcherInterface $eventDispatcher,
      string $invalidationCacheUrl,
      string $invalidationToken
  ) {
      $this->contentListItemRepository = $contentListItemRepository;
      $this->entityManager = $entityManager;
      $this->contentListService = $contentListService;
      $this->eventDispatcher = $eventDispatcher;
      $this->invalidationCacheUrl = $invalidationCacheUrl;
      $this->invalidationToken = $invalidationToken;
  }


  /**
   * @Route("/api/{version}/content/lists/{id}/items/", options={"expose"=true}, defaults={"version"="v2"}, methods={"GET"}, name="swp_api_core_list_items", requirements={"id"="\d+"})
   */
  public function listAction(Request $request, int $id): ResourcesListResponseInterface {
    $sort = $request->query->all('sorting');
    if (empty($sort)) {
      $sort = ['sticky' => 'desc'];
    }

    $items = $this->contentListItemRepository->getPaginatedByCriteria(
        $this->eventDispatcher,
        new Criteria([
            'contentList' => $id,
            'sticky' => $request->query->get('sticky', ''),
        ]),
        $sort,
        new PaginationData($request)
    );

    $responseContext = new ResponseContext();
    $responseContext->setSerializationGroups(
        [
            'Default',
            'api',
            'api_packages_list',
            'api_content_list_item_details',
            'api_articles_list',
            'api_articles_featuremedia',
            'api_article_authors',
            'api_article_media_list',
            'api_article_media_renditions',
            'api_articles_statistics_list',
            'api_image_details',
            'api_routes_list',
            'api_tenant_list',
        ]
    );

    return new ResourcesListResponse($items, $responseContext);
  }

  /**
   * @Route("/api/{version}/content/lists/{listId}/items/{id}", options={"expose"=true}, defaults={"version"="v2"}, methods={"GET"}, name="swp_api_core_show_lists_item", requirements={"id"="\d+"})
   */
  public function getAction($listId, $id) {
    return new SingleResourceResponse($this->findOr404($listId, $id));
  }

  /**
   * @Route("/api/{version}/content/lists/{listId}/items/{id}", options={"expose"=true}, defaults={"version"="v2"}, methods={"PATCH"}, name="swp_api_core_update_lists_item", requirements={"id"="\d+", "listId"="\d+"})
   */
  public function updateAction(Request $request, FormFactoryInterface $formFactory, $listId,
                                       $id): SingleResourceResponseInterface {
    $contentListItem = $this->findOr404($listId, $id);
    $form = $formFactory->createNamed(
        '',
        ContentListItemType::class,
        $contentListItem,
        ['method' => $request->getMethod()]
    );

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $contentListItem->getContentList()->setUpdatedAt(new DateTime());

      if (null !== $contentListItem->getStickyPosition()) {
        $contentListItem->setPosition($contentListItem->getStickyPosition());
      }

      $this->entityManager->flush();
        ContentListController::invalidateCache(
            $this->invalidationCacheUrl,
            $this->invalidationToken,
            [
                'id' => $contentListItem->getContentList()->getId(),
                'name' => $contentListItem->getContentList()->getName(),
                'type' => $contentListItem->getContentList()->getType(),
                'action' => 'CREATE'
            ]
        );

      return new SingleResourceResponse($contentListItem);
    }

    return new SingleResourceResponse($form, new ResponseContext(400));
  }

  /**
   * @Route("/api/{version}/content/lists/{listId}/items/", options={"expose"=true}, defaults={"version"="v2"}, methods={"PATCH"}, name="swp_api_core_batch_update_lists_item", requirements={"listId"="\d+"})
   */
  public function batchUpdateAction(
      Request                        $request,
      FormFactoryInterface           $formFactory,
      ContentListRepositoryInterface $contentListRepository,
      ArticleRepositoryInterface     $articleRepository,
      EventDispatcherInterface       $eventDispatcher,
      int                            $listId
  ): SingleResourceResponseInterface {
    /** @var ContentListInterface $list */
    $list = $contentListRepository->findOneBy(['id' => $listId]);
    if (null === $list) {
      throw new NotFoundHttpException(sprintf('Content list with id "%s" was not found.', $list));
    }

    $form = $formFactory->createNamed('', ContentListItemsType::class, [], ['method' => $request->getMethod()]);

    $form->handleRequest($request);
    if ($form->isSubmitted() && $form->isValid()) {
      $data = $form->getData();
      $updatedAt = DateTime::createFromFormat(DateTime::RFC3339, $data['updatedAt'], new DateTimeZone('UTC'));
      $updatedAt->setTimezone(new DateTimeZone('UTC'));
      $listUpdatedAt = $list->getUpdatedAt();
      $listUpdatedAt->setTimezone(new DateTimeZone('UTC'));
      if ($updatedAt < $listUpdatedAt) {
        throw new ConflictHttpException('List was already updated');
      }

      $updatedArticles = [];
      $updatedItemsInvalidateCache = [];
      /** @var ContentListAction $item */
      foreach ($data['items'] as $item) {
        $position = $item->getPosition();
        $isSticky = $item->isSticky();
        $contentId = $item->getContentId();

        $updatedItemsInvalidateCache[] = [
            'id' => $contentId,
            'action' => $item->getAction(),
            'sticky' => $item->isSticky(),
            'postition' => $item->getPosition()
        ];


        switch ($item->getAction()) {
          case ContentListAction::ACTION_MOVE:
            $updated = false;
            $contentListItem = $this->findByContentOr404($list, $contentId);

            if ($position !== $contentListItem->getPosition()) {
                $this->ensureThereIsNoItemOnPositionOrThrow409(
                    $listId,
                    $position,
                    $isSticky,
                    ContentListAction::ACTION_MOVE
                );
                $contentListItem->setPosition($position);
                $updated = true;
            }

            if ($isSticky !== $contentListItem->getStickyPosition()) {
                $this->contentListService->toggleStickOnItemPosition($contentListItem, $isSticky, $position);
                $updated = true;
            }

            if ($updated) {
                $list->setUpdatedAt(new DateTime('now'));
                $this->entityManager->flush();
            }

            $updatedArticles[$contentId] = $contentListItem->getContent();

            break;
          case ContentListAction::ACTION_ADD:
            $this->ensureThereIsNoItemOnPositionOrThrow409(
                $listId,
                $position,
                $isSticky,
                ContentListAction::ACTION_ADD
            );

            $object = $articleRepository->findOneById($contentId);
            $contentListItem = $this->contentListService->addArticleToContentList($list, $object, $position, $isSticky);

            $updatedArticles[$contentId] = $contentListItem->getContent();

            break;
          case ContentListAction::ACTION_DELETE:
            $contentListItem = $this->findByContentOr404($list, $contentId);
            $this->entityManager->remove($contentListItem);
            $list->setUpdatedAt(new DateTime('now'));
            $this->entityManager->flush();
            $updatedArticles[$contentId] = $contentListItem->getContent();

            break;
        }
      }

      $this->contentListService->repositionStickyItems($list);

      foreach ($updatedArticles as $updatedArticle) {
        $eventDispatcher->dispatch(new ArticleEvent(
            $updatedArticle,
            $updatedArticle->getPackage(),
            ArticleEvents::POST_UPDATE
        ), ArticleEvents::POST_UPDATE);
      }
        ContentListController::invalidateCache(
            $this->invalidationCacheUrl,
            $this->invalidationToken,
            [
                'id' => $list->getId(),
                'name' => $list->getName(),
                'type' => $list->getType(),
                'action' => 'BATCH-UPDATE',
                'items' => $updatedItemsInvalidateCache
            ]
        );

      return new SingleResourceResponse($list, new ResponseContext(201));
    }

    return new SingleResourceResponse($form, new ResponseContext(400));
  }

  private function findByContentOr404($listId, $contentId): ContentListItemInterface {
    /** @var ContentListItemInterface $listItem */
    $listItem = $this->contentListItemRepository->findOneBy([
        'contentList' => $listId,
        'content' => $contentId,
    ]);

    if (null === $listItem) {
      throw new NotFoundHttpException(sprintf('Content list item with content_id "%s" was not found on that list. If You want to add new item - use action type "add".', $contentId));
    }

    return $listItem;
  }

  private function findOr404($listId, $id): ContentListItemInterface {
    /** @var ContentListItemInterface $listItem */
    $listItem = $this->contentListItemRepository->findOneBy([
        'contentList' => $listId,
        'id' => $id,
    ]);

    if (null === $listItem) {
      throw new NotFoundHttpException(sprintf('Content list item with id "%s" was not found.', $id));
    }

    return $listItem;
  }

  private function ensureThereIsNoItemOnPositionOrThrow409(
      int $listId,
      int $position,
      bool $isSticky,
      string $action): void {
      $existingContentListItem = $this->contentListService->isAnyItemPinnedOnPosition($listId, $position);

      if (!$existingContentListItem && !$isSticky) {
          return;
      }

      if ($existingContentListItem && $existingContentListItem->isSticky()) {
        throw new ConflictHttpException('There is already an item pinned on that position. Unpin it first.');
      }

      if ($action === ContentListAction::ACTION_MOVE && $isSticky) {
          throw new ConflictHttpException('Cannot move pinned item. Unpin it first.');
      }
  }
}
