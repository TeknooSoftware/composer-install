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
abstract class FilesAction implements ActionInterface
{
    abstract protected function getDestinationPath(PackageInterface $package): string;

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
    private function write(IOInterface $io, string $destinationPath, string $fileName, &$content): void
    {
        $path = $destinationPath . $fileName;

        if (!\is_dir($destinationPath)) {
            \mkdir($destinationPath, 0777, true);
        }

        $destinationPath = \realpath($destinationPath);

        $content = $this->parseContent($fileName, $content);

        $message = "$destinationPath/$fileName already exist, remplace it ? (yes/no)" . PHP_EOL;
        if (
            \file_exists($path)
            && !$io->askConfirmation($message, false)
        ) {
            return;
        }

        $io->write("Extract $fileName in $destinationPath");

        \file_put_contents($path, $content);
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
        $path = $this->getDestinationPath($package);

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
        $path = $this->getDestinationPath($package);

        $io->write("Clean configuration from $packageName");
        if (!$io->askConfirmation("Confirm remove files from $packageName ? (yes/no)" . PHP_EOL, true)) {
            return;
        }

        foreach (\array_keys($arguments) as $fileName) {
            $io->write("Delete $path/$fileName");
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
