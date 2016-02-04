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

            if ($user) {
                $this->get('security.user_provider')->generateUserActivationCode($user);
                $this->get('security.mailer')->sendRecoveryLetter($user);

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
        $userProvider = $this->get('security.user_provider');
        $user         = $userProvider->findUserById($id);

        if ($user && $secret === $user->getActivationCode()) {
            $form = $this->createForm(new ChangePasswordType(), null, [
                'action' => $this->generateUrl('security.recovery_redirect', ['id' => $id, 'secret' => $secret])
            ]);

            $form->handleRequest($request);

            if ($form->isValid()) {
                $userProvider->generateUserPassword($user, $form->getData()['password']);
                $userProvider->clearActivationCode($user);

                return $this->render($this->getParameter('youshido_security_user.templates.change_password_success'));
            }

            return $this->render($this->getParameter('youshido_security_user.templates.change_password_form'), [
                'form' => $form->createView()
            ]);
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

        $errors = $this->get('validator')->validate($user);

        if (count($errors) == 0) {
            if ($this->getParameter('youshido_security_user.send_mails.register')) {
                $this->get('security.user_provider')->generateUserActivationCode($user);

                $this->get('security.mailer')->sendRegistrationLetter($user);
            }

            $this->getDoctrine()->getManager()->persist($user);
            $this->getDoctrine()->getManager()->flush();
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
     * @Route("/activate-user/{activationCode}", name="security.user.activate")
     */
    public function activeUserAction($activationCode)
    {
        $userProvider = $this->get('security.user_provider');
        $user         = $userProvider->findUserByActivationCode($activationCode);

        if (!$user) {
            throw $this->createNotFoundException();
        }

        $userProvider->activateUser($user);

        return $this->render($this->getParameter('youshido_security_user.templates.activation_success'), [
            'user' => $user
        ]);
    }

}