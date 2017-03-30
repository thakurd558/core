<?php

namespace Antares\Installation\Listeners;

use Antares\Extension\Events\Installed;
use Antares\Installation\Progress;
use Antares\Memory\Provider;
use Illuminate\Contracts\Container\Container;

class IncrementProgress {

    /**
     * @var Provider
     */
    protected $memory;

    /**
     * @var Progress
     */
    protected $progress;

    /**
     * IncrementProgress constructor.
     * @param Container $container
     * @param Progress $progress
     */
    public function __construct(Container $container, Progress $progress) {
        $this->memory   = $container->make('antares.memory')->make('primary');
        $this->progress = $progress;
    }

    /**
     * @param Installed $installed
     */
    public function handle(Installed $installed) {
        $this->progress->advanceStep();

        if($this->progress->isFinished()) {
            $this->memory->put('app.installed', true);
            $this->progress->reset();
            app('antares.memory')->make('component')->finish();
        }

        $this->progress->save();
        $this->memory->finish();
    }

}
