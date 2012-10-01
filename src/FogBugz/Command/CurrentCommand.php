<?php
namespace FogBugz\Command;

use FogBugz\Cli\AuthCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CurrentCommand extends AuthCommand
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('current')
            ->setAliases(array('ps1'))
            ->setDescription('Display the current working case')
            ->addArgument('format', InputArgument::OPTIONAL, 'Output format, in sprintf format.')
            ->requireAuth(true)
            ->setHelp(
<<<EOF
The <info>%command.name%</info> command displays the case you are currently
working on. This command accepts an optional argument, a formatting string.
Use the format of sprintf(), and the case and title is handed in as a int and
a string, respectively. To get just the raw case number:

    %command.full_name% '%d'

If this is called as 'ps1', then the format is forced to be '%d', numeric case
number only.

EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $this->app = $this->getApplication();

        $format = $input->getArgument('format');
        $case   = null;
        $title  = null;
        $xml    = $this->app->fogbugz->viewPerson(array('sEmail' => $this->app->fogbugz->user));
        $bug_id = $xml->people->person->ixBugWorkingOn;

        if (!empty($bug_id) && (0 != $bug_id)) {
            $bug = $this->app->fogbugz->search(
                array(
                    'q'    => (int) $bug_id,
                    'cols' => 'sTitle,sStatus'
                )
            );

            $case  = (int) $bug_id;
            $title = (string) $bug->cases->case->sTitle;
        }

        // The ps1 format is a shortcut to use as part an prompt
        if ($input->getArgument('command') === 'ps1') {
            $format = "%d";
        } elseif ($format == null) {
            $format = "[%d] %s";
        }

        if ($case) {
            $output->writeln(
                sprintf($format, $case, $title),
                $this->app->outputFormat
            );
        } else {
            $output->writeln("-", $this->app->outputFormat);
        }
    }
}

/* End of file CurrentCommand.php */
