<?php
/**
 * Created by PhpStorm.
 * User: vasilportey
 * Date: 23.06.15
 * Time: 12:56 PM
 */

namespace Youshido\SecurityUserBundle\Form\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ChangePasswordType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('password', 'repeated', array(
                'first_name' => 'password',
                'second_name' => 'confirm',
                'type' => 'password',
            ))
            ->add('Recover', 'submit');
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getName()
    {
        return 'yuser_recovery_form';
    }
}