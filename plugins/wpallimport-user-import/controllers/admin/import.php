<?php

if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Import configuration wizard
 * 
 * @author Max Tsiplyakov <makstsiplyakov@gmail.com>
 */

class PMUI_Admin_Import extends PMUI_Controller_Admin {

	protected function withDefaultOptions( $post ) {

		$defaults = PMUI_Plugin::get_default_import_options();

		if ( method_exists('PMXI_Plugin', 'get_default_import_options') ) {
			$defaults = array_merge( PMXI_Plugin::get_default_import_options(), $defaults );
		}

		return array_merge( $defaults, (array) $post );
	}

	public function index( $post = array() ) {

		$post = $this->withDefaultOptions( $post );

		$this->data['post'] =& $post;

		switch ($post['custom_type'])
		{
			case 'import_users':
				$this->render('admin/import/import_users/index');
				break;

			default:
				# code...
				break;
		}

	}	
	
	public function options( $isWizard = false, $post = array() ){

		$post = $this->withDefaultOptions( $post );

		$this->data['isWizard'] = $isWizard;

		$this->data['post'] =& $post;

		$this->render();
	}

    public function confirm( $isWizard = false, $post = array() ){

        $post = $this->withDefaultOptions( $post );

        $this->data['isWizard'] = $isWizard;

        $this->data['post'] =& $post;

        $this->render();
    }
}
