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


namespace CubeTest\ComposerDistInstaller\Processor;

use Cube\ComposerDistInstaller\Processor\Generic;
use Cube\ComposerDistInstaller\Processor\ProcessorInterface;
use PHPUnit_Framework_TestCase as TestCase;

class GenericTest extends TestCase
{

    // used to test a mapped env variable via the env-map config option
    const TEST_ENV_VAL = 'gabriel';
    const TEST_ENV_KEY = 'CUBE_TEST_USER';
    const TEST_MAPPED_ENV_KEY = 'USER';

    // used to test the behavior of an env variable that hasn't been mapped
    const TEST_UNMAPPED_ENV_KEY = 'CUBE_TEST_UNMAPPED';
    const TEST_UNMAPPED_ENV_VAL = 'UNMAPPED_VALUE';

    // used to test not existing env variables
    const TEST_UNKNOWN_ENV_KEY = 'UNKNOWN_ENV_KEY';
    const TEST_UNKNOWN_ENV_KEY2 = 'UNKNOWN_ENV_KEY2';


    protected $environmentBackup = [];


    protected function tearDown()
    {
        foreach($this->getTemplateNames() as $template) {
            $config = $this->_includeConfig($template);
            $file = $config['dist-installer-params']['file'];
            switch($template) {
                case 'overwrite':
                    $oldFile = $file . '.old';
                    @copy($oldFile, $file);
                    @unlink($oldFile);
                    break;
                case 'createdir':
                    @unlink($file);
                    @rmdir(dirname($file));
                    break;
                case 'no-overwrite':
                    break;
                default:
                    @unlink($file);
                    break;
            }
        }
        foreach($this->environmentBackup as $var => $value) {
            if (false === $value) {
                putenv($var);
            } else {
                putenv($var.'='.$value);
            }
        }
        parent::tearDown();
    }

    /**
     * @return array
     */
    protected function getTemplateNames()
    {
        return ['basic', 'params', 'env', 'overwrite', 'no-overwrite', 'createdir'];
    }

    /**
     * Test __constructor
     */
    public function testConstructor()
    {
        $processor = new Generic($this->_buildIO());
        $reflection = new \ReflectionProperty($processor, 'io');
        $reflection->setAccessible(true);
        $result = $reflection->getValue($processor);
        $this->assertInstanceOf('Composer\\IO\\IOInterface', $result);
    }

    /**
     * @param $config
     * @dataProvider configProvider
     */
    public function testConfig($config)
    {
        $processorConfig = $config['dist-installer-params'];
        $processor = new Generic($this->_buildIO());
        $property = new \ReflectionProperty($processor, 'config');
        $property->setAccessible(true);

        if (isset($config['exception'])) {
            $this->setExpectedException($config['exception']);
        }

        $processor->setConfig($processorConfig);
        $propertyValue = $property->getValue($processor);

        if(empty($processorConfig['dist-file'])) {
            $processorConfig['dist-file'] = $processorConfig['file'] . '.dist';
        }

        $this->assertEquals($processorConfig, $propertyValue);
        $this->assertEquals($processorConfig, $processor->getConfig());
    }

    /**
     * @param array $matches
     * @param $expected
     * @dataProvider templateReplaceProvider
     */
    public function testTemplateReplace(array $matches, $question, $expected)
    {
        $io = $this->_buildIO();
        $processor = new Generic($io);
        $reflection = new \ReflectionClass($processor);

        $replaceMethod = $reflection->getMethod('_templateReplace');
        $replaceMethod->setAccessible(true);

        if ($question !== null) {
            $io->expects($this->once())
                ->method('ask')
                ->will($this->returnArgument(1));
        }

        $config = $this->_setupEnv();
        $processor->setConfig($config);

        $result = $replaceMethod->invoke($processor, $matches);
        $this->assertEquals($expected, $result);
    }

    /**
     * Test _getEnvValue()
     */
    public function testGetEnvValueWithoutMap()
    {
        $processor = new Generic($this->_buildIO());
        $processor->setConfig($this->_setupEnv());
        $method = new \ReflectionMethod($processor, '_getEnvValue');
        $method->setAccessible(true);
        $result = $method->invoke($processor, self::TEST_UNMAPPED_ENV_KEY);
        $this->assertEquals(self::TEST_UNMAPPED_ENV_VAL, $result);
    }

    /**
     * Test _getEnvValue()
     */
    public function testGetEnvValueWithMap()
    {
        $processor = new Generic($this->_buildIO());
        $processor->setConfig($this->_setupEnv());
        $method = new \ReflectionMethod($processor, '_getEnvValue');
        $method->setAccessible(true);
        $result = $method->invoke($processor, self::TEST_MAPPED_ENV_KEY);
        $this->assertEquals(self::TEST_ENV_VAL, $result);
    }

    /**
     * Test process()
     * @dataProvider processProvider
     */
    public function testProcess($config)
    {
        $processorConfig = $config['dist-installer-params'];
        $io = $this->_buildIO();
        $writeCount = 0;
        $assertFileExists = true;
        $processor = new Generic($io);
        $processorConfig = $this->_setupEnv($processorConfig);

        if (isset($config['confirmation'])) {
            $io->expects($this->once())
                ->method('askConfirmation')
                ->will($this->returnValue((bool) $config['confirmation']));
            if ($config['confirmation'] == false) {
                $writeCount = 0;
                $assertFileExists = false;
            } else {
                $writeCount = 2;
            }
        }
        $io->expects($this->exactly($writeCount))
            ->method('write');
        $io->expects($this->any())
            ->method('ask')
            ->will($this->returnArgument(1));

        $processor->process($processorConfig);
        $expectedFile = $processor->getConfig()['file'];

        if ($assertFileExists) {
            $this->assertFileExists($expectedFile);
        }

        if (isset($config['old'])) {
            $oldFile = $expectedFile . '.old';
            $this->assertFileExists($oldFile);
            $expected = file_get_contents($config['old']);
            $actual = file_get_contents($oldFile);
            $this->assertEquals($expected, $actual);
        }
        if (isset($config['expected'])) {
            $expected = file_get_contents($config['expected']);
            $actual = file_get_contents($expectedFile);
            $this->assertEquals($expected, $actual);
        }
    }

    /**
     * @param $name
     * @return mixed
     */
    protected function _includeConfig($name) {
        return include __DIR__ . "/../config/$name.php";
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function _buildIO()
    {
        return $this->getMockBuilder('Composer\\IO\\IOInterface')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param ProcessorInterface $config
     * @return array|ProcessorInterface
     */
    protected function _setupEnv($config = null) {
        $this->environmentBackup[self::TEST_ENV_KEY] = getenv(self::TEST_ENV_KEY);
        putenv(self::TEST_ENV_KEY . '=' . self::TEST_ENV_VAL);

        $this->environmentBackup[self::TEST_UNMAPPED_ENV_KEY] = getenv(self::TEST_UNMAPPED_ENV_KEY);
        putenv(self::TEST_UNMAPPED_ENV_KEY . '=' . self::TEST_UNMAPPED_ENV_VAL);

        if (!$config) {
            $config = [
                'file' => 'test',
                'dist-file' => __DIR__ . '/../fixture/basic.php.dist',
            ];
        }
        $config['env-map'] = [
            self::TEST_MAPPED_ENV_KEY => self::TEST_ENV_KEY,
        ];
        return $config;
    }

    /**
     * @return array
     */
    public function configProvider() {
        $result = $this->processProvider();

        // BAD CONFIGS
        $result[] = [[
            // missing 'file' key
            'dist-installer-params' => [],
            'exception' => 'InvalidArgumentException',
        ]];
        $result[] = [[
            // dist-file does not exist
            'dist-installer-params' => [
                'file'      => 'non-existent',
                'dist-file' => 'non-existent',
            ],
            'exception' => 'InvalidArgumentException',
        ]];
        return $result;
    }

    /**
     * @return array
     */
    public function templateReplaceProvider()
    {
        $envKey = self::TEST_MAPPED_ENV_KEY;
        $unknownEnvKey = self::TEST_UNKNOWN_ENV_KEY;
        $unknownEnvKey2 = self::TEST_UNKNOWN_ENV_KEY2;

        return [
            [['test'], null, 'test'],
            [['{{ }}', ' '], ' ', null],
            [['{{username}}', 'username'], 'username', null],
            [['{{username|john.doe}}', 'username|john.doe'], 'username', 'john.doe'],
            [["{{environment|=ENV[$envKey]}}", "environment|=ENV[$envKey]"], 'environment', self::TEST_ENV_VAL],
            [["{{environment|=ENV[$envKey]|localhost}}", "environment|=ENV[$envKey]|localhost"], 'environment', self::TEST_ENV_VAL],
            [["{{environment|=ENV[$unknownEnvKey]|localhost}}", "environment|=ENV[$unknownEnvKey]|localhost"], 'environment', 'localhost'],
            [["{{environment|=ENV[$unknownEnvKey]|=ENV[$envKey]|localhost}}", "environment|=ENV[$unknownEnvKey]|=ENV[$envKey]|localhost"], 'environment', self::TEST_ENV_VAL],
            [["{{environment|=ENV[$unknownEnvKey]|=ENV[$unknownEnvKey2]|localhost}}", "environment|=ENV[$unknownEnvKey]|=ENV[$unknownEnvKey2]|localhost"], 'environment', 'localhost'],
        ];
    }

    public function processProvider()
    {
        $result = [];
        foreach($this->getTemplateNames() as $name) {
            $result[] = [$this->_includeConfig($name)];
        }
        return $result;
    }

}
