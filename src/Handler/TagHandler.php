<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\PlatformHttpCacheBundle\Handler;

use EzSystems\PlatformHttpCacheBundle\PurgeClient\PurgeClientInterface;
use EzSystems\PlatformHttpCacheBundle\RepositoryTagPrefix;
use FOS\HttpCache\Exception\ExceptionCollection;
use FOS\HttpCache\Exception\InvalidArgumentException;
use FOS\HttpCache\Exception\UnsupportedProxyOperationException;
use FOS\HttpCache\ProxyClient\Invalidation\PurgeCapable;
use FOS\HttpCache\ResponseTagger as FOSResponseTagger;
use FOS\HttpCacheBundle\Handler\TagHandler as FOSTagHandler;
use FOS\HttpCacheBundle\Http\SymfonyResponseTagger;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * This is not a full implementation of FOS TagHandler
 * It extends extends TagHandler and implements invalidateTags() and purge() so that you may run
 * php app/console fos:httpcache:invalidate:tag <tag>.
 *
 * It implements tagResponse() to make sure TagSubscriber (a FOS event listener) sends tags using the header
 * we have configured, and to be able to prefix tags with respository id in order to support multi repo setups.
 */
class TagHandler extends SymfonyResponseTagger
{
    private $prefixService;
    private $tagsHeader;

    public function __construct(
        $tagsHeader,
        RepositoryTagPrefix $prefixService,
        array $options = []
    ) {
        $this->tagsHeader = $tagsHeader;
        $this->prefixService = $prefixService;

        parent::__construct($options);
        $this->addTags(['ez-all']);
    }

    /**
     * Tag a symfony response with the previously added tags.
     *
     * @param Response $response
     * @param bool     $replace  Whether to replace the current tags on the
     *                           response. If false, parses the header to merge
     *                           tags
     *
     * @return $this
     */
    public function tagSymfonyResponse(Response $response, $replace = false)
    {
        $tags = [];
        if (!$replace && $response->headers->has($this->tagsHeader)) {
            $headers = $response->headers->get($this->tagsHeader, null, false);
            if (!empty($headers)) {
                // handle both both comma (FOS) and space (this bundle/xkey/fastly) separated strings
                // As there can be more requests going on, we don't add these to tag handler (ez-user-context-hash)
                $tags = preg_split("/[\s,]+/", implode(' ', $headers));
            }
        }

        if ($this->hasTags()) {
            $tags = array_merge($tags, explode(',', $this->getTagsHeaderValue()));

            // Prefix tags with repository prefix (to be able to support several repositories on one proxy)
            $repoPrefix = $this->prefixService->getRepositoryPrefix();
            if (!empty($repoPrefix)) {
                $tags = array_map(
                    static function ($tag) use ($repoPrefix) {
                        return $repoPrefix . $tag;
                    },
                    $tags
                );
                // Also add a un-prefixed `ez-all` in order to be able to purge all across repos
                $tags[] = 'ez-all';
            }

            $response->headers->set($this->tagsHeader, implode(' ', array_unique($tags)));
        }

        return $this;
    }
}
