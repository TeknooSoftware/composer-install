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
use Teknoo\Composer\Action\SymfonyRoutes;

/**
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/recipe Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 *
 * @covers \Teknoo\Composer\Action\SymfonyRoutes
 * @covers \Teknoo\Composer\Action\FilesAction
 * @covers \Teknoo\Composer\Action\SymfonyTrait
 */
class SymfonyRoutesTest extends TestCase
{
    private const CONFIG_PATH = __DIR__ . '/../fixtures/config';

    protected function setUp(): void
    {
        parent::setUp();

        \chdir(static::CONFIG_PATH);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        @\unlink(static::CONFIG_PATH . '/config/routes/string.yaml');
        @\unlink(static::CONFIG_PATH . '/config/routes/array.yaml');
        @\unlink(static::CONFIG_PATH . '/config/routes/base64.yaml');
        @\rmdir(static::CONFIG_PATH . '/config/routes');
    }

    public function buildAction(): SymfonyRoutes
    {
        return new SymfonyRoutes();
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

    public function testInstallWithoutPackage()
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

    public function testInstallWithUnsupportedContentType()
    {
        $this->expectException(\RuntimeException::class);

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

        $this->buildAction()->install(
            'foo',
            [
                "object.yaml" => new \stdClass(),
            ],
            $event,
            $this->createMock(IOInterface::class)
        );
    }

    public function testInstallWithUnsupportedContentDefinition()
    {
        $this->expectException(\RuntimeException::class);

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

        $this->buildAction()->install(
            'foo',
            [
                "aleat.yaml" => [
                    'nonSupported' => []
                ]
            ],
            $event,
            $this->createMock(IOInterface::class)
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
                [
                    "string.yaml" => 'foo/bar',
                    "array.yaml" => [
                        "# Read the documentation:",
                        "foo:",
                        "  bar:",
                        "    - 'hello'",
                    ],
                    "base64.yaml" => [
                        "base64" => \base64_encode("bar/foo")
                    ],
                ],
                $event,
                $this->createMock(IOInterface::class)
            )
        );

        self::assertEquals(
            'foo/bar',
            \file_get_contents(static::CONFIG_PATH . '/config/routes/string.yaml')
        );

        self::assertEquals(
<<<EOF
# Read the documentation:
foo:
  bar:
    - 'hello'
EOF,
            \file_get_contents(static::CONFIG_PATH . '/config/routes/array.yaml')
        );

        self::assertEquals(
            'bar/foo',
            \file_get_contents(static::CONFIG_PATH . '/config/routes/base64.yaml')
        );
    }

    public function testInstallOverwritteNotGranted()
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

        $io = $this->createMock(IOInterface::class);
        $io->expects(self::any())
            ->method('askConfirmation')
            ->willReturn(false);

        \mkdir(static::CONFIG_PATH . '/config/routes', 0777, true);
        @\touch(static::CONFIG_PATH . '/config/routes/string.yaml');

        self::assertInstanceOf(
            ActionInterface::class,
            $this->buildAction()->install(
                'foo',
                [
                    "string.yaml" => 'foo/bar',
                    "array.yaml" => [
                        "# Read the documentation:",
                        "foo:",
                        "  bar:",
                        "    - 'hello'",
                    ],
                    "base64.yaml" => [
                        "base64" => \base64_encode("bar/foo")
                    ],
                ],
                $event,
                $io
            )
        );

        self::assertEquals(
            '',
            \file_get_contents(static::CONFIG_PATH . '/config/routes/string.yaml')
        );

        self::assertEquals(
<<<EOF
# Read the documentation:
foo:
  bar:
    - 'hello'
EOF,
            \file_get_contents(static::CONFIG_PATH . '/config/routes/array.yaml')
        );

        self::assertEquals(
            'bar/foo',
            \file_get_contents(static::CONFIG_PATH . '/config/routes/base64.yaml')
        );
    }

    public function testInstallOverwritteGranted()
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

        $io = $this->createMock(IOInterface::class);
        $io->expects(self::any())
            ->method('askConfirmation')
            ->willReturn(true);

        \mkdir(static::CONFIG_PATH . '/config/routes', 0777, true);
        @\touch(static::CONFIG_PATH . '/config/routes/string.yaml');

        self::assertInstanceOf(
            ActionInterface::class,
            $this->buildAction()->install(
                'foo',
                [
                    "string.yaml" => 'foo/bar',
                    "array.yaml" => [
                        "# Read the documentation:",
                        "foo:",
                        "  bar:",
                        "    - 'hello'",
                    ],
                    "base64.yaml" => [
                        "base64" => \base64_encode("bar/foo")
                    ],
                ],
                $event,
                $io
            )
        );

        self::assertEquals(
            'foo/bar',
            \file_get_contents(static::CONFIG_PATH . '/config/routes/string.yaml')
        );

        self::assertEquals(
<<<EOF
# Read the documentation:
foo:
  bar:
    - 'hello'
EOF,
            \file_get_contents(static::CONFIG_PATH . '/config/routes/array.yaml')
        );

        self::assertEquals(
            'bar/foo',
            \file_get_contents(static::CONFIG_PATH . '/config/routes/base64.yaml')
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

    public function testUpdateWithoutPackage()
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
                [
                    "string.yaml" => 'foo/bar',
                    "array.yaml" => [
                        "# Read the documentation:",
                        "foo:",
                        "  bar:",
                        "    - 'hello'",
                    ],
                    "base64.yaml" => [
                        "base64" => \base64_encode("bar/foo")
                    ],
                ],
                $event,
                $this->createMock(IOInterface::class)
            )
        );

        self::assertEquals(
            'foo/bar',
            \file_get_contents(static::CONFIG_PATH . '/config/routes/string.yaml')
        );

        self::assertEquals(
            <<<EOF
# Read the documentation:
foo:
  bar:
    - 'hello'
EOF,
            \file_get_contents(static::CONFIG_PATH . '/config/routes/array.yaml')
        );

        self::assertEquals(
            'bar/foo',
            \file_get_contents(static::CONFIG_PATH . '/config/routes/base64.yaml')
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

    public function testUninstallWithoutPackage()
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

    public function testUninstallWithoutDeletionConfirmation()
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

        $io = $this->createMock(IOInterface::class);
        $io->expects(self::any())
            ->method('askConfirmation')
            ->willReturn(false);

        \mkdir(static::CONFIG_PATH . '/config/routes', 0777, true);
        @\touch(static::CONFIG_PATH . '/config/routes/string.yaml');
        @\touch(static::CONFIG_PATH . '/config/routes/array.yaml');
        @\touch(static::CONFIG_PATH . '/config/routes/base64.yaml');

        self::assertInstanceOf(
            ActionInterface::class,
            $this->buildAction()->uninstall(
                'foo',
                [
                    "string.yaml" => 'foo/bar',
                    "array.yaml" => [
                        "# Read the documentation:",
                        "foo:",
                        "  bar:",
                        "    - 'hello'",
                    ],
                    "base64.yaml" => [
                        "base64" => \base64_encode("bar/foo")
                    ],
                ],
                $event,
                $io
            )
        );

        self::assertTrue(
            \file_exists(static::CONFIG_PATH . '/config/routes/string.yaml')
        );

        self::assertTrue(
            \file_exists(static::CONFIG_PATH . '/config/routes/array.yaml')
        );

        self::assertTrue(
            \file_exists(static::CONFIG_PATH . '/config/routes/base64.yaml')
        );
    }

    public function testUninstallWithDeletionConfirmation()
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

        $io = $this->createMock(IOInterface::class);
        $io->expects(self::any())
            ->method('askConfirmation')
            ->willReturn(true);

        \mkdir(static::CONFIG_PATH . '/config/routes', 0777, true);
        @\touch(static::CONFIG_PATH . '/config/routes/string.yaml');
        @\touch(static::CONFIG_PATH . '/config/routes/array.yaml');
        @\touch(static::CONFIG_PATH . '/config/routes/base64.yaml');

        self::assertInstanceOf(
            ActionInterface::class,
            $this->buildAction()->uninstall(
                'foo',
                [
                    "string.yaml" => 'foo/bar',
                    "array.yaml" => [
                        "# Read the documentation:",
                        "foo:",
                        "  bar:",
                        "    - 'hello'",
                    ],
                    "base64.yaml" => [
                        "base64" => \base64_encode("bar/foo")
                    ],
                ],
                $event,
                $io
            )
        );

        self::assertFalse(
            \file_exists(static::CONFIG_PATH . '/config/routes/string.yaml')
        );

        self::assertFalse(
            \file_exists(static::CONFIG_PATH . '/config/routes/array.yaml')
        );

        self::assertFalse(
            \file_exists(static::CONFIG_PATH . '/config/routes/base64.yaml')
        );
    }
}
