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

namespace Tenside\CoreBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Tenside\SelfTest\Cli\SelfTestCanSpawnProcesses;
use Tenside\SelfTest\Cli\SelfTestCliRuntime;
use Tenside\SelfTest\Generic\SelfTestCalledViaHttps;
use Tenside\SelfTest\Generic\SelfTestFileOwnerMatches;
use Tenside\SelfTest\Php\SelfTestAllowUrlFopenEnabled;
use Tenside\SelfTest\Php\SelfTestSuhosin;
use Tenside\SelfTest\SelfTest;

/**
 * This class provides the self test entry points.
 */
class SelfTestController extends AbstractController
{
    /**
     * Retrieve the URLs to all tests.
     *
     * @return JsonResponse
     */
    public function getAllTestsAction()
    {
        $tester = $this->prepareTests();

        $data = [];
        foreach ($tester->perform() as $result) {
            $data[$this->testClassToSlug($result->getTestClass())] = [
                'state'   => $result->getState(),
                'message' => $result->getMessage(),
                'explain' => $result->getExplain(),
            ];
        }

        return JsonResponse::create($data);
    }

    /**
     * Retrieve the automatic generated tenside configuration.
     *
     * @return JsonResponse
     */
    public function getAutoConfigAction()
    {
        $tester = $this->prepareTests();
        $tester->perform();

        $config = $tester->getAutoConfig();
        $result = [];

        if ($phpCli = $config->getPhpInterpreter()) {
            $result['php-cli'] = $phpCli;
        }

        return JsonResponse::create($result);
    }

    /**
     * Create a slug from a test class.
     *
     * @param string $className The class name to convert.
     *
     * @return string
     */
    private function testClassToSlug($className)
    {
        $className = basename(str_replace('\\', '/', $className));

        if ('SelfTest' === substr($className, 0, 8)) {
            $className = substr($className, 8);
        }

        $className = strtolower(substr(preg_replace('#([A-Z])#', '-$1', $className), 1));

        return $className;
    }

    /**
     * Prepare the tests.
     *
     * @return SelfTest
     */
    private function prepareTests()
    {
        $tester = new SelfTest();

        $tester->addTest(new SelfTestCalledViaHttps());
        $tester->addTest(new SelfTestFileOwnerMatches());
        $tester->addTest(new SelfTestAllowUrlFopenEnabled());
        $tester->addTest(new SelfTestSuhosin());
        $tester->addTest(new SelfTestCanSpawnProcesses());
        $tester->addTest(new SelfTestCliRuntime());
        // Can execute stand alone.
        // Can raise memory limit in web/console (remote execution test - if 500/exit code != 0 - impossible).
        // Future:
        // can execute standalone websocket.
        return $tester;
    }
}
