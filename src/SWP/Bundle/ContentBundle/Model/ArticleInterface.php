<?php

/*
 * This file is part of the Superdesk Web Publisher Content Bundle.
 *
 * Copyright 2016 Sourcefabric z.ú. and contributors.
 *
 * For the full copyright and license information, please see the
 * AUTHORS and LICENSE files distributed with this source code.
 *
 * @copyright 2016 Sourcefabric z.ú
 * @license http://www.superdesk.org/license
 */

namespace SWP\Bundle\ContentBundle\Model;

use Doctrine\Common\Collections\Collection;
use SWP\Bundle\ContentBundle\Doctrine\TimestampableCancelInterface;
use SWP\Component\Bridge\Model\AuthorsAwareInterface;
use SWP\Component\Common\Model\SoftDeletableInterface;
use SWP\Component\Common\Model\TimestampableInterface;
use SWP\Component\Common\Model\TranslatableInterface;
use SWP\Component\Rule\Model\RuleSubjectInterface;
use SWP\Component\Seo\Model\SeoMetadataAwareInterface;
use SWP\Component\Storage\Model\PersistableInterface;

interface ArticleInterface extends TimestampableInterface, TimestampableCancelInterface, RuleSubjectInterface, TranslatableInterface, PersistableInterface, SoftDeletableInterface, MetadataAwareInterface, MediaAwareInterface, AuthorsAwareInterface, KeywordAwareInterface, RelatedArticlesAwareInterface, SeoMetadataAwareInterface {
  const STATUS_NEW = 'new';

  const STATUS_PUBLISHED = 'published';

  const STATUS_UNPUBLISHED = 'unpublished';

  const STATUS_CANCELED = 'canceled';

  /**
   * @return mixed
   */
  public function getId();

  /**
   * @return string
   */
  public function getBody();

  /**
   * @param string $body
   */
  public function setBody($body);

  /**
   * @return string
   */
  public function getTitle();

  /**
   * @param string $title
   */
  public function setTitle($title);

  /**
   * @return string
   */
  public function getSlug();

  /**
   * @param string $slug
   */
  public function setSlug($slug);

  /**
   * @return \DateTime
   */
  public function getPublishedAt();

  /**
   * @param string $publishedAt
   *
   * @return \DateTime
   */
  public function setPublishedAt(?\DateTime $publishedAt);

  /**
   * @return bool
   */
  public function isPublished();

  /**
   * @param string $status
   *
   * @return string
   */
  public function setStatus($status);

  /**
   * @return string
   */
  public function getStatus();

  /**
   * @return string
   */
  public function getTemplateName();

  /**
   * @param string $templateName
   */
  public function setTemplateName($templateName);

  /**
   * @param RouteInterface|void $route
   */
  public function setRoute(RouteInterface $route = null);

  /**
   * @return RouteInterface
   */
  public function getRoute();

  /**
   * @return string
   */
  public function getLead();

  /**
   * @param string $lead
   */
  public function setLead($lead);

  public function getCode(): string;

  public function setCode(string $code);

  public function addSourceReference(ArticleSourceReferenceInterface $source);

  public function removeSourceReference(ArticleSourceReferenceInterface $source);

  public function hasSourceReference(ArticleSourceReferenceInterface $source): bool;

  /**
   * @return Collection|ArticleSourceReferenceInterface[]
   */
  public function getSources(): Collection;

  public function getExtra(): array;

  public function setExtra(?array $extra): void;

  public function getSlideshows(): Collection;

  public function hasSlideshow(SlideshowInterface $slideshow): bool;

  public function addSlideshow(SlideshowInterface $slideshow): void;

  public function removeSlideshow(SlideshowInterface $slideshow): void;

  public function getPreviousRelativeUrl(): Collection;

  public function hasPreviousRelativeUrl(ArticlePreviousRelativeUrlInterface $previousRelativeUrl): bool;

  public function addPreviousRelativeUrl(ArticlePreviousRelativeUrlInterface $previousRelativeUrl): void;

  public function removePreviousRelativeUrl(ArticlePreviousRelativeUrlInterface $previousRelativeUrl): void;

  public function getData(): ?MetadataInterface;

  public function setData(?MetadataInterface $metadata): void;

  public function getExtraTextFields(): Collection;

  public function getExtraEmbedFields(): Collection;

  public function setExtraFields(array $extra): void;

  public function getExtraByKey(string $key): ?ArticleExtraFieldInterface;

  public function getExtraArray(): array;

  public function isPublishable(): bool;

  public function setPublishable(bool $boolean): void;

  public function setPublishStartDate(\DateTime $startDate = null);

  public function getPublishStartDate();

  public function setPublishEndDate(\DateTime $endDate = null);

  public function getPublishEndDate();
}
