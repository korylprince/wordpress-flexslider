jQuery(document).ready(function(){
    wp.media.controller.FlexsliderImage = wp.media.controller.Library.extend({
        defaults: _.defaults({
            id:         'flexslider-image',
            filterable: 'uploaded',
            multiple:   false,
            toolbar:    'flexslider-image',
            title:      'Select Slider Image',
            priority:   60,  

            syncSelection: false
        }, wp.media.controller.Library.prototype.defaults ),

        initialize: function() {
            var library, comparator;

            // If we haven't been provided a `library`, create a `Selection`.
            if ( ! this.get('library') )
                this.set( 'library', wp.media.query({ type: 'image' }) );

            wp.media.controller.Library.prototype.initialize.apply( this, arguments );

            library    = this.get('library');
            comparator = library.comparator;

            // Overload the library's comparator to push items that are not in
            // the mirrored query to the front of the aggregate collection.
            library.comparator = function( a, b ) {
                var aInQuery = !! this.mirroring.getByCid( a.cid ),
                    bInQuery = !! this.mirroring.getByCid( b.cid );

                if ( ! aInQuery && bInQuery )
                    return -1;
                else if ( aInQuery && ! bInQuery )
                    return 1;
                else 
                    return comparator.apply( this, arguments );
            };   

        }, 

        activate: function() {
            this.updateSelection();
            this.frame.on( 'open', this.updateSelection, this );
            wp.media.controller.Library.prototype.activate.apply( this, arguments );
        },

        deactivate: function() {
            this.frame.off( 'open', this.updateSelection, this );
            wp.media.controller.Library.prototype.deactivate.apply( this, arguments );
        },

        updateSelection: function() {
            var selection = this.get('selection'),
                id = wp.media.view.settings.post.flexsliderImageId,
                attachment;
            if ( '' !== id && -1 !== id ) {
                attachment = wp.media.model.Attachment.get( id );
                attachment.fetch();
            }

            selection.reset( attachment ? [ attachment ] : [] );
        }
    });

    wp.media.flexsliderImage = { 
        get: function() {
            return wp.media.view.settings.post.flexsliderImageId;
        },  

        set: function( id ) { 
            var settings = wp.media.view.settings;

            settings.post.flexsliderImageId = id; 
            
            wp.media.post( 'set-flexslider-image', {
                json:         true,
                post_ID:      settings.post.id,
                post_type: jQuery("#flexslider_uploader").data('type'),
                _flexslider_image: settings.post.flexsliderImageId,
                flexslider_nonce: jQuery("#flexslider_nonce").val() 
            }).done( function( html ) {
                jQuery( '#flexslider_uploader' ).html( html );
            });
        },

        frame: function() {
            if ( this._frame )
                return this._frame;

            this._frame = wp.media({
                state: 'flexslider-image',
                states: [ new wp.media.controller.FlexsliderImage() ]
            }); 

            this._frame.on( 'toolbar:create:flexslider-image', function( toolbar ) { 
                this.createSelectToolbar( toolbar, {
                    text: 'Select Slider Image'
                }); 
            }, this._frame );

            this._frame.state('flexslider-image').on( 'select', this.select );
            return this._frame;
        },  

        select: function() {
            selection = this.get('selection').single();
            wp.media.flexsliderImage.set( selection ? selection.id : -1 );
        },
        init: function() {
            // Open the content media manager to the 'slider image' tab when
            // the post thumbnail is clicked.
            jQuery('#flexslider').on( 'click', '#flexslider_add, #flexslider_image', function( event ) {
                event.preventDefault();
                // Stop propagation to prevent thickbox from activating.
                event.stopPropagation();

                wp.media.flexsliderImage.frame().open();

            // Update the flexslider image id when the 'remove' link is clicked.
            }).on( 'click', '#flexslider_remove', function() {
                wp.media.flexsliderImage.set( -1 );
            });
        }
    };
    //propagate settings
    wp.media.view.settings.post.flexsliderImageId = jQuery("#flexslider_image").data("id");
    jQuery( wp.media.flexsliderImage.init );
});
