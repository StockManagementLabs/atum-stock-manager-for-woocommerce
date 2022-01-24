/* =======================================
   DASHBOARD VIDEOS WIDGET
   ======================================= */

import NiceScroll from '../../_nice-scroll';

export default class VideosWidget {
	
	$videosWidget: JQuery;
	
	constructor(
		private $widgetsContainer: JQuery
	) {

		this.$videosWidget = $( '.videos-widget' );

		if ( this.$videosWidget.length ) {
			this.initVideosWidget();
		}
		
	}
	
	initVideosWidget() {
		
		// Video Switcher.
		this.$videosWidget.on( 'click', 'article a', ( evt: JQueryEventObject ) => {

			evt.preventDefault();

			const $videoItem: JQuery   = $( evt.currentTarget ).closest( 'article' ),
			      $videoPlayer: JQuery = this.$videosWidget.find( '.video-player' ),
			      videoId: string      = $videoItem.data( 'video' );

			$videoItem.siblings( '.active' ).removeClass( 'active' );
			$videoItem.addClass( 'active' );

			$videoPlayer.find( 'iframe' ).attr( 'src', `//www.youtube.com/embed/${ videoId }?rel=0&modestbranding=1` );
			$videoPlayer.find( '.video-title' ).text( $videoItem.find( '.video-title' ).text().trim() );
			$videoPlayer.find( '.video-meta' ).html( $videoItem.find( '.video-meta' ).html() );
			$videoPlayer.find( '.video-desc' ).text( $videoItem.find( '.video-desc' ).text().trim() );
			$videoItem.data( 'video', videoId );

		} );
		
		// Video Layout Switcher
		this.$videosWidget.find('.video-list-layout a').click( (evt: JQueryEventObject) => {

			evt.preventDefault();

			const $button = $( evt.currentTarget );

			if ( $button.hasClass( 'active' ) ) {
				return false;
			}

			this.$videosWidget.find( '.video-list' ).attr( 'data-view', $button.data( 'view' ) );

			NiceScroll.removeScrollBars( this.$videosWidget );

			setTimeout( () => NiceScroll.addScrollBars( this.$videosWidget ), 400 );

			$button.siblings( '.active' ).removeClass( 'active' );
			$button.addClass( 'active' );
			
		});

		// Filter Videos.
		this.$videosWidget.find( '.video-filter-by' ).change( ( evt: JQueryEventObject ) => {
			evt.stopPropagation(); // Avoid event bubbling to not trigger the layout saving.
			this.filterVideos();
		} );
		
		// Sort Videos.
		this.$videosWidget.find( '.video-sort-by' ).change( ( evt: JQueryEventObject ) => {

			evt.stopPropagation(); // Avoid event bubbling to not trigger the layout saving.

			const sortBy: string         = $( evt.currentTarget ).val(),
			      $videosWrapper: JQuery = this.$videosWidget.find( '.scroll-box' );

			$.ajax( {
				url       : window[ 'ajaxurl' ],
				method    : 'POST',
				data      : {
					action  : 'atum_videos_widget_sorting',
					security: this.$widgetsContainer.data( 'nonce' ),
					sortby  : sortBy,
				},
				beforeSend: () => $videosWrapper.addClass( 'overlay' ),
				success   : ( response: any ) => {

					if ( response != -1 ) {
						$videosWrapper.html( $( response ).find( '.scroll-box' ).html() );
						this.filterVideos();
					}

					$videosWrapper.removeClass( 'overlay' );

				},
				error     : () => $videosWrapper.removeClass( 'overlay' ),
			} );

		} );
		
	}
	
	filterVideos() {

		const $videos: JQuery  = this.$videosWidget.find( 'article' ),
		      filterBy: string = this.$videosWidget.find( '.video-filter-by' ).val();

		if ( filterBy === '' ) {
			$videos.fadeIn( 'fast' );
		}
		else {
			$videos.not( '.' + filterBy ).hide();
			$videos.filter( '.' + filterBy ).fadeIn( 'fast' );
		}
		
	}
	
}