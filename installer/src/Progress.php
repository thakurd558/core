<?php

declare(strict_types=1);

namespace Antares\Installation;

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
     * Count of steps.
     *
     * @var int
     */
    protected $stepsCount = 0;

    /**
     * Count of completed steps.
     *
     * @var int
     */
    protected $completedStepsCount;

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
        $this->memory   = $memoryManager->make('primary');
        $this->filePath = storage_path('installation.txt');

        $this->memory->getHandler()->initiate();

        $this->stepsCount           = (int) count( $this->memory->get('app.installation.components', []) );
        $this->completedStepsCount  = (int) $this->memory->get('app.installation.completed', 0);
        $this->isRunning            = (bool) $this->memory->get('app.installing', false);
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

        $this->completedStepsCount = 0;
        $this->memory->put('app.installation.completed', $this->completedStepsCount);

        $this->isRunning = true;
        $this->memory->put('app.installing', $this->isRunning);
    }

    /**
     * Resets the progress state.
     */
    public function reset() {
        $this->memory->forget('app.installation.components');
        $this->stepsCount = 0;

        $this->isRunning = false;
        $this->memory->put('app.installing', $this->isRunning);

        File::delete($this->filePath);
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
     * Returns the count of steps.
     *
     * @return int
     */
    public function getStepsCount() : int {
        return $this->stepsCount;
    }

    /**
     * Increments completed steps.
     */
    public function advanceStep() {
        $this->memory->put('app.installation.completed', ++$this->completedStepsCount);
    }

    /**
     * Returns the percentage of installation progress (from 0 to 100).
     *
     * @return int
     */
    public function getPercentageProgress() : int {
        if($this->stepsCount === 0) {
            return 0;
        }

        return (int) round(($this->completedStepsCount / $this->stepsCount) * 100, 0);
    }

    /**
     * Determines if the progress has been finished.
     *
     * @return bool
     */
    public function isFinished() : bool {
        return $this->completedStepsCount === $this->stepsCount;
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
