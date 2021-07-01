<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\PlatformHttpCacheBundle\Twig;

use eZ\Publish\API\Repository\Values\Content\Location;
use EzSystems\PlatformHttpCacheBundle\Handler\ContentTagInterface;
use EzSystems\PlatformHttpCacheBundle\ResponseTagger\ResponseTagger;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig content extension for eZ Publish specific usage.
 * Exposes helpers to play with public API objects.
 */
class ContentTaggingExtension extends AbstractExtension
{
    /** @var \EzSystems\PlatformHttpCacheBundle\ResponseTagger\ResponseTagger */
    protected $responseTagger;

    /** @var \EzSystems\PlatformHttpCacheBundle\Handler\ContentTagInterface */
    protected $contentTagHandler;

    public function __construct(ResponseTagger $responseTagger, ContentTagInterface $contentTagHandler)
    {
        $this->responseTagger = $responseTagger;
        $this->contentTagHandler = $contentTagHandler;
    }

    /**
     * @return array|\Twig\TwigFunction[]
     */
    public function getFunctions()
    {
        return [
            new TwigFunction(
                'ez_http_cache_tag_location',
                [$this, 'tagHttpCacheForLocation']
            ),
            new TwigFunction(
                'ez_http_cache_tag_relation_ids',
                [$this, 'tagHttpCacheForRelationIds']
            ),
            new TwigFunction(
                'ez_http_cache_tag_relation_location_ids',
                [$this, 'tagHttpCacheForRelationLocationIds']
            ),
            // For 2.5 BC, and to be consistent with the other functions, to be cleaned up with new prefix in the future
            new TwigFunction(
                'ez_http_tag_location',
                [$this, 'tagHttpCacheForLocation'],
                [
                    'deprecated' => true,
                    'alternative' => 'ez_http_cache_tag_location',
                ]
            ),
            new TwigFunction(
                'ez_http_tag_relation_ids',
                [$this, 'tagHttpCacheForRelationIds'],
                [
                    'deprecated' => true,
                    'alternative' => 'ez_http_cache_tag_relation_ids',
                ]
            ),
            new TwigFunction(
                'ez_http_tag_relation_location_ids',
                [$this, 'tagHttpCacheForRelationLocationIds'],
                [
                    'deprecated' => true,
                    'alternative' => 'ez_http_cache_tag_relation_location_ids',
                ]
            ),
        ];
    }

    /**
     * Adds tags to current response, for all tags relevant for the location object.
     *
     * @internal Function is only for use within this class (and implicit by Twig).
     *
     * @param Location $location
     */
    public function tagHttpCacheForLocation(Location $location)
    {
        $this->responseTagger->tag($location);
        $this->responseTagger->tag($location->getContentInfo());
    }

    /**
     * Adds tags to current response, for relations only.
     *
     * @internal Function is only for use within this class (and implicit by Twig).
     *
     * @param int|int[] $contentIds
     */
    public function tagHttpCacheForRelationIds($contentIds)
    {
        $this->contentTagHandler->addRelationTags((array)$contentIds);
    }

    /**
     * Adds tags to current response, for relations locations only.
     *
     * @internal Function is only for use within this class (and implicit by Twig).
     *
     * @param int|int[] $locationIds
     */
    public function tagHttpCacheForRelationLocationIds($locationIds)
    {
        $this->contentTagHandler->addRelationLocationTags((array)$locationIds);
    }
}
