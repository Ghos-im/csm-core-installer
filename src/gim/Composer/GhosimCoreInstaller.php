<?php

/**
 * Ghosim Core Installer - A Composer to install Ghosim in a webroot subdirectory
 * Copyright (C) 2023    GIM
 */

namespace gim\Composer;

use Composer\Config;
use Composer\Installer\LibraryInstaller;
use Composer\Package\PackageInterface;

class GhosimCoreInstaller extends LibraryInstaller {

	const TYPE = 'ghosim-core';

	const MESSAGE_CONFLICT = 'Deux packages (%s et %s) ne peuvent pas partager le même répertoire!';
	const MESSAGE_SENSITIVE = 'Avertissement! %s est un répertoire installation Ghosim non valide (à partir de %s)!';

	private static $_installedPaths = array();

	private $sensitiveDirectories = array( '.' );

	/**
	 * {@inheritDoc}
	 */
	public function getInstallPath( PackageInterface $package ) {
		$installationDir = false;
		$prettyName      = $package->getPrettyName();
		if ( $this->composer->getPackage() ) {
			$topExtra = $this->composer->getPackage()->getExtra();
			if ( ! empty( $topExtra['ghosim-install-dir'] ) ) {
				$installationDir = $topExtra['ghosim-install-dir'];
				if ( is_array( $installationDir ) ) {
					$installationDir = empty( $installationDir[ $prettyName ] ) ? false : $installationDir[ $prettyName ];
				}
			}
		}
		$extra = $package->getExtra();
		if ( ! $installationDir && ! empty( $extra['ghosim-install-dir'] ) ) {
			$installationDir = $extra['ghosim-install-dir'];
		}
		if ( ! $installationDir ) {
			$installationDir = 'ghosim';
		}
		$vendorDir = $this->composer->getConfig()->get( 'vendor-dir', Config::RELATIVE_PATHS ) ?: 'vendor';
		if (
			in_array( $installationDir, $this->sensitiveDirectories ) ||
			( $installationDir === $vendorDir )
		) {
			throw new \InvalidArgumentException( $this->getSensitiveDirectoryMessage( $installationDir, $prettyName ) );
		}
		if (
			! empty( self::$_installedPaths[ $installationDir ] ) &&
			$prettyName !== self::$_installedPaths[ $installationDir ]
		) {
			$conflict_message = $this->getConflictMessage( $prettyName, self::$_installedPaths[ $installationDir ] );
			throw new \InvalidArgumentException( $conflict_message );
		}
		self::$_installedPaths[ $installationDir ] = $prettyName;

		return $installationDir;
	}

	/**
	 * {@inheritDoc}
	 */
	public function supports( $packageType ) {
		return self::TYPE === $packageType;
	}

	/**
	 * Get the exception message with conflicting packages
	 *
	 * @param string $attempted
	 * @param string $alreadyExists
	 *
	 * @return string
	 */
	private function getConflictMessage( $attempted, $alreadyExists ) {
		return sprintf( self::MESSAGE_CONFLICT, $attempted, $alreadyExists );
	}

	/**
	 * Get the exception message for attempted sensitive directories
	 *
	 * @param string $attempted
	 * @param string $packageName
	 *
	 * @return string
	 */
	private function getSensitiveDirectoryMessage( $attempted, $packageName ) {
		return sprintf( self::MESSAGE_SENSITIVE, $attempted, $packageName );
	}

}