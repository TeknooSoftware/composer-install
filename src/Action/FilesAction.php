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
use RuntimeException;

use function array_keys;
use function base64_decode;
use function current;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function implode;
use function is_dir;
use function is_string;
use function key;
use function mkdir;
use function realpath;
use function reset;
use function unlink;

use const PHP_EOL;

/**
 * Abstract class to create actions to create/update/delete new file in the project's filesystem, with contents from
 * extra in composer.json. The content can be a json array, each line is a new row, or a base64 encoded text.
 *
 * Files will be created or updated on install or update operation, and will be deleted (if configured by the user) on
 * delete. If the file already exist, this class will require a user's confirmation.
 *
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
     * @param string|array<int|string, string> $content
     */
    private function parseContent(string $fileName, string | array &$content): string
    {
        if (is_string($content)) {
            return $content;
        }

        reset($content);
        $key = key($content);

        return match ($key) {
            0 => implode(PHP_EOL, $content),
            'base64' => base64_decode((string) current($content)),
            default => throw new RuntimeException("Content type $key for $fileName is not supported"),
        };
    }

    /**
     * Write a file from its content. If the file already exist, will require a user's confirmation, unless if the
     * current content is identical to new content
     * .
     * @param string|array<int|string, mixed> $content
     */
    private function write(IOInterface $io, string $destinationPath, string $fileName, string | array &$content): void
    {
        $path = $destinationPath . $fileName;

        if (!is_dir($destinationPath)) {
            mkdir($destinationPath, 0777, true);
        }

        $destinationPath = realpath($destinationPath);

        $content = $this->parseContent($fileName, $content);

        $message = PHP_EOL . "$destinationPath/$fileName already exist, remplace it ? (yes/no)" . PHP_EOL;
        if (
            file_exists($path)
            && trim((string) file_get_contents($path)) !== trim($content)
            && !$io->askConfirmation($message, false)
        ) {
            return;
        }

        $io->write("Extract $fileName in $destinationPath");

        file_put_contents($path, $content);
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
        if (!$io->askConfirmation(PHP_EOL . "Confirm remove files from $packageName ? (yes/no)" . PHP_EOL, true)) {
            return;
        }

        foreach (array_keys($arguments) as $fileName) {
            $io->write("Delete $path/$fileName");
            $filePath = $path . DIRECTORY_SEPARATOR . $fileName;
            @unlink($filePath);
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
