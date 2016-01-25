<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 21/01/16
 * Time: 14:54
 */

namespace Dawen\Bundle\JsLoggerBundle\Component;


interface JsLoggerInterface
{
    const DIC_NAME = 'js_logger.logger';

    /**
     * @param string $level
     * @param string $message
     * @param array $context
     *
     * @return bool
     */
    public function log($level, $message, array $context = array());

}