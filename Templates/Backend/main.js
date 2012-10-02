Ext.onReady(function() {
	if(!Ext.util.Cookies.get('doNotShowNewsAgain')) {
		content = '###NEWS###';
		if(atob) {
			decrContent = atob(content);
		} else {
			decrContent = 'Please take a look on the news. Your Browser doesn`t allow me to show you the news ...';
		}
		win = new Ext.Window({
			closeAction      : 'destroy',
			modal            : true,
			title            : '###NEWSTITLE###',
			html             : decrContent,
			preventBodyReset : true,
			width            : 800,
			height           : 500,
			autoScroll       : true,
			ctCls            : 'benewsWindow',
			buttons:[
				{
					text     : 'Meldungen 7 Tage nicht anzeigen',
					handler  : function(btn,e){
						date = new Date();
						date.setTime(date.getTime()+1000*60*60*24*7);
						Ext.util.Cookies.set('doNotShowNewsAgain',1,date);
						btn.findParentByType('window').close();
					}
				},{
					text     : Ext.MessageBox.buttonText.ok,
					handler  : function(btn,e){
						btn.findParentByType('window').close();
					}
				}
			]
		});
		win.show();
		new Ext.util.DelayedTask(function (e){
			Ext.select('#loginNews a').set({
				target : '_blank'
			});
		}).delay(100);
	}
});