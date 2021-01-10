<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function()
    {

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'AshokaTree.Mobile',
            'Data',
            [
                'Data' => 'sendReceive'
            ],
            // non-cacheable actions
            [
                'Data' => 'sendReceive'
            ]
        );
        // wizards
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
            'mod {
                wizards.newContentElement.wizardItems.plugins {
                    elements {
                        data {
                            iconIdentifier = mobile-plugin-data
                            title = LLL:EXT:mobile/Resources/Private/Language/locallang_db.xlf:tx_mobile_data.name
                            description = LLL:EXT:mobile/Resources/Private/Language/locallang_db.xlf:tx_mobile_data.description
                            tt_content_defValues {
                                CType = list
                                list_type = mobile_data
                            }
                        }
                    }
                    show = *
                }
           }'
        );
		$iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
		
			$iconRegistry->registerIcon(
				'mobile-plugin-data',
				\TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
				['source' => 'EXT:mobile/Resources/Public/Icons/user_plugin_data.svg']
			);
    }
);
