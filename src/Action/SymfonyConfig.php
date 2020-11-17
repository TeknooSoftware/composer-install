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
class SymfonyConfig implements ActionInterface
{
    use SymfonyTrait;

    private function getConfigPath(PackageInterface $package): string
    {
        return $this->getConfigDir($package) . DIRECTORY_SEPARATOR . 'packages' . DIRECTORY_SEPARATOR;
    }

    /**
     * @param string|array<int|string, mixed> $content
     */
    private function parseContent(string $fileName, &$content): string
    {
        if (\is_string($content)) {
            return $content;
        }

        if (!\is_array($content)) {
            throw new \RuntimeException("Content type is not supported for $fileName");
        }

        \reset($content);
        $key = \key($content);

        if (0 === $key) {
            return \implode(PHP_EOL, $content);
        }

        if ('base64' === $key) {
            return \base64_decode(\current($content));
        }

        throw new \RuntimeException("Content type $key for $fileName is not supported");
    }

    /**
     * @param string|array<int|string, mixed> $content
     */
    private function write(IOInterface $io, string $configDir, string $fileName, &$content): void
    {
        $path = $configDir . $fileName;

        if (!\is_dir($configDir)) {
            \mkdir($configDir, 0777, true);
        }

        if (
            \file_exists($path)
            && !$io->askConfirmation("$fileName already exist, remplace it ? (yes/no)" . PHP_EOL, false)
        ) {
            return;
        }

        $io->write("Write $fileName");

        \file_put_contents($path, $this->parseContent($fileName, $content));
    }

    /**
     * @param array<string|array<int|string, mixed>> $arguments
     */
    private function writeFiles(
        string $packageName,
        array $arguments,
        PackageInterface $package,
        IOInterface $io
    ): void {
        $path = $this->getConfigPath($package);

        $io->write("Install from $packageName");
        foreach ($arguments as $fileName => &$content) {
            $this->write(
                $io,
                $path,
                $fileName,
                $content
            );
        }
    }

    /**
     * @param array<string|array<int|string, mixed>> $arguments
     */
    private function deleteFiles(
        string $packageName,
        array $arguments,
        PackageInterface $package,
        IOInterface $io
    ): void {
        $path = $this->getConfigPath($package);

        $io->write("Clean configuration from $packageName");
        if (!$io->askConfirmation("Conform cleaning files configured for $packageName ? (yes/no)" . PHP_EOL, true)) {
            return;
        }

        foreach (\array_keys($arguments) as $fileName) {
            $io->write("Delete $fileName");
            $filePath = $path . DIRECTORY_SEPARATOR . $fileName;
            @\unlink($filePath);
        }
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
            $this->writeFiles($packageName, $arguments, $package, $io);
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
            $this->writeFiles($packageName, $arguments, $package, $io);
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

        if ($package instanceof PackageInterface) {
            $this->deleteFiles($packageName, $arguments, $package, $io);
        }

        return $this;
    }
}
