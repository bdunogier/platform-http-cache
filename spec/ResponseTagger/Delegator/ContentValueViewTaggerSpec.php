<?php

namespace spec\EzSystems\PlatformHttpCacheBundle\ResponseTagger\Delegator;

use eZ\Publish\API\Repository\Values\Content\ContentInfo;
use EzSystems\PlatformHttpCacheBundle\ResponseTagger\Delegator\ContentValueViewTagger;
use EzSystems\PlatformHttpCacheBundle\ResponseTagger\ResponseTagger;
use eZ\Publish\Core\MVC\Symfony\View\ContentValueView;
use eZ\Publish\Core\Repository\Values\Content\Content;
use eZ\Publish\Core\Repository\Values\Content\VersionInfo;
use PhpSpec\ObjectBehavior;

class ContentValueViewTaggerSpec extends ObjectBehavior
{
    public function let(ResponseTagger $contentInfoTagger)
    {
        $this->beConstructedWith($contentInfoTagger);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ContentValueViewTagger::class);
    }

    public function it_delegates_tagging_of_the_content_info(
        ResponseTagger $contentInfoTagger,
        ContentValueView $view
    ) {
        $contentInfo = new ContentInfo();
        $content = new Content(['versionInfo' => new VersionInfo(['contentInfo' => $contentInfo])]);
        $view->getContent()->willReturn($content);

        $this->tag($view);

        $contentInfoTagger->tag($contentInfo)->shouldHaveBeenCalled();
    }
}
