<?php

namespace Dawen\Bundle\JsLoggerBundle\Tests\Component;

use Dawen\Bundle\JsLoggerBundle\Twig\JsLoggerTwigExtension;

class JsLoggerTwigExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $router;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $packages;

    /**
     * @var JsLoggerTwigExtension
     */
    private $twigExtension;

    protected function setUp()
    {
        parent::setUp();

        $this->router = $this->getMockBuilder('Symfony\Component\Routing\Generator\UrlGeneratorInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->packages = $this->getMockBuilder('Symfony\Component\Asset\Packages')
            ->disableOriginalConstructor()
            ->getMock();

        $this->twigExtension = new JsLoggerTwigExtension($this->router, $this->packages);
    }

    protected function tearDown()
    {
        $this->router = null;
        $this->packages = null;
        $this->twigExtension = null;

        parent::tearDown();
    }

    public function testInstance()
    {
        $this->assertInstanceOf('Dawen\Bundle\JsLoggerBundle\Twig\JsLoggerTwigExtension', $this->twigExtension);
        $this->assertInstanceOf('\Twig_ExtensionInterface', $this->twigExtension);
        $this->assertInstanceOf('\Twig_Extension', $this->twigExtension);
    }

    public function testGetName()
    {
        $this->assertSame('js_logger', $this->twigExtension->getName());
    }

    public function testGetFunctions()
    {
        $result = $this->twigExtension->getFunctions();

        $this->assertTrue(is_array($result));
        $this->assertCount(1, $result);

        /** @var \Twig_SimpleFunction $function */
        $function = $result[0];
        $this->assertInstanceOf('\Twig_SimpleFunction', $function);
        $this->assertSame($this->twigExtension->getName(), $function->getName());
        $callable = $function->getCallable();
        $this->assertSame($this->twigExtension, $callable[0]);
        $this->assertSame('initLogger', $callable[1]);
    }

    public function testInitLoggerDisabled()
    {
        $this->router->expects($this->never())->method('generate');
        $this->packages->expects($this->never())->method('getUrl');

        $this->twigExtension = new JsLoggerTwigExtension($this->router, $this->packages, false);
        $this->assertSame('', $this->twigExtension->initLogger());
    }

    public function testInitLoggerWithoutLevelRestriction()
    {
        $backendUrl = '/my/backend';
        $assetUrl = '/my/asset';
        $expected = '<script src="' . $assetUrl . '" data-backend-url="' . $backendUrl . '"></script>';

        $this->router->expects($this->once())
            ->method('generate')
            ->with(JsLoggerTwigExtension::ROUTE)
            ->willReturn($backendUrl);
        $this->packages->expects($this->once())
            ->method('getUrl')
            ->with(JsLoggerTwigExtension::ASSET_PATH)
            ->willReturn($assetUrl);

        $this->assertSame($expected, $this->twigExtension->initLogger());
    }

    public function testInitLoggerWithLevelRestriction()
    {
        $backendUrl = '/my/backend';
        $assetUrl = '/my/asset';
        $allowedLevels = ['warning', 'error'];
        $expected = '<script src="' . $assetUrl . '" data-backend-url="' . $backendUrl . '" data-allowed-levels="' . implode(',', $allowedLevels) . '"></script>';

        $this->twigExtension = new JsLoggerTwigExtension($this->router, $this->packages, true, $allowedLevels);

        $this->router->expects($this->once())
            ->method('generate')
            ->with(JsLoggerTwigExtension::ROUTE)
            ->willReturn($backendUrl);
        $this->packages->expects($this->once())
            ->method('getUrl')
            ->with(JsLoggerTwigExtension::ASSET_PATH)
            ->willReturn($assetUrl);

//        var_dump($this->twigExtension->initLogger());
        $this->assertSame($expected, $this->twigExtension->initLogger());
    }

}