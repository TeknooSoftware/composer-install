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


namespace Teknoo\Tests\Composer\Action;

use Composer\Composer;
use Composer\Installer\PackageEvent;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use PHPUnit\Framework\TestCase;
use Teknoo\Composer\Action\ActionInterface;
use Teknoo\Composer\Action\SymfonyBundle;

/**
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/recipe Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 *
 * @covers \Teknoo\Composer\Action\SymfonyBundle
 * @covers \Teknoo\Composer\Action\SymfonyTrait
 */
class SymfonyBundleTest extends TestCase
{
    private const CONFIG_PATH = __DIR__ . '/../fixtures/bundles';

    protected function setUp(): void
    {
        parent::setUp();

        \chdir(static::CONFIG_PATH);

        \copy(
            static::CONFIG_PATH . '/config/bundles.php.template',
            static::CONFIG_PATH . '/config/bundles.php'
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        @\unlink(static::CONFIG_PATH . '/config/bundles.php');
        @\unlink(static::CONFIG_PATH . '/config2/bundles.php');
        @\rmdir(static::CONFIG_PATH . '/config2/');
    }

    public function buildAction(): SymfonyBundle
    {
        return new SymfonyBundle();
    }

    public function testInstallBadPackageName()
    {
        $this->expectException(\TypeError::class);

        $this->buildAction()->install(
            new \stdClass(),
            ['foo' => 'bar'],
            $this->createMock(PackageEvent::class),
            $this->createMock(IOInterface::class)
        );
    }

    public function testInstallBadArgument()
    {
        $this->expectException(\TypeError::class);

        $this->buildAction()->install(
            'foo',
            new \stdClass(),
            $this->createMock(PackageEvent::class),
            $this->createMock(IOInterface::class)
        );
    }

    public function testInstallBadEvent()
    {
        $this->expectException(\TypeError::class);

        $this->buildAction()->install(
            'foo',
            ['foo' => 'bar'],
            new \stdClass(),
            $this->createMock(IOInterface::class)
        );
    }

    public function testInstallBadIo()
    {
        $this->expectException(\TypeError::class);

        $this->buildAction()->install(
            'foo',
            ['foo' => 'bar'],
            $this->createMock(PackageEvent::class),
            new \stdClass()
        );
    }

    public function testInstallWithNoPackage()
    {
        $event = $this->createMock(PackageEvent::class);
        $event->expects(self::any())
            ->method('getComposer')
            ->willReturn($this->createMock(Composer::class));

        self::assertInstanceOf(
            ActionInterface::class,
            $this->buildAction()->install(
                'foo',
                ['foo' => 'bar'],
                $event,
                $this->createMock(IOInterface::class)
            )
        );
    }

    public function testInstall()
    {
        $package = $this->createMock(PackageInterface::class);
        $package->expects(self::any())
            ->method('getExtra')
            ->willReturn([]);
        
        $composer = $this->createMock(Composer::class);
        $composer->expects(self::any())
            ->method('getPackage')
            ->willReturn($package);

        $event = $this->createMock(PackageEvent::class);
        $event->expects(self::any())
            ->method('getComposer')
            ->willReturn($composer);

        self::assertInstanceOf(
            ActionInterface::class,
            $this->buildAction()->install(
                'foo',
                [\Hello\World::class => ['all' => true]],
                $event,
                $this->createMock(IOInterface::class)
            )
        );

        self::assertEquals(
            [
                \Hello\World::class => ['all' => true],
                \Foo\Bar::class => ['all' => true],
                \Bar\Foo::class => ['dev' => true]
            ],
            include(static::CONFIG_PATH . '/config/bundles.php')
        );
    }

    public function testInstallMissingConfigDir()
    {
        $package = $this->createMock(PackageInterface::class);
        $package->expects(self::any())
            ->method('getExtra')
            ->willReturn([
                'config-dir' => 'config2'
            ]);

        $composer = $this->createMock(Composer::class);
        $composer->expects(self::any())
            ->method('getPackage')
            ->willReturn($package);

        $event = $this->createMock(PackageEvent::class);
        $event->expects(self::any())
            ->method('getComposer')
            ->willReturn($composer);

        self::assertInstanceOf(
            ActionInterface::class,
            $this->buildAction()->install(
                'foo',
                [\Hello\World::class => ['all' => true]],
                $event,
                $this->createMock(IOInterface::class)
            )
        );

        self::assertEquals(
            [
                \Hello\World::class => ['all' => true],
            ],
            include((string) \realpath(static::CONFIG_PATH . '/config2/bundles.php'))
        );
    }

    public function testUpdateBadPackageName()
    {
        $this->expectException(\TypeError::class);

        $this->buildAction()->update(
            new \stdClass(),
            ['foo' => 'bar'],
            $this->createMock(PackageEvent::class),
            $this->createMock(IOInterface::class)
        );
    }

    public function testUpdateBadArgument()
    {
        $this->expectException(\TypeError::class);

        $this->buildAction()->update(
            'foo',
            new \stdClass(),
            $this->createMock(PackageEvent::class),
            $this->createMock(IOInterface::class)
        );
    }

    public function testUpdateBadEvent()
    {
        $this->expectException(\TypeError::class);

        $this->buildAction()->update(
            'foo',
            ['foo' => 'bar'],
            new \stdClass(),
            $this->createMock(IOInterface::class)
        );
    }

    public function testUpdateBadIo()
    {
        $this->expectException(\TypeError::class);

        $this->buildAction()->update(
            'foo',
            ['foo' => 'bar'],
            $this->createMock(PackageEvent::class),
            new \stdClass()
        );
    }

    public function testUpdateWithNoPackage()
    {
        $event = $this->createMock(PackageEvent::class);
        $event->expects(self::any())
            ->method('getComposer')
            ->willReturn($this->createMock(Composer::class));

        self::assertInstanceOf(
            ActionInterface::class,
            $this->buildAction()->update(
                'foo',
                ['foo' => 'bar'],
                $event,
                $this->createMock(IOInterface::class)
            )
        );
    }

    public function testUpdate()
    {
        $package = $this->createMock(PackageInterface::class);
        $package->expects(self::any())
            ->method('getExtra')
            ->willReturn([]);

        $composer = $this->createMock(Composer::class);
        $composer->expects(self::any())
            ->method('getPackage')
            ->willReturn($package);

        $event = $this->createMock(PackageEvent::class);
        $event->expects(self::any())
            ->method('getComposer')
            ->willReturn($composer);

        self::assertInstanceOf(
            ActionInterface::class,
            $this->buildAction()->update(
                'foo',
                [\Hello\World::class => ['all' => true]],
                $event,
                $this->createMock(IOInterface::class)
            )
        );

        self::assertEquals(
            [
                \Hello\World::class => ['all' => true],
                \Foo\Bar::class => ['all' => true],
                \Bar\Foo::class => ['dev' => true]
            ],
            include(static::CONFIG_PATH . '/config/bundles.php')
        );
    }

    public function testUninstallBadPackageName()
    {
        $this->expectException(\TypeError::class);

        $this->buildAction()->uninstall(
            new \stdClass(),
            ['foo' => 'bar'],
            $this->createMock(PackageEvent::class),
            $this->createMock(IOInterface::class)
        );
    }

    public function testUninstallBadArgument()
    {
        $this->expectException(\TypeError::class);

        $this->buildAction()->uninstall(
            'foo',
            new \stdClass(),
            $this->createMock(PackageEvent::class),
            $this->createMock(IOInterface::class)
        );
    }

    public function testUninstallBadEvent()
    {
        $this->expectException(\TypeError::class);

        $this->buildAction()->uninstall(
            'foo',
            ['foo' => 'bar'],
            new \stdClass(),
            $this->createMock(IOInterface::class)
        );
    }

    public function testUninstallBadIo()
    {
        $this->expectException(\TypeError::class);

        $this->buildAction()->uninstall(
            'foo',
            ['foo' => 'bar'],
            $this->createMock(PackageEvent::class),
            new \stdClass()
        );
    }

    public function testUninstallWithNoPackage()
    {
        $event = $this->createMock(PackageEvent::class);
        $event->expects(self::any())
            ->method('getComposer')
            ->willReturn($this->createMock(Composer::class));

        self::assertInstanceOf(
            ActionInterface::class,
            $this->buildAction()->uninstall(
                'foo',
                ['foo' => 'bar'],
                $event,
                $this->createMock(IOInterface::class)
            )
        );
    }

    public function testUninstallWithAlreadyRemoved()
    {
        $package = $this->createMock(PackageInterface::class);
        $package->expects(self::any())
            ->method('getExtra')
            ->willReturn([]);

        $composer = $this->createMock(Composer::class);
        $composer->expects(self::any())
            ->method('getPackage')
            ->willReturn($package);

        $event = $this->createMock(PackageEvent::class);
        $event->expects(self::any())
            ->method('getComposer')
            ->willReturn($composer);

        self::assertInstanceOf(
            ActionInterface::class,
            $this->buildAction()->uninstall(
                'foo',
                [\Hello\World::class => ['all' => true]],
                $event,
                $this->createMock(IOInterface::class)
            )
        );

        self::assertEquals(
            [
                \Foo\Bar::class => ['all' => true],
                \Bar\Foo::class => ['dev' => true]
            ],
            include(static::CONFIG_PATH . '/config/bundles.php')
        );
    }

    public function testUninstallConfirmDeletion()
    {
        $package = $this->createMock(PackageInterface::class);
        $package->expects(self::any())
            ->method('getExtra')
            ->willReturn([]);

        $composer = $this->createMock(Composer::class);
        $composer->expects(self::any())
            ->method('getPackage')
            ->willReturn($package);

        $event = $this->createMock(PackageEvent::class);
        $event->expects(self::any())
            ->method('getComposer')
            ->willReturn($composer);


        \file_put_contents(
            static::CONFIG_PATH . '/config/bundles.php',
            <<<'EOF'
<?php

return [
    Hello\World::class => ['all' => true],
    Foo\Bar::class => ['all' => true],
    Bar\Foo::class => ['dev' => true]
];
EOF
        );

        $io = $this->createMock(IOInterface::class);
        $io->expects(self::any())
            ->method('askConfirmation')
            ->willReturn(true);

        self::assertInstanceOf(
            ActionInterface::class,
            $this->buildAction()->uninstall(
                'foo',
                [\Hello\World::class => ['all' => true]],
                $event,
                $io
            )
        );

        self::assertEquals(
            [
                \Foo\Bar::class => ['all' => true],
                \Bar\Foo::class => ['dev' => true]
            ],
            @include(static::CONFIG_PATH . '/config/bundles.php')
        );
    }

    public function testUninstallRefuseDeletion()
    {
        $package = $this->createMock(PackageInterface::class);
        $package->expects(self::any())
            ->method('getExtra')
            ->willReturn([]);

        $composer = $this->createMock(Composer::class);
        $composer->expects(self::any())
            ->method('getPackage')
            ->willReturn($package);

        $event = $this->createMock(PackageEvent::class);
        $event->expects(self::any())
            ->method('getComposer')
            ->willReturn($composer);


        \file_put_contents(
            static::CONFIG_PATH . '/config/bundles.php',
            <<<'EOF'
<?php

return [
    Hello\World::class => ['all' => true],
    Foo\Bar::class => ['all' => true],
    Bar\Foo::class => ['dev' => true]
];
EOF
        );

        $io = $this->createMock(IOInterface::class);
        $io->expects(self::any())
            ->method('askConfirmation')
            ->willReturn(false);

        self::assertInstanceOf(
            ActionInterface::class,
            $this->buildAction()->uninstall(
                'foo',
                [\Hello\World::class => ['all' => true]],
                $event,
                $io
            )
        );

        self::assertEquals(
            [
                \Hello\World::class => ['all' => true],
                \Foo\Bar::class => ['all' => true],
                \Bar\Foo::class => ['dev' => true]
            ],
            @include(static::CONFIG_PATH . '/config/bundles.php')
        );
    }
}
