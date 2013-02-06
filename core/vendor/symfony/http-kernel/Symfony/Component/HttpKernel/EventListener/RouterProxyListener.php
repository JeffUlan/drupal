<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpKernel\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\UriSigner;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Proxies URIs when the current route name is "_proxy".
 *
 * If the request does not come from a trusted IP, it throws an
 * AccessDeniedHttpException exception.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class RouterProxyListener implements EventSubscriberInterface
{
    private $signer;
    private $proxyPath;

    /**
     * Constructor.
     *
     * @param UriSigner $signer    A UriSigner instance
     * @param string    $proxyPath The path that triggers this listener
     */
    public function __construct(UriSigner $signer, $proxyPath = '/_proxy')
    {
        $this->signer = $signer;
        $this->proxyPath = $proxyPath;
    }

    /**
     * Fixes request attributes when the route is '_proxy'.
     *
     * @param GetResponseEvent $event A GetResponseEvent instance
     *
     * @throws AccessDeniedHttpException if the request does not come from a trusted IP.
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if ($this->proxyPath !== rawurldecode($request->getPathInfo())) {
            return;
        }

        $this->validateRequest($request);

        parse_str($request->query->get('_path', ''), $attributes);
        $request->attributes->add($attributes);
        $request->attributes->set('_route_params', array_replace($request->attributes->get('_route_params', array()), $attributes));
        $request->query->remove('_path');
    }

    protected function validateRequest(Request $request)
    {
        // is the Request safe?
        if (!$request->isMethodSafe()) {
            throw new AccessDeniedHttpException();
        }

        // does the Request come from a trusted IP?
        $trustedIps = array_merge($this->getLocalIpAddresses(), $request->getTrustedProxies());
        $remoteAddress = $request->server->get('REMOTE_ADDR');
        foreach ($trustedIps as $ip) {
            if (IpUtils::checkIp($remoteAddress, $ip)) {
                return;
            }
        }

        // is the Request signed?
        // we cannot use $request->getUri() here as we want to work with the original URI (no query string reordering)
        if ($this->signer->check($request->getSchemeAndHttpHost().$request->getBaseUrl().$request->getPathInfo().(null !== ($qs = $request->server->get('QUERY_STRING')) ? '?'.$qs : ''))) {
            return;
        }

        throw new AccessDeniedHttpException();
    }

    protected function getLocalIpAddresses()
    {
        return array('127.0.0.1', 'fe80::1', '::1');
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST => array(array('onKernelRequest', 48)),
        );
    }
}
