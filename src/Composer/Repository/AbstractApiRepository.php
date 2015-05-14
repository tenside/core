<?php
/**
 * Created by PhpStorm.
 * User: nicoschneider
 * Date: 14/05/15
 * Time: 19:56
 */

namespace Tenside\Composer\Repository;

use Composer\IO\BufferIO;
use Composer\Repository\RepositoryInterface;
use Composer\Util\RemoteFilesystem;
use Tenside\Util\JsonArray;

/**
 * Class AbstractApiRepository
 *
 * @package Tenside\Composer\Repository
 */
abstract class AbstractApiRepository implements RepositoryInterface
{

    /**
     * @var RemoteFilesystem
     */
    protected $remoteFilesystem;

    function __construct()
    {
        $this->remoteFilesystem = new RemoteFilesystem(new BufferIO());
    }

    /**
     * @param $url string
     *
     * @return JsonArray
     */
    protected function getApiResponse($url) {
        $response = $this->remoteFilesystem->getContents($url, $url);

        return new JsonArray($response);
    }

}