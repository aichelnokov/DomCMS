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
});