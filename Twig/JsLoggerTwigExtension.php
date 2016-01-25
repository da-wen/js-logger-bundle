<?php
/**
 * Created by PhpStorm.
 * User: dwendlandt
 * Date: 25/01/16
 * Time: 12:00
 */

namespace Dawen\Bundle\JsLoggerBundle\Twig;

use Symfony\Component\Asset\Packages;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class JsLoggerTwigExtension extends \Twig_Extension
{
    const DIC_NAME = 'js_logger.twig.extension';
    const ASSET_PATH = 'bundles/jslogger/js/jslogger.min.js';
    const ROUTE = 'js_logger_log';

    /**
     * @var UrlGeneratorInterface
     */
    private $router;

    /**
     * @var Packages
     */
    private $packages;

    /**
     * @var bool
     */
    private $isEnabled;

    /**
     * @var array
     */
    private $allowedLevels;

    /**
     * JsLoggerTwigExtension constructor.
     *
     * @param UrlGeneratorInterface $router
     * @param Packages $packages
     * @param bool $isEnabled
     * @param array $allowedLevels
     */
    public function __construct(UrlGeneratorInterface $router,
                                Packages $packages,
                                $isEnabled = true,
                                array $allowedLevels = [])
    {
        $this->router = $router;
        $this->packages = $packages;
        $this->isEnabled = $isEnabled;
        $this->allowedLevels = $allowedLevels;
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('js_logger', array($this, 'initLogger'), array('is_safe' => array('html', 'js'))),
        );
    }

    public function initLogger()
    {
        if(false === $this->isEnabled) {
            return '';
        }

        $backendUrl = addslashes($this->router->generate(self::ROUTE));
        $scriptUrl = $this->packages->getUrl(self::ASSET_PATH);
        $allowedLevels = (empty($this->allowedLevels)) ?
            '' :
            ' data-allowed-levels="'. implode(',', $this->allowedLevels) . '""';

        return '<script src="' . $scriptUrl . '" data-backendUrl="' . $backendUrl . '"' . $allowedLevels . '></script>';
    }

    public function getName()
    {
        return 'js_logger';
    }
}