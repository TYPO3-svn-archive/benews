<?php

class tx_Benews_Controller_ExtDirectController {
    /**
     * contains the extension key
     * @var string
     */
    protected $EXTKEY = 'benews';
    /**
     * @var contains the tables and some config
     */
    protected $tables = array(
        'tt_news' => array(
            'dateTimeField' => 'datetime',
	        'iconCls'       => 't3-icon t3-icon-tcarecords t3-icon-tcarecords-tt_news t3-icon-tt_news-default',
        ),
        'sys_news' => array(
            'dateTimeField' => 'tstamp',
	        'iconCls'       => 't3-icon t3-icon-mimetypes t3-icon-mimetypes-x t3-icon-x-sys_news',
        ),
    );
    /**
     * init $this->settings
     * @return void
     */
    private function initSettings() {
        $t = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['benews']);
        $this->settings['startingpoint']   = $t['tt_news_startingpoint'];
        $this->settings['recursive']       = $t['tt_news_recursive'];
        $this->settings['ttNewsSinglePid'] = $t['tt_news_singlepid'];
        $this->settings['maxNewsAge']      = $t['max_news_age'];
        $this->settings['pidList']         = $this->getPidList(
            $this->settings['startingpoint'],
            $this->settings['recursive']
        );
    }
    /**
     * @param  $table
     * @return string
     */
    private function getVisibleClause($table) {
        return '';
        $buffer = t3lib_BEfunc::deleteClause(
                $table
            );
        if(array_key_exists('dateTimeField',$this->tables[$table])) {
            $buffer.= ' AND '.$this->tables[$table]['dateTimeField'].' > '.(time()-($this->settings['maxNewsAge']*86400));
        }
        return $buffer;
    }
    /**
     * based on t3lib_piBase
     *
     * @todo recursion
     *
     * @param  $pid_list
     * @param  $recursive
     * @return string
     */
    private function getPidList($pid_list='0',$recursive) {
        return $pid_list;
        $recursive = t3lib_div::intInRange($recursive, 0);

		$pid_list_arr = array_unique(t3lib_div::trimExplode(',', $pid_list, 1));
		$pid_list     = array();

		foreach($pid_list_arr as $val) {
			$val = t3lib_div::intInRange($val, 0);
			if ($val) {
				$_list = $this->cObj->getTreeList(-1 * $val, $recursive);
				if ($_list) {
					$pid_list[] = $_list;
				}
			}
		}

		return implode(',', $pid_list);

    }
    /**
     * @return string json of news entries
     */
    public function getNewsItems() {
        $this->initSettings();
        $rows = array();
        $data = array(
	        'rows'   =>array(),
            'success'=>true
        );
        foreach($this->tables as $table=>$settings) {
            $delete = $this->getVisibleClause($table);
            $rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
                '*',       //fields
                $table,    //table
                'pid IN ('.$this->settings['pidList'].') '
					.$delete
					//tstamp?
            );
            foreach($rows as $key=>$row) {
                $data['rows'][]      = array(
	                'table'  => $table,
	                'iconCls'=>$this->tables[$table]['iconCls'],
	                'uid'    => $row['uid'],
	                'tstamp' => $row['tstamp'],
	                'title'  => $row['title'],
	                'new'    => !$this->isNewsItemRead($table,$row['uid']),
                );
            }
        }
        return $data;
    }
	public function isNewsItemRead($table,$uid) {
		$uid=intval($uid);
		if(array_key_exists($table,$this->tables)) {
			$r = t3lib_BEfunc::getRecordRaw(
				'tx_benews_readarticles',
				'news_table="'.$table.'" AND news_uid='.$uid.' AND be_user_uid='.$GLOBALS['BE_USER']->user['uid'],
				'*'
			);
			if(is_array($r)) {
				return true;
			}
		}
		return false;
	}
	public function markNewsItemRead($table,$uid) {
		$this->initSettings();
		$uid = intval($uid);
		if(array_key_exists($table,$this->tables)) {
			if(!$this->isNewsItemRead($table,$uid)) {
				$GLOBALS['TYPO3_DB']->exec_INSERTquery(
					'tx_benews_readarticles',
					array(
						'news_table'  => $table,
						'news_uid'    => $uid,
						'tstamp'      => time(),
						'be_user_uid' => $GLOBALS['BE_USER']->user['uid']
					)
				);
			}
		} else {
			//table unknown
		}
	}
    public function getNewsItem($table,$uid) {
		$this->initSettings();
		if(array_key_exists($table,$this->tables)) {
			$record = t3lib_BEfunc::getRecord($table,$uid);
			switch($table) {
				case 'sys_news':
					$htmlParser = t3lib_div::makeInstance('t3lib_parsehtml_proc');
					#$htmlParser->HTMLparserConfig();
					$record['content'] = $htmlParser->TS_transform_rte(
						$htmlParser->TS_links_rte(
							$record['content']
						)
					);
				break;
				default:
				break;
			}
			return $record;
		} else {
			//table unknown
		}
    }
}