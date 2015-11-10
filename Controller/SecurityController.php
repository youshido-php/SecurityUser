<?php
/**
 * Date: 28.08.15
 *
 * @author Portey Vasil <portey@gmail.com>
 */

namespace Youshido\SecurityUserBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\ConstraintViolation;
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

        $error        = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render(
            $this->getParameter('youshido_security_user.templates.login_form'),
            [
                'last_username' => $lastUsername,
                'error'         => $error,
            ]
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
            $data  = $form->getData();
            $email = $data['email'];

            $user = $this->get('security.user_provider')->findUserByEmail($email);

            if ($user && method_exists($user, 'getApproved') && $user->getApproved()) {

                $this->sendLetter($user, 'recovery');

                return $this->render($this->getParameter('youshido_security_user.templates.recovery_success'));
            } else {
                $error = 'Ca\'nt recognize email';
            }
        }

        return $this->render($this->getParameter('youshido_security_user.templates.recovery_form'), [
            'form'  => $form->createView(),
            'error' => $error
        ]);
    }

    public function sendLetter(SecuredUser $user, $action)
    {
        if ($this->getParameter('youshido_security_user.send_mails.' . $action)) {
            switch ($action) {
                case 'register':
                    $url = $this->generateUrl('security.user.activate', [
                        'id'     => $user->getId(),
                        'secret' => $this->generateSecret($user->getPassword())
                    ], UrlGeneratorInterface::ABSOLUTE_URL);
                    break;

                case 'recovery':
                    $url = $this->generateUrl('security.recovery_redirect', [
                        'id'     => $user->getId(),
                        'secret' => $this->generateSecret($user->getPassword())
                    ], UrlGeneratorInterface::ABSOLUTE_URL);
                    break;

                default:
                    throw new \Exception('Action not found!');
            }


            $message = \Swift_Message::newInstance()
                ->setSubject($this->getParameter('youshido_security_user.mailer.subjects.' . $action))
                ->setFrom($this->getParameter('youshido_security_user.mailer.from'))
                ->setTo($user->getEmail())
                ->setBody(
                    $this->renderView(
                        $this->getParameter('youshido_security_user.templates.' . $action . '_letter'),
                        [
                            'user' => $user,
                            'url'  => $url
                        ]
                    ),
                    'text/html'
                );

            $this->get('mailer')->send($message);
        }
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
     * @param         $id
     * @param         $secret
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function recoveryRedirectAction(Request $request, $id, $secret)
    {
        $user = $this->get('security.user_provider')->findUserById($id);

        if ($user) {
            $correctSecret = $this->generateSecret($user->getPassword());

            if ($secret === $correctSecret) {
                $form = $this->createForm(new ChangePasswordType(), null, [
                    'action' => $this->generateUrl('security.recovery_redirect', ['id' => $id, 'secret' => $secret])
                ]);

                $form->handleRequest($request);

                if ($form->isValid()) {
                    $this->get('security.user_provider')->generateUserPassword($user, $form->getData()['password']);

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
     * @Route("/register", name="security.register")
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function registerAction(Request $request)
    {
        $user = $this->get('security.user_provider')->createNewUserInstance();

        $typeClass = $this->getParameter('youshido_security_user.form.registration');
        $type      = new $typeClass;

        $form = $this->createForm($type, $user, [
            'action' => $this->generateUrl('security.register'),
        ]);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->applyRegisterAction($user);

            return $this->redirectToRoute($this->getParameter('youshido_security_user.redirects.register_success'));
        }

        return $this->render($this->getParameter('youshido_security_user.templates.register_form'), [
            'form' => $form->createView(),
            'type' => $type
        ]);
    }

    private function applyRegisterAction(SecuredUser &$user)
    {
        $this->get('security.user_provider')->generateUserPassword($user, $user->getPassword());

        $user
            ->setActive(!$this->getParameter('youshido_security_user.send_mails.register'));

        $this->getDoctrine()->getManager()->persist($user);
        $this->getDoctrine()->getManager()->flush();

        if ($this->getParameter('youshido_security_user.send_mails.register')) {
            $this->sendLetter($user, 'register');
        }
    }

    /**
     * @Route("/security/ajax/register", name="security.ajax.register")
     */
    public function checkAjaxRegisterAction(Request $request)
    {
        $result = ['success' => false];

        if ($request->isXmlHttpRequest() && $request->getMethod() == 'POST') {
            $user = $this->get('security.user_provider')->createNewUserInstance();

            $typeClass = $this->getParameter('youshido_security_user.form.registration');
            $type      = new $typeClass;

            $form = $this->createForm($type, $user);
            $form->handleRequest($request);

            if ($form->isValid()) {
                $this->applyRegisterAction($user);

                $result['success'] = true;
            } else {
                foreach ($form->getErrors(true, true) as $error) {
                    /** @var ConstraintViolation $cause */
                    $cause                                       = $error->getCause();
                    $result['errors'][$cause->getPropertyPath()] = $error->getMessage();
                }
            }
        }

        return new JsonResponse($result);
    }

    /**
     * @Route("/activate-user/{id}/{secret}", name="security.user.activate")
     */
    public function activeUserAction($id, $secret)
    {
        $userProvider = $this->get('security.user_provider');
        $user         = $userProvider->findUserById($id);

        if (!$user) {
            throw $this->createNotFoundException();
        }

        if ($this->generateSecret($user->getPassword()) == $secret) {
            $userProvider->activateUser($user);

            return $this->render($this->getParameter('youshido_security_user.templates.activation_success'), [
                'user' => $user
            ]);
        }

        throw $this->createNotFoundException();
    }

}