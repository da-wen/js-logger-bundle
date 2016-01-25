<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 07/04/15
 * Time: 15:51
 */

namespace Dawen\Bundle\JsLoggerBundle\Tests\Controller;

use Dawen\Bundle\JsLoggerBundle\Component\JsLoggerInterface;
use Dawen\Bundle\JsLoggerBundle\Controller\JsLoggerController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;

class JsLoggerControllerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $jsLogger;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $dic;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $request;

    /** @var JsLoggerController  */
    private $controller;

    protected function setUp()
    {
        parent::setUp();


        $this->dic = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerInterface')
            ->getMock();
        $this->jsLogger = $this->getMockBuilder('Dawen\Bundle\JsLoggerBundle\Component\JsLoggerInterface')
            ->getMock();
        $this->request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $this->controller = new JsLoggerController();
        $this->controller->setContainer($this->dic);
    }

    protected function tearDown()
    {
        $this->jsLogger = null;
        $this->dic = null;
        $this->controller = null;

        parent::tearDown();
    }

    public function testInstance()
    {
        $this->assertInstanceOf('Symfony\Bundle\FrameworkBundle\Controller\Controller', $this->controller);
        $this->assertInstanceOf('Dawen\Bundle\JsLoggerBundle\Controller\JsLoggerController', $this->controller);
    }

    public function testLogActionWithReturnFalse()
    {
        $level = 'my-level';
        $msg = 'my-message';
        $all = ['level' => $level, 'message' => $msg, 'context' => 'my-context-stuff'];
        $allTransformed = ['context' => 'my-context-stuff'];

        $query = $this->getMockBuilder('Symfony\Component\HttpFoundation\ParameterBag')
            ->disableOriginalConstructor()
            ->getMock();

        $query->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(['level'], ['message'])
            ->willReturnOnConsecutiveCalls($level, $msg);
        $query->expects($this->once())
            ->method('all')
            ->willReturn($all);
        $this->request->query = $query;

        $this->dic->expects($this->once())
            ->method('get')
            ->with(JsLoggerInterface::DIC_NAME)
            ->willReturn($this->jsLogger);
        $this->jsLogger->expects($this->once())
            ->method('log')
            ->with($level, $msg, $allTransformed)
            ->willReturn(false);

        $this->assertEquals(new Response('', 400), $this->controller->logAction($this->request));

    }

    public function testLogActionWithReturnTrue()
    {
        $level = 'my-level';
        $msg = 'my-message';
        $all = ['level' => $level, 'message' => $msg, 'context' => 'my-context-stuff'];
        $allTransformed = ['context' => 'my-context-stuff'];

        $query = $this->getMockBuilder('Symfony\Component\HttpFoundation\ParameterBag')
            ->disableOriginalConstructor()
            ->getMock();

        $query->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(['level'], ['message'])
            ->willReturnOnConsecutiveCalls($level, $msg);
        $query->expects($this->once())
            ->method('all')
            ->willReturn($all);
        $this->request->query = $query;

        $this->dic->expects($this->once())
            ->method('get')
            ->with(JsLoggerInterface::DIC_NAME)
            ->willReturn($this->jsLogger);
        $this->jsLogger->expects($this->once())
            ->method('log')
            ->with($level, $msg, $allTransformed)
            ->willReturn(true);

        $expected = new Response(
            base64_decode('R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs'),
            201,
            array('Content-Type' => 'image/gif'));

        $this->assertEquals($expected, $this->controller->logAction($this->request));

    }

//    public function testResetAction()
//    {
//        $this->businessCase->expects($this->once())->method('reset')->willReturn(true);
//
//        $this->dic
//            ->expects($this->once())
//            ->method('get')
//            ->with(WebUiBusinessCaseInterface::DIC_NAME)
//            ->willReturn($this->businessCase);
//
//        /** @var JsonResponse $response */
//        $response = $this->controller->resetAction();
//
//        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);
//        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
//    }
//
//    public function testToggleActionWithoutSuccess()
//    {
//        $featureName = 'my-feature';
//
//        $this->businessCase
//            ->expects($this->once())
//            ->method('toggleFeature')
//            ->with($this->equalTo($featureName))
//            ->willReturn(false);
//
//        $this->dic
//            ->expects($this->once())
//            ->method('get')
//            ->with(WebUiBusinessCaseInterface::DIC_NAME)
//            ->willReturn($this->businessCase);
//
//        /** @var JsonResponse $response */
//        $response = $this->controller->toggleAction($featureName);
//
//        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);
//        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
//    }
//
//    public function testToggleActionWithSuccess()
//    {
//        $featureName = 'my-feature';
//
//        $this->businessCase
//            ->expects($this->once())
//            ->method('toggleFeature')
//            ->with($this->equalTo($featureName))
//            ->willReturn(true);
//
//        $this->dic
//            ->expects($this->once())
//            ->method('get')
//            ->with(WebUiBusinessCaseInterface::DIC_NAME)
//            ->willReturn($this->businessCase);
//
//        /** @var JsonResponse $response */
//        $response = $this->controller->toggleAction($featureName);
//
//        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);
//        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
//    }


}