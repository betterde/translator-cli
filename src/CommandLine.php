<?php

namespace Betterde\TranslatorCli;

use Symfony\Component\Process\Process;

class CommandLine
{
    /**
     * Simple global function to run commands.
     *
     * @param string $command
     * @return void
     */
    function quietly(string $command): void
    {
        $this->runCommand($command.' > /dev/null 2>&1');
    }

    /**
     * Pass the command to the command line and display the output.
     *
     * @param string $command
     * @return void
     */
    function passthru(string $command): void
    {
        passthru($command);
    }

    /**
     * Run the given command as the non-root user.
     *
     * @param string $command
     * @param callable|null $onError
     * @return string
     */
    function run(string $command, callable $onError = null): string
    {
        return $this->runCommand($command, $onError);
    }

    /**
     * Run the given command.
     *
     * @param string $command
     * @param callable|null $onError
     * @return string
     */
    function runCommand(string $command, callable $onError = null): string
    {
        $onError = $onError ?: function () {};

        // Symfony's 4.x Process component has deprecated passing a command string
        // to the constructor, but older versions (which Valet's Composer
        // constraints allow) don't have the fromShellCommandLine method.
        // For more information, see: https://github.com/laravel/valet/pull/761
        if (method_exists(Process::class, 'fromShellCommandline')) {
            $process = Process::fromShellCommandline($command);
        } else {
            $process = new Process($command);
        }

        $processOutput = '';
        $process->setTimeout(null)->run(function ($type, $line) use (&$processOutput) {
            $processOutput .= $line;
        });

        if ($process->getExitCode() > 0) {
            $onError($process->getExitCode(), $processOutput);
        }

        return $processOutput;
    }
}
