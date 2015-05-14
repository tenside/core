<?php
/**
 * Created by PhpStorm.
 * User: nicoschneider
 * Date: 14/05/15
 * Time: 19:57
 */

namespace Tenside\Composer\Repository;


use Composer\Package\PackageInterface;

class PackagistRepository extends AbstractApiRepository
{

    /**
     * @var string
     */
    protected $packagistUrl;

    /**
     * @param string $packagistUrl
     */
    function __construct($packagistUrl = 'http://packagist.org/')
    {
        parent::__construct();

        $this->packagistUrl = $packagistUrl;
    }

    /**
     * @param string $uri
     *
     * @return \Tenside\Util\JsonArray
     */
    private function call($uri)
    {
        return $this->getApiResponse($this->packagistUrl . $uri);
    }

    /**
     * {@inheritDoc}
     */
    public function hasPackage(PackageInterface $package)
    {
        $name    = $package->getName();
        $results = $this->call('search.json?q=' . $name);

        return $results->get('total') > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function findPackage($name, $version)
    {
        $data = $this->call('search.json?q=' . $name);

        foreach ($data->get('results') as $result) {
            // TODO: match versions
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function findPackages($name, $version = null)
    {
        // TODO: Implement findPackages() method.
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function getPackages()
    {
        // TODO: Implement getPackages() method.
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function search($query, $mode = 0)
    {
        $data = $this->call('search.json?q=' . $query);

        return $data->get('results');
    }

    /**
     * {@inheritDoc}
     */
    public function count()
    {
        return count($this->call('packages/list.json')->get('packageNames'));
    }


}