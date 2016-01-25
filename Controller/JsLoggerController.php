<?php

namespace Dawen\Bundle\JsLoggerBundle\Controller;

use Dawen\Bundle\JsLoggerBundle\Component\JsLoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class JsLoggerController extends Controller
{
    public function logAction(Request $request)
    {
        $level = $request->query->get('level');
        $message = $request->query->get('message');
        $context= $request->query->all();

        if(isset($context['level'])) {
            unset($context['level']);
        }

        if(isset($context['message'])) {
            unset($context['message']);
        }

        if ($this->get(JsLoggerInterface::DIC_NAME)->log($level, $message, $context)) {
            return new Response(
                base64_decode('R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs'),
                201,
                array('Content-Type' => 'image/gif'));
        }

        return new Response('', 400);
    }
}
