function trace(text,o){text+='<br />';o=o==1?{clear:1}:o?o:{};var d=document.getElementById('vik-trace')||(function(){var d=document.createElement("DIV");d.id="vik-trace";d.style.cssText='max-width:600px;font-size:12px;white-space:pre;z-index:1000;font-family:monospace;top:3px;right:0px;border:solid 5px #B7BEC4;background-color:#E7ECF0;padding:5px;';document.body.insertBefore(d,document.body.firstChild);var x=document.createElement("div");x.style.cssText="position:absolute;top:-8px;right:-4px;color:white;font-size:9px;cursor:pointer;";x.innerHTML='x';x.onclick=function(){d.style.display='none';c.innerHTML='';return false;};d.appendChild(x);c=document.createElement("div");d.appendChild(c);return d;})();d.style.display='block';var c=d.lastChild;if(o.clear)c.innerHTML=text;else c.innerHTML+=text;if(o.fix)d.style.position="fixed";else d.style.position="absolute";}
function print_r(trg,ret){var data='';for(i in trg){data+=i+' => '+trg[i]+'<br />';}if(ret){return data;}else{trace(data,1);}}
function captcha_reload(){$("#captcha").attr("src",'includes/captcha/captcha.php?rnd='+Math.round(Math.random(0)*1000));}
function var_dump(obj, outputType, opt, _lvl){

	if(typeof(outputType) == 'object' && outputType !== null){
		opt = outputType;
		outputType = 'trace';
	}
	
	outputType = (outputType === 1 || outputType === true) ? 'return' : outputType ? outputType : 'trace';
	
	opt = opt || {};
	// добавлять ли результат var_dump в прежний контейнер, или перезаписывать
	opt['append'] = typeof(opt['append']) != 'undefined' ? opt['append'] : false;
	// применять ли обработку типа htmlspecialchars
	opt['escape'] = typeof(opt['escape']) != 'undefined' ? opt['escape'] : true;
	// разворачивать ли некоторые объекты [dom,window,document,tags,jquery]
	opt['expand'] = opt['expand']?typeof(opt['expand'])=='string'?opt['expand'].split(','):opt['expand']:[];
	opt['max-depth'] = opt['max-depth'] || 5;
	
	for(var i = 0, l = opt['expand'].length; i < l; i++)
		opt['expand'][opt['expand'][i].toLowerCase()] = 1;
		
	_lvl = _lvl || 0;
	var output = '';

	var_dump.tab = var_dump.tab || function (_lvl){
		var str = '';
		for(var i = 0; i < _lvl; i++)
			str += '<span style="color: #DDD;">|</span>   ';
		return str;
	}
	var_dump.htmlspecialchars = var_dump.htmlspecialchars || function (str){
		str = str.replace(/</gi, '&lt;');
		str = str.replace(/>/gi, '&gt;');
		return str;
	}
	var_dump.detectSpecObj = var_dump.detectSpecObj || function (obj, expand){
			
			if(obj === null)
				return 'null';
			else if(obj === window)
				return expand.dom || expand.window
					? false
					: '[ DOM window ]';
			else if(obj === document)
				return expand.dom || expand.document
					? false
					: '[ DOM document ]';
			else if(obj.hasOwnProperty('nodeName') && obj.hasOwnProperty('innerHTML'))
				return expand.dom || expand.tags
					? false
					: obj.toString();
			else if(obj.jquery){
				return expand.jquery
					? false
					: 'jQuery(' + (obj.selector ? '"'+obj.selector+'"' : obj[0] ? obj[0].toString() : 'null') + ') [length: ' + obj.length + ']';
			}
				
			return false;
	}

	switch(typeof(obj)){
		case 'string':output = '"' + (opt['escape'] ? var_dump.htmlspecialchars(obj) : obj) + '"';break;
		case 'number':output = obj;break;
		case 'boolean':output = obj;break;
		case 'undefined':output = 'undefined';break;
		case 'object':
			var spec = var_dump.detectSpecObj(obj, opt['expand']);
			if(spec){
				output += spec;
			}else{
				if(_lvl >= opt['max-depth']){
					output += '[object] <span style="color: #B99;">*MAX DEPTH REACHED*</span>';
				}else{
					var _str = '';
					for(i in obj){
						_str += var_dump.tab(_lvl + 1) + i + ' => ';
						try{
							_str += (obj[i] === obj ? '<span style="color: #B99;">*RECURSION*</span>' : var_dump(obj[i], 'return', opt, (_lvl + 1)));
						}catch(e){_str += '<span style="color: #F55;">*ERROR [' + e + ']*</span>';}
						_str += '\n';
					}
					output += _str.length
						? '{\n' + _str + var_dump.tab(_lvl) + '}'
						: '{}';
				}
			}
			break;
		default:
			try{
				_str = var_dump.htmlspecialchars(obj.toString());
			}catch(e){_str += ' <span style="color: #F55;">*ERROR [' + e + ']*</span>';}
		output += '(' + typeof(obj) + ') ' + _str;
	}
	
	switch(outputType){
		case 'return':return output;break;
		case 'console':VikDebug.print(output, 'var_dump', (opt['append'] ? null : 1));break;
		default:trace(output, (opt['append'] ? null : 1));
	}
}
var VikDebug = {
	
	// включен ли VikDebug
	'isEnabled': true,
	
	settings: {
		// действие, выполняемое при вызове метода print
		// 'open' - открыть консоль
		// 'notify' - уведомить всплывающим сообщением
		// 'none' - ничего не делать
		'onPrintAction': 'notify',
		// отчищать ли предыдущее содержимое вкладки
		'clear': false,
		// расположение нового сообщения [top|bottom]
		'position': 'bottom',
		// активировать таб при вызове метода print
		'activateTab': true,
		// прокручивать ли вкладку до нового сообщения
		'scrollToNew': true
	},
	
	_isOpened: false,
	_html: null,
	_tabs: {},
	_activeTabName: '',
	_normalScreenHeight: 300,
	_isFullScreen: false,
	
	init: function(){
		
		if(!this.isEnabled)
			return;
		
		this._createHtml();
		this._getHtml('wrapper').height(this._normalScreenHeight);
		$(document).keydown(function(e){
			if(e.keyCode == 192 && e.ctrlKey)
				VikDebug.toggle();
		});
	},
	
	print: function(text, tabname, settings){
		
		if(!this.isEnabled)
			return;
		
		// замена третьего параметра на settings.clear = true
		if(settings === 1 || settings === true){
			settings = {};
			settings.clear = true;
		}
		
		// слияние настроек с дефолтными
		var s = {};
		for(var i in this.settings)
			s[i] = this.settings[i];
		for(var i in settings)
			s[i] = settings[i];
		
		tabname = tabname || 'default';
		
		// активация вкладки
		if(s.activateTab)
			this._activateTab(tabname);
		
		var tab = this._getTab(tabname);
		tab.msgIndex = s.clear ? 0 : tab.msgIndex + 1;
		var body = tab.body;
		var messageHtml = $('<div class="vik-debug-body-item"></div>')
			.append($('<div class="vik-debug-body-item-options"></div>')
				.append($(' <a href="#">close</a> ').click(function(){$(this).parent().parent().remove(); return false;}))
				.append(' <span>#' + tab.msgIndex + '</span> '))
			.append('<div>' + text + '</div>');
		
		// замер высоты вкладки
		var bodyHeight = body.height();
		
		// вставка сообщения во вкладку
		if(s.clear){
			body.html(messageHtml);
		}else{
			if(s.position == 'top')
				body.prepend(messageHtml);
			else
				body.append(messageHtml);
		}
		
		// открытие консоли, или показ нотифая
		if(!this._isOpened){
			
			switch(s.onPrintAction){
				case 'open':
					this.open();
					break;
				case 'notify':
					this.notify('new debug message in <b>' + tabname + '</b> tab.');
					break;
			}
		}else{
		
			// прокрутка до нового сообщения
			if(s.activateTab && s.scrollToNew && s.position == 'bottom'){
				VikDebug._getHtml('wrapper').scrollTop(bodyHeight);
			}
		}
		
	},
	
	open: function(callback){
		
		if(!VikDebug.isEnabled)
			return;
		
		VikDebug._isOpened = true;
		VikDebug._getHtml('notifier').slideUp();
		VikDebug._getHtml('box').slideDown('fast', callback);
	},
	
	close: function(){
		
		if(!VikDebug.isEnabled)
			return;
		
		VikDebug._isOpened = false;
		VikDebug._getHtml('box').slideUp();
	},
	
	toggle: function(){
		
		if(!VikDebug.isEnabled)
			return;
		
		if(VikDebug._isOpened)
			VikDebug.close();
		else
			VikDebug.open();
	},
	
	notify: function(text){
		
		if(!VikDebug.isEnabled)
			return;
		
		VikDebug._getHtml('notifier').append('<div>' + text + '</div>').slideDown();
	},
	
	fullScreenToggle: function(){
		
		if(!VikDebug.isEnabled)
			return;
		
		if(VikDebug._isFullScreen){
			VikDebug._getHtml('wrapper').height(VikDebug._normalScreenHeight);
			VikDebug._getHtml('iconFullScreen').html('full');
			VikDebug._isFullScreen = false;
		}else{
			VikDebug._getHtml('wrapper').height(VikDebug._getFullScreenHeight());
			VikDebug._getHtml('iconFullScreen').html('normal');
			VikDebug._isFullScreen = true;
		}
	},
	
	_getFullScreenHeight: function(){
	
		return $(window).height() - 33 - 24 - 2;
	},
	
	_getTab: function(name){
		
		if(!this._tabs[name]){
			this._tabs[name] = {
				body: $('<div class="vik-debug-body" style="display: none;"></div>')
					.appendTo(this._getHtml('wrapper')),
				button: $('<a href="#" class="vik-debug-tab">' + name + '</a>')
					.click(function(){VikDebug._activateTab(name); return false;})
					.appendTo(this._getHtml('tabBox')),
				msgIndex: 0
			}
		}
		return this._tabs[name];
	},
	
	_activateTab: function(tabname){
		
		// скрыть предыдущий таб
		if(this._activeTabName){
			var oldTab = this._getTab(this._activeTabName);
			oldTab.body.hide();
			oldTab.button.removeClass('active');
		}
		
		var newTab = this._getTab(tabname);
		newTab.body.show();
		newTab.button.addClass('active');
		this._activeTabName = tabname;
	},
	
	_getHtml: function(name){
		
		return this._html[name];
	},
	
	_createHtml: function(){
		
		this._html = {
			'box': $('<div id="vik-debug-box"></div>'),
			'notifier': $('<div id="vik-debug-notifier"></div>')
				.click(function(){$(this).slideUp().empty(); VikDebug.open();})
				.mouseleave(function(){var t = $(this); t.stop(true, true).delay(1000).slideUp(1000, function(){t.empty()});}),
			'title': $('<div id="vik-debug-title">Отладочная консоль</div>'),
			'preWrapper': $('<div id="vik-debug-pre-wrapper"></div>'),
			'wrapper': $('<div id="vik-debug-wrapper"></div>'),
			'tabBox': $('<div id="vik-debug-tab-box"></div>'),
			
			'iconClose': $('<a class="vik-debug-icon" href="#" style="right: 8px;">x</a>')
				.click(function(){VikDebug.close(); return false;}),
			'iconFullScreen': $('<a class="vik-debug-icon" href="#" style="right: 30px;">full</a>')
				.click(function(){VikDebug.fullScreenToggle(); return false;})
		};
		this._html.notifier.appendTo('body');
		this._html.box
			.append(this._html.iconClose)
			.append(this._html.iconFullScreen)
			.append(this._html.title)
			.append(this._html.preWrapper.append(this._html.wrapper))
			.append(this._html.tabBox)
			.appendTo('body');
		
	}
	
};

// набор стандартных опций для tinymce
function getDefaultTinyMceSettings(WWW_ROOT){
	
	return {
	
		script_url : WWW_ROOT + 'includes/tiny_mce/tiny_mce.js',
		
		language : 'ru',
		
		theme : "advanced",
		plugins : "pagebreak,emotions,inlinepopups,preview,media,contextmenu,paste,fullscreen,template,advlist",
		
		class_filter : function(cls, rule) {
			// trace(cls + ', ' + rule + '<br />');
			return cls;
		},
		
		theme_advanced_buttons1 : "bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,sub,sup,styleselect,formatselect,fontselect,fontsizeselect,|,undo,redo,|,preview,fullscreen",
		theme_advanced_buttons2 : "hr,removeformat,|,charmap,emotions,iespell,media,advhr,|,cut,copy,paste,pastetext,pasteword,|,bullist,numlist,|,outdent,indent,blockquote,|,link,unlink,image,cleanup,help,code,|,forecolor,backcolor,pagebreak",
		theme_advanced_buttons3 : "",
		theme_advanced_toolbar_location : "top",
		theme_advanced_toolbar_align : "left",
		theme_advanced_statusbar_location : "bottom",
		theme_advanced_resizing : true,

		content_css : WWW_ROOT + "css/backend.css"
	};
}

$(function(){
	
	VikDebug.init();
	
	$.ajaxSetup({
		error: function(xhr){VikDebug.print(xhr.responseText, 'ajax-error', {position: 'top'});}
	});
	
	$('.ctrlentersend').ctrlentersend();
	
	$('table.tr-highlight>tbody>tr').hover(
		function(){$(this).addClass("tr-hover")},
		function(){$(this).removeClass("tr-hover");}
	);					

});