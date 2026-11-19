<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class PMUI_Import_Record extends PMUI_Model_Record {		

	/**
	 * Associative array of data which will be automatically available as variables when template is rendered
	 * @var array
	 */
	public $data = array();

	public $parsing_data = array();

	/**
	 * Initialize model instance
	 * @param array[optional] $data Array of record data to initialize object with
	 */
	public function __construct($data = array()) { 
		parent::__construct($data);
		$this->setTable(PMXI_Plugin::getInstance()->getTablePrefix() . 'imports');
	}

	/**
	 * Perform import operation
	 *
	 * @param array $parsing_data - $import, $count, $xml, $logger = NULL, $chunk = false, $xpath_prefix = ""
	 *
	 * @return array|false
	 * @throws XmlImportException
	 * @chainable
	 */
	public function parse($parsing_data = array()) {

		add_filter('user_has_cap', array($this, '_filter_has_cap_unfiltered_html')); kses_init(); // do not perform special filtering for imported content

		$cxpath = $parsing_data['xpath_prefix'] . $parsing_data['import']->xpath;

		$this->data = array();
		$records    = array();
		$tmp_files  = array();

		$xml = $parsing_data['xml'];
	
		if ( !empty($parsing_data['import']->options['pmui']['import_users']) ) {

			$parsing_data['chunk'] == 1 and $parsing_data['logger'] and call_user_func($parsing_data['logger'], __('Composing users...', 'wpallimport-user-import'));

			if ( ! empty($parsing_data['import']->options['pmui']['login']) ) {
				$this->data['pmui_logins'] = XmlImportParser::factory($xml, $cxpath, $parsing_data['import']->options['pmui']['login'], $file)->parse($records); $tmp_files[] = $file;				
			} else {
				$parsing_data['count'] and $this->data['pmui_logins'] = array_fill(0, $parsing_data['count'], '');
			}

			$user_options = ['pass', 'nicename', 'email', 'display_name', 'url', 'first_name', 'last_name', 'description', 'nickname'];
			foreach ( $user_options as $user_option ) {
				if ( ! empty($parsing_data['import']->options['pmui'][$user_option]) ) {
					$this->data['pmui_' . $user_option] = XmlImportParser::factory($xml, $cxpath, $parsing_data['import']->options['pmui'][$user_option], $file)->parse($records); $tmp_files[] = $file;
				} else {
					$parsing_data['count'] and $this->data['pmui_' . $user_option] = array_fill(0, $parsing_data['count'], '');
				}
			}

			if ( ! empty($parsing_data['import']->options['pmui']['registered']) ) {
				$this->data['pmui_registered'] = XmlImportParser::factory($xml, $cxpath, $parsing_data['import']->options['pmui']['registered'], $file)->parse($records); $tmp_files[] = $file;				
				$warned = array(); // used to prevent the same notice displaying several times
				foreach ($this->data['pmui_registered'] as $i => $d) {
					if ($d == 'now') $d = current_time('mysql'); // Replace 'now' with the WordPress local time to account for timezone offsets (WordPress references its local time during publishing rather than the server’s time so it should use that)
					$time = strtotime($d);
					if (FALSE === $time) {
						// translators: %s is the unrecognized date value from the import file.
						in_array($d, $warned) or $parsing_data['logger'] and call_user_func($parsing_data['logger'], '<b>' . pmui_log_line_label('warning') . '</b>: ' . sprintf(__('unrecognized date format `%s`, assigning current date', 'wpallimport-user-import'), $warned[] = $d));
						$time = time();
					}
					$this->data['pmui_registered'][$i] = gmdate('Y-m-d H:i:s', $time);
				}
			} else {
				$parsing_data['count'] and $this->data['pmui_registered'] = array_fill(0, $parsing_data['count'], '');
			}

			if ( ! empty($parsing_data['import']->options['pmui']['role']) ) {
				if ( $parsing_data['import']->options['pmui']['role'] == 'xpath' ) {
				    if( ! empty($parsing_data['import']->options['pmui']['role_xpath']) ) {
                        $this->data['pmui_role'] = XmlImportParser::factory($xml, $cxpath, $parsing_data['import']->options['pmui']['role_xpath'], $file)->parse($records);
                        $tmp_files[] = $file;
                    } else{
                        $parsing_data['count'] and $this->data['pmui_role'] = array_fill(0, $parsing_data['count'], '');
                    }
				} else {
					$this->data['pmui_role'] = XmlImportParser::factory($xml, $cxpath, $parsing_data['import']->options['pmui']['role'], $file)->parse($records); $tmp_files[] = $file;
				}
			} else {
				$parsing_data['count'] and $this->data['pmui_role'] = array_fill(0, $parsing_data['count'], '');
			}

		}

		remove_filter('user_has_cap', array($this, '_filter_has_cap_unfiltered_html')); kses_init(); // return any filtering rules back if they has been disabled for import procedure

		foreach ($tmp_files as $file) { // remove all temporary files created
			wp_delete_file($file);
		}

		$this->data = apply_filters('pmui_parsed_data', $this->data, $parsing_data['import'], $parsing_data);

		return $this->data;
	}

	public function import($importData = array()){ //$pid, $i, $import, $articleData, $xml, $is_cron = false, $xpath_prefix = ""
		if ( !in_array($importData['import']->options['custom_type'], array('import_users', 'shop_customer')) ) return;

        // import multiple user roles
        if (
            // This is a new user
            (empty($importData['articleData']['ID'])
                and
                !empty($importData['articleData']['role'])
            )
            or
            // This is an existing user, and we can update the role
            ($importData['import']->options['is_update_role'] == '1'
                and
                !empty($importData['articleData']['role']) // no need to run as default will already be set
            )
        ) {
            $roles_to_import = explode("|", $importData['articleData']['role']);
            $roles_array = array();
            foreach ($roles_to_import as $key => $value) {
                $roles_array[trim($value)] = true;
            }
            update_user_meta($importData['pid'], $this->wpdb->prefix . 'capabilities', $roles_array);
        }

		if (
			// This is a new user
	    	(empty($importData['articleData']['ID'])
			// The 'is_hashed_wordpress_password' variable is present
	    	and isset($importData['import']->options['is_hashed_wordpress_password'])
			// The 'is_hashed_wordpress_password' option is enabled
			and $importData['import']->options['is_hashed_wordpress_password'] == '1')
			or
			// This is an existing user, and we can update the password
			($importData['import']->options['is_update_password'] == '1'
			// The 'is_hashed_wordpress_password' variable is present
			and isset($importData['import']->options['is_hashed_wordpress_password'])
			// The 'is_hashed_wordpress_password' option is enabled
			and $importData['import']->options['is_hashed_wordpress_password'] == '1')
    	){

			$user_pass_hash = isset($importData['articleData']['user_pass']) ? $importData['articleData']['user_pass'] : '';

			// A blank hash would lock the user out; keep whatever hash is already stored.
			if ('' === trim((string) $user_pass_hash)) return;

			global $wpdb;
			$table = $wpdb->users;
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- already hashed password must be written directly to the users table, table name comes from WPDB, values go through prepare()
			$wpdb->query( $wpdb->prepare( "UPDATE `{$table}` SET `user_pass` = %s WHERE `ID` = %d", $user_pass_hash, $importData['pid'] ) );

			clean_user_cache( $importData['pid'] );
		}
	}

	public function _filter_has_cap_unfiltered_html($caps) {
		$caps['unfiltered_html'] = true;
		return $caps;
	}
	
	public function filtering($var){
		return ("" == $var) ? false : true;
	}		
}
