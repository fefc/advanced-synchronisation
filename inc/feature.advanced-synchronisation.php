<?php # -*- coding: utf-8 -*-
add_action( 'mlp_and_wp_loaded', 'mlp_feature_advanced_synchronisation' );

/**
 * Init the advanced synchronisation filter routine.
 *
 * @author  fefe
 * @version 2018.06.23
 * @param Inpsyde_Property_List_Interface $data
 * @return void
 */
function mlp_feature_advanced_synchronisation( Inpsyde_Property_List_Interface $data ) {
	$controller = new Mlp_Advanced_Synchronisation(
													$data->get( 'module_manager' ),
													$data->get( 'content_relations' )
	);
	
	$controller->initialize();
}
