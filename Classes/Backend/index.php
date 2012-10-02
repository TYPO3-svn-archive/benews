<?php
	//include_once(t3lib_extMgm::extPath('dashboard').'toolbar/class.tx_dashboard_toolbaritem.php');
	//$GLOBALS['TYPO3backend']->addJavascriptFile('str');
	//$GLOBALS['TYPO3backend']->addToolbarItem('dashboard', 'tx_dashboard_toolbaritem');
	include_once(t3lib_extMgm::extPath('benews').'Classes/Backend/renderer.php');
	$renderer = new benews_Backend_Render();
	$renderer->main();
?>