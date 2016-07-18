<?php

/**
 * This file is part of tenside/core.
 *
 * (c) Christian Schiffler <c.schiffler@cyberspectrum.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    tenside/core
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright  2015 Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @license    https://github.com/tenside/core/blob/master/LICENSE MIT
 * @link       https://github.com/tenside/core
 * @filesource
 */

namespace Tenside\Core\Test\Util;

use Symfony\Component\Process\Process;
use Tenside\Core\Test\TestCase;
use Tenside\Core\Util\ProcessBuilder;

/**
 * Test the RuntimeHelper.
 */
class ProcessBuilderTest extends TestCase
{
    /**
     * Test that the builder really builds the process correctly.
     *
     * @return void
     */
    public function testCreate()
    {
        $builder = ProcessBuilder::create('/bin/foo');
        $process = $builder->generate();

        $this->assertInstanceOf(ProcessBuilder::class, $builder);
        $this->assertInstanceOf(Process::class, $process);
        $this->assertEquals(escapeshellarg('/bin/foo'), $process->getCommandLine());
    }

    /**
     * Test that the builder really builds the process correctly.
     *
     * @return void
     */
    public function testCreateWithArguments()
    {
        $builder = ProcessBuilder::create('/bin/foo', ['arg1', 'arg2']);
        $process = $builder->generate();

        $this->assertInstanceOf(ProcessBuilder::class, $builder);
        $this->assertInstanceOf(Process::class, $process);
        $this->assertEquals($this->cliEscapeArray(['/bin/foo', 'arg1', 'arg2']), $process->getCommandLine());
    }

    /**
     * Test that the builder adds an argument.
     *
     * @return void
     */
    public function testAddArgument()
    {
        $builder = ProcessBuilder::create('/bin/foo', ['arg1', 'arg2']);

        $this->assertSame($builder, $builder->addArgument('arg3'));

        $process = $builder->generate();

        $this->assertEquals($this->cliEscapeArray(['/bin/foo', 'arg1', 'arg2', 'arg3']), $process->getCommandLine());
    }

    /**
     * Test that the builder adds arguments.
     *
     * @return void
     */
    public function testAddArguments()
    {
        $builder = ProcessBuilder::create('/bin/foo', ['arg1']);

        $this->assertSame($builder, $builder->addArguments(['arg2', 'arg3']));

        $process = $builder->generate();

        $this->assertEquals($this->cliEscapeArray(['/bin/foo', 'arg1', 'arg2', 'arg3']), $process->getCommandLine());
    }

    /**
     * Test that the builder overrides arguments.
     *
     * @return void
     */
    public function testSetArguments()
    {
        $builder = ProcessBuilder::create('/bin/foo', ['arg1', 'arg2']);

        $this->assertSame($builder, $builder->setArguments(['arg3']));

        $process = $builder->generate();

        $this->assertEquals($this->cliEscapeArray(['/bin/foo', 'arg3']), $process->getCommandLine());
    }

    /**
     * Test that the builder sets the working directory.
     *
     * @return void
     */
    public function testSetWorkingDirectory()
    {
        $builder = ProcessBuilder::create('/bin/foo');

        $this->assertSame($builder, $builder->setWorkingDirectory($tmp = $this->getTempDir()));

        $process = $builder->generate();

        $this->assertEquals($tmp, $process->getWorkingDirectory());
    }

    /**
     * Test that an invalid working directory raises an exception.
     *
     * @return void
     *
     * @expectedException \Symfony\Component\Process\Exception\InvalidArgumentException
     *
     * @expectedExceptionMessage The working directory must exist.
     */
    public function testSetInvalidWorkingDirectoryRaisesException()
    {
        ProcessBuilder::create('/bin/foo')
            ->setWorkingDirectory('/does/not/exist')
            ->generate();
    }

    /**
     * Test that inheriting the environment works.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    public function testInheritingEnvironment()
    {
        $builder = ProcessBuilder::create('/bin/foo');
        $process = $builder->generate();

        // Fetch the current environment.
        $this->assertArraySubset(
            array_filter(array_merge($_ENV, $_SERVER), function ($v) {
                return is_string($v);
            }),
            $process->getEnv()
        );
    }

    /**
     * Test that not inheriting the environment works.
     *
     * @return void
     */
    public function testNotInheritingEnvironment()
    {
        $builder = ProcessBuilder::create('/bin/foo');

        $this->assertSame($builder, $builder->inheritEnvironmentVariables(false));

        $process = $builder->generate();

        $this->assertEquals([], $process->getEnv());
    }

    /**
     * Test that manually setting the environment works.
     *
     * @return void
     */
    public function testManuallySettingEnvironment()
    {
        $builder = ProcessBuilder::create('/bin/foo');

        $this->assertSame($builder, $builder->setEnv('FOO', 'BAR'));

        $process = $builder->generate();

        $this->assertArraySubset(['FOO' => 'BAR'], $process->getEnv());
    }

    /**
     * Test that not inheriting the environment works.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    public function testManuallyUnSettingEnvironment()
    {
        $_SERVER['FOO'] = 'BAR';

        $builder = ProcessBuilder::create('/bin/foo');

        $this->assertSame($builder, $builder->setEnv('FOO', null));

        $process = $builder->generate();
        unset($_SERVER['FOO']);

        $this->assertArraySubset(['FOO' => null], $process->getEnv());
    }

    /**
     * Test that manually setting the environment works.
     *
     * @return void
     */
    public function testManuallySettingEnvironmentVariables()
    {
        $builder = ProcessBuilder::create('/bin/foo');

        $this->assertSame($builder, $builder->addEnvironmentVariables(['x1' => 'a', 'x2' => 'b']));

        $process = $builder->generate();

        $this->assertArraySubset(['x1' => 'a', 'x2' => 'b'], $process->getEnv());
    }

    /**
     * Test that setting the input works.
     *
     * @return void
     */
    public function testSetInput()
    {
        $tmpStream = fopen('php://temp', 'a+');

        $builder = ProcessBuilder::create('/bin/foo');

        $this->assertSame($builder, $builder->setInput($tmpStream));

        $process = $builder->generate();

        $this->assertSame($tmpStream, $process->getInput());

    }

    /**
     * Test that setting the input works.
     *
     * @return void
     *
     * @expectedException \Symfony\Component\Process\Exception\InvalidArgumentException
     */
    public function testSetInvalidInput()
    {
        ProcessBuilder::create('/bin/foo')
            ->setInput((object) [0, 1, 2, 3, 4]);
    }

    /**
     * Test that setting a timeout works.
     *
     * @return void
     */
    public function testTimeout()
    {
        $builder = new ProcessBuilder('/bin/foo');
        $this->assertSame($builder, $builder->setTimeout(10));

        $process = $builder->generate();

        $this->assertEquals(10, $process->getTimeout());
    }

    /**
     * Test that negative timeout raises exception.
     *
     * @return void
     *
     * @expectedException \Symfony\Component\Process\Exception\InvalidArgumentException
     */
    public function testNegativeTimeout()
    {
        $builder = new ProcessBuilder('/bin/foo');
        $builder->setTimeout(-1);
    }

    /**
     * Test that setting a null timeout works.
     *
     * @return void
     */
    public function testNullTimeout()
    {
        $builder = new ProcessBuilder('/bin/foo');
        $this->assertSame($builder, $builder->setTimeout(10));
        $this->assertSame($builder, $builder->setTimeout(null));

        $process = $builder->generate();

        $this->assertNull($process->getTimeout());
    }

    /**
     * Test that setting a null timeout works.
     *
     * @return void
     */
    public function testSetOption()
    {
        $builder = new ProcessBuilder('/bin/foo');
        $this->assertSame($builder, $builder->setOption('binary_pipes', true));

        $process = $builder->generate();

        $this->assertArraySubset(['binary_pipes' => true], $process->getOptions());
    }

    /**
     * Test disabled output
     *
     * @return void
     */
    public function testShouldReturnProcessWithDisabledOutput()
    {
        $process = ProcessBuilder::create('/bin/foo')
            ->disableOutput()
            ->generate();

        $this->assertTrue($process->isOutputDisabled());
    }

    /**
     * Test enabled output
     *
     * @return void
     */
    public function testShouldReturnProcessWithEnabledOutput()
    {
        $process = ProcessBuilder::create('/bin/foo')
            ->disableOutput()
            ->enableOutput()
            ->generate();

        $this->assertFalse($process->isOutputDisabled());
    }

    /**
     * Test force background
     *
     * @return void
     */
    public function testShouldForceBackground()
    {
        $process = ProcessBuilder::create('/bin/foo')
            ->setForceBackground(false)
            ->setForceBackground()
            ->generate();

        if ('\\' === DIRECTORY_SEPARATOR) {
            $this->assertStringStartsWith('start /B ', $process->getCommandLine());
        } else {
            $this->assertStringEndsWith(' &', $process->getCommandLine());
        }
    }

    /**
     * Map an array through escapeshellarg and combine it with spaces.
     *
     * @param string[] $values The values to combine.
     *
     * @return string
     */
    private function cliEscapeArray($values)
    {
        return implode(' ', array_map('escapeshellarg', $values));
    }
}
