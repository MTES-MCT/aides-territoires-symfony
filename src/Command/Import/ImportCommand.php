<?php

namespace App\Command\Import;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Doctrine\Persistence\ManagerRegistry;
use phpDocumentor\Reflection\Types\Null_;
use Twig\Cache\NullCache;

#[AsCommand(name: 'at:import:generic', description: 'Import generic')]
class ImportCommand extends Command
{
    protected InputInterface $input;
    protected OutputInterface $output;
    protected string $commandTextStart = '<Import';
    protected string $commandTextEnd = '>Import';

    public function __construct(
        protected KernelInterface $kernelInterface,
        protected ManagerRegistry $managerRegistry
    )
    {
        ini_set('max_execution_time', 60*60*60);
        ini_set('memory_limit', '20G');
        parent::__construct();
    }

    protected function configure() : void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        $io = new SymfonyStyle($input, $output);
        $io->title($this->commandTextStart);

        try  {
            // generate menu
            $this->import($input, $output);
        } catch (\Exception $exception) {
            $io->error($exception->getMessage());
        }

        $io->success('Mémoire maximale utilisée : ' . round(memory_get_peak_usage() / 1024 / 1024) . ' MB');
        $io->title($this->commandTextEnd);
        return Command::SUCCESS;
    }

    protected function import($input, $output)
    {
        $io = new SymfonyStyle($input, $output);

        // success
        $io->success('import ok');
    }

    protected function redoSlug(string $str) {
        return strtr($str, '_', '-');
    }

    protected function stringToJsonOrNull(string $str): string|null
    {
        try {
            $str = strtr($str, ['{' => '', '}' => '']);
            $array = explode(',', $str);
            foreach ($array as $key => $value) {
                if (trim($value) == '') {
                    unset($array[$key]);
                }
            }
            sort($array);
    
            if (!count($array)) {
                return null;
            }
            return json_encode($array);
        } catch (\Exception $e) {
            return null;
        }
    }
    protected function stringToArrayOrNull(string $str): array|null
    {
        try {
            $str = strtr($str, ['{' => '', '}' => '']);
            $array = explode(',', $str);
            foreach ($array as $key => $value) {
                if (trim($value) == '') {
                    unset($array[$key]);
                }
            }
            sort($array);
    
            if (!count($array)) {
                return null;
            }
            return $array;
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function stringOrNull(string $str): string|null
    {
        if (trim($str) == '') {
            return null;
        }
        return $str;
    }

    protected function intOrNull(string $str): int|null
    {
        if (trim($str) == '') {
            return null;
        }
        return (int) $str;
    }

    protected function floatOrNull(string $str): float|null
    {
        if (trim($str) == '') {
            return null;
        }
        return (float) $str;
    }

    protected function stringToBool(string $str): bool
    {
        return strtolower(trim($str)) == 'false' ? false : true;
    }

    protected function findCsvFile(string $table): string|null
    {
        $folder = $this->kernelInterface->getProjectDir().'/datas/import/';
        $files = scandir($folder);
        foreach ($files as $file) {
            if (preg_match('/'.$table.'[0-9]+\.csv/', $file)) {
                return $folder.$file;
            }
        }
        return null;
    }

    protected function intrangeToArray(string $range): array
    {
        $range = preg_replace('([^0-9,]?)', '', $range);
        $range = explode(',', $range);

        return [
            'min' => (isset($range[0]) && $range[0] && trim($range[0]) !== '') ? (int) $range[0] : null,
            'max' => (isset($range[1]) && $range[1] && trim($range[1]) !== '') ? (int) $range[1] : null,
        ];
    }

    protected function stringToDateTimeOrNull(string $string): \DateTime|null
    {
        if (trim($string) == '') {
            return null;
        }
        try {
            return new \DateTime(date($string));
        } catch (\Exception $exception) {
            return null;
        }
    }

    protected function stringToDateTimeOrNow(string $string): \DateTime
    {
        try {
            return new \DateTime(date($string));
        } catch (\Exception $exception) {
            return new \DateTime(date('Y-m-d H:i:s'));
        }
    }
}