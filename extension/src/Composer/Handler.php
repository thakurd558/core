<?php

declare(strict_types=1);

namespace Antares\Extension\Composer;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Closure;

class Handler {

	/**
	 * Handler constructor.
	 */
    public function __construct() {
		putenv('COMPOSER_HOME=' . env('COMPOSER_HOME'));
    }

	/**
	 * Runs the command.
	 *
	 * @param string $command
	 * @param Closure|null $callback
	 * @return Process
     * @throws \Exception
	 */
    public function run(string $command, Closure $callback = null) : Process {
        set_time_limit(0);
        gc_disable();

		$process = new Process($command);
		$process->setWorkingDirectory( base_path() );
		$process->setTimeout(null);

		try {
			$process->mustRun(function($type, $buffer) use($callback, $process) {
				if( empty($buffer) ) {
					return null;
				}

				if($callback instanceof Closure) {
					$callback($process, $type, $buffer);
				}
			});
		}
		catch(ProcessFailedException $e) {
			throw $e;
		}

		return $process;
    }

}
