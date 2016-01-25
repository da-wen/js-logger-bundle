<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 21/01/16
 * Time: 14:54
 */

namespace Dawen\Bundle\JsLoggerBundle\Component;


use Psr\Log\LoggerInterface;

class JsLogger implements JsLoggerInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array
     */
    private $allowedLevels;

    /**
     * @var array
     */
    private $levelToMethod = array(
        'emergency' => 'emerg',
        'alert' => 'alert',
        'critical' => 'crit',
        'error' => 'err',
        'warning' => 'warn',
        'notice' => 'notice',
        'info' => 'info',
        'debug' => 'debug',
    );

    /**
     * JsLogger constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger, array $allowedLevels = [])
    {
        $this->logger = $logger;
        $this->allowedLevels = $allowedLevels;
    }

    /**
     * @param string $level
     * @param string $message
     * @param array $context
     *
     * @return bool
     */
    public function log($level, $message, array $context = array())
    {
        if (!$message) {
            return false;
        }

        $level = strtolower($level);
        if (!empty($this->allowedLevels) && !in_array($level, $this->allowedLevels)) {
            return false;
        }

        $this->logger->{$this->levelToMethod[$level]}($message, $context);
        return true;
    }
}