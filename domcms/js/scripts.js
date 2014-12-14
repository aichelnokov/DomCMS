jQuery.fn.exists = function() {
	return jQuery(this).length;
}

function abc(n) {
    n = new Array(4 - n.length % 3).join("U") + n;
	var str = n.replace(/([0-9U]{3})/g, "jQuery1 ").replace(/U/g, "");
	if (str.substr(str.length-1,1)==" ") {
		str = str.substr(0,str.length-1)
		if (str.substr(0,1)==" ") {
			return str.substr(1,str.length-1);
		} else {
			return str;
		}
	}
}

function cba(n) {
	return n.replace(/\s/g,"");
}

function url_param_replace(url,param,value) {
	pos = url.indexOf(param+'=');
	if (pos != -1) {
		end = url.indexOf('&',pos);
		url = url.substring(0,pos)+param+'='+value+(end!=-1?url.substring(end):'');
	} else {
		url = url+'&'+param+'='+value;
	}
	return url;
}

jQuery.fn.serializeUl = function(options) {
	var defaults = {
		key: 'id',
		attribute: 'id'
	};
	jQuery.extend(defaults,options);
	var s = '';
	jQuery(this).children("li").each(function(){
		s += '&'+defaults.key+'[]='+jQuery(this).attr(defaults.attribute);
	});
	return s.substring(1);
}

$(function(){
	$('.selectpicker').selectpicker({
		size: 8
	});
	$('.nav-tabs a').click(function (e) {
		e.preventDefault()
		$(this).tab('show')
	})
	$('.wysiwyg').wysiwyg({
		hotKeys: {
			'ctrl+b meta+b': 'bold',
			'ctrl+i meta+i': 'italic',
			'ctrl+u meta+u': 'underline',
			'ctrl+z meta+z': 'undo',
			'ctrl+y meta+y meta+shift+z': 'redo'
        }
	});
	$('#filter_accept').bind('click',function(){
		domcms.url.filter = '';
		$('#filters').find('select.filter,input.filter').each(function(){
			if (typeof($(this).val())==='string')
				if ($(this).val()!=0)
					domcms.url.filter += '&' + $(this).attr('id') + '=' + $(this).val();
		});
		document.location.href = domcms.url.module_mode + domcms.url.filter;
	});
	$('#filter_clear').bind('click',function(){
		$('#filters select.filter').each(function(){
			if ($(this).find('option[value=0]').length>0)
				$(this).find('option[value=0]').attr('selected','selected');
		});
		$('#filters input.filter').val('');
		$('#filter_accept').click();
	});
	
	$('.cat_children .cat_icon').bind('click',function(){
		$(this).parent().toggleClass('opened');
	});
	
	if($(".sortable:first").exists()) 
		$(".sortable:first").nestedSortable({
			handle: 'div',
			protectRoot: true,
			disableNesting: 'no-nesting',
			items: 'li',
			cursor:"move",
			tolerance:"pointer",
			toleranceElement: '> div.nested_li',
			placeholder: "ui-sortable-placeholder",
			//axis: "y",
			start: function(event,ui) {
				jQuery(".ui-sortable-placeholder").css({height:ui.item.innerHeight()+1});
			},
			stop: function(event,ui){
				var ar = ui.item.parent().serializeUl({key:'id'});
			},
			isAllowed: function(item,parent){
				return true;
			}
		}).disableSelection();
	
	/*$('[rel=tooltip]').bind("hover",function(){
		$(this).tooltip({
			animation: true,
			placement: 'bottom',
			selector: false,
			trigger: 'hover',
			delay:0
		});
	});*/
	
});