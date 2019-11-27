<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\PlatformHttpCacheBundle\SignalSlot;

use eZ\Publish\Core\SignalSlot\Signal;

/**
 * A slot handling UnAssignUserFromUserGroupSignal.
 */
class UnassignUserFromUserGroupSlot extends AbstractContentSlot
{
    /**
     * @param \eZ\Publish\Core\SignalSlot\Signal\UserService\UnAssignUserFromUserGroupSignal $signal
     */
    protected function generateTags(Signal $signal)
    {
        return ['content-' . $signal->userId, 'content-' . $signal->userGroupId, 'ez-user-context-hash'];
    }

    protected function supports(Signal $signal)
    {
        return $signal instanceof Signal\UserService\UnAssignUserFromUserGroupSignal;
    }
}
