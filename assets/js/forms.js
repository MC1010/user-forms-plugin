jQuery(document).ready(function($) {
	$(window).on('ajaxErrorMessage', function(event, message) {
		event.preventDefault();
		
		$('#login-error').html(message).removeClass('d-none');
	});
	
	$(window).on('ajaxInvalidField', function(event, fieldElement, fieldName, errorMsg, isFirst) {
	    $(fieldElement).closest('.form-group').addClass('has-error');
	    
	    //only put of the first error
	    if($('#login-error').hasClass('d-none')) {
		    $('#login-error').text(errorMsg).removeClass('d-none');
	    }
	});

	$(document).on('ajaxPromise', '[data-request]', function() {
	    $(this).closest('form').find('.form-group.has-error').removeClass('has-error');
		$('#login-error').addClass('d-none');
	});
});