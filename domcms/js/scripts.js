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