<?php # -*- coding: utf-8 -*-

/**
 * Displays an element link flyout tab in the frontend.
 * @author  fefe
 * @version 2018.06.23
 */
class Mlp_Advanced_Synchronisation implements Mlp_Updatable {

	/**
	 * Prefix for 'name' attribute in form fields.
	 *
	 * @var string
	 */
	private $form_name = 'mlp-advanced-synchronisation-extra';

	/**
	 * @var Mlp_Module_Manager_Interface
	 */
	private $module_manager;

	/**
	 * @var Inpsyde_Nonce_Validator
	 */
	private $nonce_validator;

	/**
	 * @var Mlp_Content_Relations_Interface
	 */
	private $content_relations;
	
	/**
	 * @var bool
	 */
	private $saved_post = false;
	
	/**
	 * Constructor. Sets up the properties.
	 *
	 * @param Mlp_Module_Manager_Interface $module_manager Module manager object.
	 */
	public function __construct(
		Mlp_Module_Manager_Interface $module_manager,
		Mlp_Content_Relations_Interface $content_relations  ) {

		$this->module_manager = $module_manager;

		$this->content_relations = $content_relations;
		
		$this->nonce_validator = Mlp_Nonce_Validator_Factory::create( 'save_advanced_synchronisation_setting' );
	}

	/**
	 * Wires up all functions.
	 *
	 * @return void
	 */
	public function initialize() {

		// Quit here if module is turned off
		if ( ! $this->register_setting() ) {
			return;
		}

		if ( is_admin() ) {
			add_action( 'mlp_modules_add_fields', array( $this, 'draw_options_page_form_fields' ) );

			// Use this hook to handle the user input of your modules' options page form fields
			add_filter( 'mlp_modules_save_fields', array( $this, 'save_options_page_form_fields' ) );
		}
		
		// Register Trasher post meta to the submit box.
		add_action( 'quick_edit_custom_box', array( $this, 'post_submitbox_misc_actions' ) );
		add_action( 'post_submitbox_misc_actions', array( $this, 'post_submitbox_misc_actions' ) );
		
		add_action( 'save_post', array( $this, 'save_post' ) );
	}

	/**
	 * Nothing to do here.
	 *
	 * @param string $name
	 *
	 * @return void
	 */
	public function update( $name ) {
	}

	/**
	 * Registers the module.
	 *
	 * @return bool
	 */
	private function register_setting() {

		return $this->module_manager->register( array(
			'description'  => __( 'Allows you to enable synchronisation of publish date, categories and tags between each related site. ' . 
								'If options are enabled and a published post is updated the publish date, ' . 
								'categories and tags are copied from the main currently edited post to the translated posts.', 'multilingual-press' ),
			'display_name' => __( 'Advanced Synchronisation', 'multilingual-press' ),
			'slug'         => 'class-' . __CLASS__,
			'state'        => 'off',
		) );
	}

	/**
	 * Deletes the according site option on module deactivation.
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public static function deactivate_module() {

		delete_site_option( 'inpsyde_multilingual_advanced_synchronisation_options' );
	}

	/**
	 * Displays the module options page form fields.
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public function draw_options_page_form_fields() {

		$data = new Mlp_Advanced_Synchronisation_Box_Data( $this->nonce_validator, $this->get_settings() );

		$box = new Mlp_Extra_General_Settings_Box( $data );
		$box->print_box();
	}

	/**
	 * Saves module user input.
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public function save_options_page_form_fields() {

		if ( ! $this->nonce_validator->is_valid() ) {
			return;
		}

		// Get current site options
		$options = get_site_option( 'inpsyde_multilingual_advanced_synchronisation_options' );

		$value = false;
		
		$settings = $this->get_settings();
		$options    = (array) get_site_option( 'inpsyde_multilingual_advanced_synchronisation_options' );

		foreach ( $settings as $setting ) {
			if ( empty( $_POST[ $this->form_name ][ $setting['name'] ]  ) ) {
				$value = true;
				$options['mlp_advanced_synchronisation_extra_' . $setting['name']] = false;
			} else {
				$options['mlp_advanced_synchronisation_extra_' . $setting['name']] = true;
			}
		}
				
		update_site_option( 'inpsyde_multilingual_advanced_synchronisation_options', $options );
	}
	
	/**
	 * Returns the keys and labels for the positions.
	 *
	 * @return string[]
	 */
	private function get_settings() {

		$out = array();
	
		// Prepare post array
		/*$allow_publish_remote = array(
			'name' 			=> 'allow_publish_remote',
			'display_name'  => 'Publish remote content.',
			'description'   => 'Adds a Publish checkbox on the translated posts to allow publish from current blog on remote blog.',
		);*/

		$sync_publish_date = array(
			'name' 			=> 'sync_publish_date',
			'display_name'  => 'Synchronise publish date',
			'description'   => 'Copies the publish date of the current post and applys it to the translated post.',
		);

		$sync_terms = array(
			'name' 			=> 'sync_terms',
			'display_name'  => 'Synchronise taxonomies',
			'description'   => 'Copies the taxonomies (categories and tags) of the current post and applys it to the translated post. This features overwrites the Change-taxonomies feature.',
		);
		
        //$out[] = $allow_publish_remote;
		$out[] = $sync_publish_date;
		$out[] = $sync_terms;	
			
		return $out;
	}
	
	/**
	 * Displays the checkbox for the Trasher post meta.
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public function post_submitbox_misc_actions() {

		$post_id = absint( filter_input( INPUT_GET, 'post' ) );

		?>
			<?php wp_nonce_field( $this->nonce_validator->get_action(), $this->nonce_validator->get_name() ); ?>
		<?php
	}
		
	/**
	 * Updates the post meta.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return void
	 */
	public function save_post( $post_id ) {	
		//only execute on the current editor post
		if ( ms_is_switched() ) {
			return;
		}
	
		//Check if we have a valid nonce_validator (which is added in post_submitbox_misc_actions)
		if ( ! $this->nonce_validator->is_valid() ) {
			return;
		}

		// We're only interested in published posts at this time
		$post_status = get_post_status( $post_id );
		if ( ! in_array( $post_status, array( 'publish' ), true ) ) {
			return;
		}

		// The wp_insert_post() method fires the save_post action hook, so we have to avoid recursion.
		if ( $this->saved_post ) {
			return;
		} else {
			$this->saved_post = true;
		}

		//get relevant information from source blog and source post
		//this is going to be needed to updated remote posts
		$source_blog_id = get_current_blog_id();

		$post_date = get_the_date( 'Y-m-d H:i:s', $post_id );	
		$post_terms = wp_get_post_terms($post_id, array('category', 'post_tag'));
		
		//Get current settings for Advanced Synchronisation
		$advanced_synchronisation_extra_options = get_site_option( 'inpsyde_multilingual_advanced_synchronisation_options' );

        $sync_publish_date = false;
        $sync_terms = false;

		if ( ! empty( $advanced_synchronisation_extra_options['mlp_advanced_synchronisation_extra_sync_publish_date'] ) ) {
			$sync_publish_date = $advanced_synchronisation_extra_options['mlp_advanced_synchronisation_extra_sync_publish_date'];
		}
		
		if ( ! empty( $advanced_synchronisation_extra_options['mlp_advanced_synchronisation_extra_sync_terms'] ) ) {
			$sync_terms = $advanced_synchronisation_extra_options['mlp_advanced_synchronisation_extra_sync_terms'];
		}
		
		// Get linked posts
		$linked_posts = mlp_get_linked_elements( $post_id );
		foreach ( $linked_posts as $remote_blog_id => $remote_post_id ) {
			if ( $remote_blog_id !== $source_blog_id ) {
				$remote_blog_terms = $this->get_remote_terms($source_blog_id, $remote_blog_id, $post_terms);
				
				switch_to_blog( $remote_blog_id );
				
				if ( $sync_publish_date ) {
					$this->set_remote_publish_date( $remote_post_id, $post_date );
				}
				
                if ( $sync_terms ) {			
					$this->set_remote_terms( $remote_post_id, $remote_blog_terms );
                }
								
				restore_current_blog();
			}
		}
	}
	
	/**
	 * Set publish date of remote post to same date as currently edited/saved post
	 * Has to be used after switch_to_blog
	 *
	 * @author  fefe
     * @version 2018.06.20
	 * @param  int      $new_id
	 * @param  string   $new_date
	 *
	 * @return int|WP_Error
	 */
	private function set_remote_publish_date( $new_id, $new_date ) {
		
		// Only update if remote post has already been published
		if ( get_post_status ( $new_id ) == 'publish' ) {
			
			// Prepare post array
			$post = array(
				'ID'           => $new_id,
				'post_date'    => $new_date,
			);
		
			return wp_update_post($post);
			
		} else {
			return $new_id;
		}
	}
	
	/**
	 * Return the current terms taxonomy ID and taxonomy for the given site and the given term ID in the current site.
	 * Has to be used before switch_to_blog
	 *
	 * @author  fefe
     * @version 2018.06.22
	 * @param int $source_blog_id Blog ID.
	 * @param int $remote_blog_id Blog ID.
	 * @param int $source_terms Terms of the currently edited term.
	 *
	 * @return array
	 */
	private function get_remote_terms( $source_blog_id, $remote_blog_id, array $source_terms ) {

		$out = array();

		foreach ( $source_terms as $source_term ) {

			$term_taxonomy_id = $this->content_relations->get_element_for_site(
				$source_blog_id,
				$remote_blog_id,
				$source_term->term_taxonomy_id,
				'term'
			);
			
			// Prepare post array
			$remote_term = array(
				'term_taxonomy_id' => $term_taxonomy_id,
				'taxonomy'         => $source_term->taxonomy,
			);

			$out[] = $remote_term;
		}
		
		if ( ! in_array( 'post_tag', array_column( $out, 'taxonomy' ) ) ) {
			$empty_term = array(
				'term_taxonomy_id' => '',
				'taxonomy'         => 'post_tag',
			);

			$out[] = $empty_term;
		}
		
		if ( ! in_array( 'category', array_column( $out, 'taxonomy' ) ) ) {
			$empty_term = array(
				'term_taxonomy_id' => '',
				'taxonomy'         => 'category',
			);

			$out[] = $empty_term;
		}
		
        return $out;
	}

	/**
	 * Return the current terms taxonomy ID and taxonomy for the given site and the given term ID in the current site.
	 * Has to be used after switch_to_blog
	 *
	 * @author  fefe
     * @version 2018.06.22
	 * @param  int      $new_id
	 * @param int $remote_blog_terms Terms of the currently edited term.
	 *
	 * @return int
	 */
	private function set_remote_terms( $new_id, array $remote_blog_terms ) {
		
		$taxonomies = array_values( array_unique( array_column( $remote_blog_terms, 'taxonomy' ), SORT_STRING ) );
		$return = 0;
		
		foreach ( $taxonomies as $taxonomy ) {
			$term_taxonomy_ids = array_column( array_filter( $remote_blog_terms, function ( $var ) use ( $taxonomy ) {
														return ( $var['taxonomy'] == $taxonomy );
														} ), 'term_taxonomy_id' );
														
			$return_set_object_termps = wp_set_object_terms( $new_id, $term_taxonomy_ids, $taxonomy );
			
			if ( is_wp_error( $return_set_object_termps ) ) {
				// There was an error somewhere and the terms couldn't be set.
				$return = 1;
			}
		}
	}
}
