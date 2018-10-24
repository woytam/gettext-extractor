<?php declare(strict_types=1);


namespace Webwings\Gettext\Commands;


use Symfony\Component\Console\Input\InputOption;
use Webwings\Gettext\Extractor\NetteExtractor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Extract extends Command
{

    protected function configure()
    {
        $this
            ->setName('extract:pot')
            ->setDescription('Extract Gettext strings from given path to pot file.')
            //->setHelp()

            // arguments
            ->addArgument('output_file_path',InputArgument::REQUIRED,'Set output file name')
            ->addArgument('extract_path',InputArgument::REQUIRED,'Path to extract')
            // options
            ->addOption('log_file','-l',InputOption::VALUE_OPTIONAL,'Path to log file. Default is stderr','php://stderr')
            ->addOption('meta','-m',InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,'Set meta header. Use : as delimiter');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $extractor = new NetteExtractor($input->getOption('log_file'));
        $extractor->setupForms()->setupDataGrid();


        $meta = $input->getOption('meta');
        if ($meta) {
            foreach ($meta as $key => $value) {
                list($key, $metaValue) = explode(':', $value, 2);
                $extractor->setMeta($key, $metaValue);
            }
        }

        $extractor->scan($input->getArgument('extract_path'));
        $extractor->save($input->getArgument('output_file_path'));

    }

}