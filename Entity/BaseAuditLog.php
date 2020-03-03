<?php

/*
 * This file is part of the XiideaEasyAuditBundle package.
 *
 * (c) Xiidea <http://www.xiidea.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Xiidea\EasyAuditBundle\Entity;

use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel;
use Xiidea\EasyAuditBundle\Traits\EntityHydrationMethod;

abstract class BaseAuditLog
{
    use EntityHydrationMethod;

    /**
     * @var string
     */
    protected $typeId;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var \DateTime
     */
    protected $eventTime;

    protected $user;

    /**
     * @var string
     */
    protected $email;

    /**
     * @var String
     */
    protected $ip;

    /**
     * @var integer
     */
    protected $port;

    /**
     * @var string
     */
    protected $host;

    /**
     * @var string
     */
    protected $userAgent;

    /**
     * @var String
     */
    protected $level = LogLevel::INFO;

    public function getUser()
    {
        return $this->user;
    }

    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return \DateTime
     */
    final public function getEventTime()
    {
        return $this->eventTime;
    }

    /**
     * @param \DateTime $eventTime
     */
    final public function setEventTime(\DateTime $eventTime)
    {
        $this->eventTime = $eventTime;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getTypeId()
    {
        return $this->typeId;
    }

    /**
     * @param string $typeId
     *
     * @return $this
     */
    public function setTypeId($typeId)
    {
        $this->typeId = $typeId;

        return $this;
    }

    /**
     * @return String
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @param String $ip
     *
     * @return $this
     */
    public function setIp($ip)
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * @return string
     */
    final public function getLevel()
    {
        return $this->level;
    }

    /**
     * @param string $level
     * @return $this
     */
    final public function setLevel($level)
    {
        if (!in_array(strtolower($level), $this->getAllowedLevel())) {
            throw new InvalidArgumentException();
        }

        $this->level = $level;

        return $this;
    }

    private function getAllowedLevel()
    {
        $oClass = new \ReflectionClass ('Psr\Log\LogLevel');

        return $oClass->getConstants();
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param int $port
     */
    public function setPort($port)
    {
        $this->port = $port;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param string $host
     */
    public function setHost($host)
    {
        $this->host = $host;
    }

    /**
     * @return string
     */
    public function getUserAgent()
    {
        return $this->userAgent;
    }

    /**
     * @param string $userAgent
     */
    public function setUserAgent($userAgent)
    {
        $this->userAgent = $userAgent;
    }
}
