<?php

/*
 * LICENSE
 *
 * This source file is subject to the MIT license
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/composer-install Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Composer\Action;

use Composer\Package\RootPackageInterface;

use function getcwd;

/**
 * Trait to detect the current project's config dir and the current project's root dir of a Symfony project, required
 * by `SymfonyPackages` and `SymfonyRoutes`.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/composer-install Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
trait SymfonyTrait
{
    private function getConfigDir(RootPackageInterface $package): string
    {
        $config = $package->getExtra();

        $configDir = $config['config-dir'] ?? 'config';
        $rootDir = $config['root-dir'] ?? '.';

        return getcwd() . DIRECTORY_SEPARATOR . $rootDir . DIRECTORY_SEPARATOR . $configDir;
    }
}
