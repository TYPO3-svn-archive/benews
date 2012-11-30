<?php
/***************************************************************
*  Copyright notice
*
*  (c) Kay Strobach
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * based on Login-screen of TYPO3.
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author  Kay Strobach <typo3@kay-strobach.de 
 */
class benews_Backend_Render{
	function init() {
		$this->moduleTemplate = $GLOBALS['TBE_TEMPLATE']->getHtmlTemplate(PATH_typo3.'templates/login.html');
	}
	function main() {
		$this->init();
		$buffer = t3lib_div::getURL(t3lib_extMgm::extPath('benews').'Templates/Backend/main.js');
		$this->conf  = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['benews']);
		$news = $this->makeLoginNews();
		if($news!='') {
			$newsContent = base64_encode($news);
			$buffer = str_replace('###NEWSTITLE###'  , $this->conf['WindowTitle'],          $buffer);
			$buffer = str_replace('###NEWSDISABLE###', $this->conf['HideMessageButtonText'],$buffer);
			$buffer = str_replace('###NEWS###'       , $newsContent,                        $buffer);
			$GLOBALS['TYPO3backend']->addJavascript($buffer);
			$GLOBALS['TYPO3backend']->addCssFile('benews_main',t3lib_extMgm::extRelPath('benews').'Templates/Backend/main.css');
		}
	}
	function makeLoginNews() {
		$newsContent = '';

		$systemNews = $this->getSystemNews();
		if (count($GLOBALS['TYPO3_CONF_VARS']['BE']['loginNews'])) {
			t3lib_div::logDeprecatedFunction();

			$GLOBALS['TYPO3_CONF_VARS']['BE']['loginNews'] = array_merge(
				$systemNews,
				$GLOBALS['TYPO3_CONF_VARS']['BE']['loginNews']
			);
		} else {
			$GLOBALS['TYPO3_CONF_VARS']['BE']['loginNews'] = $systemNews;
		}
		
		if($this->conf['tt_news']) {
			$GLOBALS['TYPO3_CONF_VARS']['BE']['loginNews'] = array_merge(
				$this->getTtNews(),
				$GLOBALS['TYPO3_CONF_VARS']['BE']['loginNews']
			);
		}
			// Traverse news array IF there are records in it:
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['BE']['loginNews']) && count($GLOBALS['TYPO3_CONF_VARS']['BE']['loginNews']) && !t3lib_div::_GP('loginRefresh')) {

			$htmlParser = t3lib_div::makeInstance('t3lib_parsehtml_proc');
				// get the main news template, and replace the subpart after looped through
			$newsContent      = t3lib_parsehtml::getSubpart($this->moduleTemplate, '###LOGIN_NEWS###');
			$newsItemTemplate = t3lib_parsehtml::getSubpart($newsContent, '###NEWS_ITEM###');

			$newsItem = '';
			$count = 1;
			foreach ($GLOBALS['TYPO3_CONF_VARS']['BE']['loginNews'] as $newsItemData) {
				$additionalClass = '';
				if ($count == 1) {
					$additionalClass = ' first-item';
				} elseif($count == count($GLOBALS['TYPO3_CONF_VARS']['BE']['loginNews'])) {
					$additionalClass = ' last-item';
				}

				$newsItemContent = $htmlParser->TS_transform_rte($htmlParser->TS_links_rte($newsItemData['content']));
				$newsItemMarker = array(
					'###HEADER###'  => htmlspecialchars($newsItemData['header']),
					'###DATE###'    => htmlspecialchars($newsItemData['date']),
					'###CONTENT###' => $newsItemContent,
					'###CLASS###'   => $additionalClass
				);

				$count++;
				$newsItem .= t3lib_parsehtml::substituteMarkerArray($newsItemTemplate, $newsItemMarker);
			}

			$title = ($GLOBALS['TYPO3_CONF_VARS']['BE']['loginNewsTitle'] ? $GLOBALS['TYPO3_CONF_VARS']['BE']['loginNewsTitle'] : $GLOBALS['LANG']->getLL('newsheadline'));

			$newsContent = t3lib_parsehtml::substituteMarker($newsContent,  '###NEWS_HEADLINE###', htmlspecialchars($title));
			$newsContent = t3lib_parsehtml::substituteSubpart($newsContent, '###NEWS_ITEM###', $newsItem);
		}

		return $newsContent;
	}
	protected function getTtNews() {
		$systemNewsTable = 'tt_news';
		$systemNews      = array();

		$systemNewsRecords = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'title, bodytext, tstamp',
			$systemNewsTable,
		    	$GLOBALS['TYPO3_DB']->listQuery('pid',$this->conf['tt_news'],'tt_news') .
		    	'AND tstamp > '.(time()-intval($this->conf['max_news_age'])*60*60*24).
				t3lib_BEfunc::BEenableFields($systemNewsTable) .
				t3lib_BEfunc::deleteClause($systemNewsTable),
			'',
			'crdate DESC',
			'0,10'
		);

		foreach ($systemNewsRecords as $systemNewsRecord) {
			$systemNews[] = array(
				'date'    => date(
					$GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'],
					$systemNewsRecord['tstamp']
				),
				'header'  => $systemNewsRecord['title'],
				'content' => $systemNewsRecord['bodytext']
			);
		}

		return $systemNews;	
	}
	/**
	 * Gets news from sys_news and converts them into a format suitable for
	 * showing them at the login screen.
	 *
	 * @return	array	An array of login news.
	 */
	protected function getSystemNews() {
		$systemNewsTable = 'sys_news';
		$systemNews      = array();

		$systemNewsRecords = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'title, content, crdate',
			$systemNewsTable,
				$GLOBALS['TYPO3_DB']->listQuery('pid',$this->conf['tt_news'],'tt_news') .
				'AND crdate > '.(time()-intval($this->conf['max_news_age'])*60*60*24).
				t3lib_BEfunc::BEenableFields($systemNewsTable) .
				t3lib_BEfunc::deleteClause($systemNewsTable),
			'',
			'crdate DESC',
			'0,10'
		);

		foreach ($systemNewsRecords as $systemNewsRecord) {
			$systemNews[] = array(
				'date'    => date(
					$GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'],
					$systemNewsRecord['crdate']
				),
				'header'  => $systemNewsRecord['title'],
				'content' => $systemNewsRecord['content']
			);
		}

		return $systemNews;	
	}
}	
?>