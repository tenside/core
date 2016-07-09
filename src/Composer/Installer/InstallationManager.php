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

namespace Tenside\Core\Composer\Installer;

use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\OperationInterface;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\DependencyResolver\Pool;
use Composer\DependencyResolver\Rule;
use Composer\Package\Dumper\ArrayDumper;
use Composer\Repository\RepositoryInterface;
use Tenside\Core\Util\JsonArray;

/**
 * This class wraps the real installation manager to be able to generate a log of package changes when upgrading.
 */
class InstallationManager extends \Composer\Installer\InstallationManager
{
    /**
     * The package information.
     *
     * @var JsonArray
     */
    private $packageInformation;

    /**
     * The dumper to use.
     *
     * @var ArrayDumper
     */
    private $dumper;

    /**
     * The pool in use.
     *
     * @var Pool
     */
    private $pool;

    /**
     * Create a new instance.
     *
     * @param JsonArray $packageInformation The log where package manipulations shall get logged to.
     */
    public function __construct(JsonArray $packageInformation)
    {
        $this->packageInformation = $packageInformation;
        $this->dumper             = new ArrayDumper();
    }

    /**
     * Set the pool.
     *
     * @param Pool $pool The new value.
     *
     * @return InstallationManager
     */
    public function setPool($pool)
    {
        $this->pool = $pool;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function install(RepositoryInterface $repo, InstallOperation $operation)
    {
        $this->packageInformation->set(
            $this->packageInformation->escape($operation->getPackage()->getPrettyName()),
            [
                'type'    => 'install',
                'reason'  => $this->getReason($operation),
                'package' => $this->dumper->dump($operation->getPackage())
            ]
        );

        parent::install($repo, $operation);
    }

    /**
     * {@inheritDoc}
     */
    public function update(RepositoryInterface $repo, UpdateOperation $operation)
    {
        $this->packageInformation->set(
            $this->packageInformation->escape($operation->getInitialPackage()->getPrettyName()),
            [
                'type'    => 'update',
                'reason'  => $this->getReason($operation),
                'package' => $this->dumper->dump($operation->getInitialPackage()),
                'target'  => $this->dumper->dump($operation->getTargetPackage())
            ]
        );
        parent::update($repo, $operation);
    }

    /**
     * {@inheritDoc}
     */
    public function uninstall(RepositoryInterface $repo, UninstallOperation $operation)
    {
        $this->packageInformation->set(
            $this->packageInformation->escape($operation->getPackage()->getPrettyName()),
            [
                'type'    => 'uninstall',
                'package' => $this->dumper->dump($operation->getPackage())
            ]
        );
        parent::uninstall($repo, $operation);
    }

    /**
     * Convert reason to text.
     *
     * @param OperationInterface $operation The operation to obtain the reason from.
     *
     * @return string|null
     */
    private function getReason(OperationInterface $operation)
    {
        if (!$this->pool) {
            return null;
        }

        $reason = $operation->getReason();
        if ($reason instanceof Rule) {
            switch ($reason->getReason()) {
                case Rule::RULE_JOB_INSTALL:
                    return 'Required by the root package: ' . $reason->getPrettyString($this->pool);
                case Rule::RULE_PACKAGE_REQUIRES:
                    return $reason->getPrettyString($this->pool);
                default:
            }
        }

        return null;
    }
}
