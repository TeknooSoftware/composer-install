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

namespace Teknoo\Composer;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\OperationInterface;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Package\RootPackageInterface;
use Composer\Plugin\PluginInterface;
use Teknoo\Composer\Action\ActionInterface;

/**
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/composer-install Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class Installer implements PluginInterface, EventSubscriberInterface
{
    public const CONFIG_KEY = 'config';
    public const CONFIG_DISABLED_KEY = 'disabled';

    private static bool $activated = true;

    private ?IOInterface $io = null;

    /**
     * @var array<string, mixed>
     */
    private array $config = [];

    /**
     * @return array<string, array<string>>
     */
    public static function getSubscribedEvents(): array
    {
        if (!self::$activated) {
            return [];
        }

        return array(
            PackageEvents::POST_PACKAGE_INSTALL => ['postInstall'],
            PackageEvents::POST_PACKAGE_UPDATE => ['postUpdate'],
            PackageEvents::POST_PACKAGE_UNINSTALL => ['postUninstall'],
        );
    }

    private function getIo(): IOInterface
    {
        return $this->io;
    }

    /**
     * @param OperationInterface $operation
     * @return array<string|int|null, mixed>
     */
    private function getExtra(OperationInterface $operation): array
    {
        if (
            null === $this->io
            || (
                !$operation instanceof InstallOperation
                && !$operation instanceof UpdateOperation
                && !$operation instanceof UninstallOperation
            )
        ) {
            return [null, []];
        }

        if ($operation instanceof UpdateOperation) {
            $package = $operation->getTargetPackage();
        } else {
            $package = $operation->getPackage();
        }

        $extra = $package->getExtra();

        if (empty($extra[static::class])) {
            return [null, []];
        }

        return [$package->getName(), $extra[static::class]];
    }

    /**
     * @param array<string, mixed> $extra
     * @return iterable<ActionInterface, array>
     * @throws \ReflectionException
     */
    private function browseAction(string $packageName, array &$extra): iterable
    {
        foreach ($extra as $actionClass => $arguments) {
            if (!\class_exists($actionClass)) {
                continue;
            }

            $reflectionClass = new \ReflectionClass($actionClass);
            if (!$reflectionClass->implementsInterface(ActionInterface::class)) {
                $this->getIo()->writeError("class $actionClass must implements the ActionInterface");

                continue;
            }

            $this->getIo()->write("Run for $packageName => $actionClass");
            yield $reflectionClass->newInstance() => $arguments;
        }
    }

    public function postInstall(PackageEvent $event): self
    {
        [$packageName, $extra] = $this->getExtra($event->getOperation());
        if (empty($extra) || !empty($this->config[static::CONFIG_DISABLED_KEY])) {
            return $this;
        }

        foreach ($this->browseAction($packageName, $extra) as $action => $arguments) {
            try {
                $action->install($packageName, $arguments, $event, $this->io);
            } catch (\Throwable $error) {
                $this->getIo()->writeError($error->getMessage());
                $this->getIo()->writeError($error->getFile() . ':' . $error->getLine());

                throw $error;
            }
        }

        return $this;
    }

    public function postUpdate(PackageEvent $event): self
    {
        [$packageName, $extra] = $this->getExtra($event->getOperation());
        if (empty($extra) || !empty($this->config[static::CONFIG_DISABLED_KEY])) {
            return $this;
        }

        foreach ($this->browseAction($packageName, $extra) as $action => $arguments) {
            try {
                $action->update($packageName, $arguments, $event, $this->io);
            } catch (\Throwable $error) {
                $this->getIo()->writeError($error->getMessage());
                $this->getIo()->writeError($error->getFile() . ':' . $error->getLine());

                throw $error;
            }
        }

        return $this;
    }

    public function postUninstall(PackageEvent $event): self
    {
        [$packageName, $extra] = $this->getExtra($event->getOperation());
        if (empty($extra) || !empty($this->config[static::CONFIG_DISABLED_KEY])) {
            return $this;
        }

        foreach ($this->browseAction($packageName, $extra) as $action => $arguments) {
            try {
                $action->uninstall($packageName, $arguments, $event, $this->io);
            } catch (\Throwable $error) {
                $this->getIo()->writeError($error->getMessage());
                $this->getIo()->writeError($error->getFile() . ':' . $error->getLine());

                throw $error;
            }
        }

        return $this;
    }

    public function activate(Composer $composer, IOInterface $io): self
    {
        self::$activated = true;
        $this->io = $io;

        $rootPackage = $composer->getPackage();
        if ($rootPackage instanceof RootPackageInterface) {
            $extra = $rootPackage->getExtra();

            if (!empty($extra[static::class][static::CONFIG_KEY])) {
                $this->config = $extra[static::class][static::CONFIG_KEY];
            }
        }

        $io->write('Teknoo Composer Installer activated');

        return $this;
    }

    public function deactivate(Composer $composer, IOInterface $io): self
    {
        self::$activated = false;
        $this->io = null;
        $this->config = [];

        $io->write('Teknoo Composer Installer deactivated');

        return $this;
    }

    public function uninstall(Composer $composer, IOInterface $io): self
    {
        $io->write('Teknoo Composer Installer uninstalled');

        return $this;
    }
}
