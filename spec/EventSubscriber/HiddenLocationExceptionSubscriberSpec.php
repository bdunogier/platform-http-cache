<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace spec\EzSystems\PlatformHttpCacheBundle\EventSubscriber;

use EzSystems\PlatformHttpCacheBundle\ResponseTagger\Value\ContentInfoTagger;
use EzSystems\PlatformHttpCacheBundle\ResponseTagger\Value\LocationTagger;
use PhpSpec\ObjectBehavior;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\API\Repository\Values\Content\ContentInfo;
use eZ\Publish\Core\MVC\Exception\HiddenLocationException;
use Prophecy\Argument;

class HiddenLocationExceptionSubscriberSpec extends ObjectBehavior
{
    public function let(
        LocationTagger $locationTagger,
        ContentInfoTagger $contentInfoTagger
    ) {
        $this->beConstructedWith($locationTagger, $contentInfoTagger);
    }

    public function it_tags_on_hidden_location_exception(
        GetResponseForExceptionEvent $event,
        LocationTagger $locationTagger,
        ContentInfoTagger $contentInfoTagger,
        Location $location,
        ContentInfo $contentInfo,
        HiddenLocationException $exception
    ) {
        $event->getException()->willReturn($exception);
        $exception->getLocation()->willReturn($location);
        $location->getContentInfo()->willReturn($contentInfo);
        $locationTagger->tag($location)->willReturn($locationTagger);
        $contentInfoTagger->tag($contentInfo)->willReturn($contentInfoTagger);

        $this->tagHiddenLocationExceptionResponse($event);

        $event->getException()->shouldHaveBeenCalled();
        $exception->getLocation()->shouldHaveBeenCalled();
        $locationTagger->tag(Argument::type(Location::class))->shouldHaveBeenCalled();
        $contentInfoTagger->tag(Argument::type(ContentInfo::class))->shouldHaveBeenCalled();
    }

    public function it_does_not_tag_on_other_exceptions(
        GetResponseForExceptionEvent $event,
        LocationTagger $locationTagger,
        ContentInfoTagger $contentInfoTagger,
        \Exception $exception
    ) {
        $event->getException()->willReturn($exception);

        $locationTagger->tag(Argument::type(Location::class))->shouldNotBeCalled();
        $contentInfoTagger->tag(Argument::type(ContentInfo::class))->shouldNotBeCalled();

        $this->tagHiddenLocationExceptionResponse($event);
    }
}
