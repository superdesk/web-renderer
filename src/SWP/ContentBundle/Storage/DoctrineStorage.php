<?php

/**
 * This file is part of the Superdesk Web Publisher Content Bundle.
 *
 * Copyright 2015 Sourcefabric z.u. and contributors.
 *
 * For the full copyright and license information, please see the
 * AUTHORS and LICENSE files distributed with this source code.
 *
 * @copyright 2015 Sourcefabric z.ú.
 * @license http://www.superdesk.org/license
 */

namespace SWP\ContentBundle\Storage;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ODM\PHPCR\DocumentManager;
use InvalidArgumentException;
use SWP\ContentBundle\Document\DocumentNotFoundException;
use SWP\ContentBundle\Document\DocumentLockedException;
use SWP\ContentBundle\Storage\StorageException;

/**
 * Generic Doctrine storage implementation. All Doctrine storage libraries
 * implement the Doctrine\Common\Persistence\ObjectManager interface.
 */
class DoctrineStorage extends AbstractStorage
{
    /**
     * Doctrine object manager.
     *
     * @var \Doctrine\Common\Persistence\ObjectManager
     */
    protected $manager;

    public function __construct(ObjectManager $documentManager)
    {
        $this->manager = $documentManager;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchDocument(
        $class,
        $id,
        VersionInterface $version = null,
        LocaleInterface $locale = null
    ) {
        $document = $this->manager->find($class, $documentId);

        if (is_null($document) {
            throw new DocumentNotFoundException('Document doesn\'t exist.');
        }

        return $document;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchDocuments(
        $class,
        array $id,
        VersionInterface $version = null,
        LocaleInterface $locale = null
    ) {
        $documents = $this->manager->find($class, $documentIds);

        if (is_null($documents) {
            throw new DocumentNotFoundException('Document doesn\'t exist.');
        }

        return $documents;
    }

    /**
     * {@inheritdoc}
     */
    public function searchDocuments($classes, $parameters)
    {
        $classes = (is_string($classes)) ? array($classes) : $classes;

        if (!is_array($classes)) {
            throw new InvalidArgumentException('Invalid datatype for first argument.');
        }
        if (!is_null($parameters) || !is_array($parameters)) {
            throw new InvalidArgumentException('Invalid datatype for second argument.');
        }

        $repositories = array();
        foreach ($classes as $class) {
            $repositories[] = $this->manager->getRepository($class);
        }
        if (count($repositories) <= 0) {
            throw new StorageException('No repositories to select from.');
        }

        $specialParameters = $this->extractSpecialParameters($parameters);
        $parameters = array_diff_assoc($parameters, $specialParameters);

        foreach ($repositories as $repository) {
            $documents = $repository->findBy(
                $parameters,
                $specialParameters['orderfull'],
                $specialParameters['limit'],
                $specialParameters['offset']
            );
        }

        if (is_null($documents) {
            throw new DocumentNotFoundException('Document doesn\'t exist.');
        }

        return $documents;
    }

    /**
     * {@inheritdoc}
     */
    public function saveDocument($document, $forceWhenLocked = false)
    {
        if ($this->documentExists($document->getId()) && $this->documentIdLocked($document->getId()) && !$forceWhenLocked) {
            throw new DocumentLockedException('Cannot overwrite a locked Document.');
        } else {
            try {
                $this->manager->persist($document);
                $this->manager->flush();
            } catch(\Exception $e) {
                throw new StorageException('Could not save document.', $e->getCode(), $e);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteDocument($documentId, $forceWhenLocked = false)
    {
        if ($this->documentIsLocked($documentId) && !$forceWhenLocked) {
            throw new DocumentLockedException('Cannot delete a locked Document.');
        }

        $document = $this->fetch($documentId);
        $this->manager->remove($document);
        $this->manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function documentExists($documentId)
    {
        return (!is_null($this->manager->find(null, $documentId)));
    }
}
