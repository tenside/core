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

namespace Tenside\Composer;

use Composer\Composer;
use Composer\DependencyResolver\DefaultPolicy;
use Composer\DependencyResolver\Operation\OperationInterface;
use Composer\DependencyResolver\Pool;
use Composer\DependencyResolver\Request;
use Composer\DependencyResolver\Solver;
use Composer\DependencyResolver\SolverProblemsException;
use Composer\Package\Link;
use Composer\Package\LinkConstraint\VersionConstraint;
use Composer\Package\PackageInterface;
use Composer\Package\RootPackageInterface;
use Composer\Repository\CompositeRepository;
use Composer\Repository\InstalledArrayRepository;
use Composer\Repository\PlatformRepository;

/**
 * This class is resolving dependencies.
 */
class SolverRunner
{
    /**
     * The composer instance.
     *
     * @var Composer
     */
    private $composer;

    /**
     * The root package.
     *
     * @var RootPackageInterface
     */
    private $package;

    /**
     * The built pool.
     *
     * @var Pool
     */
    private $pool;

    /**
     * The platform repository.
     *
     * @var PlatformRepository
     */
    private $platform;

    /**
     * The repository reflecting the current installation.
     *
     * @var CompositeRepository
     */
    private $installed;

    /**
     * Create a new instance.
     *
     * @param Composer $composer The composer instance.
     */
    public function __construct(Composer $composer)
    {
        // clone root package to have one in the installed repo that does not require anything
        // we don't want it to be uninstallable, but its requirements should not conflict
        // with the lock file for example
        $this->composer = $composer;
        $this->package  = $this->composer->getPackage();
        $installedRoot  = clone $this->package;
        $installedRoot->setRequires(array());
        $installedRoot->setDevRequires(array());

        $this->pool      = new Pool($this->package->getMinimumStability(), $this->package->getStabilityFlags());
        $this->platform  = new PlatformRepository();
        $this->installed = new CompositeRepository(
            [
                new InstalledArrayRepository(array($installedRoot)),
                $this->composer->getRepositoryManager()->getLocalRepository(),
                $this->platform
            ]
        );

        $this->pool->addRepository(
            new CompositeRepository(
                array_merge([$this->installed], $this->composer->getRepositoryManager()->getRepositories())
            )
        );
    }

    /**
     * Build a new request preserving the current requires.
     *
     * @return Request
     */
    private function getRequest()
    {
        $request = new Request($this->pool);

        $constraint = new VersionConstraint('=', $this->package->getVersion());
        $constraint->setPrettyString($this->package->getPrettyVersion());
        $request->install($this->package->getName(), $constraint);

        $fixedPackages = $this->platform->getPackages();

        // fix the version of all platform packages + additionally installed packages
        // to prevent the solver trying to remove or update those
        /** @var  Link[] $provided */
        $provided = $this->package->getProvides();
        /** @var PackageInterface $package */
        foreach ($fixedPackages as $package) {
            $constraint = new VersionConstraint('=', $package->getVersion());
            $constraint->setPrettyString($package->getPrettyVersion());

            // skip platform packages that are provided by the root package
            if (($package->getRepository() !== $this->platform)
                || !isset($provided[$package->getName()])
                || !$provided[$package->getName()]->getConstraint()->matches($constraint)
            ) {
                $request->fix($package->getName(), $constraint);
            }
        }

        // add requirements
        $links = array_merge($this->package->getRequires(), $provided);
        /** @var Link $link */
        foreach ($links as $link) {
            // FIXME: can't we also fix these here? Could be faster.
            $request->install($link->getTarget(), $link->getConstraint());
        }

        return $request;
    }

    /**
     * Solve the current dependencies and return the tasks that will be performed.
     *
     * @return OperationInterface[]
     *
     * @throws SolverProblemsException When the dependencies can not be resolved.
     */
    public function solve()
    {
        gc_collect_cycles();
        gc_disable();

        $solver  = new Solver(new DefaultPolicy($this->package->getPreferStable()), $this->pool, $this->installed);
        $request = $this->getRequest();
        $request->updateAll();

        $operations = $solver->solve($request);

        gc_enable();

        return $operations;
    }
}
