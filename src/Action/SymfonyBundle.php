<?php

/*
 * LICENSE
 *
 * This source file is subject to the MIT license and the version 3 of the GPL3
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/composer-install Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Composer\Action;

use Composer\Installer\PackageEvent;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;

/**
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/composer-install Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class SymfonyBundle implements ActionInterface
{
    use SymfonyTrait;

    private function getBundlesFilename(PackageInterface $package): string
    {
        return $this->getConfigDir($package) . DIRECTORY_SEPARATOR . 'bundles.php';
    }

    /**
     * @return array<string, array<string, boolean>>
     */
    private function getBundles(string $path): array
    {
        if (!\file_exists($path)) {
            return [];
        }

        return include($path);
    }

    /**
     * @param array<string, array<string, boolean>> $installedBundles
     * @param array<string, array<string, boolean>> $newBundles
     * @return array<string, array<string, boolean>>
     */
    private function mergeBundles(array $installedBundles, array $newBundles): array
    {
        return \array_merge_recursive(
            $newBundles,
            $installedBundles
        );
    }

    /**
     * @param array<string, array<string, boolean>> $installedBundles
     * @param array<string, array<string, boolean>> $newBundles
     * @return array<string, array<string, boolean>>
     */
    private function removeBundles(array $installedBundles, array $newBundles): array
    {
        return \array_diff_key(
            $installedBundles,
            $newBundles
        );
    }

    /**
     * Code from https://github.com/symfony/flex/blob/main/src/Configurator/BundlesConfigurator.php
     * @author Fabien Potencier <fabien@symfony.com>
     * @param array<string, array<string, boolean>> $bundles
     */
    private function writeBundle(string $path, array $bundles): void
    {
        $contents = "<?php\n\nreturn [\n";
        foreach ($bundles as $class => $envs) {
            $contents .= "    $class::class => [";
            foreach ($envs as $env => $value) {
                $booleanValue = var_export($value, true);
                $contents .= "'$env' => $booleanValue, ";
            }
            $contents = substr($contents, 0, -2) . "],\n";
        }
        $contents .= "];\n";

        if (!\is_dir(\dirname($path))) {
            \mkdir(\dirname($path), 0777, true);
        }

        \file_put_contents($path, $contents);

        if (\function_exists('opcache_invalidate')) {
            \opcache_invalidate($path);
        }
    }

    /**
     * @param array<string, array<string, boolean>> $bundles
     */
    private function registerBundles(array $bundles, PackageInterface $rootPackage): void
    {
        $path = $this->getBundlesFilename($rootPackage);
        $installedBundles = $this->getBundles($path);
        $bundles = $this->mergeBundles($installedBundles, $bundles);

        $this->writeBundle($path, $bundles);
    }

    /**
     * @param array<string, array<string, boolean>> $bundles
     */
    private function unregisterBundles(array $bundles, PackageInterface $rootPackage): void
    {
        $path = $this->getBundlesFilename($rootPackage);
        $installedBundles = $this->getBundles($path);
        $bundles = $this->removeBundles($installedBundles, $bundles);

        $this->writeBundle($path, $bundles);
    }

    public function install(
        string $packageName,
        array $arguments,
        PackageEvent $event,
        IOInterface $io
    ): ActionInterface {
        $composer = $event->getComposer();
        $package = $composer->getPackage();

        if ($package instanceof PackageInterface) {
            $this->registerBundles($arguments, $package);
        }

        return $this;
    }

    public function update(
        string $packageName,
        array $arguments,
        PackageEvent $event,
        IOInterface $io
    ): ActionInterface {
        $composer = $event->getComposer();
        $package = $composer->getPackage();

        if ($package instanceof PackageInterface) {
            $this->registerBundles($arguments, $package);
        }

        return $this;
    }

    public function uninstall(
        string $packageName,
        array $arguments,
        PackageEvent $event,
        IOInterface $io
    ): ActionInterface {
        $composer = $event->getComposer();
        $package = $composer->getPackage();

        if (
            $package instanceof PackageInterface
            && $io->askConfirmation("Confirm remove bundles for $packageName ? (yes/no)" . PHP_EOL, true)
        ) {
            $this->unregisterBundles($arguments, $package);
        }

        return $this;
    }
}
