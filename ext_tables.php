<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function()
    {

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            'AshokaTree.Mobile',
            'Data',
            'Customer Mobile request process'
        );

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('mobile', 'Configuration/TypoScript', 'Mobile');

    }
);
