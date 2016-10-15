<?php

namespace nk\DocumentBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GeneratePreviewCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('nk:document:preview:generate')
            ->setDescription('Genère l\'aperçu de tous les fichiers')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getContainer()->get('nk.preview_generator')->generateAllPreview();

        $output->writeln('done');
    }
}
