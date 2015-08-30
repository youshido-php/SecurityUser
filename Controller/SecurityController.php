<?php
/**
 * Date: 28.08.15
 *
 * @author Portey Vasil <portey@gmail.com>
 */

namespace Youshido\SecurityUserBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Youshido\SecurityUserBundle\Entity\SecuredUser;
use Youshido\SecurityUserBundle\Form\Type\ChangePasswordType;
use Youshido\SecurityUserBundle\Form\Type\RecoveryType;

class SecurityController extends Controller
{

    /**
     * @Route("/login", name="security.login")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function loginAction()
    {
        $authenticationUtils = $this->get('security.authentication_utils');

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render(
            $this->getParameter('youshido_security_user.templates.login_form'),
            array(
                'last_username' => $lastUsername,
                'error' => $error,
            )
        );
    }

    /**
     * @param Request $request
     * @Route("login_check", name="security.login_check")
     */
    public function checkLoginAction(Request $request)
    {

    }

    /**
     * @Route("/app/logout", name="security.logout")
     */
    public function logoutAction()
    {

    }

    /**
     * @Route("/recovery", name="security.recovery")
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function recoveryAction(Request $request)
    {
        $form = $this->createForm(new RecoveryType(), null, [
            'action' => $this->generateUrl('security.recovery')
        ]);

        $form->handleRequest($request);

        $error = '';
        if ($form->isValid()) {
            $data = $form->getData();
            $email = $data['email'];

            $user = $this->getDoctrine()->getRepository('YoushidoSecurityUserBundle:SecuredUser')
                ->findByEmail($email);

            if ($user && method_exists($user, 'getApproved') && $user->getApproved()) {
                $recoveryUrl = $this->generateRecoveryUrl($user);

                //todo: send recovery email

                return $this->render($this->getParameter('youshido_security_user.templates.recovery_success'));
            } else {
                $error = 'Ca\'nt recognize email';
            }
        }

        return $this->render($this->getParameter('youshido_security_user.templates.recovery_form'), [
            'form' => $form->createView(),
            'error' => $error
        ]);
    }

    /**
     * @param $user SecuredUser
     * @return string
     */
    private function generateRecoveryUrl($user)
    {
        $field = $user->getPassword();

        return $this->generateUrl('security.recovery_redirect', [
            'id' => $user->getId(),
            'secret' => $this->generateSecret($field)
        ]);
    }

    private function generateSecret($field)
    {
        $secret = $this->getParameter('secret');

        return md5($secret . $field);
    }

    /**
     * @Route("/recovery/{id}/{secret}", name="security.recovery_redirect")
     *
     * @param Request $request
     * @param $id
     * @param $secret
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function recoveryRedirectAction(Request $request, $id, $secret)
    {
        $user = $this->getDoctrine()->getRepository('YoushidoSecurityUserBundle:SecuredUser')
            ->find($id);

        if ($user) {
            $correctSecret = $this->generateSecret($user->getPassword());

            if ($secret === $correctSecret) {
                $form = $this->createForm(new ChangePasswordType(), null, [
                    'action' => $this->generateUrl('security.recovery_redirect', ['id' => $id, 'secret' => $secret])
                ]);

                $form->handleRequest($request);

                if ($form->isValid()) {
                    $data = $form->getData();
                    $password = $data['password'];

                    $encoded = $this->generatePassword($user, $password);

                    $user->setPassword($encoded);

                    $this->getDoctrine()->getManager()->persist($user);
                    $this->getDoctrine()->getManager()->flush();

                    return $this->render($this->getParameter('youshido_security_user.templates.change_password_success'));
                }

                return $this->render($this->getParameter('youshido_security_user.templates.change_password_form'), [
                    'form' => $form->createView()
                ]);
            }
        }

        return $this->render($this->getParameter('youshido_security_user.templates.change_password_error'));
    }

    /**
     * @param $user
     * @param $password
     * @return string
     */
    private function generatePassword(SecuredUser $user, $password)
    {
        $encoder = $this->container->get('security.password_encoder');

        return $encoder->encodePassword($user, $password);
    }

    /**
     * @Route("/register", name="security.register")
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function registerAction(Request $request)
    {
        /** @var SecuredUser $user */
        $modelClass = $this->getParameter('youshido_security_user.model');
        $user = new $modelClass;

        $typeClass = $this->getParameter('youshido_security_user.form.registration');
        $type = new $typeClass;

        $form = $this->createForm($type, $user, array(
            'action' => $this->generateUrl('security.register'),
        ));

        $form->handleRequest($request);

        if ($form->isValid()) {
            $encoded = $this->generatePassword($user, $user->getPassword());

            $user->setPassword($encoded);

            $this->getDoctrine()->getManager()->persist($user);
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute($this->getParameter('youshido_security_user.redirects.register_success'));
        }

        return $this->render($this->getParameter('youshido_security_user.templates.register_form'), [
            'form' => $form->createView(),
            'type' => $type
        ]);
    }

}