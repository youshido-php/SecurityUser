<?php
/**
 * Date: 31.07.15
 *
 * @author Portey Vasil <portey@gmail.com>
 */

namespace Youshido\SecurityUserBundle\Service;


use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class AuthenticationHandler extends ContainerAware implements AuthenticationSuccessHandlerInterface, AuthenticationFailureHandlerInterface
{

    /** @var RouterInterface */
    private $router;
    /** @var Session */
    private $session;

    public function __construct(RouterInterface $router, Session $session)
    {
        $this->router = $router;
        $this->session = $session;
    }

    /**
     * @inheritdoc
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        if ($request->isXmlHttpRequest()) {

            $array = array( // data to return via JSON
                'success'      => false,
                'message'      => $exception->getMessage(),
                'redirect_url' => $this->generateRedirectUrl($request, 'on_failure')
            );
            $response = new Response(json_encode($array));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        } else {
            $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);

            return new RedirectResponse($this->router->generate('security.login'));
        }
    }

    /**
     * @inheritdoc
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        if ($request->isXmlHttpRequest()) {
            $array = [
                'success' => true,
                'redirect_url' => $this->generateRedirectUrl($request)
            ] ;

            $response = new Response(json_encode($array));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        } else {
            return new RedirectResponse($this->generateRedirectUrl($request));
        }
    }

    private function generateRedirectUrl(Request $request, $key = 'on_success')
    {
        $referer = $request->get('referer');
        if($this->container->getParameter('youshido_security_user.redirects.user_referer') && $referer){
            return $referer;
        }

        return $this->router->generate($this->container->getParameter('youshido_security_user.redirects.'.$key));
    }
}