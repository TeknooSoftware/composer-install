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
use Composer\Package\RootPackageInterface;
use Composer\Plugin\PluginInterface;
use ReflectionClass;
use ReflectionException;
use Teknoo\Composer\Action\ActionInterface;
use Throwable;

use function class_exists;

/**
 * Composer Plugin to perform some actions after install, update or delete, referenced under the key
 * Installer::PLUGIN_IDENTIFIER.
 * Each action must be defined via a class implementing `Teknoo\Composer\Action\ActionInterface`. This plugin, when
 * an action is configured, and available, by a package in the root composer.json or the composer.json of a package,
 * will create a new instance via the reflection and pass arguments and the current IO instance to dialog with user.
 * If an action is not available (class does not exist), the configuration is skipped.
 *
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
    public const PLUGIN_IDENTIFIER = 'Teknoo\\Composer\\Installer';
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
        if (!static::$activated) {
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
     * Get configuration defined in extra, for Install, Update or Uninstall operation, for the new package.
     * (If update, use configuration defined in the new package).
     *
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

        if (empty($extra[static::PLUGIN_IDENTIFIER])) {
            return [null, []];
        }

        return [$package->getName(), $extra[static::PLUGIN_IDENTIFIER]];
    }

    /*
     * Event about package's lifecycle
     */

    /**
     * Fetch all actions defined in the extra dedicated to this plugin. If an action is found (class exists) but it not
     * implements the interface ActionInterface, an error will be print and this action will be skipped.
     * If an action does not exist, its configuration is skipped
     * Else, a new instance of this action class will be instanciated.
     *
     * @param array<string, mixed> $extra
     * @return iterable<ActionInterface, array>
     * @throws ReflectionException
     */
    private function browseAction(string $packageName, array &$extra): iterable
    {
        foreach ($extra as $actionClass => $arguments) {
            if (!class_exists($actionClass)) {
                continue;
            }

            $reflectionClass = new ReflectionClass($actionClass);
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
            } catch (Throwable $error) {
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
            } catch (Throwable $error) {
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
            } catch (Throwable $error) {
                $this->getIo()->writeError($error->getMessage());
                $this->getIo()->writeError($error->getFile() . ':' . $error->getLine());

                throw $error;
            }
        }

        return $this;
    }

    /*
     * Events about plugin's lifecycle
     */
    private function loadConfiguration(RootPackageInterface $rootPackage): void
    {
        $extra = $rootPackage->getExtra();

        if (!empty($extra[static::PLUGIN_IDENTIFIER][static::CONFIG_KEY])) {
            $this->config = $extra[static::PLUGIN_IDENTIFIER][static::CONFIG_KEY];
        }
    }

    public function activate(Composer $composer, IOInterface $io): self
    {
        static::$activated = true;
        $this->io = $io;

        $rootPackage = $composer->getPackage();
        if ($rootPackage instanceof RootPackageInterface) {
            $this->loadConfiguration($rootPackage);
        }

        return $this;
    }

    public function deactivate(Composer $composer, IOInterface $io): self
    {
        static::$activated = false;
        $this->io = null;
        $this->config = [];

        return $this;
    }

    public function uninstall(Composer $composer, IOInterface $io): self
    {
        $io->write('Teknoo Composer Installer uninstalled');

        return $this;
    }
}
