<?php # -*- coding: utf-8 -*-

/**
 * Provides data for the configuration on the MultilingualPress network settings page.
 * @author  fefe
 * @version 2018.06.23
 */ 
class Mlp_Advanced_Synchronisation_Box_Data implements Mlp_Extra_General_Settings_Box_Data_Interface {

	/**
	 * Prefix for 'name' attribute in form fields.
	 *
	 * @var string
	 */
	private $form_name = 'mlp-advanced-synchronisation-extra';

	/**
	 * @var Inpsyde_Nonce_Validator_Interface
	 */
	private $nonce_validator;

    private $settings = array();

	/**
	 * Constructor. Sets up the properties.
	 *
	 * @param Inpsyde_Nonce_Validator_Interface $nonce_validator Nonce validator object.
	 */
	public function __construct( Inpsyde_Nonce_Validator_Interface $nonce_validator, array $settings ) {

		$this->nonce_validator = $nonce_validator;
        
        $this->settings = $settings;
	}

	/**
	 * Returns the box title.
	 *
	 * Will be wrapped in h4 tags by the view if it is not empty.
	 *
	 * @return string
	 */
	public function get_title() {

		return esc_html__( 'Advanced Synchronisation Settings', 'multilingual-press' );
	}

	/**
	 * Returns the box description.
	 *
	 * Will be enclosed in p tags by the view, so make sure the markup is valid afterwards.
	 *
	 * @return string
	 */
	public function get_main_description() {

		return 'Allows you to enable synchronisation of publish date, categories and tags between each related site. ' . 
								'If options are enabled and a published post is updated the publish date, ' . 
								'categories and tags are copied from the main currently edited post to the translated posts.';
	}

	/**
	 * Returns the ID used in the main form element.
	 *
	 * Used to wrap the description in a label element, so it is accessible for screen reader users.
	 *
	 * @return string
	 */
	public function get_main_label_id() {

		return '';
	}

	/**
	 * Returns the value for ID attribute for the box.
	 *
	 * @return string
	 */
	public function get_box_id() {

		return $this->form_name . '-setting';
	}

	/**
	 * @param string $name
	 *
	 * @return mixed|void Either a value, or void for actions.
	 */
	public function update( $name ) {

		if ( 'general.settings.extra.box' === $name ) {
			return $this->get_box_content();
		}

		return '';
	}

	/**
	 * Create the content for the extra box, a table with checkboxes.
	 *
	 * @return string
	 */
	private function get_box_content() {

		$options = (array) get_site_option( 'inpsyde_multilingual_advanced_synchronisation_options' );

		if ( empty( $this->settings ) ) {
			return '';
		}

		$out = wp_nonce_field(
			$this->nonce_validator->get_action(),
			$this->nonce_validator->get_name(),
			true,
			false
		);
		$out .= '<table><tbody>';

		foreach ( $this->settings as $setting ) {
			$out .= $this->get_row( $setting, $options );
		}

		return "$out</tbody></table>";
	}

	/**
	 * Create the table rows.
	 *
	 * @param string   $setting
	 * @param array    $options
	 * @return string
	 */
	private function get_row( $setting, array $options ) {

		$id = 'mlp_advanced_synchronisation_extra_' . $setting['name'];

		if ( empty( $options[ $id ] ) ) {
			$active = 0;
		} else {
			$active = (bool) $options[ $id ];
		}

		$check_use = $this->get_checkbox(
			$this->form_name . '[' . $setting['name'] . ']',
			$id,
			$active
		);

		$name = $setting['display_name'];
        $description = $setting['description'];
		
		return "<tr>
			<td>
				<label for='$id' class='mlp-block-label'>
					$check_use
					$name
				</label>
			</td>
			<td>
			<span class='description'>
			$description
			</span>
			</td>
		</tr>";
	}
	
	/**
	 * Checkbox view
	 *
	 * @param  string $name
	 * @param  string $id
	 * @param  bool   $checked
	 * @return string
	 */
	private function get_checkbox( $name, $id, $checked ) {

		return sprintf(
			'<input type="checkbox" value="1" name="%1$s" id="%2$s"%3$s> ',
			esc_attr( $name ),
			esc_attr( $id ),
			checked( (bool) $checked, true, false )
		);
	}
}
