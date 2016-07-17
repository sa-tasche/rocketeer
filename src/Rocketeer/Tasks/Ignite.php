<?php

/*
 * This file is part of Rocketeer
 *
 * (c) Maxime Fabre <ehtnam6@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace Rocketeer\Tasks;

use Rocketeer\Services\Config\ConfigurationDefinition;
use Rocketeer\Services\Config\Files\ConfigurationPublisher;
use Symfony\Component\Finder\Finder;

/**
 * A task to ignite Rocketeer.
 *
 * @author Maxime Fabre <ehtnam6@gmail.com>
 */
class Ignite extends AbstractTask
{
    /**
     * A description of what the task does.
     *
     * @var string
     */
    protected $description = "Creates Rocketeer's configuration";

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $this->command->writeln(<<<'TXT'
                           *     .--.
                                / /  `
               +               | |
                      '         \ \__,
                  *          +   '--'  *
                      +   /\
         +              .'  '.   *
                *      /======\      +
                      ;:.  _   ;
                      |:. (_)  |
                      |:.  _   |
            +         |:. (_)  |          *
                      ;:.      ;
                    .' \:.    / `.
                   / .-'':._.'`-. \
                   |/    /||\    \|
                 _..--"""````"""--.._
           _.-'``                    ``'-._
         -'      WELCOME TO ROCKETEER      '-
TXT
        );

        // Get application name
        $default = basename($this->localStorage->getFilename(), '.json');
        $applicationName = $this->command->ask('What is your application\'s name ?', $default);
        $this->config->set('application_name', $applicationName);

        // Gather repository/connections credentials
        $this->command->title('<info>[1/3]</info> Credentials gathering');
        $this->command->writeln('Before we begin let\'s gather the credentials for your app');
        $credentials = $this->credentialsGatherer->getCredentials();
        $this->exportDotenv($credentials);

        // Export configuration
        $this->command->title('<info>[2/3]</info> Configuration exporting');
        $configuration = $this->exportConfiguration($applicationName);

        // Namespace generation
        $this->command->title('<info>[3/3]</info> Namespace generation');
        $this->command->text(<<<'TXT'
For advanced usage, Rocketeer can generate a PSR4 namespace in your application's name.
It contains folders to create custom tasks, strategies, commands and such as well as a service provider to have access to Rocketeer's internals.
TXT
        );

        if ($this->command->confirm('Do you want to generate that folder?', true)) {
            $this->generateStubs($configuration, ucfirst($applicationName));
        }

        $this->command->writeln('Okay, you are ready to send your projects in the cloud. Fire away rocketeer!');
    }

    /**
     * @param array $credentials
     */
    protected function exportDotenv(array $credentials)
    {
        // Build dotenv file
        $dotenv = '';
        foreach ($credentials as $credential => $value) {
            $dotenv .= $credential.'='.$value.PHP_EOL;
        }

        // Write to disk
        $this->files->append($this->paths->getDotenvPath(), $dotenv);
        $this->command->writeln('<info>A <comment>.env</comment> file with your credentials has been created!</info>');
        $this->command->writeln('Do not track this file in your repository, <error>it is meant to be private</error>');
    }

    /**
     * Export the configuration to file.
     *
     * @return string
     */
    protected function exportConfiguration()
    {
        $format = $this->command->choice('What format do you want your configuration in?', ConfigurationPublisher::$formats, 'php');
        $consolidated = $this->command->confirm('Do you want it consolidated (one file instead of many?', false);

        // Set values on definition
        $definition = new ConfigurationDefinition();
        $definition->setValues($this->config->all());

        $this->configurationPublisher->setDefinition($definition);
        $path = $this->configurationPublisher->publish($format, $consolidated);
        $path = realpath($path.'/..');

        // Summary
        $folder = basename(dirname($path)).'/'.basename($path);
        $this->command->writeln('<info>Your configuration was exported at</info> <comment>'.$folder.'</comment>.');

        return $path;
    }

    /**
     * @param string $folder
     * @param string $namespace
     */
    protected function generateStubs($folder, $namespace)
    {
        $folder = $folder.DS.$namespace;

        $stubs = __DIR__.'/../../stubs';
        $files = (new Finder())->in($stubs)->files();

        $this->files->createDir($folder);
        foreach ($files as $file) {
            $contents = $this->files->read($file->getRealPath());
            $contents = str_replace('namespace App', 'namespace '.$namespace, $contents);
            $contents = str_replace('AppServiceProvider', $namespace.'ServiceProvider', $contents);

            $destination = strpos($file->getBasename(), 'ServiceProvider') === false
                ? $folder.'/'.basename(dirname($file->getRealPath())).'/'.$file->getBasename()
                : $folder.'/'.$namespace.'ServiceProvider.php';

            $this->files->put($destination, $contents);
        }
    }
}
