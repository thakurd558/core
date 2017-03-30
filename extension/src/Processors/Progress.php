<?php

declare(strict_types=1);

namespace Antares\Extension\Processors;

use Antares\Extension\Contracts\ProgressContract;
use Antares\Memory\MemoryManager;
use Antares\Memory\Provider;
use File;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class Progress implements ProgressContract {

    /**
     * Memory provider instance.
     *
     * @var Provider
     */
    protected $memory;

    /**
     * File path of the console output.
     *
     * @var string
     */
    protected $filePath;

    /**
     * Running indicator.
     *
     * @var bool
     */
    protected $isRunning;

    /**
     * Progress constructor.
     * @param MemoryManager $memoryManager
     */
    public function __construct(MemoryManager $memoryManager) {
        $this->memory       = $memoryManager->make('primary');
        $this->filePath     = storage_path('extension-operation.txt');
        $this->isRunning    = (bool) $this->memory->get('app.extension.installing', false);
    }

    /**
     * Returns the file system path of the output console.
     *
     * @return string
     */
    public function getFilePath() : string {
        return $this->filePath;
    }

    /**
     * Starts the progress state.
     */
    public function start() {
        File::put($this->filePath, '');

        $this->isRunning = true;
        $this->memory->put('app.extension.installing', $this->isRunning);
    }

    /**
     * Adds content to the output file.
     *
     * @param string $content
     */
    public function addToOutput(string $content) {
        File::append($this->filePath, $content);
    }

    /**
     * Returns the installation console output.
     *
     * @return string
     */
    public function getOutput() : string {
        try {
            return File::get($this->filePath);
        }
        catch(FileNotFoundException $e) {
            return '';
        }
    }

    /**
     * Sets progress as finished.
     */
    public function setFinished() {
        File::delete($this->filePath);

        $this->isRunning = false;
        $this->memory->put('app.extension.installing', $this->isRunning);
    }

    /**
     * Determines if the progress has been finished.
     *
     * @return bool
     */
    public function isFinished() : bool {
        return ! $this->isRunning;
    }

    /**
     * Determines if the progress is running.
     *
     * @return bool
     */
    public function isRunning() : bool {
        return $this->isRunning;
    }

    /**
     * Saves the progress in the memory.
     */
    public function save() {
        $this->memory->finish();
    }

}
