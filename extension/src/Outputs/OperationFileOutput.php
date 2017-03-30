<?php

declare(strict_types=1);

namespace Antares\Extension\Outputs;

use Antares\Extension\Contracts\Handlers\OperationHandlerContract;
use Symfony\Component\Console\Output\StreamOutput;
use Antares\Extension\Model\Operation;

class OperationFileOutput implements OperationHandlerContract  {

    /**
     * @var StreamOutput
     */
    protected $output;

    /**
     * OperationFileOutput constructor.
     * @param string $filePath
     * @throws \InvalidArgumentException
     */
    public function __construct(string $filePath) {
        $this->output = new StreamOutput( fopen($filePath, 'ab') );
    }

    /**
     * @return StreamOutput
     */
    public function getStream() : StreamOutput {
        return $this->output;
    }

    /**
     * @param Operation $operation
     * @return void
     */
    public function operationSuccess(Operation $operation) {
        $this->output->writeln($operation->getMessage());
    }

    /**
     * @param Operation $operation
     * @return void
     */
    public function operationFailed(Operation $operation) {
        $this->output->writeln($operation->getMessage());
    }

    /**
     * @param Operation $operation
     * @return void
     */
    public function operationInfo(Operation $operation) {
        $this->output->writeln($operation->getMessage());
    }
}