<?php
namespace FogBugz\Command;

use There4\FogBugz\ApiError;
use FogBugz\Cli\AuthCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class ParentCommand extends AuthCommand
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('parent')
            ->setDescription('View the parent of a case')
            ->addArgument('case', InputArgument::OPTIONAL, 'Case number, defaults to current active case.')
            ->requireAuth(true)
            ->setHelp(
<<<EOF
The <info>%command.name%</info> will display the 'view' of the parent of the
case number supplied. If you omit the case number, it will use the current
active case.

EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->app = $this->getApplication();
        $case      = $input->getArgument('case');

        if (null == $case) {
            $case = $this->app->getCurrent();
            if ($case == null || $case == 0) {
                $case = $dialog->ask($output, "Enter a case number: ");
            }
        }

        try {
            $bug = $this->app->fogbugz->search(
                array(
                    'q'    => (int) $case,
                    'cols' => 'ixBugParent'
                )
            );
        } catch (ApiError $e) {
            $output->writeln(
                sprintf("<error>%s</error>", $e->getMessage()),
                $this->app->outputFormat
            );
            exit(1);
        }

        if (0 == $bug->cases['count']) {
            $output->writeln(
                sprintf("<error>Unable to retrieve [%d]</error>", $case),
                $this->app->outputFormat
            );
            exit(1);
        }

        // Now that we have a parent, we'll show it with the view command
        $command = $this->getApplication()->find('view');
        $arguments = array(
            'command' => 'view',
            'case' => (int) $bug->cases->case->ixBugParent
        );
        $input = new ArrayInput($arguments);
        $command->run($input, $output);
    }
}

/* End of file ParentCommand.php */
