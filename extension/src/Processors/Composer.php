<?php

declare(strict_types=1);

namespace Antares\Extension\Processors;

use Antares\Extension\Contracts\Handlers\OperationHandlerContract;
use Antares\Extension\Exception\ExtensionException;
use Antares\Extension\Model\Operation;
use Antares\Extension\Composer\Handler as ComposerHandler;

class Composer {

    /**
     * @var ComposerHandler
     */
    protected $composerHandler;

    /**
     * Composer constructor.
     * @param ComposerHandler $composerHandler
     */
    public function __construct(ComposerHandler $composerHandler) {
        $this->composerHandler = $composerHandler;
    }

    /**
     * Run the operation for composer.
     *
     * @param OperationHandlerContract $handler
     * @param array $extensionsNames
     * @return mixed
     * @throws \Exception
     */
    public function run(OperationHandlerContract $handler, array $extensionsNames) {
        try {
            $names = implode(' ', $extensionsNames);

            $handler->operationInfo(new Operation('Running composer command.'));

            $process = $this->composerHandler->run('composer require ' . $names . ' --no-progress', function($process, $type, $buffer) use($handler) {
                $handler->operationInfo(new Operation($buffer));
            });

            if( ! $process->isSuccessful() ) {
                throw new ExtensionException($process->getErrorOutput());
            }

            return $handler->operationInfo(new Operation('Composer command has been finished.'));
        }
        catch(ExtensionException $e) {
            return $handler->operationFailed($e->getOperationModel());
        }
        catch(\Exception $e) {
            return $handler->operationFailed(new Operation($e->getMessage()));
        }
    }

}
