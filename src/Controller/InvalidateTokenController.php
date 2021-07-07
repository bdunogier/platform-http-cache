<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\PlatformHttpCacheBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\IpUtils;
use FOS\HttpCacheBundle\Handler\TagHandler;
use eZ\Publish\Core\MVC\ConfigResolverInterface;

class InvalidateTokenController
{
    public const TOKEN_HEADER_NAME = 'X-Invalidate-Token';

    /**
     * @var \eZ\Publish\Core\MVC\ConfigResolverInterface
     */
    private $configResolver;

    /**
     * @var int
     */
    private $ttl;

    /**
     * @var FOS\HttpCacheBundle\Handler\TagHandler
     */
    private $tagHandler;

    /**
     * TokenController constructor.
     *
     * @param ConfigResolverInterface $configResolver
     * @param int $ttl
     * @param TagHandler $tagHandler
     *
     * @internal param string $invalidatetoken
     */
    public function __construct(ConfigResolverInterface $configResolver, $ttl, TagHandler $tagHandler)
    {
        $this->configResolver = $configResolver;
        $this->ttl = $ttl;
        $this->tagHandler = $tagHandler;
    }

    /**
     * Request::isFromTrustedProxy is private in Symfony <3.1, so this is a re-implementation of it.
     *
     * @param Request $request
     *
     * @return bool
     */
    private function isFromTrustedProxy(Request $request)
    {
        return $request->getTrustedProxies() && IpUtils::checkIp($request->server->get('REMOTE_ADDR'), $request->getTrustedProxies());
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function tokenAction(Request $request)
    {
        $response = new Response();

        // Important to keep this condition, as .vcl rely on this to prevent everyone from being able to fetch the token.
        if ($request->headers->get('accept') !== 'application/vnd.ezplatform.invalidate-token') {
            $response->setStatusCode(400, 'Bad request');

            return $response;
        }

        $this->tagHandler->addTags(['ez-invalidate-token']);

        $token = $this->configResolver->getParameter('http_cache.varnish_invalidate_token');
        // PHP send PURGE with configured token included
        // -> Varnish validate it by sending `/_ez_http_invalidatetoken` with the same token header included
        // -> PHP validate tokens match (and return token to make it cached on Varnish)
        if ($request->headers->get(self::TOKEN_HEADER_NAME) !== $token) {
            $response->setStatusCode(401, 'Unauthorized');

            return $response;
        }

        $headers = $response->headers;
        $headers->set('Content-Type', 'application/vnd.ezplatform.invalidate-token');
        $headers->set(self::TOKEN_HEADER_NAME, $token);
        $response->setSharedMaxAge($this->ttl);
        $response->setVary('Accept', true);

        return $response;
    }
}
