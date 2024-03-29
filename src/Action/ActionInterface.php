<?php

/*
 * LICENSE
 *
 * This source file is subject to the MIT license
 * it is available in LICENSE file at the root of this package
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        http://teknoo.software/composer-install Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Composer\Action;

use Composer\Installer\PackageEvent;
use Composer\IO\IOInterface;

/**
 * Interface defining executable actions about a package. Each action class must define operations to execute (can be
 * nothing) at post instal, post update and post uninstall.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        http://teknoo.software/composer-install Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
interface ActionInterface
{
    /**
     * @param array<string, string|array<string, bool>> $arguments
     */
    public function install(
        string $packageName,
        array $arguments,
        PackageEvent $event,
        IOInterface $io
    ): ActionInterface;

    /**
     * @param array<string, string|array<string, bool>> $arguments
     */
    public function update(
        string $packageName,
        array $arguments,
        PackageEvent $event,
        IOInterface $io
    ): ActionInterface;

    /**
     * @param array<string, string|array<string, bool>> $arguments
     */
    public function uninstall(
        string $packageName,
        array $arguments,
        PackageEvent $event,
        IOInterface $io
    ): ActionInterface;
}
