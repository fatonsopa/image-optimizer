<?php

namespace Spatie\ImageOptimizer\Optimizers;

use Psr\Log\LoggerInterface;
use Spatie\ImageOptimizer\DummyLogger;
use Spatie\ImageOptimizer\Optimizer;
use Symfony\Component\Process\Process;
use Spatie\ImageOptimizer\OptimizerChain;

abstract class BaseOptimizer implements Optimizer
{
    public $options = [];

    public $imagePath = '';

    /**
     * Binary path.
     *
     * @var string $binaryPath
     */
    protected $binaryPath = '';

    public function __construct($options = [])
    {
        $this->setOptions($options);
    }


    /**
     * Set binary Path
     *
     * @param string|array $binaryPath
     * @return string
     */
    public function setBinaryPath($binaryPath)
    {
        $this->binaryPath = $binaryPath;

        return $this;
    }

    /**
     * Get binary path
     *
     * @return string|array
     */
    public function binaryPath()
    {
        return $this->binaryPath;
    }

    public function binaryName(): string
    {
        return $this->binaryName;
    }

    public function setImagePath(string $imagePath)
    {
        $this->imagePath = $imagePath;

        return $this;
    }

    public function setOptions(array $options = [])
    {
        $this->options = $options;

        return $this;
    }



    /**
     * Authomatically detect the path where the image optimizes is installed
     *
     * @return $this
     */
    public function checkBinary(){
        // check binary by a given list of binary path
        if(is_array($this->binaryPath())) {
            foreach ($this->binaryPath() as $path) {
                $path = rtrim($path, '/') . '/';
                $process = new Process("which -a " . $path . '' . $this->binaryName());
                $process->setTimeout(null);
                $process->run();

                if ($process->isSuccessful()) {
                    $this->setBinaryPath($path);
                    return $this;
                }
            }

            // if we come so far, it means the binary could not be found
            (new OptimizerChain())->getLogger()->error("Binary could not be found in any of the following configured paths: `".implode(",", array_values($this->binaryPath())."`"));

            // Although a given list of possible binary path has been given, the binary may exists
            // in the global environment. Therefore, we will unset binary path list so we can later
            // check if it exists the global environment
            $this->setBinaryPath('');
        }

        // check if binary exists in the global environment
        $process = new Process("which -a " .$this->binaryName());
        $process->setTimeout(null);
        $process->run();
        if ($process->isSuccessful()) {
            return $this;
        }else{
            (new OptimizerChain())->getLogger()->error("Binary could not be found: `".$this->binaryName()."`");
        }

        return $this;
    }

    public function getCommand(): string
    {
        $optionString = implode(' ', $this->options);
        $fullBinaryPath = $this->checkBinary()->binaryPath().$this->binaryName();

        return "\"{$fullBinaryPath}\" {$optionString} ".escapeshellarg($this->imagePath);
    }
}
