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


namespace Teknoo\Tests\Composer;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\OperationInterface;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Installer\PackageEvent;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use PHPUnit\Framework\TestCase;
use Teknoo\Composer\Action\ActionInterface;
use Teknoo\Composer\Installer;

/**
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/recipe Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 *
 * @covers \Teknoo\Composer\Installer
 */
class InstallerTest extends TestCase
{
    public function buildInstaller(): Installer
    {
        return new Installer();
    }

    public function testGetSubscribedEventsNotActived()
    {
        self::assertInstanceOf(
            Installer::class,
            $this->buildInstaller()->deactivate(
                $this->createMock(Composer::class),
                $this->createMock(IOInterface::class)
            )
        );

        self::assertIsArray(
            Installer::getSubscribedEvents()
        );

        self::assertEmpty(
            Installer::getSubscribedEvents()
        );
    }

    public function testGetSubscribedEventsActived()
    {
        self::assertInstanceOf(
            Installer::class,
            $this->buildInstaller()->activate(
                $this->createMock(Composer::class),
                $this->createMock(IOInterface::class)
            )
        );

        self::assertIsArray(
            Installer::getSubscribedEvents()
        );

        self::assertNotEmpty(
            Installer::getSubscribedEvents()
        );
    }

    public function testPostInstallBadArgument()
    {
        $this->expectException(\TypeError::class);

        $this->buildInstaller()->postInstall(new \stdClass());
    }

    public function testPostInstallWithoutOperation()
    {
        $installer = $this->buildInstaller();

        self::assertInstanceOf(
            Installer::class,
            $installer->activate(
                $this->createMock(Composer::class),
                $this->createMock(IOInterface::class)
            )
        );

        $event = $this->createMock(PackageEvent::class);
        $event->expects(self::any())
            ->method('getOperation')
            ->willReturn($this->createMock(OperationInterface::class));

        self::assertInstanceOf(
            Installer::class,
            $installer->postInstall($event)
        );
    }

    public function testPostInstallWithoutExtra()
    {
        $installer = $this->buildInstaller();

        self::assertInstanceOf(
            Installer::class,
            $installer->activate(
                $this->createMock(Composer::class),
                $this->createMock(IOInterface::class)
            )
        );

        $package = $this->createMock(PackageInterface::class);
        $package->expects(self::any())
            ->method('getExtra')
            ->willReturn([]);

        $package->expects(self::any())
            ->method('getName')
            ->willReturn('foo/bar');

        $operation = $this->createMock(InstallOperation::class);
        $operation->expects(self::any())
            ->method('getPackage')
            ->willReturn($package);

        $event = $this->createMock(PackageEvent::class);
        $event->expects(self::any())
            ->method('getOperation')
            ->willReturn($operation);

        self::assertInstanceOf(
            Installer::class,
            $installer->postInstall($event)
        );
    }

    public function testPostInstallWithExtraNotValid()
    {
        $installer = $this->buildInstaller();

        $io = $this->createMock(IOInterface::class);
        $io->expects(self::once())
            ->method('writeError');

        self::assertInstanceOf(
            Installer::class,
            $installer->activate(
                $this->createMock(Composer::class),
                $io
            )
        );

        $package = $this->createMock(PackageInterface::class);
        $package->expects(self::any())
            ->method('getExtra')
            ->willReturn([
                Installer::class => [
                    'foo' => [],
                    \DateTime::class => []
                ]
            ]);

        $package->expects(self::any())
            ->method('getName')
            ->willReturn('foo/bar');

        $operation = $this->createMock(InstallOperation::class);
        $operation->expects(self::any())
            ->method('getPackage')
            ->willReturn($package);

        $event = $this->createMock(PackageEvent::class);
        $event->expects(self::any())
            ->method('getOperation')
            ->willReturn($operation);

        self::assertInstanceOf(
            Installer::class,
            $installer->postInstall($event)
        );
    }

    public function testPostInstallWithExtraValid()
    {
        $installer = $this->buildInstaller();

        $io = $this->createMock(IOInterface::class);
        $io->expects(self::never())
            ->method('writeError');

        self::assertInstanceOf(
            Installer::class,
            $installer->activate(
                $this->createMock(Composer::class),
                $io
            )
        );

        $mock = new class implements ActionInterface {
            public function install(
                string $packageName,
                array $arguments,
                PackageEvent $event,
                IOInterface $io
            ): ActionInterface {
                return $this;
            }

            public function update(
                string $packageName,
                array $arguments,
                PackageEvent $event,
                IOInterface $io
            ): ActionInterface {
                return $this;
            }

            public function uninstall(
                string $packageName,
                array $arguments,
                PackageEvent $event,
                IOInterface $io
            ): ActionInterface {
                return $this;
            }
        };

        $package = $this->createMock(PackageInterface::class);
        $package->expects(self::any())
            ->method('getExtra')
            ->willReturn([
                Installer::class => [
                    \get_class($mock) => []
                ]
            ]);

        $package->expects(self::any())
            ->method('getName')
            ->willReturn('foo/bar');

        $operation = $this->createMock(InstallOperation::class);
        $operation->expects(self::any())
            ->method('getPackage')
            ->willReturn($package);

        $event = $this->createMock(PackageEvent::class);
        $event->expects(self::any())
            ->method('getOperation')
            ->willReturn($operation);

        self::assertInstanceOf(
            Installer::class,
            $installer->postInstall($event)
        );
    }

    public function testPostInstallWithExtraValidButExceptionInAction()
    {
        $this->expectException(\Exception::class);

        $installer = $this->buildInstaller();

        $io = $this->createMock(IOInterface::class);
        $io->expects(self::atLeastOnce())
            ->method('writeError');

        self::assertInstanceOf(
            Installer::class,
            $installer->activate(
                $this->createMock(Composer::class),
                $io
            )
        );

        $mock = new class implements ActionInterface {
            public function install(
                string $packageName,
                array $arguments,
                PackageEvent $event,
                IOInterface $io
            ): ActionInterface {
                throw new \Exception('foo');

                return $this;
            }

            public function update(
                string $packageName,
                array $arguments,
                PackageEvent $event,
                IOInterface $io
            ): ActionInterface {
                return $this;
            }

            public function uninstall(
                string $packageName,
                array $arguments,
                PackageEvent $event,
                IOInterface $io
            ): ActionInterface {
                return $this;
            }
        };

        $package = $this->createMock(PackageInterface::class);
        $package->expects(self::any())
            ->method('getExtra')
            ->willReturn([
                Installer::class => [
                    \get_class($mock) => []
                ]
            ]);

        $package->expects(self::any())
            ->method('getName')
            ->willReturn('foo/bar');

        $operation = $this->createMock(InstallOperation::class);
        $operation->expects(self::any())
            ->method('getPackage')
            ->willReturn($package);

        $event = $this->createMock(PackageEvent::class);
        $event->expects(self::any())
            ->method('getOperation')
            ->willReturn($operation);

        $installer->postInstall($event);
    }

    public function testPostUpdateBadArgument()
    {
        $this->expectException(\TypeError::class);

        $this->buildInstaller()->postUpdate(new \stdClass());
    }

    public function testPostUpdate()
    {
        $event = $this->createMock(PackageEvent::class);
        $event->expects(self::any())
            ->method('getOperation')
            ->willReturn($this->createMock(OperationInterface::class));

        self::assertInstanceOf(
            Installer::class,
            $this->buildInstaller()->postUpdate($event)
        );
    }

    public function testPostUpdateWithoutExtra()
    {
        $installer = $this->buildInstaller();

        self::assertInstanceOf(
            Installer::class,
            $installer->activate(
                $this->createMock(Composer::class),
                $this->createMock(IOInterface::class)
            )
        );

        $package = $this->createMock(PackageInterface::class);
        $package->expects(self::any())
            ->method('getExtra')
            ->willReturn([]);

        $package->expects(self::any())
            ->method('getName')
            ->willReturn('foo/bar');

        $operation = $this->createMock(UpdateOperation::class);
        $operation->expects(self::any())
            ->method('getTargetPackage')
            ->willReturn($package);

        $event = $this->createMock(PackageEvent::class);
        $event->expects(self::any())
            ->method('getOperation')
            ->willReturn($operation);

        self::assertInstanceOf(
            Installer::class,
            $installer->postUpdate($event)
        );
    }

    public function testPostUpdateWithExtraNotValid()
    {
        $installer = $this->buildInstaller();

        $io = $this->createMock(IOInterface::class);
        $io->expects(self::once())
            ->method('writeError');

        self::assertInstanceOf(
            Installer::class,
            $installer->activate(
                $this->createMock(Composer::class),
                $io
            )
        );

        $package = $this->createMock(PackageInterface::class);
        $package->expects(self::any())
            ->method('getExtra')
            ->willReturn([
                Installer::class => [
                    'foo' => [],
                    \DateTime::class => []
                ]
            ]);

        $package->expects(self::any())
            ->method('getName')
            ->willReturn('foo/bar');

        $operation = $this->createMock(UpdateOperation::class);
        $operation->expects(self::any())
            ->method('getTargetPackage')
            ->willReturn($package);

        $event = $this->createMock(PackageEvent::class);
        $event->expects(self::any())
            ->method('getOperation')
            ->willReturn($operation);

        self::assertInstanceOf(
            Installer::class,
            $installer->postUpdate($event)
        );
    }

    public function testPostUpdateWithExtraValid()
    {
        $installer = $this->buildInstaller();

        $io = $this->createMock(IOInterface::class);
        $io->expects(self::never())
            ->method('writeError');

        self::assertInstanceOf(
            Installer::class,
            $installer->activate(
                $this->createMock(Composer::class),
                $io
            )
        );

        $mock = new class implements ActionInterface {
            public function install(
                string $packageName,
                array $arguments,
                PackageEvent $event,
                IOInterface $io
            ): ActionInterface {
                return $this;
            }

            public function update(
                string $packageName,
                array $arguments,
                PackageEvent $event,
                IOInterface $io
            ): ActionInterface {
                return $this;
            }

            public function uninstall(
                string $packageName,
                array $arguments,
                PackageEvent $event,
                IOInterface $io
            ): ActionInterface {
                return $this;
            }
        };

        $package = $this->createMock(PackageInterface::class);
        $package->expects(self::any())
            ->method('getExtra')
            ->willReturn([
                Installer::class => [
                    \get_class($mock) => []
                ]
            ]);

        $package->expects(self::any())
            ->method('getName')
            ->willReturn('foo/bar');

        $operation = $this->createMock(UpdateOperation::class);
        $operation->expects(self::any())
            ->method('getTargetPackage')
            ->willReturn($package);

        $event = $this->createMock(PackageEvent::class);
        $event->expects(self::any())
            ->method('getOperation')
            ->willReturn($operation);

        self::assertInstanceOf(
            Installer::class,
            $installer->postUpdate($event)
        );
    }

    public function testPostUpdateWithExtraValidButExceptionInAction()
    {
        $this->expectException(\Exception::class);

        $installer = $this->buildInstaller();

        $io = $this->createMock(IOInterface::class);
        $io->expects(self::atLeastOnce())
            ->method('writeError');

        self::assertInstanceOf(
            Installer::class,
            $installer->activate(
                $this->createMock(Composer::class),
                $io
            )
        );

        $mock = new class implements ActionInterface {
            public function install(
                string $packageName,
                array $arguments,
                PackageEvent $event,
                IOInterface $io
            ): ActionInterface {

                return $this;
            }

            public function update(
                string $packageName,
                array $arguments,
                PackageEvent $event,
                IOInterface $io
            ): ActionInterface {
                throw new \Exception('foo');

                return $this;
            }

            public function uninstall(
                string $packageName,
                array $arguments,
                PackageEvent $event,
                IOInterface $io
            ): ActionInterface {
                return $this;
            }
        };

        $package = $this->createMock(PackageInterface::class);
        $package->expects(self::any())
            ->method('getExtra')
            ->willReturn([
                Installer::class => [
                    \get_class($mock) => []
                ]
            ]);

        $package->expects(self::any())
            ->method('getName')
            ->willReturn('foo/bar');

        $operation = $this->createMock(UpdateOperation::class);
        $operation->expects(self::any())
            ->method('getTargetPackage')
            ->willReturn($package);

        $event = $this->createMock(PackageEvent::class);
        $event->expects(self::any())
            ->method('getOperation')
            ->willReturn($operation);

        $installer->postUpdate($event);
    }

    public function testPostUninstallBadArgument()
    {
        $this->expectException(\TypeError::class);

        $this->buildInstaller()->postUninstall(new \stdClass());
    }

    public function testPostUninstall()
    {

        $event = $this->createMock(PackageEvent::class);
        $event->expects(self::any())
            ->method('getOperation')
            ->willReturn($this->createMock(OperationInterface::class));

        self::assertInstanceOf(
            Installer::class,
            $this->buildInstaller()->postUninstall($event)
        );
    }

    public function testPostUninstallWithoutExtra()
    {
        $installer = $this->buildInstaller();

        self::assertInstanceOf(
            Installer::class,
            $installer->activate(
                $this->createMock(Composer::class),
                $this->createMock(IOInterface::class)
            )
        );

        $package = $this->createMock(PackageInterface::class);
        $package->expects(self::any())
            ->method('getExtra')
            ->willReturn([]);

        $package->expects(self::any())
            ->method('getName')
            ->willReturn('foo/bar');

        $operation = $this->createMock(UninstallOperation::class);
        $operation->expects(self::any())
            ->method('getPackage')
            ->willReturn($package);

        $event = $this->createMock(PackageEvent::class);
        $event->expects(self::any())
            ->method('getOperation')
            ->willReturn($operation);

        self::assertInstanceOf(
            Installer::class,
            $installer->postUninstall($event)
        );
    }

    public function testPostUninstallWithExtraNotValid()
    {
        $installer = $this->buildInstaller();

        $io = $this->createMock(IOInterface::class);
        $io->expects(self::once())
            ->method('writeError');

        self::assertInstanceOf(
            Installer::class,
            $installer->activate(
                $this->createMock(Composer::class),
                $io
            )
        );

        $package = $this->createMock(PackageInterface::class);
        $package->expects(self::any())
            ->method('getExtra')
            ->willReturn([
                Installer::class => [
                    'foo' => [],
                    \DateTime::class => []
                ]
            ]);

        $package->expects(self::any())
            ->method('getName')
            ->willReturn('foo/bar');

        $operation = $this->createMock(UninstallOperation::class);
        $operation->expects(self::any())
            ->method('getPackage')
            ->willReturn($package);

        $event = $this->createMock(PackageEvent::class);
        $event->expects(self::any())
            ->method('getOperation')
            ->willReturn($operation);

        self::assertInstanceOf(
            Installer::class,
            $installer->postUninstall($event)
        );
    }

    public function testPostUninstallWithExtraValid()
    {
        $installer = $this->buildInstaller();

        $io = $this->createMock(IOInterface::class);
        $io->expects(self::never())
            ->method('writeError');

        self::assertInstanceOf(
            Installer::class,
            $installer->activate(
                $this->createMock(Composer::class),
                $io
            )
        );

        $mock = new class implements ActionInterface {
            public function install(
                string $packageName,
                array $arguments,
                PackageEvent $event,
                IOInterface $io
            ): ActionInterface {
                return $this;
            }

            public function update(
                string $packageName,
                array $arguments,
                PackageEvent $event,
                IOInterface $io
            ): ActionInterface {
                return $this;
            }

            public function uninstall(
                string $packageName,
                array $arguments,
                PackageEvent $event,
                IOInterface $io
            ): ActionInterface {
                return $this;
            }
        };

        $package = $this->createMock(PackageInterface::class);
        $package->expects(self::any())
            ->method('getExtra')
            ->willReturn([
                Installer::class => [
                    \get_class($mock) => []
                ]
            ]);

        $package->expects(self::any())
            ->method('getName')
            ->willReturn('foo/bar');

        $operation = $this->createMock(UninstallOperation::class);
        $operation->expects(self::any())
            ->method('getPackage')
            ->willReturn($package);

        $event = $this->createMock(PackageEvent::class);
        $event->expects(self::any())
            ->method('getOperation')
            ->willReturn($operation);

        self::assertInstanceOf(
            Installer::class,
            $installer->postUninstall($event)
        );
    }

    public function testPostUninstallWithExtraValidButExceptionInAction()
    {
        $this->expectException(\Exception::class);

        $installer = $this->buildInstaller();

        $io = $this->createMock(IOInterface::class);
        $io->expects(self::atLeastOnce())
            ->method('writeError');

        self::assertInstanceOf(
            Installer::class,
            $installer->activate(
                $this->createMock(Composer::class),
                $io
            )
        );

        $mock = new class implements ActionInterface {
            public function install(
                string $packageName,
                array $arguments,
                PackageEvent $event,
                IOInterface $io
            ): ActionInterface {

                return $this;
            }

            public function update(
                string $packageName,
                array $arguments,
                PackageEvent $event,
                IOInterface $io
            ): ActionInterface {
                return $this;
            }

            public function uninstall(
                string $packageName,
                array $arguments,
                PackageEvent $event,
                IOInterface $io
            ): ActionInterface {
                throw new \Exception('foo');

                return $this;
            }
        };

        $package = $this->createMock(PackageInterface::class);
        $package->expects(self::any())
            ->method('getExtra')
            ->willReturn([
                Installer::class => [
                    \get_class($mock) => []
                ]
            ]);

        $package->expects(self::any())
            ->method('getName')
            ->willReturn('foo/bar');

        $operation = $this->createMock(UninstallOperation::class);
        $operation->expects(self::any())
            ->method('getPackage')
            ->willReturn($package);

        $event = $this->createMock(PackageEvent::class);
        $event->expects(self::any())
            ->method('getOperation')
            ->willReturn($operation);

        $installer->postUninstall($event);
    }

    public function testActivateBadComposer()
    {
        $this->expectException(\TypeError::class);

        $this->buildInstaller()->activate(new \stdClass(), $this->createMock(IOInterface::class));
    }

    public function testActivateBadIo()
    {
        $this->expectException(\TypeError::class);

        $this->buildInstaller()->activate($this->createMock(Composer::class), new \stdClass());
    }

    public function testActivate()
    {
        self::assertInstanceOf(
            Installer::class,
            $this->buildInstaller()->activate(
                $this->createMock(Composer::class),
                $this->createMock(IOInterface::class)
            )
        );
    }

    public function testDesactivateBadComposer()
    {
        $this->expectException(\TypeError::class);

        $this->buildInstaller()->deactivate(new \stdClass(), $this->createMock(IOInterface::class));
    }

    public function testDesactivateBadIo()
    {
        $this->expectException(\TypeError::class);

        $this->buildInstaller()->deactivate($this->createMock(Composer::class), new \stdClass());
    }

    public function testDesactivate()
    {
        self::assertInstanceOf(
            Installer::class,
            $this->buildInstaller()->deactivate(
                $this->createMock(Composer::class),
                $this->createMock(IOInterface::class)
            )
        );
    }

    public function testUninstallBadComposer()
    {
        $this->expectException(\TypeError::class);

        $this->buildInstaller()->uninstall(new \stdClass(), $this->createMock(IOInterface::class));
    }

    public function testUninstallBadIo()
    {
        $this->expectException(\TypeError::class);

        $this->buildInstaller()->uninstall($this->createMock(Composer::class), new \stdClass());
    }

    public function testUninstallWithoutOperation()
    {
        self::assertInstanceOf(
            Installer::class,
            $this->buildInstaller()->uninstall(
                $this->createMock(Composer::class),
                $this->createMock(IOInterface::class)
            )
        );
    }
}
