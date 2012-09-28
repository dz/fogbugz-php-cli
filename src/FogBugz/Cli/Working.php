<?php
namespace FogBugz\Cli;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Yaml\Yaml;
use There4\FogBugz;
use FogBugz\Cli;
use FogBugz\Command;
use FogBugz\Command\CasesCommand;
use FogBugz\Command\CurrentCommand;
use FogBugz\Command\EstimateCommand;
use FogBugz\Command\FiltersCommand;
use FogBugz\Command\LoginCommand;
use FogBugz\Command\LogoutCommand;
use FogBugz\Command\NoteCommand;
use FogBugz\Command\OpenCommand;
use FogBugz\Command\ParentCommand;
use FogBugz\Command\RecentCommand;
use FogBugz\Command\ResolveCommand;
use FogBugz\Command\SearchCommand;
use FogBugz\Command\SetFilterCommand;
use FogBugz\Command\SetupCommand;
use FogBugz\Command\StartCommand;
use FogBugz\Command\StopCommand;
use FogBugz\Command\VersionCommand;
use FogBugz\Command\ViewCommand;

class Working extends Application
{

    public $baseDir;
    public $tokenPath;

    public function __construct($baseDir)
    {
        $this->baseDir   = $baseDir;
        $this->tokenPath = "token.txt";
        $runSetup        = false;

        // Add the composer information for use in version info and such.
        $this->project = json_decode(file_get_contents('composer.json'));

        // Load our application config information
        if (file_exists('.config.yml')) {
            $this->config = Yaml::parse('.config.yml');
        } else {
            $runSetup = true;
            $this->config = $this->getDefaultConfig();
        }

        // We do this now because we've loaded the project info from the composer file
        parent::__construct($this->project->description, $this->project->version);

        // Load our commands into the application
        $this->add(new CasesCommand());
        $this->add(new CurrentCommand());
        $this->add(new EstimateCommand());
        $this->add(new FiltersCommand());
        $this->add(new LoginCommand());
        $this->add(new LogoutCommand());
        $this->add(new NoteCommand());
        $this->add(new OpenCommand());
        $this->add(new ParentCommand());
        $this->add(new RecentCommand());
        $this->add(new ResolveCommand());
        $this->add(new SearchCommand());
        $this->add(new SetFilterCommand());
        $this->add(new SetupCommand());
        $this->add(new StartCommand());
        $this->add(new StopCommand());
        $this->add(new VersionCommand());
        $this->add(new ViewCommand());

        // https://github.com/symfony/Console/blob/master/Output/Output.php
        $this->outputFormat
            = $this->config['UseColor']
            ? OutputInterface::OUTPUT_NORMAL
            : OutputInterface::OUTPUT_PLAIN;

        $loader = new \Twig_Loader_Filesystem($baseDir . '/templates');
        $this->twig = new \Twig_Environment(
            $loader,
            array(
                "cache"            => false,
                "autoescape"       => false,
                "strict_variables" => false // SET TO TRUE WHILE DEBUGGING
            )
        );

        $this->twig->addFilter('pad', new \Twig_Filter_Function("FogBugz\Cli\TwigFormatters::strpad"));
        $this->twig->addFilter('style', new \Twig_Filter_Function("FogBugz\Cli\TwigFormatters::style"));
        $this->twig->addFilter('repeat', new \Twig_Filter_Function("str_repeat"));
        $this->twig->addFilter('wrap', new \Twig_Filter_Function("wordwrap"));

        // If the config file is empty, run the setup script here
        // If the config file version is a different major number, run the setup script here
        $currentVersion = explode('.', $this->project->version);
        $configVersion  = explode('.', $this->config['ConfigVersion']);
        $majorVersionChange = $currentVersion[0] != $configVersion[0];
        if ($runSetup || $majorVersionChange) {
            $command = $this->find('setup');
            $arguments = array(
                'command' => 'setup'
            );
            $input = new ArrayInput($arguments);
            $command->run($input, new ConsoleOutput());
        }
    }

    public function getLongVersion()
    {
        return parent::getLongVersion().' by <comment>Craig Davis</comment>';
    }

    public function getDefaultConfig()
    {
        return array(
            'ConfigDir'     => '~/.fogbugz',
            'ConfigVersion' => '0.0.1',
            'UseColor'      => true
        );
    }

    public function getCurrent($user = '')
    {
        if ($user === '') {
            $user = $this->fogbugz->user;
        }
        $xml = $this->fogbugz->viewPerson(array('sEmail' => $user));

        return (int) $xml->people->person->ixBugWorkingOn;
    }

    public function getRecent()
    {
        $recentCases = Yaml::parse('.recent.yml');

        return is_array($recentCases) ? $recentCases : array();
    }

    public function pushRecent($case, $title)
    {
        $recentCases = $this->getRecent();
        array_push(
            $recentCases,
            array(
                "id"    => $case,
                "title" => $title
            )
        );
        // Only keep the last x number of cases in the list
        $recentCases = array_slice($recentCases, -5);
        $yaml = Yaml::dump($recentCases, true);
        file_put_contents($this->baseDir . '/.recent.yml', $yaml);

        return true;
    }

    public function registerStyles(&$output)
    {
        // https://github.com/symfony/Console/blob/master/Formatter/OutputFormatterStyle.php
        // http://symfony.com/doc/2.0/components/console/introduction.html#coloring-the-output
        //
        // * <info></info> green
        // * <comment></comment> yellow
        // * <question></question> black text on a cyan background
        // * <alert></alert> yellow
        // * <error></error> white text on a red background
        // * <fire></fire> red text on a yellow background
        // * <notice></notice> blue
        // * <heading></heading> black on white

        $style = new OutputFormatterStyle('red', 'yellow', array('bold'));
        $output->getFormatter()->setStyle('fire', $style);

        $style = new OutputFormatterStyle('blue', 'black', array());
        $output->getFormatter()->setStyle('notice', $style);

        $style = new OutputFormatterStyle('red', 'black', array('bold'));
        $output->getFormatter()->setStyle('alert', $style);

        $style = new OutputFormatterStyle('white', 'black', array('bold'));
        $output->getFormatter()->setStyle('bold', $style);

        $style = new OutputFormatterStyle('black', 'white', array());
        $output->getFormatter()->setStyle('heading', $style);

        $style = new OutputFormatterStyle('blue', 'black', array('bold'));
        $output->getFormatter()->setStyle('logo', $style);

        return $output;
    }

    public function statusStyle($status)
    {
        switch (true) {
            case (strpos(strtolower($status), 'closed') === 0):
                return 'alert';
            case (strpos(strtolower($status), 'open') === 0):
            case (strpos(strtolower($status), 'active') === 0):
                return 'logo';
            // fallthrough to final return
        }

        return "info";
    }

    public function run(InputInterface $input = null, OutputInterface $output = null)
    {
        if (null === $input) {
            $input = new ArgvInput();
        }

        if (null === $output) {
            $output = new ConsoleOutput();
        }

        $this->registerStyles($output);

        // Does the command require authentication?
        $name = $this->getCommandName($input);
        if (!empty($name) && $command = $this->find($name)) {
            if (property_exists($command, "requireAuth") && $command->requireAuth) {
                $simple_input = new ArgvInput(
                    array(
                        $_SERVER['argv'][0],
                        $_SERVER['argv'][1],
                        "--quiet"
                    )
                );
                $login = $this->find('login');
                $returnCode = $login->run($simple_input, $output);
            }
        }

        return parent::run($input, $output);
    }
}

/* End of file Working.php */
