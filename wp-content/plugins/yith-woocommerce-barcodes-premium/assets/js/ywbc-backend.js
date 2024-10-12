jQuery(function ($) {
        $(document).on('click', '.ywbc-generate', function (e) {
            e.preventDefault();

            var id = $(this).data('id');
            var type = $(this).data('type');
            var container = $(this).closest('.ywbc-barcode-generation');
            var text_input = container.find('input[name="ywbc-value"]');

            var data = {
                'action': 'create_barcode',
                'type'  : type,
                'id'    : id,
                'value' : text_input.length ? text_input.val() : ''
            };

            container.block({
                message   : null,
                overlayCSS: {
                    background: "#fff url(" + ywbc_data.loader + ") no-repeat center",
                    opacity   : .6
                }
            });

            $.post(ywbc_data.ajax_url, data, function (response) {
                container.replaceWith(response);
                container.unblock();
            });
        });


      $(document).on('click', '.ywbc-delete-barcode', function (e) {
        e.preventDefault();

        var id = $(this).data('id');
        var container = $(this).closest('.ywbc-barcode-generation');
        var barcode_container = $(this).closest('.ywbc-barcode-generation #ywbc_barcode_value');


        var data = {
          'action': 'delete_barcode',
          'id'    : id,
        };

        container.block({
          message   : null,
          overlayCSS: {
            background: "#fff url(" + ywbc_data.loader + ") no-repeat center",
            opacity   : .6
          }
        });

        $.post(ywbc_data.ajax_url, data, function (response) {

          container.replaceWith(response);
          container.unblock();
        });
      });


      //Product URL option QR dependency


  var product_url_option = $( '#ywbc_product_barcode_type input#ywbc_product_barcode_type-product_url' ).parent();

  var qr_or_barcode_option = $( '#product_barcode_or_qr-wrapper li' );
  var qr_or_barcode_value_default = $( '#product_barcode_or_qr-wrapper li.yith-plugin-fw-select-images__item--selected' ).data( 'key' );

  if ( qr_or_barcode_value_default == 'qr_code'){
    product_url_option.show();
  }
  else{
    product_url_option.hide();
  }

  $(document).on('click', qr_or_barcode_option , function (e) {

    var qr_or_barcode_value_on_change= $( '#product_barcode_or_qr-wrapper li.yith-plugin-fw-select-images__item--selected' ).data( 'key' );

    if ( qr_or_barcode_value_on_change == 'qr_code'){
      product_url_option.show();
    }
    else{
      product_url_option.hide();
    }

  });



  });
