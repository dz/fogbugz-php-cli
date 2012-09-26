<?php
namespace FogBugz\Command;

use FogBugz\Cli\AuthCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class SetupCommand extends AuthCommand
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('setup')
            ->setDescription('Configure this FogBugz client')
            ->requireAuth(true);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->app    = $this->getApplication();
        $this->config = Yaml::parse($this->app->baseDir . '/.config.yml');
        $dialog       = new DialogHelper();

        // Prompt the values in the config file
        $question = "Config file path";
        $question .= empty($this->config['ConfigDir']) ? "" : " (" . $this->config['ConfigDir'] . ")";
        $question .= ": ";
        $this->config['ConfigDir'] = $dialog->ask($output, $question, $this->config['ConfigDir']);

        $question = "Enable color output (";
        $question .= !empty($this->config['UseColor']) && $this->config['UseColor']  ? "yes" : "no";
        $question .= "): ";
        $useColor = $dialog->ask($output, $question, $this->config['UseColor']);
        $this->config['UseColor'] = (strtolower($useColor[0]) == 'y');
        
        // Put the version number into the config
        $this->config['ConfigVersion'] = $this->app->project->version;
        
        $yaml = Yaml::dump($this->config, true);
        file_put_contents($this->app->baseDir . '/.config.yml', $yaml);

        
        // Display the alias to use in bash config.
    }
}

/* End of file SetupCommand.php */
