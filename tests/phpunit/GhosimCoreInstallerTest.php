<?php

/**
 * Ghosim Core Installer - A Composer to install Ghosim in a webroot subdirectory
 * Copyright (C) 2023    GIM
 */

namespace Tests\gim\Composer\phpunit;

use Composer\Composer;
use Composer\Config;
use Composer\IO\NullIO;
use Composer\Package\Package;
use Composer\Package\RootPackage;
use gim\Composer\GhosimCoreInstaller;
use PHPUnit\Framework\TestCase;

class GhosimCoreInstallerTest extends TestCase {

	public function testSupports() {
		$installer = new GhosimCoreInstaller( new NullIO(), $this->createComposer() );

		$this->assertTrue( $installer->supports( 'ghosim-core' ) );
		$this->assertFalse( $installer->supports( 'not-ghosim-core' ) );
	}

	public function testDefaultInstallDir() {
		$installer = new GhosimCoreInstaller( new NullIO(), $this->createComposer() );
		$package   = new Package( 'gim/test-package', '1.0.0.0', '1.0.0' );

		$this->assertEquals( 'ghosim', $installer->getInstallPath( $package ) );
	}

	public function testSingleRootInstallDir() {
		$composer    = $this->createComposer();
		$rootPackage = new RootPackage( 'test/root-package', '1.0.1.0', '1.0.1' );
		$composer->setPackage( $rootPackage );
		$installDir = 'tmp-wp-' . rand( 0, 9 );
		$rootPackage->setExtra( array(
			'ghosim-install-dir' => $installDir,
		) );
		$installer = new GhosimCoreInstaller( new NullIO(), $composer );

		$this->assertEquals(
			$installDir,
			$installer->getInstallPath(
				new Package( 'not/important', '1.0.0.0', '1.0.0' )
			)
		);
	}

	public function testArrayOfInstallDirs() {
		$composer    = $this->createComposer();
		$rootPackage = new RootPackage( 'test/root-package', '1.0.1.0', '1.0.1' );
		$composer->setPackage( $rootPackage );
		$rootPackage->setExtra( array(
			'ghosim-install-dir' => array(
				'test/package-one' => 'install-dir/one',
				'test/package-two' => 'install-dir/two',
			),
		) );
		$installer = new GhosimCoreInstaller( new NullIO(), $composer );

		$this->assertEquals(
			'install-dir/one',
			$installer->getInstallPath(
				new Package( 'test/package-one', '1.0.0.0', '1.0.0' )
			)
		);

		$this->assertEquals(
			'install-dir/two',
			$installer->getInstallPath(
				new Package( 'test/package-two', '1.0.0.0', '1.0.0' )
			)
		);
	}

	public function testCorePackageCanDefineInstallDirectory() {
		$installer = new GhosimCoreInstaller( new NullIO(), $this->createComposer() );
		$package   = new Package( 'test/has-default-install-dir', '0.1.0.0', '0.1' );
		$package->setExtra( array(
			'ghosim-install-dir' => 'not-ghosim',
		) );

		$this->assertEquals( 'not-ghosim', $installer->getInstallPath( $package ) );
	}

	public function testCorePackageDefaultDoesNotOverrideRootDirectoryDefinition() {
		$composer = $this->createComposer();
		$composer->setPackage( new RootPackage( 'test/root-package', '0.1.0.0', '0.1' ) );
		$composer->getPackage()->setExtra( array(
			'ghosim-install-dir' => 'wp',
		) );
		$installer = new GhosimCoreInstaller( new NullIO(), $composer );
		$package   = new Package( 'test/has-default-install-dir', '0.1.0.0', '0.1' );
		$package->setExtra( array(
			'ghosim-install-dir' => 'not-ghosim',
		) );

		$this->assertEquals( 'wp', $installer->getInstallPath( $package ) );
	}

	public function testTwoPackagesCannotShareDirectory() {
		$this->jpbExpectException(
			'\InvalidArgumentException',
			'Two packages (test/bazbat and test/foobar) cannot share the same directory!'
		);
		$composer  = $this->createComposer();
		$installer = new GhosimCoreInstaller( new NullIO(), $composer );
		$package1  = new Package( 'test/foobar', '1.1.1.1', '1.1.1.1' );
		$package2  = new Package( 'test/bazbat', '1.1.1.1', '1.1.1.1' );

		$installer->getInstallPath( $package1 );
		$installer->getInstallPath( $package2 );
	}

	/**
	 * @dataProvider dataProviderSensitiveDirectories
	 */
	public function testSensitiveInstallDirectoriesNotAllowed( $directory ) {
		$this->jpbExpectException(
			'\InvalidArgumentException',
			'/Warning! .+? is an invalid Ghosim install directory \(from test\/package\)!/',
			true
		);
		$composer  = $this->createComposer();
		$installer = new GhosimCoreInstaller( new NullIO(), $composer );
		$package   = new Package( 'test/package', '1.1.0.0', '1.1' );
		$package->setExtra( array( 'ghosim-install-dir' => $directory ) );
		$installer->getInstallPath( $package );
	}

	public function dataProviderSensitiveDirectories() {
		return array(
			array( '.' ),
			array( 'vendor' ),
		);
	}

	/**
	 * @before
	 * @afterClass
	 */
	public static function resetInstallPaths() {
		$prop = new \ReflectionProperty( '\gim\Composer\GhosimCoreInstaller', '_installedPaths' );
		$prop->setAccessible( true );
		$prop->setValue( array() );
	}

	/**
	 * @return Composer
	 */
	private function createComposer() {
		$composer = new Composer();
		$composer->setConfig( new Config() );

		return $composer;
	}

	private function jpbExpectException( $class, $message = '', $isRegExp = false ) {
		$this->expectException($class);
		if ( $message ) {
			if ( $isRegExp ) {
				if ( method_exists( $this, 'expectExceptionMessageRegExp' ) ) {
					$this->expectExceptionMessageRegExp( $message );
				} else {
					$this->expectExceptionMessageMatches( $message );
				}
			} else {
				$this->expectExceptionMessage( $message );
			}
		}
	}

}