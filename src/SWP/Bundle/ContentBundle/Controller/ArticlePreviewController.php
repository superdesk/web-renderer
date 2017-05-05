<?php

declare(strict_types=1);

/*
 * This file is part of the Superdesk Web Publisher Content Bundle.
 *
 * Copyright 2017 Sourcefabric z.ú. and contributors.
 *
 * For the full copyright and license information, please see the
 * AUTHORS and LICENSE files distributed with this source code.
 *
 * @copyright 2017 Sourcefabric z.ú
 * @license http://www.superdesk.org/license
 */

namespace SWP\Bundle\ContentBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use SWP\Bundle\ContentBundle\Model\ArticleInterface;
use SWP\Bundle\ContentBundle\Model\RouteInterface;
use SWP\Bundle\CoreBundle\Model\UserInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class ArticlePreviewController extends Controller
{
    /**
     * @Route("/article/preview/{routeId}/{slug}/{token}", options={"expose"=true}, requirements={"slug"=".+", "routeId"="\d+", "token"=".+"}, name="swp_article_preview")
     * @Method("GET")
     */
    public function previewAction(int $routeId, string $slug, string $token)
    {
        $this->ensureIsGranted($token);

        /** @var RouteInterface $route */
        $route = $this->findRouteOr404($routeId);
        /** @var ArticleInterface $article */
        $article = $this->findArticleOr404($slug);

        $metaFactory = $this->get('swp_template_engine_context.factory.meta_factory');
        $templateEngineContext = $this->get('swp_template_engine_context');
        $templateEngineContext->setCurrentPage($metaFactory->create($route));
        $templateEngineContext->getMetaForValue($article);

        if (null === $route->getArticlesTemplateName()) {
            throw $this->createNotFoundException(
                sprintf('Template "%s" not found!', $route->getArticlesTemplateName())
            );
        }

        return $this->render($route->getArticlesTemplateName());
    }

    private function ensureIsGranted(string $token)
    {
        $apiKey = $this->get('swp.repository.api_key')
            ->getValidToken($token)
            ->getQuery()
            ->getOneOrNullResult();

        if (null === $apiKey) {
            throw $this->createAccessDeniedException();
        }

        /** @var UserInterface $user */
        $user = $apiKey->getUser();

        if (!$user->hasRole('ROLE_ARTICLE_PREVIEW')) {
            throw $this->createAccessDeniedException();
        }
    }

    private function findRouteOr404(int $id)
    {
        if (null === ($route = $this->get('swp.repository.route')->findOneBy(['id' => $id]))) {
            throw $this->createNotFoundException(sprintf('Route with id: "%s" not found!', $id));
        }

        return $route;
    }

    private function findArticleOr404(string $slug)
    {
        if (null === ($article = $this->get('swp.repository.article')->findOneBy(['slug' => $slug]))) {
            throw $this->createNotFoundException(sprintf('Article with slug: "%s" not found!', $slug));
        }

        return $article;
    }
}
