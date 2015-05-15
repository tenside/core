<?php
/**
 * Created by PhpStorm.
 * User: nicoschneider
 * Date: 15/05/15
 * Time: 14:09
 */

namespace Tenside\Composer\Package;

use Composer\Package\PackageInterface;
use Composer\Package\Version\VersionParser;
use Composer\Repository\RepositoryInterface;

/**
 * Class VersionedPackage
 *
 * @package Tenside\Composer\Package
 */
class VersionedPackage implements PackageInterface
{

    /**
     * @var PackageInterface
     */
    protected $package;

    /**
     * @var PackageInterface[]
     */
    protected $versions;

    /**
     * @var array
     */
    protected $meta = [];

    /**
     * @param PackageInterface $package
     * @param array            $versions
     */
    function __construct(PackageInterface $package, array $versions = [])
    {
        $this->package  = $package;
        $this->versions = $versions;
    }

    /**
     * @return array
     */
    public function getMetaData($key)
    {
        return isset($this->meta[$key]) ? $this->meta[$key] : null;
    }

    /**
     * @param      $key
     * @param      $value
     * @param bool $overwrite
     *
     * @return $this
     */
    public function addMetaData($key, $value, $overwrite = true)
    {
        $this->meta[$key] = isset($this->meta[$key])
            ? $overwrite ? $value : $this->meta[$key]
            : $value;

        return $this;
    }

    /**
     * @param array $meta
     */
    public function replaceMetaData(array $meta)
    {
        $this->meta = $meta;

        return $this;
    }

    /**
     * @return PackageInterface[]
     */
    public function getVersions()
    {
        return $this->versions;
    }

    /**
     * @param PackageInterface[] $versions
     */
    public function setVersions(array $versions)
    {
        $this->versions = $versions;

        return $this;
    }

    /**
     * @param PackageInterface $version
     *
     * @return $this
     */
    public function addVersion(PackageInterface $version)
    {
        $this->versions[] = $version;

        return $this;
    }

    /**
     * @param PackageInterface[] $versions
     *
     * @return $this
     */
    public function addVersions(array $versions)
    {
        foreach ($versions as $version) {
            $this->addVersion($version);
        }

        return $this;
    }

    /**
     * @param PackageInterface|string $version
     *
     * @return $this
     */
    public function removeVersion($version)
    {

        $versionParser = new VersionParser();

        if ($version instanceof PackageInterface) {
            $normalizedVersion = $version->getVersion();
        } elseif (is_string($version)) {
            $normalizedVersion = $versionParser->normalize($version);
        } else {
            throw new \InvalidArgumentException(
                "You have to pass either an instance of PackageInterface or a version string to remove a version from this package object!"
            );
        }

        foreach ($this->getVersions() as $key => $attachedVersion) {
            if ($attachedVersion->getVersion() == $normalizedVersion) {
                unset($this->versions[$key]);
            }
        }

        return $this;
    }

    /**
     * @return PackageInterface|null
     */
    public function getLatestVersion()
    {
        if (!count($this->versions)) {
            return null;
        }

        $latestVersion = reset($this->versions);

        foreach ($this->versions as $version) {
            if ($version->getReleaseDate() > $latestVersion->getReleaseDate()) {
                $latestVersion = $version;
            }
        }

        return $latestVersion;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->package->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function getPrettyName()
    {
        return $this->package->getPrettyName();
    }

    /**
     * {@inheritdoc}
     */
    public function getNames()
    {
        return $this->package->getNames();
    }

    /**
     * {@inheritdoc}
     */
    public function setId($id)
    {
        return $this->package->setId($id);
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->package->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function isDev()
    {
        return $this->package->isDev();
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return $this->package->getType();
    }

    /**
     * {@inheritdoc}
     */
    public function getTargetDir()
    {
        return $this->package->getTargetDir();
    }

    /**
     * {@inheritdoc}
     */
    public function getExtra()
    {
        return $this->package->getExtra();
    }

    /**
     * {@inheritdoc}
     */
    public function setInstallationSource($type)
    {
        return $this->package->setInstallationSource($type);
    }

    /**
     * {@inheritdoc}
     */
    public function getInstallationSource()
    {
        return $this->package->getInstallationSource();
    }

    /**
     * {@inheritdoc}
     */
    public function getSourceType()
    {
        return $this->package->getSourceType();
    }

    /**
     * {@inheritdoc}
     */
    public function getSourceUrl()
    {
        return $this->package->getSourceUrl();
    }

    /**
     * {@inheritdoc}
     */
    public function getSourceUrls()
    {
        return $this->package->getSourceUrls();
    }

    /**
     * {@inheritdoc}
     */
    public function getSourceReference()
    {
        return $this->package->getSourceReference();
    }

    /**
     * {@inheritdoc}
     */
    public function getSourceMirrors()
    {
        return $this->package->getSourceMirrors();
    }

    /**
     * {@inheritdoc}
     */
    public function getDistType()
    {
        return $this->package->getDistType();
    }

    /**
     * {@inheritdoc}
     */
    public function getDistUrl()
    {
        return $this->package->getDistUrl();
    }

    /**
     * {@inheritdoc}
     */
    public function getDistUrls()
    {
        return $this->package->getDistUrls();
    }

    /**
     * {@inheritdoc}
     */
    public function getDistReference()
    {
        return $this->package->getDistReference();
    }

    /**
     * {@inheritdoc}
     */
    public function getDistSha1Checksum()
    {
        return $this->package->getDistSha1Checksum();
    }

    /**
     * {@inheritdoc}
     */
    public function getDistMirrors()
    {
        return $this->package->getDistMirrors();
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion()
    {
        return $this->package->getVersion();
    }

    /**
     * {@inheritdoc}
     */
    public function getPrettyVersion()
    {
        return $this->package->getPrettyVersion();
    }

    /**
     * {@inheritdoc}
     */
    public function getReleaseDate()
    {
        return $this->package->getReleaseDate();
    }

    /**
     * {@inheritdoc}
     */
    public function getStability()
    {
        return $this->package->getStability();
    }

    /**
     * {@inheritdoc}
     */
    public function getRequires()
    {
        return $this->package->getRequires();
    }

    /**
     * {@inheritdoc}
     */
    public function getConflicts()
    {
        return $this->package->getConflicts();
    }

    /**
     * {@inheritdoc}
     */
    public function getProvides()
    {
        return $this->package->getProvides();
    }

    /**
     * {@inheritdoc}
     */
    public function getReplaces()
    {
        return $this->package->getReplaces();
    }

    /**
     * {@inheritdoc}
     */
    public function getDevRequires()
    {
        return $this->package->getDevRequires();
    }

    /**
     * {@inheritdoc}
     */
    public function getSuggests()
    {
        return $this->package->getSuggests();
    }

    /**
     * {@inheritdoc}
     */
    public function getAutoload()
    {
        return $this->package->getAutoload();
    }

    /**
     * {@inheritdoc}
     */
    public function getDevAutoload()
    {
        return $this->package->getDevAutoload();
    }

    /**
     * {@inheritdoc}
     */
    public function getIncludePaths()
    {
        return $this->package->getIncludePaths();
    }

    /**
     * {@inheritdoc}
     */
    public function setRepository(RepositoryInterface $repository)
    {
        return $this->package->setRepository($repository);
    }

    /**
     * {@inheritdoc}
     */
    public function getRepository()
    {
        return $this->package->getRepository();
    }

    /**
     * {@inheritdoc}
     */
    public function getBinaries()
    {
        return $this->package->getBinaries();
    }

    /**
     * {@inheritdoc}
     */
    public function getUniqueName()
    {
        return $this->package->getUniqueName();
    }

    /**
     * {@inheritdoc}
     */
    public function getNotificationUrl()
    {
        return $this->package->getNotificationUrl();
    }

    /**
     * {@inheritdoc}
     */
    public function getPrettyString()
    {
        return $this->package->getPrettyString();
    }

    /**
     * {@inheritdoc}
     */
    public function getArchiveExcludes()
    {
        return $this->package->getArchiveExcludes();
    }

    /**
     * {@inheritdoc}
     */
    public function getTransportOptions()
    {
        return $this->package->getTransportOptions();
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return $this->package->__toString();
    }


}