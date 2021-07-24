<?php

/*
 * This file is part of the XiideaEasyAuditBundle package.
 *
 * (c) Xiidea <http://www.xiidea.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Xiidea\EasyAuditBundle\Resolver;

use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Xiidea\EasyAuditBundle\Common\UserAwareComponent;
use Xiidea\EasyAuditBundle\Entity\BaseAuditLog;
use Xiidea\EasyAuditBundle\Exception\InvalidServiceException;
use Xiidea\EasyAuditBundle\Exception\UnrecognizedEntityException;
use Xiidea\EasyAuditBundle\Exception\UnrecognizedEventInfoException;

class EventResolverFactory extends UserAwareComponent
{
    /**
     * @param Event $event
     * @param string $eventName
     * @return null|BaseAuditLog
     * @throws UnrecognizedEventInfoException
     * @throws \Exception
     */
    public function getEventLog(Event $event, $eventName)
    {
        $eventLog = $this->getEventLogObject($this->getEventLogInfo($event, $eventName));

        if ($eventLog === null) {
            return null;
        }

        $eventLog->setTypeId($eventName);
        $eventLog->setIp($this->getClientIp());
        $eventLog->setPort($this->getPort());
        $eventLog->setHost($this->getHost());
        $eventLog->setUserAgent($this->getUserAgent());
        $eventLog->setEventTime(new \DateTime());
        $this->setUser($eventLog);
        $this->setStaticValues($eventLog);

        return $eventLog;
    }

    /**
     * @param $eventInfo
     *
     * @return null|BaseAuditLog
     * @throws UnrecognizedEventInfoException
     */
    protected function getEventLogObject($eventInfo)
    {
        if (empty($eventInfo)) {
            return null;
        }

        if ($eventInfo instanceof BaseAuditLog) {
            return $eventInfo;
        }

        return $this->createEventObjectFromArray($eventInfo);
    }

    /**
     * @param string $eventName
     *
     * @throws \Exception
     * @return EventResolverInterface
     */
    protected function getResolver($eventName)
    {

        if ($this->isEntityEvent($eventName)) {
            return $this->getEntityEventResolver();
        }

        $customResolvers = $this->getParameter('custom_resolvers');

        if (isset($customResolvers[$eventName])) {
            return $this->getCustomResolver($customResolvers[$eventName]);
        }

        return $this->getCommonResolver();
    }

    /**
     * @param string $eventName
     * @return bool
     */
    protected function isEntityEvent($eventName)
    {
        return in_array($eventName, $this->getDoctrineEventsList());
    }

    /**
     * @param Event $event
     * @param string $eventName
     * @return null
     * @throws InvalidServiceException
     */
    protected function getEventLogInfo(Event $event, $eventName)
    {
        if ($event instanceof EmbeddedEventResolverInterface) {
            return $event->getEventLogInfo($eventName);
        }

        if (null === $eventResolver = $this->getResolver($eventName)) {
            return null;
        }

        return $eventResolver->getEventLogInfo($event, $eventName);
    }

    /**
     * @param BaseAuditLog $entity
     * @throws \Exception
     */
    protected function setUser(BaseAuditLog $entity)
    {
        $userProperty = $this->container->getParameter('xiidea.easy_audit.user_property');

        if (null === $user = $this->getUser()) {
            $entity->setUser($this->getAnonymousUserName());
            return;
        }

        $entity->setUser($this->getSettablePropertyValue($userProperty, $user));

        $emailProperty = $this->container->getParameter('xiidea.easy_audit.email_property');

        if ($emailProperty !== null) {
            $entity->setEmail($this->getSettablePropertyValue($emailProperty, $user));
        }
    }


    /**
     * @return string
     */
    protected function getClientIp()
    {
        try {
            return $this->container->get('request')->getClientIp();
        } catch (\Exception $e) {
            return "";
        }
    }

    /**
     * @return string
     */
    protected function getPort()
    {
        try {
            return $this->container->get('request')->getPort();
        } catch (\Exception $e) {
            return "";
        }
    }

    /**
     * @return string
     */
    protected function getHost()
    {
        try {
            return $this->container->get('request')->getHost();
        } catch (\Exception $e) {
            return "";
        }
    }

    /**
     * @return string
     */
    protected function getUserAgent()
    {
        try {
            return $this->container->get('request')->headers->get('User-Agent');
        } catch (\Exception $e) {
            return "";
        }
    }

    protected function setStaticValues($entity)
    {
        $staticFieldValues = $this->container->getParameter('xiidea.easy_audit.static_field_values');

        if (count($staticFieldValues)) {
            $this->setEntityProperties($entity, $staticFieldValues);
        }
    }

    function setEntityProperties($entity, array $array)
    {
        $refl = new \ReflectionClass($this->getParameter('entity_class'));

        foreach ($array as $propertyToSet => $value) {
            $property = $refl->getProperty($propertyToSet);
            $property->setAccessible(true);
            $property->setValue($entity, $value);
        }
    }

    /**
     * @return \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected function getContainer()
    {
        return $this->container;
    }

    /**
     * @return null
     * @throws InvalidServiceException
     */
    protected function handleInvalidResolverConfiguration()
    {
        if ($this->isDebug()) {
            throw new InvalidServiceException(
                'Resolver Service must implement' . __NAMESPACE__ . "EventResolverInterface"
            );
        }

        return null;
    }

    /**
     * @param $serviceName
     * @return null|EventResolverInterface
     * @throws InvalidServiceException
     */
    protected function getCustomResolver($serviceName)
    {
        $resolver = $this->getService($serviceName);

        if (!$resolver instanceof EventResolverInterface) {
            return $this->handleInvalidResolverConfiguration();
        }

        return $resolver;
    }

    /**
     * @param \Exception $e
     * @throws \Exception
     * @return null
     */
    protected function handleException(\Exception $e)
    {
        if ($this->isDebug()) {
            throw $e;
        }

        return null;
    }

    /**
     * @param $eventInfo
     * @return null|BaseAuditLog
     * @throws \Exception
     */
    protected function createEventObjectFromArray($eventInfo)
    {
        if (!is_array($eventInfo)) {
            return $this->handleException(new UnrecognizedEventInfoException());
        }

        $auditLogClass = $this->getParameter('entity_class');
        $eventObject = new $auditLogClass();

        if (!$eventObject instanceof BaseAuditLog) {
            return $this->handleException(new UnrecognizedEntityException());
        }

        return $eventObject->fromArray($eventInfo);
    }

    /**
     * @param $userProperty
     * @param $user
     * @return mixed
     */
    protected function getSettablePropertyValue($userProperty, $user)
    {
        if (empty($userProperty)) {
            return $user;
        }

        try {
            $propertyAccessor = PropertyAccess::createPropertyAccessor();
            return $propertyAccessor->getValue($user, $userProperty);
        } catch (NoSuchPropertyException $e) {
            return $this->handleException($e);
        }
    }
}
