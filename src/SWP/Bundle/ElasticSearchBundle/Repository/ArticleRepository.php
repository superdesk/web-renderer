<?php

declare(strict_types=1);

/*
 * This file is part of the Superdesk Web Publisher ElasticSearch Bundle.
 *
 * Copyright 2017 Sourcefabric z.ú. and contributors.
 *
 * For the full copyright and license information, please see the
 * AUTHORS and LICENSE files distributed with this source code.
 *
 * @copyright 2017 Sourcefabric z.ú
 * @license http://www.superdesk.org/license
 */

namespace SWP\Bundle\ElasticSearchBundle\Repository;

use Elastica\Query;
use Elastica\Query\BoolQuery;
use Elastica\Query\MatchAll;
use Elastica\Query\MultiMatch;
use Elastica\Query\Nested;
use Elastica\Query\Range;
use Elastica\Query\Term;
use FOS\ElasticaBundle\Paginator\PaginatorAdapterInterface;
use FOS\ElasticaBundle\Repository;
use SWP\Bundle\ElasticSearchBundle\Criteria\Criteria;
use SWP\Bundle\ElasticSearchBundle\Loader\SearchResultLoader;

class ArticleRepository extends Repository
{
    public function findByCriteria(Criteria $criteria, array $extraFields = []): PaginatorAdapterInterface
    {
        $fields = $criteria->getFilters()->getFields();
        $boolFilter = new BoolQuery();

        if (null !== $criteria->getTerm() && '' !== $criteria->getTerm()) {
            $searchBy = ['title', 'lead', 'body', 'keywords.name'];

            foreach ($extraFields as $extraField) {
                $searchBy[] = 'extra.'.$extraField;
            }

            $boolQuery = new BoolQuery();

            $phraseMultiMatchQuery = new MultiMatch();
            $phraseMultiMatchQuery->setQuery($criteria->getTerm());
            $phraseMultiMatchQuery->setFields($searchBy);
            $phraseMultiMatchQuery->setType(MultiMatch::TYPE_PHRASE);
            $phraseMultiMatchQuery->setParam('boost', 5);

            $boolQuery->addShould($phraseMultiMatchQuery);

            $multiMatchQuery = new MultiMatch();
            $multiMatchQuery->setQuery($criteria->getTerm());
            $multiMatchQuery->setFields($searchBy);

            $boolQuery->addShould($multiMatchQuery);

            $bool = new BoolQuery();
            $bool->setBoost(10);
            $bool->addMust(new Query\Match('authors.name', $criteria->getTerm()));

            $nested = new Nested();
            $nested->setPath('authors');
            $nested->setQuery($bool);

            $boolQuery->addShould($nested);

            $boolFilter->addMust($boolQuery);
        } else {
            $boolFilter->addMust(new MatchAll());
        }

        if (null !== $fields->get('keywords') && !empty($fields->get('keywords'))) {
            $bool = new BoolQuery();
            $bool->addFilter(new Query\Terms('keywords.name', $fields->get('keywords')));
            $nested = new Nested();
            $nested->setPath('keywords');
            $nested->setQuery($bool);
            $boolFilter->addMust($nested);
        }

        if (null !== $fields->get('authors') && !empty($fields->get('authors'))) {
            $bool = new BoolQuery();
            foreach ($fields->get('authors') as $author) {
                $bool->addFilter(new Query\Match('authors.name', $author));
            }

            $nested = new Nested();
            $nested->setPath('authors');
            $nested->setQuery($bool);
            $boolFilter->addMust($nested);
        }

        if (null !== $fields->get('sources') && !empty($fields->get('sources'))) {
            $nested = new Nested();
            $nested->setPath('sources');
            $boolQuery = new BoolQuery();
            $boolQuery->addMust(new Query\Terms('sources.name', $fields->get('sources')));
            $nested->setQuery($boolQuery);
            $boolFilter->addMust($nested);
        }

        if (null !== $fields->get('statuses') && !empty($fields->get('statuses'))) {
            $boolFilter->addFilter(new Query\Terms('status', $fields->get('statuses')));
        }

        if (null !== $fields->get('metadata') && !empty($fields->get('metadata'))) {
            foreach ($fields->get('metadata') as $key => $values) {
                foreach ((array) $values as $value) {
                    $boolFilter->addFilter(new Query\Match($key, $value));
                }
            }
        }

        if (null !== $fields->get('tenantCode')) {
            $boolFilter->addFilter(new Term(['tenantCode' => $fields->get('tenantCode')]));
        }

        $bool = new BoolQuery();
        if (null !== $fields->get('routes') && !empty($fields->get('routes'))) {
            $bool->addFilter(new Query\Terms('route.id', $fields->get('routes')));
        }

        if (null !== $fields->get('publishedAfter') || null !== $fields->get('publishedBefore')) {
            $boolFilter->addFilter(new Range(
                'publishedAt',
                [
                    'gte' => null !== $fields->get('publishedAfter') ? $fields->get('publishedAfter')->format('Y-m-d') : null,
                    'lte' => null !== $fields->get('publishedBefore') ? $fields->get('publishedBefore')->format('Y-m-d') : null,
                ]
            ));

            $boolFilter->addFilter(new \Elastica\Query\Term(['isPublishable' => true]));
        }

        if (!empty($bool->getParams())) {
            $boolFilter->addMust($bool);
        }

        $query = Query::create($boolFilter)
            ->addSort([
                '_score' => 'desc',
                $criteria->getOrder()->getField() => $criteria->getOrder()->getDirection(),
            ]);

        $query->setSize(SearchResultLoader::MAX_RESULTS);

        return $this->createPaginatorAdapter($query);
    }
}
