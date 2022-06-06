<?php

namespace Betterde\TranslatorCli;

use Exception;
use Illuminate\Support\Str;
use Symfony\Component\Console\Helper\Table;
use Illuminate\Http\Client\RequestException;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Project language package handler.
 *
 * Date: 2022/6/6
 * @author George
 * @package Betterde\TranslatorCli
 */
class Project
{
    /**
     * Absolute path of the project
     *
     * @var string
     */
    private string $path;

    /**
     * The relative path of language resources.
     *
     * @var string
     */
    private string $lang;

    /**
     * Multiple language files under the specified language.
     *
     * @var array
     */
    private array $files = [];

    /**
     * Specify the language that needs to be used as a translation template.
     *
     * @var string
     */
    private string $locale;

    /**
     * Component files table.
     *
     * @var Table
     */
    private Table $table;

    /**
     * Console stdout.
     *
     * @var ConsoleOutput
     */
    private ConsoleOutput $stdout;

    /**
     * Project construct
     *
     * @param string $path
     * @param string $lang
     * @param string $locale
     */
    public function __construct(string $path, string $lang, string $locale)
    {
        $this->path = $path;
        $this->lang = $lang;
        $this->locale = $locale;
        $stdout = new ConsoleOutput();
        $this->table = new Table($stdout);
        $this->stdout = $stdout;
    }

    /**
     * Load lang files
     *
     * Date: 2022/6/5
     * @param bool $recursion
     * @author George
     */
    public function load(bool $recursion = false): void
    {
        $path = sprintf('%s/%s/%s', $this->path, $this->lang, $this->locale);

        $this->scandir($path, $this->files, $recursion);
    }

    /**
     * Synchronize all language packages under the specified language of the current project.
     *
     * Date: 2022/5/30
     * @throws RequestException
     * @author George
     */
    public function sync(): void
    {
        $this->table->setHeaders(['Path', 'Language', 'Module', 'Extension', 'Total', 'Successful', 'Existed', 'Skipped', 'Failed']);

        foreach ($this->files as $filePath) {
            if (getenv('SHELL_VERBOSITY') >= 1) {
                $this->stdout->writeln(sprintf("Now, ready to read %s file.", $filePath));
            }
            $file = pathinfo($filePath);

            // Only files with the PHP extension are synchronized.
            if ($file['extension'] == 'php') {
                $component = new Component();

                $slug = Str::replace('.' . $file['extension'], '', Str::after($filePath, $this->locale . '/'));

                $component->sync([
                    'name' => $slug,
                    'slug' => $slug,
                    'mask' => sprintf('%s/*/%s', $this->lang, $file['basename']),
                    'format' => $file['extension'],
                    'status' => Component::STATUS_ENABLED,
                    'template' => sprintf('%s/%s/%s', $this->lang, $this->locale, $slug),
                    'languages' => null,
                    'project_id' => Certification::$project
                ]);

                $keys = $component->load($filePath, $this->locale);

                $cursor = 0;
                $failed = 0;
                $exists = 0;
                $skipped = 0;
                $successful = 0;

                foreach ($keys as $key => $text) {
                    $cursor++;

                    if (empty($key) || empty($text)) {
                        $skipped++;
                        continue;
                    }

                    try {
                        $element = new Element([
                            'key' => strval($key),
                            'status' => Element::STATE_ENABLE,
                            'source' => strval($text),
                            'target' => strval($text),
                            'context' => '',
                            'position' => $cursor,
                            'source_id' => null,
                            'examples' => null,
                            'language' => $this->locale,
                            'template' => true,
                            'proofread' => false,
                            'translated' => true,
                            'parameters' => null,
                            'component_id' => $component->getAttribute('id')
                        ]);
                        $element->sync();

                        if ($element->getAttribute('exists') === false) {
                            $successful++;
                        } elseif ($element->getAttribute('exists') === true) {
                            $exists++;
                        }
                    } catch (Exception $e) {
                        $this->stdout->writeln(sprintf("<fg=red> %s </>", $e->getMessage()));
                        $failed++;
                    }
                }

                $this->table->addRow([
                    Str::before($filePath, $this->locale),
                    $this->locale,
                    Str::replace('.' . $file['extension'], '', Str::after($filePath, $this->locale . '/')),
                    $file['extension'],
                    count($keys),
                    $successful,
                    $exists,
                    $skipped,
                    $failed
                ]);

                if (getenv('SHELL_VERBOSITY') == 3) {
                    $this->stdout->writeln(sprintf("Path: %s Language: %s File: %s Total: %d Successful: %d Existed: %d Skipped: %d Failed: %d",
                        Str::before($filePath, $this->locale),
                        $this->locale,
                        Str::after($filePath, $this->locale),
                        count($keys),
                        $successful,
                        $exists,
                        $skipped,
                        $failed
                    ));
                }
            }

            if (getenv('SHELL_VERBOSITY') >= 1) {
                $message = sprintf("Now, The %s file has been synchronized", $filePath);
                $this->stdout->writeln($message);
                $this->stdout->writeln(str_repeat('-', strlen($message)) . "\n");
            }
        }

        $this->stdout->writeln(sprintf("This project has the following files under the %s language package:", $this->locale));

        $this->table->render();
    }

    /**
     * Scan the given directory path.
     *
     * Date: 2022/6/5
     * @param string $path
     * @param array $files
     * @param bool $recursion
     * @author George
     */
    private function scandir(string $path, array &$files, bool $recursion = false): void
    {
        $handler = opendir($path);

        while ($item = readdir($handler)) {
            if (in_array($item, ['.', '..'])) {
                continue;
            }

            $itemPath = sprintf('%s/%s', $path, $item);

            if (is_file($itemPath)) {
                $files[] = $itemPath;
                continue;
            }

            if ($recursion && is_dir($itemPath)) {
                $this->scandir($itemPath, $files, $recursion);
            }
        }
    }
}