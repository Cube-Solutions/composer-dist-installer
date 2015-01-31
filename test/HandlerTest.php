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
namespace CubeTest\ComposerDistInstaller;

use Composer\Composer;
use Composer\Script\Event;
use Cube\ComposerDistInstaller\Handler;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Class HandlerTest
 * @package CubeTest\ComposerDistInstaller
 */
class HandlerTest extends TestCase
{

    const PROCESSOR_TYPE_GENERIC = 'Cube\\ComposerDistInstaller\\Processor\\Generic';

    const CLASS_NAME = 'Cube\\ComposerDistInstaller\\Handler';

    protected function setUp()
    {
        parent::setUp();
        $toDelete = ['/fixture/basic.php'];
        array_map(function($val){
           @unlink(__DIR__ . $val);
        }, $toDelete);
    }


    /**
     * Test _detectProcessorForFile
     */
    public function testDetectProcessorForFile()
    {
        $handler = new Handler();
        $reflection = new \ReflectionMethod($handler, '_detectProcessorForFile');
        $reflection->setAccessible(true);
        $result = $reflection->invokeArgs($handler, ['test']);
        $this->assertEquals(self::PROCESSOR_TYPE_GENERIC, $result);
    }

    /**
     * Test _getProcessorForType returns a Processor
     */
    public function testGetProcessorForTypeReturnsProcessor()
    {
        $handler = new Handler();

        $reflection = new \ReflectionMethod($handler, '_getProcessorForType');
        $reflection->setAccessible(true);
        $result = $reflection->invokeArgs($handler, [self::PROCESSOR_TYPE_GENERIC, $this->_buildIO()]);
        $this->assertInstanceOf('Cube\ComposerDistInstaller\Processor\ProcessorInterface', $result);
        $this->assertInstanceOf(self::PROCESSOR_TYPE_GENERIC, $result);
    }

    /**
     * Test _getProcessorForType throws an exception if invalid type requested.
     */
    public function testGetProcessorForTypeThrowsException()
    {
        $handler = new Handler();

        $reflection = new \ReflectionMethod($handler, '_getProcessorForType');
        $reflection->setAccessible(true);
        $this->setExpectedException('\InvalidArgumentException');
        $reflection->invokeArgs($handler, ['invalid', $this->_buildIO()]);
    }

    /**
     * Test install() works
     */
    public function testInstall()
    {
        /** @var Handler $handler */
        $handler = $this->_buildHandlerMock();
        $config = include __DIR__ . '/config/basic.php';
        $event = $this->_buildEvent($config);
        $handler->install($event);
    }

    /**
     * Test install() throws exception when config key unset
     */
    public function testInstallThrowsExceptionConfigUnset()
    {
        /** @var Handler $handler */
        $handler = $this->_buildHandlerMock(0, 0);
        $config = [];
        $event = $this->_buildEvent($config);
        $this->setExpectedException('\InvalidArgumentException');
        $handler->install($event);
    }

    /**
     * Test install() throws exceptionw hen config key not array
     */
    public function testInstallThrowsExceptionConfigNotArray()
    {
        /** @var Handler $handler */
        $handler = $this->_buildHandlerMock(0, 0);
        $config = [
            'dist-installer-params' => 'test'
        ];
        $event = $this->_buildEvent($config);
        $this->setExpectedException('\InvalidArgumentException');
        $handler->install($event);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function _buildIO(){
       return  $this->getMockBuilder('Composer\\IO\\IOInterface')
           ->disableOriginalConstructor()
           ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function _buildEvent($extra) {
        $package = $this->getMockBuilder('Composer\\Package\\RootPackage')
            ->disableOriginalConstructor()
            ->getMock();

        $package->expects($this->once())
            ->method('getExtra')
            ->willReturn($extra);

        $composer = $this->getMockBuilder('Composer\\Composer')
            ->disableOriginalConstructor()
            ->getMock();
        $composer->expects($this->once())
            ->method('getPackage')
            ->willReturn($package);

        $io = $this->getMock('Composer\\IO\\IOInterface');

        $event = $this->getMockBuilder('Composer\\Script\\Event')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('getComposer')
            ->willReturn($composer);
        $event->expects($this->any())
            ->method('getIO')
            ->willReturn($io);
        return $event;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function _buildHandlerMock($processCount = 1, $getProcessorForTypeCount = 1)
    {
        $processor = $this->getMockBuilder('Cube\\ComposerDistInstaller\\Processor\\ProcessorInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $processor->expects($this->exactly($processCount))
            ->method('process');

        $handler = $this->getMock(self::CLASS_NAME, ['_getProcessorForType']);
        $handler->expects($this->exactly($getProcessorForTypeCount))
            ->method('_getProcessorForType')
            ->willReturn($processor);
        return $handler;
    }

}