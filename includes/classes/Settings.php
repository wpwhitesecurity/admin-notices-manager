<?php
/**
 * Contains class Settings.
 *
 * @package AdminNoticesManager
 */

namespace AdminNoticesManager;

/**
 * Takes care of the admin notices content capture.
 *
 * @package AdminNoticesManager
 * @since   1.0.0
 */
class Settings {

	/**
	 * Name of the option storing the plugin settings.
	 *
	 * @var string
	 */
	private static $option_name = 'anm_settings';

	/**
	 * Settings constructor.
	 */
	public function __construct() {

		$options = self::get_settings();

		if ( ! class_exists( 'RationalOptionPages' ) ) {
			require_once ADMIN_NOTICES_MANAGER_INC . 'vendor' . DIRECTORY_SEPARATOR . 'jeremyHixon-RationalOptionPages' . DIRECTORY_SEPARATOR . 'RationalOptionPages.php';
		}

		$notice_handling_options = array(
			'popup-only' => esc_html__( 'hide from the WordPress dashboard and show them in the plugin\'s popup', 'admin-notices-manager' ),
			'hide'       => esc_html__( 'hide them completely (do not show in the WordPress dashboard or in the plugin\'s popup)', 'admin-notices-manager' ),
			'leave'      => esc_html__( 'do not do anything (they will appear on the WordPress dashboard as per usual)', 'admin-notices-manager' ),
		);

		$system_notices_options = $notice_handling_options;
		unset( $system_notices_options['hide'] );

		$standard_notices = array(
			'success' => esc_html__( 'Success level notices', 'admin-notices-manager' ),
			'error'   => esc_html__( 'Error level notices', 'admin-notices-manager' ),
			'warning' => esc_html__( 'Warning level notices', 'admin-notices-manager' ),
			'info'    => esc_html__( 'Information level notices', 'admin-notices-manager' ),
		);

		$standard_notices_section_fields = array();
		foreach ( $standard_notices as $notice_type => $notice_field_title ) {
			$field_name                                     = $notice_type . '-notices';
			$standard_notices_section_fields[ $field_name ] = array(
				'title'   => $notice_field_title,
				'type'    => 'radio',
				'value'   => array_key_exists( $field_name, $options ) ? $options[ $field_name ] : 'popup-only',
				'choices' => $notice_handling_options,
			);
		}

		$popup_style_options = array(
			'slide-in' => esc_html__( 'Slide in from the right', 'admin-notices-manager' ),
			'popup'    => esc_html__( 'Popup', 'admin-notices-manager' ),
		);

		$pages = array(
			self::$option_name => array(
				'menu_title'  => esc_html__( 'Admin Notices', 'admin-notices-manager' ),
				'parent_slug' => 'options-general.php',
				'page_title'  => esc_html__( 'Admin notices settings', 'admin-notices-manager' ),
				'text'        => 'Use the settings in this page to configure how the plugin should handle different types of admin notices. Refer to the introduction to admin notices for a detailed explanation about the different types of admin notices available in WordPress.',
				'sections'    => array(
					'standard-notices'     => array(
						'title'  => esc_html__( 'Standard admin notices', 'admin-notices-manager' ),
						'fields' => $standard_notices_section_fields,
					),
					'non-standard-notices' => array(
						'title'  => esc_html__( 'Non-Standard admin notices', 'admin-notices-manager' ),
						'text'   => esc_html__( 'These type of admin notices are typically created by third party plugins and themes and do not have any severity level. Use the below settings to configure how the plugin should handle these type of admin notices.', 'admin-notices-manager' ),
						'fields' => array(
							'no-level-notices' => array(
								'title'   => esc_html__( 'No level notices', 'admin-notices-manager' ),
								'type'    => 'radio',
								'value'   => array_key_exists( 'no-level-notices', $options ) ? $options['no-level-notices'] : 'popup-only',
								'choices' => $notice_handling_options,
							),
							'exceptions'       => array(
								'title' => esc_html__( 'CSS selector', 'admin-notices-manager' ),
								'type'  => 'text',
								'value' => array_key_exists( 'exceptions-css-selector', $options ) ? $options['exceptions-css-selector'] : '',
								'text'  => esc_html__( 'Plugin will ignore all notices matching this CSS selector. Use jQuery compatible CSS selector. You can specify multiple selectors and comma separate them.', 'admin-notices-manager' ),
							),
						),
					),
					'system-notices'       => array(
						'title'  => esc_html__( 'WordPress system admin notices', 'admin-notices-manager' ),
						'text'   => esc_html__( 'These type of admin notices are used by WordPress to advise you about the status of specific actions, for example to confirm that the changed settings were saved, or that a plugin was successfully installed. It is recommended to let these admin notices appear in the WordPress dashboard.', 'admin-notices-manager' ),
						'fields' => array(
							'system-level-notices' => array(
								'title'   => esc_html__( 'WordPress system admin notices', 'admin-notices-manager' ),
								'type'    => 'radio',
								'value'   => array_key_exists( 'system-level-notices', $options ) ? $options['system-level-notices'] : 'leave',
								'choices' => $system_notices_options,
							),
						),
					),
					'user-visibility'      => array(
						'title'  => esc_html__( 'Hiding notifications', 'admin-notices-manager' ),
						'text'   => esc_html__( 'Plugin can hide the notifications from specific users or display them only to certain selected users. Use the below settings to configure this behaviour.', 'admin-notices-manager' ),
						'fields' => array(
							'user-visibility' => array(
								'title'    => esc_html__( 'Visibility', 'admin-notices-manager' ),
								'type'     => 'radio',
								'custom'   => true,
								'callback' => array( $this, 'render_user_visibility_field' ),
								'value'    => array_key_exists( 'user-visibility', $options ) ? $options['user-visibility'] : 'all',
								'choices'  => array(
									'all'               => esc_html__( 'Hide notifications from all users', 'admin-notices-manager' ),
									'hide-for-selected' => esc_html__( 'Hide notifications only from these users', 'admin-notices-manager' ),
									'show-for-selected' => esc_html__( 'Hide notifications to all users but not these', 'admin-notices-manager' ),
								),
								'sanitize' => false, // Stops default sanitization. It would break the data.
							),
						),
					),
					'styling'              => array(
						'title'  => esc_html__( 'Admin notices popup styling', 'admin-notices-manager' ),
						'text'   => esc_html__( 'How do you want ANM to look?', 'admin-notices-manager' ),
						'fields' => array(
							'popup-style'         => array(
								'title'   => esc_html__( 'Popup style', 'admin-notices-manager' ),
								'type'    => 'radio',
								'value'   => array_key_exists( 'popup-style', $options ) ? $options['popup-style'] : 'slide-in',
								'choices' => $popup_style_options,
							),
							'slide_in_background' => array(
								'title' => esc_html__( 'Slide in background colour', 'admin-notices-manager' ),
								'type'  => 'color',
								'value' => array_key_exists( 'popup-style', $options ) ? $options['popup-style'] : '#1d2327',
							),
						),
					),
				),
			),
		);

		new \RationalOptionPages( $pages );
	}

	/**
	 * Retrieve plugin settings.
	 *
	 * @return array
	 */
	public static function get_settings() {
		return wp_parse_args(
			get_option( self::$option_name, array() ),
			array(
				'success_level_notices'          => 'popup-only',
				'error_level_notices'            => 'popup-only',
				'warning_level_notices'          => 'popup-only',
				'information_level_notices'      => 'popup-only',
				'no_level_notices'               => 'popup-only',
				'wordpress_system_admin_notices' => 'leave',
				'popup_style'                    => 'slide-in',
				'slide_in_background'            => '#1d2327',
				'exceptions_css_selector'        => '',
			)
		);
	}

	/**
	 * Renders custom user visibility field(s).
	 *
	 * @param array               $field        Field data.
	 * @param string              $page_key     Settings page key.
	 * @param string              $section_key  Settings section key.
	 * @param string              $field_key    Field key.
	 * @param RationalOptionPages $option_pages Rational option pages object.
	 *
	 * @since latest
	 */
	public function render_user_visibility_field( $field, $page_key, $section_key, $field_key, $option_pages ) {
		if ( ! class_exists( '\S24WP' ) ) {
			return;
		}

		echo '<fieldset><legend class="screen-reader-text">' . $field['title'] . '</legend>';

		$options        = $option_pages->get_options();
		$field['value'] = $options[ $field['id'] ]['choice'];

		$counter = 0;
		foreach ( $field['choices'] as $value => $label ) {
			$checked = 0 === strlen( $value ) || $value === $field['value'] ? 'checked' : '';
			if ( isset( $this->options[ $field['id'] ] ) ) {
				$checked = $value === $this->options[ $field['id'] ] ? 'checked' : '';
			}

			$field_name = "{$page_key}[{$field['id']}]";
			printf(
				'<label><input %s %s id="%s" name="%s" type="radio" title="%s" value="%s">&nbsp; %s</label>',
				$checked,
				! empty( $field['class'] ) ? "class='{$field['class']}'" : '',
				$field['id'] . '-' . $value,
				$field_name . '[choice]',
				$label,
				$value,
				$label
			);

			echo '<br />';

			if ( 'all' === $value ) {
				continue;
			}

			if ( 'hide-for-selected' === $value ) {
				\S24WP::insert(
					array(
						'placeholder'       => 'hide these',
						'name'              => $field_name . '[hide-users][]',
						'width'             => 500,
						'data-type'         => 'user',
						'multiple'          => true,
						'selected'          => $options[ $field['id'] ]['hide-users'],
						'extra_js_callback' => function ( $element_id ) {
							echo 'window.anm_settings.append_select2_events( s2 );';
						},
					)
				);
			} elseif ( 'show-for-selected' === $value ) {
				\S24WP::insert(
					array(
						'placeholder'       => 'show these',
						'name'              => $field_name . '[show-users][]',
						'width'             => 500,
						'data-type'         => 'user',
						'multiple'          => true,
						'selected'          => $options[ $field['id'] ]['show-users'],
						'extra_js_callback' => function ( $element_id ) {
							echo 'window.anm_settings.append_select2_events( s2 );';
						},
					)
				);
			}

			echo $counter < count( $field['choices'] ) - 1 ? '<br>' : '';
			$counter ++;
		}
		echo '</fieldset>';
	}
}
