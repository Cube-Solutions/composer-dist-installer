<?php
/**
 * Copyright (c) 2015 Cu.be Solutions
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace Cube\ComposerDistInstaller;
use Composer\IO\IOInterface;
use Composer\Script\Event;
use Cube\ComposerDistInstaller\Processor\ProcessorInterface;

/**
 * Class Handler
 *
 * Loosely based on https://github.com/Incenteev/ParameterHandler/blob/master/ScriptHandler.php
 *
 * @package Cube\ComposerDistInstaller
 */
class Handler
{

    const EXTRAS_KEY = 'dist-installer-params';

    /** @var ProcessorInterface[] */
    protected $processors = [];

    public function install(Event $event)
    {
        $extras = $event->getComposer()->getPackage()->getExtra();

        if (!isset($extras[self::EXTRAS_KEY])) {
            throw new \InvalidArgumentException(sprintf('The parameter handler needs to be configured through the extra.%s setting.', self::EXTRAS_KEY));
        }

        $configs = $extras[self::EXTRAS_KEY];

        if (!is_array($configs)) {
            throw new \InvalidArgumentException(sprintf('The extra.%s setting must be an array or a configuration object.', self::EXTRAS_KEY));
        }

        if (array_keys($configs) !== range(0, count($configs) - 1)) {
            $configs = [$configs];
        }

        foreach ($configs as $config) {
            $processorType = isset($config['type']) ? $config['type'] : $this->_detectProcessorForFile($config['file']);
            /** @var ProcessorInterface $processor */
            $processor = $this->_getProcessorForType($processorType, $event->getIO());
            $processor->process($config);
        }
    }

    /**
     * @param $type
     * @param $io
     * @return ProcessorInterface
     */
    protected function _getProcessorForType($type, IOInterface $io) {
        if (!isset($this->processors[$type])) {
            if (!class_exists($type)) {
                throw new \InvalidArgumentException(sprintf('Could not find class %s. Please specify a valid class as the config file\'s "type" parameter.', $type));
            }
            $this->processors[$type] = new $type($io);
        }
        return $this->processors[$type];
    }

    /**
     * @param $file
     * @return string
     */
    protected function _detectProcessorForFile($file)
    {
        //TODO: see commented code below
        return __NAMESPACE__ . '\\Processor\\Generic';

        /*$ext = pathinfo($file, PATHINFO_EXTENSION);
        return __NAMESPACE__ . '\\Processor\\' . ucfirst($ext);*/
    }
}
