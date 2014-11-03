<?php

/*
 * This file is part of the XiideaEasyAuditBundle package.
 *
 * (c) Xiidea <http://www.xiidea.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Xiidea\EasyAuditBundle\Tests\Fixtures\Common;

use Symfony\Component\EventDispatcher\Event;
use Xiidea\EasyAuditBundle\Resolver\EventResolverInterface;

/** Custom Event Resolver Example Class */
class CustomEventResolver implements EventResolverInterface
{
    /**
     * @param Event $event
     *
     * @return array
     */
    public function getEventLogInfo(Event $event)
    {
        return array(
            'description' => 'Custom description',
            'type' => $event->getName(),
        );
    }
}
