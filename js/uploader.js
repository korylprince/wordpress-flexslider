jQuery(document).ready(function($){
  var _custom_media = true,
      _orig_send_attachment = wp.media.editor.send.attachment;
    
  if ($("#flexslider_image").attr('src') != '') {
        $('#_flexslider_button').hide();
        $("#flexslider_remove").show();
  }

  $("#flexslider_remove").click(function(e) {
      $("#flexslider_image").attr('src','').hide();
      $(this).hide();
      $('#_flexslider_button').show();
  });

  $('#_flexslider_button').click(function(e) {
    var send_attachment_bkp = wp.media.editor.send.attachment;
    var button = $(this);
    _custom_media = true;
    wp.media.editor.send.attachment = function(props, attachment){
      if ( _custom_media ) {
        $("#_flexslider_image").val(attachment.id);
        $("#flexslider_image").attr('src',attachment.url).show();
        button.hide();
        $("#flexslider_remove").show();
      } else {
        return _orig_send_attachment.apply( this, [props, attachment] );
      };
    }

    wp.media.editor.open(button);
    return false;
  });

  $('.add_media').on('click', function(){
    _custom_media = false;
  });
});
