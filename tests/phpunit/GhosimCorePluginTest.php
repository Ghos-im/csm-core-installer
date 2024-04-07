<?php

/**
 * Ghosim Core Installer - A Composer to install Ghosim in a webroot subdirectory
 * Copyright (C) 2023    GIM
 */

namespace Tests\gim\Composer\phpunit;

use Composer\Composer;
use Composer\Config;
use Composer\Installer\InstallationManager;
use Composer\IO\IOInterface;
use Composer\IO\NullIO;
use Composer\Plugin\PluginInterface;
use Composer\Test\Mock\HttpDownloaderMock;
use Composer\Util\HttpDownloader;
use Composer\Util\Loop;
use gim\Composer\GhosimCorePlugin;
use PHPUnit\Framework\TestCase;

class GhosimCorePluginTest extends TestCase {

	public function testActivate() {
		$composer = new Composer();
		$composer->setConfig( new Config() );
		$nullIO              = new NullIO();
		$installationManager = $this->getInstallationManager( $composer, $nullIO );
		$composer->setInstallationManager( $installationManager );
		$composer->setConfig( new Config() );

		$plugin = new GhosimCorePlugin();
		$plugin->activate( $composer, $nullIO );

		$installer = $installationManager->getInstaller( 'ghosim-core' );

		$this->assertInstanceOf( '\gim\Composer\GhosimCoreInstaller', $installer );
	}

	/**
	 * @param Composer $composer
	 * @param IOInterface $io
	 *
	 * @return InstallationManager
	 */
	private function getInstallationManager( $composer, $io ) {
		$installationManager = null;
		switch ( explode( '.', PluginInterface::PLUGIN_API_VERSION )[0] ) {
			case '1':
				$installationManager = new InstallationManager();
				break;
			case '2':
			default:
				$http                = new HttpDownloader( $io, $composer->getConfig() );
				$loop                = new Loop( $http );
				$installationManager = new InstallationManager( $loop, $io );
				break;
		}

		return $installationManager;
	}

}