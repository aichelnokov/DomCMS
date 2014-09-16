$(function(){
	$('.selectpicker').selectpicker({
		size: 8
	});
	$('.nav-tabs a').click(function (e) {
		e.preventDefault()
		$(this).tab('show')
	})
});