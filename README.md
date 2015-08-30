# SecurityUser

### Install via Composer:
> composer require youshido/security-user

### Enable in your AppKernel.php:

> new new Youshido\SecurityUserBundle\YoushidoSecurityUserBundle(),

### Create your User class:
``` php
<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Youshido\SecurityUserBundle\Entity\SecuredUser;

/**
 * User
 *
 * @ORM\Table(name="user")
 * @ORM\Entity
 */
class User extends SecuredUser
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    //your custom fields

```

#### Create User form (if needed):
``` php
<?php

namespace AppBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Youshido\SecurityUserBundle\Form\Type\SecuredUserType;

class UserType extends SecuredUserType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
    
        $builder
            ->add('plan', 'entity', [
                'class' => 'AppBundle\Entity\Plan'
            ])
            ->add('terms', 'checkbox', [
                'mapped' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'AppBundle\Entity\User'
        ]);
    }
```

### Insert to your security.yml file:
``` yaml

providers:
    yuser_provider:
        entity:
            class: /* your user class */
            property: email

encoders:
    /* your user class */: md5

firewalls:
    dev:
        pattern: ^/(_(profiler|wdt|error)|css|images|js)/
        security: false
        
    default:
        pattern: ^/
        provider: yuser_provider
        anonymous: ~
        form_login:
            login_path: security.login
            check_path:  security.login_check
            success_handler: security.authentication_handler
            failure_handler: security.authentication_handler
        logout:
            path:   security.logout
            target: /
```

### Configure plugin (required!):
``` yaml
youshido_security_user:
    model: /* your user class */
```    

### Available config options:
``` yaml
youshido_security_user:
    model: /* your user class */
    templates:
        login_form: @YoushidoSecurityUser/Security/login.html.twig
        register_form: @YoushidoSecurityUser/Security/register.html.twig
        
        recovery_form: @YoushidoSecurityUser/Security/recovery.html.twig
        recovery_success: @YoushidoSecurityUser/Security/recovery_success.html.twig
        
        change_password_success: @YoushidoSecurityUser/Security/change_password_success.html.twig
        change_password_error: @YoushidoSecurityUser/Security/recovery_error.html.twig
        change_password_form: @YoushidoSecurityUser/Security/change_password.html.twig
    redirects:
        register_success: homepage
    form:
        registration: AppBundle\Form\Type\UserType
```