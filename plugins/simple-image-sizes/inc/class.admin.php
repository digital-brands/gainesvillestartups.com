<?php
Class SISAdmin {
	
	// Original sizes
	public $original = array( 'thumbnail', 'medium', 'large' );

	public function __construct(){
		// Init
		add_action ( 'admin_menu', array( &$this, 'init' ) );
		add_action ( 'admin_enqueue_scripts', array( &$this, 'registerScripts' ), 11 );
		
		// Add ajax action
		add_action( 'wp_ajax_'.'sis_ajax_thumbnail_rebuild', array( &$this, 'ajaxThumbnailRebuildAjax' ) );
		add_action( 'wp_ajax_'.'get_sizes', array( &$this, 'ajaxGetSizes' ) );
		add_action( 'wp_ajax_'.'add_size', array( &$this, 'ajaxAddSize' ) );
		add_action( 'wp_ajax_'.'remove_size', array( &$this, 'ajaxRemoveSize' ) );
		
		// Add image sizes in the form, check if 3.3 is installed or not
		if( !function_exists( 'is_main_query' ) ) {
			add_filter( 'attachment_fields_to_edit', array( &$this, 'sizesInForm' ), 11, 2 ); // Add our sizes to media forms
		} else {
			add_filter( 'image_size_names_choose', array( &$this, 'AddThumbnailName' ) );
		}
		
		// Add link in plugins list
		add_filter( 'plugin_action_links', array( &$this,'addSettingsLink' ), 10, 2 );
		
		// Add action in media row quick actions
		add_filter( 'media_row_actions', array(&$this, 'addActionsList' ), 10, 2 );
		
		// Add filter for the Media single
		add_filter( 'attachment_fields_to_edit', array( &$this, 'addFieldRegenerate' ), 9, 2 );
		
	}
	
	/**
	 * Register javascripts and css.
	 * 
	 * @access public
	 * @return void
	 * @author Nicolas Juen
	 */
	public function registerScripts($hook_suffix = '' ) {
		if( !isset( $hook_suffix ) || empty( $hook_suffix ) )
			return false;
		
		if( $hook_suffix == 'options-media.php' ) {
			// Add javascript
			wp_enqueue_script( 'sis-jquery-ui-sis',  SIS_URL.'js/jquery-ui-1.8.16.custom.min.js', array('jquery'), '1.8.16' );
			wp_enqueue_script( 'sis_js', SIS_URL.'js/sis.min.js', array('jquery','sis-jquery-ui-sis'), SIS_VERSION );
			
			// Add javascript translation
			wp_localize_script( 'sis_js', 'sis', $this->localizeVars() );
			
			// Add CSS
			wp_enqueue_style( 'jquery-ui-sis', SIS_URL.'css/Aristo/jquery-ui-1.8.7.custom.css', array(), '1.8.7' );
			wp_enqueue_style( 'sis_css', SIS_URL.'css/sis-style.css', array(), SIS_VERSION );
		} elseif( $hook_suffix == 'upload.php' || ( $hook_suffix == 'media.php' && isset( $_GET['attachment_id'] ) && isset( $_GET['action'] ) && $_GET['action'] == 'edit' ) ) {
			// Add javascript
			wp_enqueue_script( 'sis_js', SIS_URL.'js/sis-attachments.min.js', array( 'jquery' ), SIS_VERSION );
			
			// Add javascript translation
			wp_localize_script( 'sis_js', 'sis', $this->localizeVars() );
		}
	}
	
	/**
	 * Localize the var for javascript
	 * 
	 * @access public
	 * @return void
	 * @author Nicolas Juen
	 */
	public function localizeVars() {
		return array(
			'ajaxUrl' 			=>  admin_url( '/admin-ajax.php' ),
			'reading' 			=> __( 'Reading attachments...', 'sis' ),
			'maximumWidth' 		=> __( 'Maximum width', 'sis' ),
			'maximumHeight' 	=> __( 'Maximum height', 'sis' ),
			'crop' 				=> __( 'Crop ?', 'sis' ),
			'tr' 				=> __( 'yes', 'sis' ),
			'fl'				=> __( 'no', 'sis' ),
			'show'				=> __( 'Show in post insertion ?', 'sis' ),
			'of' 				=> __( ' of ', 'sis' ),
			'or' 				=> __( ' or ', 'sis' ),
			'beforeEnd' 		=> __( ' before the end.', 'sis' ),
			'deleteImage' 		=> __( 'Delete', 'sis' ),
			'noMedia' 			=> __( 'No media in your site to regenerate !', 'sis' ),
			'regenerating' 		=> __( 'Regenerating ', 'sis'),
			'regenerate' 		=> __( 'Regenerate ', 'sis'),
			'validate' 			=> __( 'Validate image size name', 'sis' ),
			'done' 				=> __( 'Done.', 'sis' ),
			'size' 				=> __( 'Size', 'sis' ),	
			'notOriginal' 		=> __( 'Don\'t use the basic Wordpress thumbnail size name, use the form above to edit them', 'sis' ),
			'alreadyPresent' 	=> __( 'This size is already registered, edit it instead of recreating it.', 'sis' ),
			'confirmDelete' 	=> __( 'Do you really want to delete these size ?', 'sis' ),
			'update' 			=> __( 'Update', 'sis' ),
			'ajaxErrorHandler' 	=> __( 'Error requesting page', 'sis' ),
			'messageRegenerated' => __( 'images have been regenerated !', 'sis' ),
			'validateButton' 	=> __( 'Validate', 'sis' ),
			'startedAt' 		=> __( ' started at', 'sis' ),
			'customName'		=> __( 'Public name', 'sis' ),
			'finishedAt' 		=> __( ' finished at :', 'sis' ),
			'phpError' 			=> __( 'Error during the php treatment, be sure to not have php errors in your page', 'sis' ),
			'notSaved' 			=> __( 'All the sizes you have modifed are not saved, continue anyway ?', 'sis' ),
			'soloRegenerated'	=> __( 'This image has been regenerated in %s seconds', 'sis' ),
		);
	}
	
	/**
	 * Add action in media row
	 * 
	 * @since 2.2
	 * @access public
	 * @return $actions : array of actions and content to display
	 * @author Nicolas Juen
	 */
	function addActionsList( $actions, $object ) {
		
		// Add action for regeneration
		$actions['sis-regenerate'] = "<a href='#' class='sis-regenerate-one'>".__( 'Regenerate thumbnails', 'sis' )."</a>".'<input type="hidden" class="regen" value="'.wp_create_nonce( 'regen' ).'" />';
		
		// Return actions
		return $actions;
	}
	
	/**
	 * Add a link to the setting option page
	 * 
	 * @access public
 	 * @param array $links
	 * @param string $file
	 * @return void
	 * @author Nicolas Juen
	 */
	public function addSettingsLink( $links, $file ) {
	
		if( $file != 'simple-image-sizes/simple_image_sizes.php' )
			return $links;
			
		$settings_link = '<a href="'.admin_url('options-media.php').'"> '.__( 'Settings', 'sis' ).' </a>';
		array_unshift($links, $settings_link);
		
		return $links;
	}

	/**
	 * Init for the option page
	 * 
	 * @access public
	 * @return void
	 * @author Nicolas Juen
	 */
	function init() {
		// Check if admin
		if( !is_admin() )
			return false;
		
		// Get the image sizes
		global $_wp_additional_image_sizes;
		$options = get_option( SIS_OPTION );

		// Get the sizes and add the settings
		foreach ( get_intermediate_image_sizes() as $s ) {
			// Don't make the original sizes or numeric sizes that appear
			if( in_array( $s, $this->original ) || is_integer( $s ) )
				continue;
			
			// Set width
			if ( isset( $_wp_additional_image_sizes[$s]['width'] ) ) // For theme-added sizes
				$width = intval( $_wp_additional_image_sizes[$s]['width'] );
			else                                                     // For default sizes set in options
				$width = get_option( "{$s}_size_w" );
			
			// Set height
			if ( isset( $_wp_additional_image_sizes[$s]['height'] ) ) // For theme-added sizes
				$height = intval( $_wp_additional_image_sizes[$s]['height'] );
			else                                                      // For default sizes set in options
				$height = get_option( "{$s}_size_h" );
			
			//Set crop
			if ( isset( $_wp_additional_image_sizes[$s]['crop'] ) ) {   // For theme-added sizes
				$crop = intval( $_wp_additional_image_sizes[$s]['crop'] );
			} else {
				// For default sizes set in options
				$crop = get_option( "{$s}_crop" );
			}
			
			// Add the setting field for this size
			add_settings_field( 'image_size_'.$s, sprintf( __( '%s size', 'sis' ), $s ), array( &$this, 'imageSizes' ), 'media' , 'default', array( 'name' => $s , 'width' => $width , 'height' => $height, 'c' => $crop ) );
		}

		// Register the setting for media option page
		register_setting( 'media', SIS_OPTION );

		// Add the button
		add_settings_field( 'add_size_button', __( 'Add a new size', 'sis' ), array( &$this, 'addSizeButton' ), 'media' );

		// Add legend
		add_settings_field( 'add_legend', __( 'Legend of the sizes', 'sis' ), array( &$this, 'addLegend' ), 'media' );

		// Add php button
		add_settings_field( 'get_php_button', __( 'Get php for theme', 'sis' ), array( &$this, 'getPhpButton' ), 'media' );

		// Add section for the thumbnail regeneration
		add_settings_section( 'thumbnail_regenerate', __( 'Thumbnail regeneration', 'sis' ), array( &$this, 'thumbnailRegenerate' ), 'media' );
 	}
 	
 	/**
 	 * Display the row of the image size
 	 * 
 	 * @access public
 	 * @param mixed $args
 	 * @return void
	 * @author Nicolas Juen
 	 */
 	public function imageSizes( $args ) {
 		
		if( is_integer( $args['name'] ) )
			return false;
		
 		// Get the options
		$sizes = (array)get_option( SIS_OPTION );
		
		// Get the vars
		$height 	=	isset( $sizes[$args['name']]['h'] )? $sizes[$args['name']]['h'] : $args['height'] ;
		$width 		=	isset( $sizes[$args['name']]['w'] )? $sizes[$args['name']]['w'] : $args['width'] ;
		$crop 		=	isset( $sizes[$args['name']]['c'] ) && !empty( $sizes[$args['name']]['c'] )? $sizes[$args['name']]['c'] : $args['c'] ;
		$show 		=	isset( $sizes[$args['name']]['s'] ) && !empty( $sizes[$args['name']]['s'] )? '1' : '0' ;
		$custom 	=	isset( $sizes[$args['name']]['custom'] ) && !empty( $sizes[$args['name']]['custom'] )? '1' : '0' ;
		$name 		=	isset( $sizes[$args['name']]['n'] ) && !empty( $sizes[$args['name']]['n'] )? esc_html( $sizes[$args['name']]['n'] ) : esc_html( $args['name'] ) ;
		
		?>
		<input type="hidden" value="<?php echo esc_attr( $args['name'] ); ?>" name="image_name" />
		<?php if( $custom ): ?>
			<input name="<?php echo esc_attr( 'custom_image_sizes['.$args['name'].'][custom]' ); ?>" type="hidden" id="<?php echo esc_attr( 'custom_image_sizes['.$args['name'].'][custom]' ); ?>" value="1" />
		<?php else: ?>
			<input name="<?php echo esc_attr( 'custom_image_sizes['.$args['name'].'][theme]' ); ?>" type="hidden" id="<?php echo esc_attr( 'custom_image_sizes['.$args['name'].'][theme]' ); ?>" value="1" />
		<?php endif; ?>
		<label class="sis-label" for="<?php echo esc_attr( 'custom_image_sizes['.$args['name'].'][w]' ); ?>">
			<?php _e( 'Maximum width', 'sis'); ?> 
			<input name="<?php esc_attr_e( 'custom_image_sizes['.$args['name'].'][w]' ); ?>" class='w small-text' type="number" step='1' min='0' id="<?php echo esc_attr( 'custom_image_sizes['.$args['name'].'][w]' ); ?>" base_w='<?php echo esc_attr( $width ); ?>' value="<?php echo esc_attr( $width ); ?>" />
		</label>
		<label class="sis-label" for="<?php  esc_attr_e( 'custom_image_sizes['.$args['name'].'][h]' ); ?>">
			<?php _e( 'Maximum height', 'sis'); ?> 
			<input name="<?php esc_attr_e( 'custom_image_sizes['.$args['name'].'][h]' ); ?>" class='h small-text' type="number" step='1' min='0' id="<?php echo esc_attr( 'custom_image_sizes['.$args['name'].'][h]' ); ?>" base_h='<?php echo esc_attr( $height ); ?>' value="<?php echo esc_attr( $height ); ?>" />
		</label>
		<label class="sis-label" for="<?php echo esc_attr( 'custom_image_sizes['.$args['name'].'][n]' ); ?>">
			<?php _e( 'Public name', 'sis'); ?> 
			<input name="<?php echo esc_attr( 'custom_image_sizes['.$args['name'].'][n]' ); ?>" class='n' type="text" id="<?php echo esc_attr( 'custom_image_sizes['.$args['name'].'][n]' ); ?>" base_n='<?php echo $name; ?>' value="<?php echo $name ?>" />
		</label>
		<span class="size_options">
			<input type='checkbox' id="<?php echo esc_attr( 'custom_image_sizes['.$args['name'].'][c]' ); ?>" <?php checked( $crop, 1 ) ?> class="c crop" base_c='<?php echo esc_attr( $crop ); ?>' name="<?php echo esc_attr( 'custom_image_sizes['.$args['name'].'][c]' ); ?>" value="1" />
			<label class="c" for="<?php echo esc_attr( 'custom_image_sizes['.$args['name'].'][c]' ); ?>"><?php _e( 'Crop ?', 'sis'); ?></label>
			
			<input type='checkbox' id="<?php echo esc_attr( 'custom_image_sizes['.$args['name'].'][s]'); ?>" <?php checked( $show, 1 ) ?> class="s show" base_s='<?php echo esc_attr( $show ); ?>' name="<?php echo esc_attr( 'custom_image_sizes['.$args['name'].'][s]'); ?>" value="1" />
			<label class="s" for="<?php echo esc_attr( 'custom_image_sizes['.$args['name'].'][s]'); ?>"><?php _e( 'Show in post insertion ?', 'sis'); ?></label>
		</span>
		<span class="delete_size"><?php _e( 'Delete', 'sis'); ?></span>
		<span class="add_size validate_size"><?php _e( 'Update', 'sis'); ?></span>
		
		<input type="hidden" class="deleteSize" value='<?php echo wp_create_nonce( 'delete_'.$args['name'] ); ?>' />
	<?php }
	
	/**
	 * Add the button to add a size
	 * 
	 * @access public
	 * @return void
 	 * @author Nicolas Juen
	 */
	public function addSizeButton() { ?>
		<input type="button" class="button-secondary action" id="add_size" value="<?php esc_attr_e( 'Add a new size of thumbnail', 'sis'); ?>" />
	<?php
	}	
	
	/**
	 * Add the button to get the php for th sizes
	 * 
	 * @access public
	 * @return void
 	 * @author Nicolas Juen
	 */
	public function getPhpButton() { ?>
		<input type="button" class="button-secondary action" id="get_php" value="<?php esc_attr_e( 'Get the PHP for the theme', 'sis'); ?>" />
		<p> <?php _e( 'Copy and paste the code below into your Wordpress theme function file if you wanted to save them and deactivate the plugin.', 'sis'); ?> </p>
		<code></code>
	<?php
	}	
	
	/**
	 * Add the legend fo the colors
	 * 
	 * @access public
	 * @return void
 	 * @author Nicolas Juen
	 */
	public function addLegend() { ?>
		<?php _e('The images created on your theme are <span style="color:#F2A13A">orange</span> and your custom size are <span style="color:#89D76A"> green </span>.', 'sis'); ?>
	<?php
	}
	
	/**
	 * Display the Table of sizes and post types for regenerating
	 * 
	 * @access public
	 * @return void
	 * @author Nicolas Juen
	 */
	public function thumbnailRegenerate() {
		// Get the sizes
		global $_wp_additional_image_sizes,$_wp_post_type_features;
?>
		<input type="hidden" class="addSize" value='<?php echo wp_create_nonce( 'add_size' ); ?>' />
		<input type="hidden" class="regen" value='<?php echo wp_create_nonce( 'regen' ); ?>' />
		<input type="hidden" class="getList" value='<?php echo wp_create_nonce( 'getList' ); ?>' />
		<div id="sis-regen">
			<div class="wrapper" style="">
				<h4> <?php _e( 'Select which thumbnails you want to rebuild:', 'sis'); ?> </h4>
				<table cellspacing="0" id="sis_sizes" class="widefat page fixed sis">
					<thead>
						<tr>
							<th scope="col" id="cb" class="manage-column column-cb check-column" style=""><input checked="checked" type="checkbox"></th>
							<th class="manage-column" scope="col"><?php _e( 'Size name', 'sis'); ?></th>
							<th class="manage-column" scope="col"><?php _e( 'Width', 'sis'); ?></th>
							<th class="manage-column" scope="col"><?php _e( 'Height', 'sis'); ?></th>
							<th class="manage-column" scope="col"><?php _e( 'Crop ?', 'sis'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						// Display the sizes in the array
						foreach ( get_intermediate_image_sizes() as $s ):
							// Don't make or numeric sizes that appear
							if( is_integer( $s ) )
								continue;
	
							if ( isset( $_wp_additional_image_sizes[$s]['width'] ) ) // For theme-added sizes
								$width = intval( $_wp_additional_image_sizes[$s]['width'] );
							else                                                     // For default sizes set in options
								$width = get_option( "{$s}_size_w" );
			
							if ( isset( $_wp_additional_image_sizes[$s]['height'] ) ) // For theme-added sizes
								$height = intval( $_wp_additional_image_sizes[$s]['height'] );
							else                                                      // For default sizes set in options
								$height = get_option( "{$s}_size_h" );
			
							if ( isset( $_wp_additional_image_sizes[$s]['crop'] ) )   // For theme-added sizes
								$crop = intval( $_wp_additional_image_sizes[$s]['crop'] );
							else                                                      // For default sizes set in options
								$crop = get_option( "{$s}_crop" );
							?>
							<tr>
								<th  class="check-column">
									<input type="checkbox" class="thumbnails" id="<?php echo esc_attr( $s ) ?>" name="thumbnails[]" checked="checked" value="<?php echo esc_attr( $s ); ?>" />
								</th>
								<th>
									<label for="<?php esc_attr_e( $s ); ?>">
										<?php echo esc_html( $s ); ?>
									</label>
								</th>
								<th>
									<label for="<?php esc_attr_e( $s ); ?>">
										<?php echo esc_html( $width); ?> px
									</label>
								</th>
								<th>
									<label for="<?php esc_attr_e( $s ); ?>">
										<?php echo esc_html( $height ); ?> px
									</label>
								</th>
								<th>
									<label for="<?php esc_attr_e( $s ); ?>">
										<?php echo ( $crop == 1 )? __( 'yes', 'sis' ):__( 'no', 'sis' ); ?>
									</label>
								</th>
							</tr>
						<?php endforeach;?>
					</tbody>
					<tfoot>
						<tr>
							<th scope="col" id="cb" class="manage-column column-cb check-column" style=""><input checked="checked" type="checkbox"></th>
							<th class="manage-column" scope="col"><?php _e( 'Size name', 'sis'); ?></th>
							<th class="manage-column" scope="col"><?php _e( 'Width', 'sis'); ?></th>
							<th class="manage-column" scope="col"><?php _e( 'Height', 'sis'); ?></th>
							<th class="manage-column" scope="col"><?php _e( 'Crop ?', 'sis'); ?></th>
						</tr>
					</tfoot>
				</table>
				
				<h4><?php _e( 'Select which post type source thumbnails you want to rebuild:', 'sis'); ?></h4>
				<table cellspacing="0" class="widefat page fixed sis">
						<thead>
							<tr>
								<th scope="col" id="cb" class="manage-column column-cb check-column" style=""><input checked="checked" type="checkbox"></th>
								<th class="manage-column" scope="col"><?php _e( 'Post type', 'sis'); ?></th>
							</tr>
						</thead>
						<tbody>
						<?php
						// Diplay the post types table
						foreach ( get_post_types( array( 'public' => true, '_builtin' => false ), 'objects', 'or' ) as $ptype ):
							// Avoid the post_types without post thumbnails feature
							if( !array_key_exists( 'thumbnail' , $_wp_post_type_features[$ptype->name] ) || $_wp_post_type_features[$ptype->name] == false )
								continue;
							?>
							<tr>
								<th class="check-column">
									<label for="<?php esc_attr_e( $ptype->name ); ?>">
										<input type="checkbox" class="post_types" name="post_types[]" checked="checked" id="<?php echo esc_attr( $ptype->name ); ?>" value="<?php echo esc_attr( $ptype->name ); ?>" />
									</label>
								</th>
								<th>
									<label for="<?php esc_attr_e( $ptype->name ); ?>">
										<em><?php echo esc_html( $ptype->labels->name ); ?></em>
									</label>
								</th>
							</tr>
						<?php endforeach;?>
					</tbody>
					<tfoot>
						<tr>
							<th scope="col" id="cb" class="manage-column column-cb check-column"><input checked="checked" type="checkbox"></th>
							<th class="manage-column" scope="col"><?php _e( 'Post type', 'sis'); ?></th>
						</tr>
					</tfoot>
				</table>
			</div>
		</div>
		<div >
			<div id="regenerate_message"></div>
			<div class="progress">
				<div class=" progress-percent ui-widget">
					<div class="ui-state-highlight ui-corner-all" style="margin-top: 20px; padding: 0 .7em;"> 
						<p>
							<span class="ui-icon ui-icon-info" style="float: left; margin-right: .7em;"></span>
							<span class="text">0%</span>
						</p>
					</div>
				</div>
			</div>
			<div class="ui-widget" id="time">
				<div class="ui-state-highlight ui-corner-all" style="margin-top: 20px; padding: 0 .7em;"> 
					<p>
						<span class="ui-icon ui-icon-info" style="float: left; margin-right: .7em;"></span> 
						<span><strong><?php _e( 'End time calculated :', 'sis' ); ?></strong> <span class='time_message'>Calculating...</span> </span>
					</p>
					<ul class="messages"></ul>
				</div>
			</div>
			<div id="error_messages">
				<p>
					<ol class="messages">
					</ol>
				</p>
			</div>
			<div id="thumb"><h4><?php _e( 'Last image:', 'sis'); ?></h4><img id="thumb-img" /></div>
			<input type="button" class="button" name="ajax_thumbnail_rebuild" id="ajax_thumbnail_rebuild" value="<?php _e( 'Regenerate Thumbnails', 'sis' ) ?>" />
		</div>
		<?php
	}
		
	/**
	 * Add a size by Ajax
	 * 
	 * @access public
	 * @return void
	 * @author Nicolas Juen
	 */
	public function ajaxAddSize() {
		
		// Get the nonce
		$nonce = isset( $_POST['nonce'] ) ? $_POST['nonce']: '' ;
		
		// Get old options
		$sizes = (array)get_option( SIS_OPTION );
		
		// Check entries
		$name = isset( $_POST['name'] ) ? sanitize_title( $_POST['name'] ): '' ;
		$height = !isset( $_POST['height'] )? 0 : absint( $_POST['height'] );
		$width =  !isset( $_POST['width'] )? 0 : absint( $_POST['width'] );
		$crop = isset( $_POST['crop'] ) &&  $_POST['crop'] == 'false' ? false : true;
		$show = isset( $_POST['show'] ) &&  $_POST['show'] == 'false' ? false : true;
		$cn = isset( $_POST['customName'] ) && !empty( $_POST['customName'] ) ? sanitize_text_field( $_POST['customName'] ): $name ;
		
		// Check the nonce
		if( !wp_verify_nonce( $nonce , 'add_size' ) ) {
			echo 0;
			die();
		}
		
		// If no name given do not save
		if( empty( $name ) ) {
			echo 0;
			die();
		}

		// Make values
		$values = array( 'custom' => 1, 'w' => $width , 'h' => $height, 'c' => $crop, 's' => $show, 'n' => $cn );

		// If the size have not changed return 2
		if( isset( $sizes[$name] ) && $sizes[$name] === $values ) {
			echo 2;
			die();
		}
		
		// Put the new values
		$sizes[$name] = $values;
		
		// display update result
		echo (int)update_option( 'custom_image_sizes', $sizes );
		die();
	}
	
	/**
	 * Remove a size by Ajax
	 * 
	 * @access public
	 * @return void
	 * @author Nicolas Juen
	 */
	public function ajaxRemoveSize() {
		
		// Get old options
		$sizes = (array)get_option( SIS_OPTION );
		
		// Get the nonce and name
		$nonce = isset( $_POST['nonce'] ) ? $_POST['nonce']: '' ;
		$name = isset( $_POST['name'] ) ? sanitize_title( $_POST['name'] ): '' ;
		
		// Check the nonce
		if( !wp_verify_nonce( $nonce , 'delete_'.$name ) ) {
			echo 0;
			die();
		}
		
		// Remove the size
		unset( $sizes[sanitize_title( $name )] );
		unset( $sizes[0] );
		
		// Display the results
		echo (int)update_option( SIS_OPTION, $sizes );
		die();
	}
	
	/**
	 * Display the add_image_size for the registered sizes
	 * 
	 * @access public
	 * @return void
	 */
	public function ajaxGetSizes() {
		global $_wp_additional_image_sizes;

		foreach ( get_intermediate_image_sizes() as $s ):

		// Don't make the original sizes
		if( in_array( $s, $this->original ) )
			continue;
			
		if ( isset( $_wp_additional_image_sizes[$s]['width'] ) ) // For theme-added sizes
			$width = intval( $_wp_additional_image_sizes[$s]['width'] );
		else                                                     // For default sizes set in options
			$width = get_option( "{$s}_size_w" );

		if ( isset( $_wp_additional_image_sizes[$s]['height'] ) ) // For theme-added sizes
			$height = intval( $_wp_additional_image_sizes[$s]['height'] );
		else                                                      // For default sizes set in options
			$height = get_option( "{$s}_size_h" );

		if ( isset( $_wp_additional_image_sizes[$s]['crop'] ) )   // For theme-added sizes
			$crop = intval( $_wp_additional_image_sizes[$s]['crop'] );
		else                                                      // For default sizes set in options
			$crop = get_option( "{$s}_crop" );
		
		$crop = ( $crop == 0 )? 'false' : 'true' ;
		?>
			add_image_size( '<?php echo $s; ?>', '<?php echo $width; ?>', '<?php echo $height; ?>', <?php echo $crop ?> );<br />
		<?php endforeach;
		
		die();
	}

	/**
	 * Rebuild the image
	 * 
	 * @access public
	 * @return void
	 * @author Nicolas Juen
	 */
	public function ajaxThumbnailRebuildAjax() {
		global $wpdb;
		
		// Get the nonce
		$nonce = isset( $_POST['nonce'] ) ? $_POST['nonce']: '' ;
		
		// Time a the begining
		$start_time = microtime(true);
		
		// Get the action
		$action = $_POST["do"];
		
		// Get the thumbnails
		$thumbnails = isset( $_POST['thumbnails'] )? $_POST['thumbnails'] : NULL;
		
		if ( $action == "getlist" ) {
			// Check the nonce
			if( !wp_verify_nonce( $nonce , 'getList' ) ) {
				echo json_encode( array( ) );
				die();
			}
			
			if ( isset( $_POST['post_types'] ) && !empty( $_POST['post_types'] ) ) {
				
				// Get image medias
				$whichmimetype = wp_post_mime_type_where( 'image', $wpdb->posts );
				
				// Get all parent from post type
				$attachments = $wpdb->get_results( "SELECT *
					FROM $wpdb->posts 
					WHERE 1 = 1
					AND post_type = 'attachment'
					$whichmimetype
					AND post_parent IN (
						SELECT DISTINCT ID 
						FROM $wpdb->posts 
						WHERE post_type IN ('".implode( "', '", $_POST['post_types'] )."')
					)" );
					
			} else {
				$attachments =& get_children( array(
					'post_type' => 'attachment',
					'post_mime_type' => 'image',
					'numberposts' => -1,
					'post_status' => null,
					'post_parent' => null, // any parent
					'output' => 'object',
				) );
			}
			
			// Get the attachments
			foreach ( $attachments as $attachment ) {
				$res[] = array('id' => $attachment->ID, 'title' => $attachment->post_title);
			}
			// Return the Id's and Title of medias
			die( json_encode( $res ) );
		} else if ( $action == "regen" ) {
			
			// Check the nonce
			if( !wp_verify_nonce( $nonce , 'regen' ) ) {
				echo json_encode( array( 'error' => _e( 'Trying to cheat ?', 'sis' ) ) );
				die();
			}
			
			// Get the id
			$id = $_POST["id"];
			
			// Check Id
			if( (int)$id == 0 ) {
				die( json_encode( array( 'time' => round( microtime( true ) - $start_time, 4 ), 'error' => __( 'No id given in POST datas.', 'sis' ) ) ) );
			}
			
			// Get the path
			$fullsizepath = get_attached_file( $id );

			// Regen the attachment
			if ( FALSE !== $fullsizepath && @file_exists( $fullsizepath ) ) {
				set_time_limit( 30 );
				if( wp_update_attachment_metadata( $id, $this->wp_generate_attachment_metadata_custom( $id, $fullsizepath, $thumbnails ) ) == false )
					die( json_encode( array( 'src' => wp_get_attachment_thumb_url( $id ), 'time' => round( microtime( true ) - $start_time, 4 ) ,'message' => sprintf( __( 'This file already exists in this size and have not been regenerated :<br/><a target="_blank" href="%1$s" >%2$s</a>', 'sis'), get_edit_post_link( $id ), get_the_title( $id ) ) ) ) );
			} else {
				die( json_encode( array( 'src' => wp_get_attachment_thumb_url( $id ), 'time' => round( microtime( true ) - $start_time, 4 ), 'error' => sprintf( __( 'This file does not exists and have not been regenerated :<br/><a target="_blank" href="%1$s" >%2$s</a>', 'sis'), get_edit_post_link( $id ), get_the_title( $id ) ) ) ) );
			}
			// Display the attachment url for feedback 
			die( json_encode( array( 'time' => round( microtime( true ) - $start_time, 4 ) , 'src' => wp_get_attachment_thumb_url( $id ), 'title' => get_the_title( $id ) ) ) );
		}
	}

	/**
	 * Generate post thumbnail attachment meta data.
	 *
	 * @since 2.1.0
	 *
	 * @param int $attachment_id Attachment Id to process.
	 * @param string $file Filepath of the Attached image.
	 * @return mixed Metadata for attachment.
	 */
	public function wp_generate_attachment_metadata_custom( $attachment_id, $file, $thumbnails = NULL ) {
		$attachment = get_post( $attachment_id );
		
		$meta_datas = get_post_meta( $attachment_id, '_wp_attachment_metadata', true );

		$metadata = array();
		if ( preg_match('!^image/!', get_post_mime_type( $attachment )) && file_is_displayable_image($file) ) {
			$imagesize = getimagesize( $file );
			$metadata['width'] = $imagesize[0];
			$metadata['height'] = $imagesize[1];
			list($uwidth, $uheight) = wp_constrain_dimensions($metadata['width'], $metadata['height'], 128, 96);
			$metadata['hwstring_small'] = "height='$uheight' width='$uwidth'";

			// Make the file path relative to the upload dir
			$metadata['file'] = _wp_relative_upload_path($file);

			// make thumbnails and other intermediate sizes
			global $_wp_additional_image_sizes;

			foreach ( get_intermediate_image_sizes() as $s ) {
				$sizes[$s] = array( 'width' => '', 'height' => '', 'crop' => FALSE );
				if ( isset( $_wp_additional_image_sizes[$s]['width'] ) )
					$sizes[$s]['width'] = intval( $_wp_additional_image_sizes[$s]['width'] ); // For theme-added sizes
				else
					$sizes[$s]['width'] = get_option( "{$s}_size_w" ); // For default sizes set in options
				if ( isset( $_wp_additional_image_sizes[$s]['height'] ) )
					$sizes[$s]['height'] = intval( $_wp_additional_image_sizes[$s]['height'] ); // For theme-added sizes
				else
					$sizes[$s]['height'] = get_option( "{$s}_size_h" ); // For default sizes set in options
				if ( isset( $_wp_additional_image_sizes[$s]['crop'] ) )
					$sizes[$s]['crop'] = intval( $_wp_additional_image_sizes[$s]['crop'] ); // For theme-added sizes
				else
					$sizes[$s]['crop'] = get_option( "{$s}_crop" ); // For default sizes set in options
			}

			$sizes = apply_filters( 'intermediate_image_sizes_advanced', $sizes );

			foreach ( $sizes as $size => $size_data ) {
				if( isset( $thumbnails ) )
					if( !in_array( $size, $thumbnails ) ) {
						continue;
					}

				$resized = image_make_intermediate_size( $file, $size_data['width'], $size_data['height'], $size_data['crop'] );
				
				if( isset( $meta_datas['size'][$size] ) ) {
					// Remove the size from the orignal sizes for after work
					unset( $meta_datas['size'][$size] );
				}
				
				if ( $resized )
					$metadata['sizes'][$size] = $resized;
			}
			
			// Only if not all sizes
			if( is_array( $thumbnails ) ) {
				// Fill the array with the other sizes not have to be done
				foreach( $meta_datas['sizes'] as $name => $fsize ) {
					$metadata['sizes'][$name] = $fsize;
				}
			}
			
			// fetch additional metadata from exif/iptc
			$image_meta = wp_read_image_metadata( $file );
			if ( $image_meta )
				$metadata['image_meta'] = $image_meta;
		}

		return apply_filters( 'wp_generate_attachment_metadata', $metadata, $attachment_id );
	}
	
	/**
	 * Add the custom sizes to the image sizes in article edition
	 * 
	 * @access public
 	 * @param array $form_fields
	 * @param object $post
	 * @return void
	 * @author Nicolas Juen
	 * @author Additional Image Sizes (zui)
	 */
	public function sizesInForm( $form_fields, $post ) {
		// Protect from being view in Media editor where there are no sizes
		if ( isset( $form_fields['image-size'] ) ) {
			$out = NULL;
			$size_names = array();
			$sizes_custom = get_option( SIS_OPTION );
			
			if ( is_array( $sizes_custom ) ) {
				foreach( $sizes_custom as $key => $value ) {
					if( isset( $value['s'] ) && $value['s'] == 1 ) {
						$size_names[$key] = $this->_getThumbnailName( $key );;
					}
				}
			}
			foreach ( $size_names as $size => $label ) {
				$downsize = image_downsize( $post->ID, $size );
		
				// is this size selectable?
			$enabled = ( $downsize[3] || 'full' == $size );
			$css_id = "image-size-{$size}-{$post->ID}";

			// We must do a clumsy search of the existing html to determine is something has been checked yet
			if ( FALSE === strpos( 'checked="checked"', $form_fields['image-size']['html'] ) ) {

					if ( empty($check) )
						$check = get_user_setting( 'imgsize' ); // See if they checked a custom size last time

					$checked = '';

					// if this size is the default but that's not available, don't select it
					if ( $size == $check || str_replace( " ", "", $size ) == $check ) {
						if ( $enabled )
							$checked = " checked='checked'";
						else
							$check = '';
					} elseif ( !$check && $enabled && 'thumbnail' != $size ) {
						// if $check is not enabled, default to the first available size that's bigger than a thumbnail
						$check = $size;
						$checked = " checked='checked'";
					}
				}
				$html = "<div class='image-size-item' style='min-height: 50px; margin-top: 18px;'><input type='radio' " . disabled( $enabled, false, false ) . "name='attachments[$post->ID][image-size]' id='{$css_id}' value='{$size}'$checked />";

				$html .= "<label for='{$css_id}'>$label</label>";
				// only show the dimensions if that choice is available
				if ( $enabled )
					$html .= " <label for='{$css_id}' class='help'>" . sprintf( "(%d&nbsp;&times;&nbsp;%d)", $downsize[1], $downsize[2] ). "</label>";

				$html .= '</div>';

				$out .= $html;
			}
			$form_fields['image-size']['html'] .= $out;
		} // End protect from Media editor
		
		return $form_fields;
	}

	/**
	 * Add the thumbnail name in the post insertion, based on new WP filter
	 * 
	 * @access public
 	 * @param array $sizes
	 * @return array
	 * @since 2.3
	 * @author Nicolas Juen
	 * @author radeno based on this post : http://www.wpmayor.com/wordpress-hacks/how-to-add-custom-image-sizes-to-wordpress-uploader/
	 */
	function AddThumbnailName($sizes) {
		// Get options
		$sizes_custom = get_option( SIS_OPTION );
		// init size array
		$addsizes = array();
		
		// check there is custom sizes
		if ( is_array( $sizes_custom ) && !empty( $sizes_custom ) ) {
			foreach( $sizes_custom as $key => $value ) {
				// If we show this size in the admin
				if( isset( $value['s'] ) && $value['s'] == 1 )
					$addsizes[$key] = $this->_getThumbnailName( $key );
			}
		}
		
		// Merge the two array
		$newsizes = array_merge($sizes, $addsizes);
		
		// Add new size
		return $newsizes;
	}
	
	/**
	 * Get a thumbnail name from its slug
	 * 
	 * @access private
 	 * @param string $thumbnailSlug : the slug of the thumbnail
	 * @return array
	 * @since 2.3
	 * @author Nicolas Juen
	 */
	private function _getThumbnailName( $thumbnailSlug = '' ) {
		
		// get the options
		$sizes_custom = get_option( SIS_OPTION );
		
		// If the size exists
		if( isset( $sizes_custom[$thumbnailSlug] ) ) {
			// If the name exists return it, slug by default
			if( isset( $sizes_custom[$thumbnailSlug]['n'] ) && !empty( $sizes_custom[$thumbnailSlug]['n'] ) ) {
				return $sizes_custom[$thumbnailSlug]['n'];
			} else {
				return $thumbnailSlug;
			}
		}
		
		// return slug if not found
		return $thumbnailSlug;
	}
	
	/**
	 * Get a thumbnail name from its slug
	 * 
	 * @access public
 	 * @param array $fields : the fields of the media
	 * @param object $post : the post object
	 * @return array
	 * @since 2.3.1
	 * @author Nicolas Juen
	 */
	function addFieldRegenerate( $fields, $post ) {
		// Check this is an image
		if( strpos( $post->post_mime_type, 'image' ) === false )
			return $fields;
		
		$fields['sis-regenerate'] = array(
			'label'	=> __( 'Regenerate Thumbnails', 'sis' ),
			'input'	=> 'html',
			'html'	=> '
			<input type="button" class="button title sis-regenerate-one" value="'.__( 'Regenerate Thumbnails', 'sis' ).'" />
			<span class="title"><em></em></span>
			<input type="hidden" class="regen" value="'.wp_create_nonce( 'regen' ).'" />
			'
		);
		return $fields;
	}
}
?>