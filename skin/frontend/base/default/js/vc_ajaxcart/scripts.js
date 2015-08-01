
var TinyCart = {
	options:{
		qtyButtonPrefix: '',
		qtyInputPrefix: '',
		qtyLabel:'',
		removeButton:'',
		cartMsg: '',
		container: '',
		overlay: '',
		blockType:'mini',
		formKey:''
	},
	
	init:function() {
		jQuery('[id^=' + this.options.qtyInputPrefix.replace('#','') + ']').focus(function(){
			var id = jQuery(this).attr('data-item-id');
			jQuery(TinyCart.options.qtyButtonPrefix + id).show().attr('disabled', null);
			
		})
		.blur(function(){
		});
		
		jQuery(this.options.container).find('.close').click(function(){
			jQuery(TinyCart.options.container).hide();
		})
		
	},
	
	success: function(result) {
		if (AjaxCart.options.blockType == TinyCart.options.blockType) {
			TinyCart.hideOverlay();
		}
		
		if (result.code == 'success') {
			
			jQuery(TinyCart.options.qtyLabel).empty().html(result.cart_qty).show();
			jQuery(TinyCart.options.container).empty().append(result.mini_content);
			TinyCart.showMessage('vc_success',result.msg);
		} else {
			TinyCart.showMessage('vc_error',result.msg);
		}
	},
	
	error: function(result) {
	},
	
	showOverlay: function() {
		jQuery(TinyCart.options.overlay).addClass('vc_loading');
	},
	
	hideOverlay: function() {
		jQuery(TinyCart.options.overlay).removeClass('vc_loading');
	},
	
	showMessage: function(code, msg) {
		jQuery(TinyCart.options.cartMsg).removeClass('vc_success').removeClass('vc_error').addClass(code).html(msg).fadeIn('slow');
	},
	
	hideMessage: function() {
		jQuery(TinyCart.options.cartMsg).fideOut('slow');
	},
	
	
}
var CheckoutCart = {
	options:{
		qtyButtonPrefix: '',
		qtyInputPrefix: '',
		updateItemsButton: '',
		removeButton: '',
		emptyButton: '',
		
		container: '',
		overlay: '',
		blockType: 'checkout',
		formId: '',
		formKey: ''
	},
	
	init:function() {
		jQuery('[id^=' + this.options.qtyInputPrefix.replace('#','') + ']').unbind('focus').focus(function(){
			var id = jQuery(this).attr('data-item-id');
			jQuery(CheckoutCart.options.qtyButtonPrefix + id).show();
		})
		.blur(function(){
		});
	},
	
	success: function(result) {
		if (AjaxCart.options.blockType == CheckoutCart.options.blockType) {
			CheckoutCart.hideOverlay();
		}
		
		if (result.code == 'success') {
			jQuery(CheckoutCart.options.container).empty().append(result.checkout_content);
			AjaxCart.showMessage('success',result.msg);
		} else {
			AjaxCart.showMessage('error',result.msg);
		}
		
	},
	
	error: function(result) {
		CheckoutCart.hideOverlay();		
	},
	
	showOverlay: function() {
		if (AjaxCart.options.blockType == CheckoutCart.options.blockType) {
			jQuery(CheckoutCart.options.container).css({'position':'relative'});
			jQuery(CheckoutCart.options.overlay).show().addClass('vc_loading');
		} 
	},
	
	hideOverlay: function() {
		jQuery(CheckoutCart.options.overlay).hide().removeClass('vc_loading');
	},
	
}

var AjaxCart = {
	options:{
		blockType:'',
		cartMsg: '',
	},
	init: function() {
		_self = this;
		jQuery(function(){
			CheckoutCart.init();
			TinyCart.init();
			_self.initMessage();
			// Update Action
			_self.updateItem(function(result){
								 CheckoutCart.success(result);
								 TinyCart.success(result);
							},
							function(result){
								 CheckoutCart.error(result);
								 TinyCart.error(result);
							});
			// Delete Action
			_self.deleteItem(function(result){
								 CheckoutCart.success(result);
								 TinyCart.success(result);
							},
							function(result){
								 CheckoutCart.error(result);
								 TinyCart.error(result);
							});
			// Update Items Action
			_self.updateItems(function(result){
								 CheckoutCart.success(result);
								 TinyCart.success(result);
							},
							function(result){
								 CheckoutCart.error(result);
								 TinyCart.error(result);
							});
			// Empty All Action
			_self.emptyAllItems(function(result){
								 CheckoutCart.success(result);
								 TinyCart.success(result);
							},
							function(result){
								 CheckoutCart.error(result);
								 TinyCart.error(result);
							});
		});
	},
	
	
	
	updateItem: function(success, error) {
		//jQuery('[name="update_cart_action"]').click(function(){
		jQuery('[id^=' + CheckoutCart.options.qtyButtonPrefix.replace('#','') + '],[id^='+TinyCart.options.qtyButtonPrefix.replace('#','')+']').unbind('click').click(function(){
			var id = jQuery(this).attr('data-item-id');	
			var target = '#' + jQuery(this).attr('data-target');
			AjaxCart.options.blockType = jQuery(this).attr('data-block');
			//var input = jQuery(this.options.quantityInputPrefix + id).attr('data-link');
			CheckoutCart.showOverlay();
			TinyCart.showOverlay();
			
			jQuery.ajax({
				type: 'POST',
				dataType: 'json',
				url: jQuery(target).attr('data-link'),
				data: {qty: jQuery(target).val(), block_type: AjaxCart.options.blockType, form_key: AjaxCart.getFormKey()}
			}).done(function(result) {
				success(result);
				AjaxCart.showMessage(result.code, result.msg);
			}).error(function(result) {
				error(result);
				
			});			
		});
		
	},
	
	deleteItem: function(success, error) {
		var selector = '';
		if (CheckoutCart.options.removeButton.length > 0) {
			selector = CheckoutCart.options.removeButton;
		}
		
		if (TinyCart.options.removeButton.length > 0) {
			if (selector.length > 0) {
				selector += ',';
			}
			selector += TinyCart.options.removeButton;
		}
		jQuery(selector).unbind('click').click(function(event){
			event.preventDefault();
			if (confirm(jQuery(this).attr('data-confirm'))) {
				AjaxCart.options.blockType = jQuery(this).attr('data-block');
				CheckoutCart.showOverlay();
				TinyCart.showOverlay();
				
				jQuery.ajax({
					type: 'POST',
					dataType: 'json',
					url: jQuery(this).attr('href'),
					data: {block_type: AjaxCart.options.blockType, form_key: AjaxCart.getFormKey()}
				}).done(function(result) {
					success(result);
					AjaxCart.showMessage(result.code, result.msg);
				}).error(function() {
					error(result);
				});
				
			}
		});		
	},
	
	updateItems: function(success, error) {
		jQuery(CheckoutCart.options.updateItemsButton).unbind('click').click(function(event){
			event.preventDefault();
			AjaxCart.options.blockType = jQuery(this).attr('data-block');
			CheckoutCart.showOverlay();
			TinyCart.showOverlay();
			
			jQuery.ajax({
				type: 'POST',
				dataType: 'json',
				url: jQuery(this).attr('data-link'),
				data: jQuery(CheckoutCart.options.formId).serialize()+'&form_key='+AjaxCart.getFormKey()
			}).done(function(result) {
				success(result);
				AjaxCart.showMessage(result.code, result.msg);
			}).error(function() {
				error(result);
			});			
		});		
	},
	
	emptyAllItems: function(success, error) {
		
		jQuery(CheckoutCart.options.emptyButton).unbind('click').click(function(event){
																		
			event.preventDefault();
			if (confirm(jQuery(this).attr('data-confirm'))) {
				AjaxCart.options.blockType = jQuery(this).attr('data-block');
				CheckoutCart.showOverlay();
				TinyCart.showOverlay();
				
				jQuery.ajax({
					type: 'POST',
					dataType: 'json',
					url: jQuery(this).attr('data-link'),
					data: jQuery(CheckoutCart.options.formAction).serialize()+'&form_key='+AjaxCart.getFormKey()
				}).done(function(result) {
					success(result);
					AjaxCart.showMessage(result.code, result.msg);
				}).error(function() {
					error(result);
				});	
			}
		});		
		
	},
	
	getFormKey: function() {
		if (AjaxCart.options.blockType == CheckoutCart.options.blockType) {
			return CheckoutCart.options.formKey;
		} else {
			return TinyCart.options.formKey;
		}
	},
	
	initMessage: function() {
		jQuery('.messages li.success-msg,.messages li.error-msg').each(function(){
			var text = jQuery(this).find('span').text();
			if (text == 'disable') {
				jQuery(this).hide();
			}
		});
	},
	
	showMessage: function(code, msg) {
		this.hideMessage();
		if (msg.length > 0) {
			var span = jQuery('.messages li.'+code+'-msg').find('span');
			jQuery('.messages li.'+code+'-msg').show();
			jQuery(span).empty().html(msg);
		}
	},
	
	hideMessage: function() {
		jQuery('.messages li.success-msg,.messages li.error-msg').hide();
	},
}

jQuery(function() {
	// hide message			
	AjaxCart.initMessage();
	AjaxCart.init();
});