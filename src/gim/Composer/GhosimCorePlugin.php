<?php

/**
 * Ghosim Core Installer - A Composer to install Ghosim in a webroot subdirectory
 * Copyright (C) 2023    GIM
 */

namespace gim\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class GhosimCorePlugin implements PluginInterface {

	/**
	 * Apply plugin modifications to composer
	 *
	 * @param Composer    $composer
	 * @param IOInterface $io
	 */
	public function activate( Composer $composer, IOInterface $io ) {
		$installer = new GhosimCoreInstaller( $io, $composer );
		$composer->getInstallationManager()->addInstaller( $installer );
	}

	/**
	 * {@inheritDoc}
	 */
	public function deactivate( Composer $composer, IOInterface $io ) {
	}

	/**
	 * {@inheritDoc}
	 */
	public function uninstall( Composer $composer, IOInterface $io ) {
	}

}