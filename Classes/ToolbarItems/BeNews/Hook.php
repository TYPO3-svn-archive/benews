<?php
	include_once(t3lib_extMgm::extPath('benews').'Classes/ToolbarItems/BeNews/Item.php');
	$GLOBALS['TYPO3backend']->addToolbarItem('benews', 'Tx_Benews_ToolbarItems_BeNews_Item');
?>