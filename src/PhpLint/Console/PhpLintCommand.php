<?php
declare(strict_types=1);

namespace PhpLint\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

use PhpLint\Configuration\Configuration;
use PhpLint\Linter\Linter;
use PhpLint\Linter\LintResult;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class PhpLintCommand extends Command
{
    const ARG_NAME_LINT_PATH = 'lint-path';
    const OPTION_NAME_ERRORS_ONLY = 'errors-only';

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('phplint');
        $this->setDefinition([
            new InputArgument(
                self::ARG_NAME_LINT_PATH,
                InputArgument::OPTIONAL,
                'The path whose files shall be linted',
                '.'
            ),
            new InputOption(
                self::OPTION_NAME_ERRORS_ONLY,
                null,
                InputOption::VALUE_NONE,
                'Report errors only'
            ),
        ]);
        $this->setDescription('Lints the file(s) at the specified path');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Find all files at the given path
        $lintPath = $input->getArgument(self::ARG_NAME_LINT_PATH);
        $lintPath = $this->normalizeLintPath($lintPath);
        $phpFilePaths = $this->findPhpFiles($lintPath);
        if (count($phpFilePaths) === 0) {
            $output->writeln('Nothing to lint at path' . $lintPath);

            return;
        }

        // TODO: Create config from the CLI options
        $config = new Configuration([]);

        // Prepare lint result, respecting the passed options
        $errorsOnly = $input->getOption(self::OPTION_NAME_ERRORS_ONLY);
        $lintResult = new LintResult($errorsOnly);

        // Lint all PHP files found in the path
        $output->writeln('Linting all files at path ' . $lintPath);
        $linter = new Linter();
        $lintResult = $linter->lintFilesAtPaths($phpFilePaths, $config, $lintResult);
        $output->writeln('Done!');

        // TODO: Format the result
        if (count($lintResult) > 0) {
            $output->writeln(sprintf('Found %d violations!', count($lintResult)));
        } else {
            $output->writeln('No violations found.');
        }
    }

    /**
     * @param string $lintPath
     * @return string
     * @throws IOException
     */
    private function normalizeLintPath(string $lintPath): string
    {
        $filesystem = new Filesystem();
        if (!$filesystem->isAbsolutePath($lintPath)) {
            if ($lintPath === '.' || $lintPath === './') {
                $lintPath = getcwd();
            } else {
                $lintPath = getcwd() . '/' . $lintPath;
            }
        }
        if (!$filesystem->exists($lintPath)) {
            throw new IOException('File file or directory does not exist.', 0, null, $lintPath);
        }
        if (!is_readable($lintPath)) {
            throw new IOException('File file or directory is not readable.', 0, null, $lintPath);
        }

        return $lintPath;
    }

    /**
     * @param string $path
     * @param string[]
     */
    private function findPhpFiles(string $path): array
    {
        if (is_file($path)) {
            return [$path];
        }

        // Create a directory iterator for finding all PHP files
        $directoryIterator = new RecursiveDirectoryIterator($path);
        $filterIterator = new RecursiveCallbackFilterIterator(
            $directoryIterator,
            function ($current, $key, $iterator) {
                // Allow recursion
                if ($iterator->hasChildren()) {
                    return true;
                }

                return $current->isFile() && $current->getExtension() === 'php';
            }
        );

        // List and sort the file paths
        $phpFilePaths = [];
        foreach (new RecursiveIteratorIterator($filterIterator) as $fileInfo) {
            $phpFilePaths[] = $fileInfo->getPathname();
        }
        sort($phpFilePaths);

        return $phpFilePaths;
    }
}
