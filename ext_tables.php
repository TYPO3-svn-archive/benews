<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

/*******************************************************************************
 * register handler for Ext.Direct
 */
    if (TYPO3_MODE == 'BE') {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ExtDirect']['TYPO3.Benews'] =
     	'EXT:benews/Classes/Controller/ExtDirectController.php:tx_Benews_Controller_ExtDirectController';
    }

/**
 * Toolbar item
 */ 
	if (TYPO3_MODE == 'BE') {
		$GLOBALS['TYPO3_CONF_VARS']['typo3/backend.php']['additionalBackendItems'][]
			= t3lib_extMgm::extPath($_EXTKEY).'Classes/ToolbarItems/BeNews/Hook.php';
	}
?>
