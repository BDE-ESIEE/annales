<?php

namespace nk\DocumentBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

class GenerateFileSlugCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('nk:document:generate:file:slug')
            ->setDescription('(re)genère les slugs de tous les fichiers')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $files = $em->getRepository('nkDocumentBundle:File')->findAll();

        $progress = new ProgressBar($output, count($files));
        $progress->setFormat('%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s% %message%');
        $progress->start();

        $progress->setMessage('start');

        foreach ($files as $file) {
            $progress->setMessage($file->getId());
            $file->setSlug(null);

            if ($progress->getProgress() % 10 === 0) {
                $em->flush();
            }

            $progress->advance();
        }

        $em->flush();

        $progress->setMessage('finish');
        $progress->finish();
    }
}
