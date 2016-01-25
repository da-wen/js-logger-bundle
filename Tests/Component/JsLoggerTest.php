<?php

namespace Dawen\Bundle\JsLoggerBundle\Tests\Component;

use Dawen\Bundle\JsLoggerBundle\Component\JsLogger;
use Dawen\Bundle\JsLoggerBundle\Component\JsLoggerInterface;

class JsLoggerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    /**
     * @var JsLoggerInterface
     */
    private $jsLogger;

    protected function setUp()
    {
        parent::setUp();

        $this->logger = $this->getMockBuilder('Monolog\Logger')
            ->disableOriginalConstructor()
            ->getMock();

        $this->jsLogger = new JsLogger($this->logger);
    }

    protected function tearDown()
    {
        $this->logger = null;
        $this->jsLogger = null;

        parent::tearDown();
    }

    public function testInstance()
    {
        $this->assertInstanceOf('Dawen\Bundle\JsLoggerBundle\Component\JsLoggerInterface', $this->jsLogger);
        $this->assertInstanceOf('Dawen\Bundle\JsLoggerBundle\Component\JsLogger', $this->jsLogger);
    }

    public function testLogWithoutMessage()
    {
        $level = 'error';
        $msg = null;
        $context = ['my' => 'context'];

        $this->logger->expects($this->never())->method('err');

        $result = $this->jsLogger->log($level, $msg, $context);
        $this->assertFalse($result);
    }

    public function testLogWithRestrictedLevels()
    {
        $level = 'error';
        $msg = 'my-message';
        $context = ['my' => 'context'];

        $this->jsLogger = new JsLogger($this->logger, ['warning']);

        $this->logger->expects($this->never())->method('err');

        $result = $this->jsLogger->log($level, $msg, $context);
        $this->assertFalse($result);
    }

    public function testLogWithoutLevelRestriction()
    {
        $level = 'error';
        $msg = 'my-message';
        $context = ['my' => 'context'];

        $this->logger->expects($this->once())
            ->method('err')
            ->with($msg, $context);

        $result = $this->jsLogger->log($level, $msg, $context);

        $this->assertTrue($result);
    }

    public function testLogWithoutLevelRestrictionWithCamelCaseLevel()
    {
        $level = 'ErRor';
        $msg = 'my-message';
        $context = ['my' => 'context'];

        $this->logger->expects($this->once())
            ->method('err')
            ->with($msg, $context);

        $result = $this->jsLogger->log($level, $msg, $context);

        $this->assertTrue($result);
    }

    public function testAllAvailableLevels()
    {
        $msg = 'my-message';
        $levels = [
            'emergency' => 'emerg',
            'alert' => 'alert',
            'critical' => 'crit',
            'error' => 'err',
            'warning' => 'warn',
            'notice' => 'notice',
            'info' => 'info',
            'debug' => 'debug',
        ];

        foreach($levels as $level => $levelFunction) {

            $this->logger->expects($this->once())
                ->method($levelFunction)
                ->with($msg . '-' . $level, ['level' => $level, 'func' => $levelFunction]);
        }

        foreach($levels as $level => $levelFunction) {
            $result = $this->jsLogger->log($level, $msg . '-' . $level, ['level' => $level, 'func' => $levelFunction]);
            $this->assertTrue($result);
        }
    }

    public function testLogWithNOtExistingLevel()
    {
        $level = 'not-existing-level';
        $msg = 'my-message';
        $context = ['my' => 'context'];

        $this->logger->expects($this->never())->method('err');

        $result = $this->jsLogger->log($level, $msg, $context);

        $this->assertFalse($result);
    }
}