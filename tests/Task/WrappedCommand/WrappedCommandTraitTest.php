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

namespace Tenside\Core\Test\Task\WrappedCommand;

use Composer\Composer;
use Tenside\Core\Task\WrappedCommand\WrappedCommandTrait;
use Tenside\Core\Test\TestCase;

/**
 * This class tests the wrapped command trait.
 */
class WrappedCommandTraitTest extends TestCase
{
    /**
     * Test that the get composer method works.
     *
     * @return void
     */
    public function testGetComposerWorks()
    {
        $trait = $this->getMockForTrait(WrappedCommandTrait::class);

        /** @var WrappedCommandTrait $trait */
        $trait->setComposerFactory(
            function () {
                return new Composer();
            }
        );

        $composer = $trait->getComposer();

        $this->assertInstanceOf(Composer::class, $composer);
        $this->assertSame($composer, $trait->getComposer());
    }

    /**
     * Test that the get composer method will return a prior set instance.
     *
     * @return void
     */
    public function testSetComposerWorks()
    {
        $trait = $this->getMockForTrait(WrappedCommandTrait::class);

        /** @var WrappedCommandTrait $trait */
        $trait->setComposer(new Composer());

        $composer = $trait->getComposer();

        $this->assertInstanceOf(Composer::class, $composer);
        $this->assertSame($composer, $trait->getComposer());
    }

    /**
     * Test that retrieval of a required composer instance fails without factory.
     *
     * @return void
     *
     * @expectedException \RuntimeException
     */
    public function testGetRequiredComposerFailsWithoutFactory()
    {
        $trait = $this->getMockForTrait(WrappedCommandTrait::class);

        /** @var WrappedCommandTrait $trait */
        $trait->getComposer();
    }

    /**
     * Test that retrieval of a not required composer instance return null without factory.
     *
     * @return void
     *
     * @expectedException \RuntimeException
     */
    public function testGetNotRequiredComposerReturnsNullWithoutFactory()
    {
        $trait = $this->getMockForTrait(WrappedCommandTrait::class);

        /** @var WrappedCommandTrait $trait */
        $this->assertNull($trait->getComposer());
    }
}
