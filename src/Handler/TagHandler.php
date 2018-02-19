<?php

namespace EzSystems\PlatformHttpCacheBundle\Handler;

use FOS\HttpCacheBundle\Handler\TagHandler as FOSTagHandler;
use Symfony\Component\HttpFoundation\Response;
use FOS\HttpCacheBundle\CacheManager;

/**
 * This is not a full implementation of FOS TagHandler
 * It extends extends TagHandler and implements invalidateTags() and purge() so that you may run
 * php app/console fos:httpcache:invalidate:tag <tag>.
 *
 * It implements tagResponse() to make sure TagSubscriber( a FOS event listener ) do not try to tag the response.
 * as we use ConfigurableResponseCacheConfigurator for that purpose instead.
 */
class TagHandler extends FOSTagHandler implements TagHandlerInterface
{
    private $cacheManager;
    private $purgeClient;
    private $tagsHeader;

    public function __construct(CacheManager $cacheManager, $tagsHeader, $purgeClient)
    {
        $this->cacheManager = $cacheManager;
        $this->tagsHeader = $tagsHeader;
        $this->purgeClient = $purgeClient;
    }

    public function invalidateTags(array $tags)
    {
        $this->purge($tags);
    }

    public function purge($tags)
    {
        $this->purgeClient->purge($tags);
    }

    public function tagResponse(Response $response, $replace = false)
    {
        return $this;
    }

    public function addTagHeaders(Response $response, array $tags)
    {
        if ($response->headers->has($this->tagsHeader)) {
            // Get as array and handle both array based and string based values
            $headerValue = $response->headers->get($this->tagsHeader, null, false);
            $tags = array_merge(
                $tags,
                count($headerValue) === 1 ? explode(' ', $headerValue[0]) : $headerValue
            );
        }

        $response->headers->set($this->tagsHeader, implode(' ', array_unique($tags)));
    }
}
