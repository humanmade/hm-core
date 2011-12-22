<?php

class HM_Feature_Image_Meta_Box {
	
	public $show_view_large_link;
	public $link_image_to_gallery_tab;
	public $show_watermark_on_thumbnail;
	private $post;
	
	function __construct( $show_view_large_link = false, $link_image_to_gallery_tab = false, $show_watermark_on_thumbnail = false ) {
		
		$this->show_view_large_link = $show_view_large_link;
		$this->link_image_to_gallery_tab = $link_image_to_gallery_tab;
		$this->show_watermark_on_thumbnail = $show_watermark_on_thumbnail;
		
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX && isset( $_POST['action'] ) && $_POST['action'] == 'set-post-thumbnail' ) {
			$this->post = get_post( $_POST['post_id'] );
			$this->setup_hooks();
		}
		
	}
	
	public function display( $post ) {
		
		$this->post = $post;
		$this->setup_hooks();
		
		post_thumbnail_meta_box( $post );
	
	}
	
	function _admin_post_thumbnail_html( $html ) {
	
		$html .= '<span class="hm_view_large_link">' . $this->get_view_large_link() . '</span>';
		
		return $html;
	
	}
	
	function _set_featured_image_link_to_gallery_tab( $html ) {
				
		return str_replace( esc_url( get_upload_iframe_src( 'image' ) ), add_query_arg( 'tab', 'gallery', esc_url( get_upload_iframe_src( 'image' ) ) ), $html );
	
	}
	
	function _show_watermark_on_image_downsize( $return, $id, $size ) {
    
    	$options = wpthumb_wm_get_options( $id );
    	$options['pre_resize'] = true;
		
		if ( is_array( $size) && key( $size ) === 0 ) {
		
			$size = array( 'width' => $size[0], 'height' => $size[1] );
		
		} else {
			$size = wp_parse_args( $size );
		}
		
		$size['watermark_options'] = $options;

    	return array( wpthumb( get_attached_file( $id ), $size ) );
    
	}
	
	private function setup_hooks() {
	
		if ( $this->show_watermark_on_thumbnail )
    		add_filter( 'image_downsize', array( $this, '_show_watermark_on_image_downsize' ), 99, 3 );
    	
    	if ( $this->link_image_to_gallery_tab )
    		add_filter( 'admin_post_thumbnail_html', array( $this, '_set_featured_image_link_to_gallery_tab' ) );
    	    	
    	if ( $this->show_view_large_link )
			add_filter( 'admin_post_thumbnail_html', array( $this, '_admin_post_thumbnail_html' ) );
	
	}
	
	private function get_view_large_link() {
		
		if ( get_post_thumbnail_id( $this->post->ID ) )
			return '<a href="' . reset( wp_get_attachment_image_src( get_post_thumbnail_id( $this->post->ID ), 'width=800&height=800&crop=0' ) ) . '" target="_blank">View Large</a>';	
		
	}

}