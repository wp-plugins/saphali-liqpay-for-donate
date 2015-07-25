(function() {
	
	tinymce.create('tinymce.plugins.saphali_liqpay', {
        init : function(ed, url) {
            ed.addButton('saphali_liqpay', {
                title : 'Добавить кнопку пожертвований',
                image : url+'/order_form.png',
                onclick : function() {
                     ed.selection.setContent('[saphali_liqpay amount="10" desc="Пожертвование"]');  
 
                }
            });
        },
        createControl : function(n, cm) {
            return null;
        },
    });
     tinymce.PluginManager.add('saphali_liqpay', tinymce.plugins.saphali_liqpay);
})();