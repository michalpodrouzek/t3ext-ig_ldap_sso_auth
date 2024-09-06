<?php

use TYPO3\CMS\Core\Information\Typo3Version;

defined('TYPO3') || die();

(static function (string $_EXTKEY) {
    // Register additional sprite icons
    /** @var \TYPO3\CMS\Core\Imaging\IconRegistry $iconRegistry */
    $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
    $iconRegistry->registerIcon('extensions-ig_ldap_sso_auth-overlay-ldap-record',
        \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
        [
            'source' => 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/overlay-ldap-record.png',
        ]
    );
    $iconRegistry->registerIcon('ldap-module-icon',
        \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
        [
            'source' => 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/module-ldap.png',
        ]
    );
    unset($iconRegistry);

    // Initialize "context sensitive help" (csh)
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_igldapssoauth_config', 'EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_csh_db.xlf');
})('ig_ldap_sso_auth');
