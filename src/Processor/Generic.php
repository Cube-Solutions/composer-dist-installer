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

namespace Cube\ComposerDistInstaller\Processor;

use Composer\IO\IOInterface;

/**
 * Class Generic
 *
 * Loosely based in https://github.com/Incenteev/ParameterHandler/blob/master/Processor.php
 *
 * @package Cube\ComposerDistInstaller
 */
class Generic implements ProcessorInterface
{

    protected $io;
    protected $config;

    public function __construct(IOInterface $io)
    {
        $this->setIO($io);
    }

    public function process(array $config)
    {
        $this->setConfig($config);
        $config = $this->getConfig();

        $realFile = $config['file'];

        $exists = is_file($realFile);

        $action = $exists ? 'Rewriting' : 'Creating';
        $this->getIO()->write(sprintf('<info>%s the "%s" file</info>', $action, $realFile));

        if ($exists && $this->io->askConfirmation('Destination file already exists, overwrite (y/n)? ')) {
            $oldFile = $realFile . '.old';
            copy($realFile, $oldFile);
            $this->getIO()->write(sprintf('A copy of the old configuration file was saved to %s', $oldFile));
        } else {
            if (!is_dir($dir = dirname($realFile))) {
                mkdir($dir, 0755, true);
            }
        }

        $template = file_get_contents($config['dist-file']);
        $contents = preg_replace_callback('/\{\{(.*)\}\}/', array($this, '_templateReplace'), $template);
        file_put_contents($realFile, $contents);

        return true;
    }

    /**
     * @return IOInterface
     */
    public function getIO()
    {
        return $this->io;
    }

    /**
     * @param IOInterface $io
     */
    public function setIO(IOInterface $io)
    {
        $this->io = $io;
    }

    /**
     * @return mixed
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param mixed $config
     */
    public function setConfig($config)
    {
        if (empty($config['file'])) {
            throw new \InvalidArgumentException('The extra.dist-installer-params.file setting is required.');
        }

        if (empty($config['dist-file'])) {
            $config['dist-file'] = $config['file'].'.dist';
        }

        if (!is_file($config['dist-file'])) {
            throw new \InvalidArgumentException(sprintf('The dist file "%s" does not exist. Check your dist-file config or create it.', $config['dist-file']));
        }
        $this->config = $config;
    }

    /**
     * @return array
     */
    protected function _getEnvValues()
    {
        $params = [];
        foreach ($this->config['env-map'] as $param => $env) {
            $value = getenv($env);
            if ($value) {
                $params[$param] = $value;
            }
        }
        return $params;
    }

    /**
     * @param array $matches
     * @return string
     */
    protected function _templateReplace(array $matches)
    {
        $result = $matches[0];
        if (count($matches) > 1) {
            $explode = explode('|', $matches[1]);
            $question = $explode[0];
            $default = @$explode[1] ?: null;
            // if default syntax is =ENV[VARIABLE_NAME] then extract VARIABLE_NAME from the environment as default value
            if (strpos($default, '=ENV[') === 0) {
                $envs = $this->_getEnvValues();
                $envMatch = [];
                preg_match('/^\=ENV\[(.*)\]$/', $default, $envMatch);
                if (isset($envMatch[1])) {
                    $default = $envs[$envMatch[1]];
                }
            }
            $question = str_replace('[]', "[$default]", $question);
            $result = $this->getIO()->ask(rtrim($question) . ' ', $default);
        }
        return $result;
    }
}
