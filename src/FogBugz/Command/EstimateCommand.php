<?php
namespace FogBugz\Command;

use There4\FogBugz\ApiError;
use FogBugz\Cli\AuthCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EstimateCommand extends AuthCommand
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('estimate')
            ->setDescription('Set a the working estimate for a case')
            ->addArgument('case', InputArgument::OPTIONAL, 'Case number')
            ->addArgument('estimate', InputArgument::OPTIONAL, 'Estimate in hours')
            ->requireAuth(true)
            ->setHelp(
<<<EOF
The <info>%command.name%</info> command allows you to set an estimate on a case.
This can be called with two optional arguments, the case and the estimate. If
you don't provide one of these values, you will be prompted for them.

This command is also automatically used when you `start` on a case when the case
hasn't had an estimate set on it.

EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->app = $this->getApplication();
        $dialog    = new DialogHelper();
        $case      = $input->getArgument('case');
        $estimate  = $input->getArgument('estimate');

        if ($case == null) {
            $case = $dialog->ask(
                $output,
                "Please provide a case:"
            );
        }
        if ($estimate == null) {
            $estimate = $dialog->ask(
                $output,
                "Please enter an estimate for this case in hours: "
            );
        }

        try {
            $this->app->fogbugz->edit(
                array(
                    'ixBug' => $case,
                    'hrsCurrEst' => $estimate
                )
            );
        } catch (ApiError $e) {
            $output->writeln(
                sprintf("<error>%s</error>", $e->getMessage()),
                $this->app->outputFormat
            );
            exit(1);
        }

        $output->writeln(
            sprintf(
                "Set estimate on <info>Case %s</info> to <info>%s %s</info>.",
                $case,
                $estimate,
                ($estimate == '1') ? 'hour' : 'hours'
            ),
            $this->app->outputFormat
        );
    }
}

/* End of file EstimateCommand.php */
