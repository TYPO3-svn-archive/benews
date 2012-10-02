<?php

require_once(PATH_typo3 . 'interfaces/interface.backend_toolbaritem.php');

class Tx_Benews_ToolbarItems_BeNews_Item  implements backend_toolbarItem  {
	/**
	 * reference back to the backend object
	 *
	 * @var	TYPO3backend
	 */
	protected $backendReference;
	protected $EXTKEY = 'benews';
    /**
	 * constructor, loads the documents from the user control
	 *
	 * @param	TYPO3backend	TYPO3 backend object reference
	 */
	public function __construct(TYPO3backend &$backendReference = null) {
		$this->backendReference = $backendReference;
	}
	/**
	 * checks whether the user has access to this toolbar item
	 *
	 * @return  boolean  true if user has access, false if not
	 */
	public function checkAccess() {
		return $GLOBALS['BE_USER']->user['admin'];
	}
	/**
	 * renders the toolbar item and the initial menu
	 *
	 * @return	string		the toolbar item including the initial menu content as HTML
	 */
	public function render() {
		$this->addJavascriptToBackend();
		$this->addCssToBackend();
		$this->addLLToBackend();
		return $this->renderMenu();
	}
    /**
     * @return string
     */
	function renderMenu() {
        
		$settings = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['benews']);

		$path     = t3lib_div::getFileAbsFileName($settings['notificationSound'],true);
		$path     = str_replace(PATH_site,'../',$path);

		$ogg      = str_replace('.mp3','.ogg',$path);
		$mp3      = str_replace('.ogg','.mp3',$path);
        
        $buffer = '<a href="#" class="toolbar-item"><img src="'.t3lib_extMgm::extRelPath('benews').'Resources/Public/Images/internet-mail.png"    class="t3-icon" style="background-image:none;"></a>';
		$buffer.= '<div class="toolbar-item-menu" style="display: none;">';
		$buffer.= '<audio id="beNewsAudio">';
		$buffer.= '  <source src="'.$ogg.'" type="audio/ogg" />';
		$buffer.= '  <source src="'.$mp3.'" type="audio/mp3" />';
		#$buffer.= '  <source src="'.t3lib_extMgm::extRelPath('benews').'Resources/Public/Sounds/sound.mp3" type="audio/mpeg" />';
		$buffer.= '</audio>';
		$buffer.= '<div class="toolbar-item-menu-dynamic">';
        #$buffer.= $this->renderMenu();
        $buffer.= '</div>';
		$buffer.= '</div>';
		return $buffer;
	}

	/**
	 * returns additional attributes for the list item in the toolbar
	 *
	 * @return	string		list item HTML attibutes
	 */
	public function getAdditionalAttributes() {
		return ' id="tx-benews-menu"';
	}
	/**
	 * adds the neccessary javascript to the backend
	 *
	 * @return	void
	 */
	protected function addJavascriptToBackend() {
		$this->backendReference->addJavascriptFile(t3lib_extMgm::extRelPath($this->EXTKEY) . 'Resources/Public/JavaScripts/ToolbarItems/BeNews.js');
		/**
		 * @var $pageRenderer t3lib_PageRenderer
		 */
		$settings = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['benews']);
		/**
		 * @var $pageRenderer t3lib_PageRenderer
		 */
		$pageRenderer = t3lib_div::makeInstance('t3lib_PageRenderer');
		if(is_array($settings)) {
			$pageRenderer->addInlineSettingArray('Benews',$settings);
		} else {
			$settings = array(
				'HideMessageButtonText' => 'Hide Message for 7 days',
				'max_news_age' => 31,
				'tt_news_startingpoint' => 0,
				'tt_news_recursive' => 99,
				'tt_news_singlepid' => 0,
				'notificationSound' => 'EXT:benews/Resources/Public/Sounds/Electrical_Sweep-Sweeper-1760111493.mp3',
			);
			$pageRenderer->addInlineSettingArray('Benews',$settings);
		}
	}

	/**
	 * adds the neccessary CSS to the backend
	 *
	 * @return	void
	 */
	protected function addCssToBackend() {
		$this->backendReference->addCssFile('benews', t3lib_extMgm::extRelPath($this->EXTKEY) . 'Resources/Public/Stylesheets/ToolbarItems/BeNews.css');
	}

	function addLLToBackend() {
		// to be done @todo
	}

	//==========================================================================
	// AJAX
	//==========================================================================
	/**
	 * renders the menu so that it can be returned as response to an AJAX call
	 *
	 * @param	array		array of parameters from the AJAX interface, currently unused
	 * @param	TYPO3AJAX	object of type TYPO3AJAX
	 * @return	void
	 */
	public function renderAjax($params = array(), TYPO3AJAX &$ajaxObj = null) {
		$menuContent = $this->renderMenu();
		$ajaxObj->addContent('opendocsMenu', $menuContent);
	}
}