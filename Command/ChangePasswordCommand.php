<?php

/**
 * Date: 29.02.16
 *
 * @author Portey Vasil <portey@gmail.com>
 */

namespace Youshido\SecurityUserBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class ChangePasswordCommand extends ContainerAwareCommand
{

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('security:change-password')
            ->addArgument('id', InputArgument::REQUIRED)
            ->addOption('password', 'p', InputOption::VALUE_OPTIONAL, 'User password')
            ->setDescription('Change password of user');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $password     = $input->getOption('password');
        $helper       = $this->getHelper('question');
        $userProvider = $this->getContainer()->get('security.user_provider');

        $user = $userProvider->findUserById((int)$input->getArgument('id'));
        if (!$user) {
            $output->writeln(sprintf('<error>%s</error>', 'User not found'));

            return;
        }

        if ($password) {
            $question = new ConfirmationQuestion(sprintf('Continue with "%s" password? ', $password), false);

            if (!$helper->ask($input, $output, $question)) {
                return;
            }
        } else {
            $question        = new Question('Please enter user password: ');
            $confirmQuestion = new Question('Please confirm user password: ');

            $password          = $helper->ask($input, $output, $question);
            $confirmedPassword = $helper->ask($input, $output, $confirmQuestion);

            if ($password !== $confirmedPassword) {
                $output->writeln(sprintf('<error>%s</error>', 'Password mismatch'));

                return;
            }
        }

        $userProvider->generateUserPassword($user, $password);

        $this->getContainer()->get('doctrine')->getManager()->flush();
    }

}