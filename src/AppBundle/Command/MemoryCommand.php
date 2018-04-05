<?php
namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MemoryCommand extends ContainerAwareCommand {
    protected function configure() {
        $this
            ->setName('status:memory')
            ->setDescription('Show Memory');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $text = shell_exec("free -m");

        $output->writeln($text);
    }
}