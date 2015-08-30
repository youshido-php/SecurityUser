<?php
/**
 * Date: 28.08.15
 *
 * @author Portey Vasil <portey@gmail.com>
 */

namespace Youshido\SecurityUserBundle\Form\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SecuredUserType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstName')
            ->add('email')
            ->add('password', 'repeated', [
                'type' => 'password',
                'invalid_message' => 'The password fields must match.',
                'options' => array('attr' => array('class' => 'password-field')),
                'required' => true,
                'first_options'  => array('label' => 'Password'),
                'second_options' => array('label' => 'Repeat Password'),
            ]);
    }

    public function getName()
    {
        return 'yuser_secured_user_type';
    }
}