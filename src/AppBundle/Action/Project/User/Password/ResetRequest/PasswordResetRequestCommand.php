<?php
namespace AppBundle\Action\Project\User\Password\ResetRequest;

use AppBundle\Action\Project\User\ProjectUserRepository;

use Swift_Mailer;
use Swift_MemorySpool;
use Swift_Transport_SpoolTransport;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
//  Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PasswordResetRequestCommand extends Command
{
    /** @var Swift_Mailer */
    private $mailer;
    //private $transportReal;

    /** @var ProjectUserRepository  */
    private $projectUserRepository;

    public function __construct(
        Swift_Mailer    $mailer,
        //\Swift_Transport $transport, // Does not work in production with no spool set
        ProjectUserRepository $projectUserRepository)
    {
        parent::__construct();
        
        $this->mailer        = $mailer;
        //$this->transportReal = $transport;
        $this->projectUserRepository = $projectUserRepository;
    }
    protected function configure()
    {
        $this
            ->setName('user:password:reset:request')
            ->setDescription('Reset user password.')
            ->addArgument('username', InputArgument::REQUIRED, 'Zayso username or email');
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $username = $input->getArgument('username');

        echo sprintf("Reset user password: %s.\n",$username);

        $this->sendEmail($username);

        echo sprintf("Reset user password email sent.\n");
    }
    private function sendEmail($username)
    {
        $mailer = $this->mailer;

        $subject = sprintf('[zAYSO) Password reset request for %s',$username);
        
        $message = $mailer->createMessage()
            ->setSubject($subject)
            ->setFrom('web.ng2019@gmail.com')
            ->setTo  ('web.ng2019@gmail.com')
            ->setBody('WTF')
            //->setBody('<h3>The Body</h3>','text/html');
            /*
             * If you also want to include a plaintext version of the message
            ->addPart(
                $this->renderView(
                    'Emails/registration.txt.twig',
                    array('name' => $name)
                ),
                'text/plain'
            )
            */
        ;

        /** noinspection PhpParamsInspection */
        $status = $mailer->send($message);

        $transport = $mailer->getTransport();
        if (!$transport instanceof Swift_Transport_SpoolTransport) {
            echo sprintf("Not a spool transport\n");
            return;
        }
        $spool = $transport->getSpool();
        if (!$spool instanceof Swift_MemorySpool) {
            echo sprintf("Not a spool memory\n");
            return;
        }
        //$spool->flushQueue($this->transportReal);

        echo sprintf("Message class %s %s %s %s %d\n",
            get_class($message),get_class($mailer),
            get_class($spool),get_class($transport),
            $status);
    }
 }
