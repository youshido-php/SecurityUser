<?php
/**
 * Date: 10.11.15
 *
 * @author Portey Vasil <portey@gmail.com>
 */

namespace Youshido\SecurityUserBundle\Service;


use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Youshido\SecurityUserBundle\Entity\SecuredUser;

class Mailer extends ContainerAware
{

    public function sendRecoveryLetter(SecuredUser $user)
    {
        $this->sendLetter($user, 'recovery');
    }

    public function sendRegistrationLetter(SecuredUser $user)
    {
        $this->sendLetter($user, 'register');
    }

    private function sendLetter(SecuredUser $user, $action)
    {
        if ($this->container->getParameter('youshido_security_user.send_mails.' . $action)) {
            switch ($action) {
                case 'register':
                    $url = $this->container->get('router')->generate(
                        'security.user.activate',
                        [
                            'id'     => $user->getId(),
                            'secret' => $user->getActivationCode()
                        ],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    );

                    break;

                case 'recovery':
                    $url = $this->container->get('router')->generate(
                        'security.recovery_redirect',
                        [
                            'id'     => $user->getId(),
                            'secret' => $user->getActivationCode()
                        ],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    );

                    break;

                default:
                    throw new \Exception('Action not found!');
            }

            $html = $this->container->get('templating')->render(
                $this->container->getParameter('youshido_security_user.templates.' . $action . '_letter'),
                ['user' => $user, 'url' => $url]
            );

            $message = \Swift_Message::newInstance()
                ->setSubject($this->container->getParameter('youshido_security_user.mailer.subjects.' . $action))
                ->setFrom($this->container->getParameter('youshido_security_user.mailer.from'))
                ->setTo($user->getEmail())
                ->setBody($html, 'text/html');

            $this->container->get('mailer')->send($message);
        }
    }

}