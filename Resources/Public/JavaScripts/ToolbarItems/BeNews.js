/***************************************************************
*  Copyright notice
*
*  (c) 2008-2010 Kay Strobach <typo3@kay-strobach.de>
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
 *
 */
var beNews = Class.create({
	ajaxScript: 'ajax.php',
	menu: null,
	toolbarItemIcon: null,

	/**
	 * registers for resize event listener and executes on DOM ready
	 */
	initialize: function() {
		Event.observe(window, 'resize', this.positionMenu);

		Ext.onReady(function() {
			this.positionMenu();
			this.toolbarItemIcon        = $$('#tx-benews-menu .toolbar-item img.t3-icon')[0];
			this.origToolbarItemIcon    = this.toolbarItemIcon.src;
			this.origToolbarItemIconAct = this.origToolbarItemIcon.replace(/internet-mail/g,'mail-message-new');
			this.ajaxScript             = top.TS.PATH_typo3 + this.ajaxScript; // can't be initialized earlier
			this.audioElem              =  $$('#beNewsAudio')[0];

			Event.observe($$('#tx-benews-menu .toolbar-item')[0], 'click', this.toggleMenu);
			this.menu     = $$('#tx-benews-menu .toolbar-item-menu-dynamic')[0];
            this.menuItem = $$('#tx-benews-menu')[0];

			this.loadingMask = new Ext.LoadMask(Ext.getBody(), {msg:"Please wait while loading..."});

            this.itemStore = new Ext.data.DirectStore(
                {
                    directFn : TYPO3.Benews.getNewsItems,
                    root     : 'rows',
                    fields   : [
                        'table',
	                    'iconCls',
	                    'uid',
	                    'tstamp',
	                    'title',
		                'new'
                    ],
	                sortInfo: {
						field: 'tstamp',
						direction: 'DESC' // or 'DESC' (case sensitive for local sorting)
					},
                    listeners:{
                        beforeload: {
	                        fn: function() {
		                        this.toolbarItemIcon.src = 'gfx/spinner.gif';
	                        },
                            scope:this
                        },
	                    load:{
                            fn: function(xhr) {
                                this.toolbarItemIcon.src = this.origToolbarItemIcon;
	                            this.checkNewItems();
                            },
                            scope:this
                        },
	                    update:{
		                    fn: function() {
			                    this.checkNewItems();
		                    },
		                    scope:this
	                    }
                    }

                }
            );
            this.list      = new Ext.grid.GridPanel(
                {
                    renderTo      : this.menu,
                    store         : this.itemStore,
                    emptyText     : 'No News',
                    loadingText   : 'Loading ...',
	                deferEmptyText: true,
                    width         : 360,
                    height        : 300,
	                singleSelect  : true,
	                hideHeaders   : true,
	                //autoExpandColumn : 1,
	                autoHeight    : false,
                    columns       : [
                        {
                            header    : '',
                            dataIndex : 'iconCls',
	                        width     : 20,
	                        renderer  : function(value) {
								return '<span class="'+value+'">&nbsp;</span>';
	                        }
                        },{
                            header    : 'News',
                            dataIndex : 'title',
	                        width     : 320,
		                    renderer  : function(value,cell,record) {
			                    date = Date.parseDate(record.get('tstamp'),'U').format('d.m.Y');
			                    value = date+' '+value;
			                    if(!record.get('new')) {
									return value;
								} else {
									return '<b>'+value+'</b>';
								}
	                        }
                        }
                    ],
	                tbar: [
						'<h2>News</h2>',
						'->',
		                {
							iconCls : 't3-icon t3-icon-actions t3-icon-actions-system t3-icon-system-refresh',
							listeners: {
								click: {
									scope : this,
									fn:function() {
										this.itemStore.reload();
									}
								}
							}
						}
	                ],
	                listeners:{
		                cellclick:{
			                fn:function(grid, rowIndex, columnIndex, e) {
								record = grid.getStore().getAt(rowIndex)
				                table = record.get('table');
								uid   = record.get('uid');
				                TYPO3.Benews.markNewsItemRead(table,uid);
								record.set('new',false);
								this.itemStore.commitChanges();
								switch(table) {
									case 'tt_news':
										window.open('../index.php?id='+TYPO3.settings.Benews.tt_news_singlepid+'&tx_ttnews[tt_news]='+uid);
									break;
									case 'sys_news':
										this.loadingMask.show();
										TYPO3.Benews.getNewsItem(table,uid,function(response) {
											win = new Ext.Window(
												{
													closeAction      : 'close',
													modal            : true,
													html             : '<h1>'+response.title+'</h1>'+response.content,
													title            : 'News: '+response.title,
													width            : 600,
													height           : 500,
													preventBodyReset : true
												}
											);
											win.show();
											TYPO3BackendBeNews.loadingMask.hide();
										});
									break;
									default:
										alert('unknown news table type ;) please contact the extension author');
									break;
								}
				                this.toggleMenu();
							},
							scope:this
						}
	                }
                }
            );

            Ext.TaskMgr.start({
                run: this.updateMenu,
                interval: 30*60*1000, //check all 30 Minutes ;) - property in milliseconds
                scope:this
            });

		}, this);
	},
	/**
	 * change the menu icon according to the state of the store
	 */
	checkNewItems: function() {
		if(this.itemStore.find('new',true)!=-1) {
			this.audioElem.play();
			this.toolbarItemIcon.src = this.origToolbarItemIconAct;
			TYPO3.Flashmessage.display(
				TYPO3.Severity.information,
				'New messages',
				'See mail icon for details and to select a message.',
				30
			);
		} else {
			this.toolbarItemIcon.src = this.origToolbarItemIcon;
		}
	},
	/**
	 * positions the menu below the toolbar icon, let's do some math!
	 */
	positionMenu: function() {
		var calculatedOffset = 0;
		var parentWidth      = $('tx-benews-menu').getWidth();
		var currentToolbarItemLayer = $$('#tx-benews-menu .toolbar-item-menu')[0];
		var ownWidth         = currentToolbarItemLayer.getWidth();
		var parentSiblings   = $('tx-benews-menu').previousSiblings();

		parentSiblings.each(function(toolbarItem) {
			calculatedOffset += toolbarItem.getWidth() - 1;
			// -1 to compensate for the margin-right -1px of the list items,
			// which itself is necessary for overlaying the separator with the active state background

			if(toolbarItem.down().hasClassName('no-separator')) {
				calculatedOffset -= 1;
			}
		});
		calculatedOffset = calculatedOffset - ownWidth + parentWidth;

			// border correction
		if (currentToolbarItemLayer.getStyle('display') !== 'none') {
			calculatedOffset += 2;
		}

		$$('#tx-benews-menu .toolbar-item-menu')[0].setStyle({
			left: calculatedOffset + 'px'
		});
	},

	/**
	 * toggles the visibility of the menu and places it under the toolbar icon
	 */
	toggleMenu: function(event) {
		var toolbarItem = $$('#tx-benews-menu > a')[0];
		var menu        = $$('#tx-benews-menu .toolbar-item-menu')[0];
		toolbarItem.blur();

		if(!toolbarItem.hasClassName('toolbar-item-active')) {
			toolbarItem.addClassName('toolbar-item-active');
			Effect.Appear(menu, {duration: 0.2});
			TYPO3BackendToolbarManager.hideOthers(toolbarItem);
		} else {
			toolbarItem.removeClassName('toolbar-item-active');
			Effect.Fade(menu, {duration: 0.1});
		}

		if(event) {
			Event.stop(event);
		}
	},

	/**
	 * displays the menu and does the AJAX call to the TYPO3 backend
	 */
	updateMenu: function() {
        this.itemStore.reload();
	},
	openSysNews:function(uid) {
		win = new Ext.Window({
            closeAction : 'close',
            title       : 'news',
            width       : 500,
            height      : 500,
            modal       : true,
            autoLoad    : null
        });
        win.show();
	}
});

var TYPO3BackendBeNews = new beNews();
