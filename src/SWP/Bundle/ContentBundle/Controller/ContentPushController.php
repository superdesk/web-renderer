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

use Doctrine\ORM\EntityManagerInterface;
use SWP\Bundle\BridgeBundle\Doctrine\ORM\PackageRepository;
use SWP\Bundle\ContentBundle\Doctrine\ArticleRepositoryInterface;
use SWP\Bundle\CoreBundle\Util\MimeTypeHelper;
use SWP\Component\MultiTenancy\Context\TenantContextInterface;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use SWP\Bundle\ContentBundle\Form\Type\MediaFileType;
use SWP\Bundle\ContentBundle\Manager\MediaManagerInterface;
use SWP\Bundle\ContentBundle\Model\ArticleMedia;
use SWP\Bundle\ContentBundle\Provider\FileProviderInterface;
use SWP\Bundle\CoreBundle\MessageHandler\Message\ContentPushMessage;
use SWP\Component\Bridge\Events;
use SWP\Component\Bridge\Transformer\DataTransformerInterface;
use SWP\Component\Common\Response\ResponseContext;
use SWP\Component\Common\Response\SingleResourceResponse;
use SWP\Component\Common\Response\SingleResourceResponseInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use FOS\RestBundle\Controller\Annotations\Route;

class ContentPushController extends AbstractController {
  private EventDispatcherInterface $eventDispatcher;
  private FormFactoryInterface $formFactory;
  private MessageBusInterface $messageBus;//swp_multi_tenancy.tenant_context
  private DataTransformerInterface $dataTransformer; // swp_bridge.transformer.json_to_package
  private MediaManagerInterface $mediaManager; // swp_content_bundle.manager.media
  private EntityManagerInterface $entityManager; // swp.object_manager.media
  private PackageRepository $packageRepository;//swp.repository.package
  private  ArticleRepositoryInterface $articleRepository;//swp.repository.article
  private FileProviderInterface $fileProvider;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param FormFactoryInterface $formFactory
     * @param MessageBusInterface $messageBus
     * @param DataTransformerInterface $dataTransformer
     * @param MediaManagerInterface $mediaManager
     * @param EntityManagerInterface $entityManager
     * @param PackageRepository $packageRepository
     * @param ArticleRepositoryInterface $articleRepository
     * @param FileProviderInterface $fileProvider
     */
  public function __construct(
      EventDispatcherInterface $eventDispatcher,
      FormFactoryInterface $formFactory,
      MessageBusInterface $messageBus,
      DataTransformerInterface $dataTransformer,
      MediaManagerInterface $mediaManager,
      EntityManagerInterface $entityManager,
      PackageRepository $packageRepository,
      ArticleRepositoryInterface $articleRepository,
      FileProviderInterface    $fileProvider
  ) {
    $this->eventDispatcher = $eventDispatcher;
    $this->formFactory = $formFactory;
    $this->messageBus = $messageBus;
    $this->dataTransformer = $dataTransformer;
    $this->mediaManager = $mediaManager;
    $this->entityManager = $entityManager;
    $this->packageRepository = $packageRepository;
    $this->articleRepository = $articleRepository;
    $this->fileProvider = $fileProvider;
  }


  /**
   * @Route("/api/{version}/content/push", methods={"POST"}, options={"expose"=true}, defaults={"version"="v2"}, name="swp_api_content_push")
   */
  public function pushContentAction(Request $request, TenantContextInterface $tenantContext): SingleResourceResponseInterface {
    $package = $this->dataTransformer->transform($request->getContent());
    $this->eventDispatcher->dispatch(new GenericEvent($package), Events::SWP_VALIDATION);

    $currentTenant = $tenantContext->getTenant();

    $this->messageBus->dispatch(
        new ContentPushMessage($currentTenant->getId(), $request->getContent())
    );

    return new SingleResourceResponse(['status' => 'OK'], new ResponseContext(201));
  }


    /**
     * @Route("/api/{version}/content/push-with-options", methods={"POST"}, options={"expose"=true}, defaults={"version"="v2"}, name="swp_api_content_push_with_status")
     */
    public function pushContentWithOptionsAction(
        Request $request,
        TenantContextInterface $tenantContext
    ): SingleResourceResponseInterface {
        $status = $request->query->get('status', '');
        $request->getQueryString();
        $package = $this->dataTransformer->transform($request->getContent());
        $this->eventDispatcher->dispatch(new GenericEvent($package), Events::SWP_VALIDATION);

        $currentTenant = $tenantContext->getTenant();

        $options = [];
        if (!empty($status)) {
            $options['status'] = $status;
        }
        $this->messageBus->dispatch(
            new ContentPushMessage($currentTenant->getId(), $request->getContent(), $options)
        );
      
        return new SingleResourceResponse(['status' => 'OK'], new ResponseContext(201));
    }

  /**
   * @Route("/api/{version}/assets/push", methods={"POST"}, options={"expose"=true}, defaults={"version"="v2"}, name="swp_api_assets_push")
   */
  public function pushAssetsAction(Request $request): SingleResourceResponseInterface {
    $form = $this->formFactory->createNamed('', MediaFileType::class);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $mediaManager = $this->mediaManager;
      $uploadedFile = $form->getData()['media'];
      $mediaId = $request->request->get('mediaId');

      if ($uploadedFile->isValid()) {
        $fileProvider = $this->fileProvider;
        $file = $fileProvider->getFile(ArticleMedia::handleMediaId($mediaId), $uploadedFile->guessExtension());

        if (null === $file) {
          $file = $mediaManager->handleUploadedFile($uploadedFile, $mediaId);
          $this->entityManager->flush();
        }

        return new SingleResourceResponse(
            [
//                'media_id' => $mediaId,
//                'URL' => $mediaManager->getMediaPublicUrl($file),
//                'media' => base64_encode($mediaManager->getFile($file)),
//                'mime_type' => Mime::getMimeFromExtension($file->getFileExtension()),
//                'filemeta' => [],
            ],
            new ResponseContext(201)
        );
      }

      throw new \Exception('Uploaded file is not valid:' . $uploadedFile->getErrorMessage());
    }

    return new SingleResourceResponse($form);
  }

  /**
   * @Route("/api/{version}/assets/{action}/{mediaId}.{extension}", methods={"GET"}, options={"expose"=true}, defaults={"version"="v2"}, requirements={"mediaId"=".+", "action"="get|push"}, name="swp_api_assets_get")
   */
  public function getAssetsAction(string $mediaId, string $extension): SingleResourceResponseInterface {
    $fileProvider = $this->fileProvider;
    $file = $fileProvider->getFile(ArticleMedia::handleMediaId($mediaId), $extension);

    if (null === $file) {
      throw new NotFoundHttpException('Media don\'t exist in storage');
    }

    $mediaManager = $this->mediaManager;

    return new SingleResourceResponse([
        'media_id' => $mediaId,
        'URL' => $mediaManager->getMediaPublicUrl($file),
        'media' => base64_encode($mediaManager->getFile($file)),
        'mime_type' => MimeTypeHelper::getByExtension($file->getFileExtension()),
        'filemeta' => [],
    ]);
  }

  protected function getPackageRepository() {
    return $this->packageRepository;
  }
}
