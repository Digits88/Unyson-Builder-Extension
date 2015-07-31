<?php if (!defined('FW')) die('Forbidden');

class FW_Ext_Builder_Templates_Component_Full extends FW_Ext_Builder_Templates_Component
{
	public function get_type()
	{
		return 'full';
	}

	public function _render($data)
	{
		$html = '';

		foreach ($this->get_templates($data['builder_type']) as $template_id => $template) {
			$html .=
				'<li>'
					. '<a href="#" onclick="return false;" data-delete-template="'. fw_htmlspecialchars($template_id) .'"'
					. ' class="template-delete dashicons fw-x"></a>'
					. '<a href="#" onclick="return false;" data-load-template="'. fw_htmlspecialchars($template_id) .'"'
					. ' class="template-title">'
						. fw_htmlspecialchars($template['title'])
					. '</a>'
				. '</li>';
		}

		if (empty($html)) {
			$html = '<div class="fw-text-muted">'. __('No Builder Templates Saved', 'fw') .'</div>';
		} else {
			$html =
				'<p class="fw-text-muted load-template-title">'. __('Load Builder Template', 'fw') .':</p>'
				. '<ul>'. $html .'</ul>';
		}

		$html =
			'<div class="save-template-wrapper">'
			. '<a href="#" onclick="return false;" class="save-template">'. __('Save Builder Template', 'fw') .'</a>'
			. '</div>'
			. $html;

		return $html;
	}

	public function _enqueue()
	{
		$uri = fw_ext('builder')->get_uri('/includes/option-types/builder/includes/templates/components/full');
		$version = fw_ext('builder')->manifest->get_version();

		wp_enqueue_style(
			'fw-option-builder-templates-full',
			$uri .'/styles.css',
			array('fw-option-builder-templates'),
			$version
		);

		wp_enqueue_script(
			'fw-option-builder-templates-full',
			$uri .'/scripts.js',
			array('fw-option-builder-templates'),
			$version,
			true
		);

		wp_localize_script(
			'fw-option-builder-templates-full',
			'_fw_option_type_builder_templates_full',
			array(
				'l10n' => array(
					'template_name' => __('Template Name', 'fw'),
					'save_template' => __('Save Builder Template', 'fw'),
				),
			)
		);
	}

	public function _init()
	{
		add_action('wp_ajax_fw_builder_templates_full_load',   array($this, '_action_ajax_load_template'));
		add_action('wp_ajax_fw_builder_templates_full_save',   array($this, '_action_ajax_save_template'));
		add_action('wp_ajax_fw_builder_templates_full_delete', array($this, '_action_ajax_delete_template'));
	}

	private function get_templates($builder_type)
	{
		return fw_get_db_extension_data('builder', 'templates/'. $builder_type, array());
	}

	private function set_templates($builder_type, $templates)
	{
		fw_set_db_extension_data('builder', 'templates/'. $builder_type, $templates);
	}

	/**
	 * @internal
	 */
	public function _action_ajax_load_template()
	{
		if (!current_user_can('edit_posts')) {
			wp_send_json_error();
		}

		$builder_type = (string)FW_Request::POST('builder_type');

		if (!$this->builder_type_is_valid($builder_type)) {
			wp_send_json_error();
		}

		$templates = $this->get_templates($builder_type);

		$template_id = (string)FW_Request::POST('template_id');

		if (!isset($templates[$template_id])) {
			wp_send_json_error();
		}

		wp_send_json_success(array(
			'json' => $templates[$template_id]['json']
		));
	}

	/**
	 * @internal
	 */
	public function _action_ajax_save_template()
	{
		if (!current_user_can('edit_posts')) {
			wp_send_json_error();
		}

		$builder_type = (string)FW_Request::POST('builder_type');

		if (!$this->builder_type_is_valid($builder_type)) {
			wp_send_json_error();
		}

		$template = array(
			'title' => trim((string)FW_Request::POST('template_name')),
			'json' => trim((string)FW_Request::POST('builder_json'))
		);

		if (
			empty($template['json'])
			||
			($decoded_json = json_decode($template['json'], true)) === null
		) {
			wp_send_json_error();
		}

		unset($decoded_json);

		if (empty($template['title'])) {
			$template['title'] = __('No Title', 'fw');
		}

		$templates = $this->get_templates($builder_type);

		$template_id = md5($template['json']);
		$templates[$template_id] = $template;

		$this->set_templates($builder_type, $templates);

		wp_send_json_success();
	}

	/**
	 * @internal
	 */
	public function _action_ajax_delete_template()
	{
		if (!current_user_can('edit_posts')) {
			wp_send_json_error();
		}

		$builder_type = (string)FW_Request::POST('builder_type');

		if (!$this->builder_type_is_valid($builder_type)) {
			wp_send_json_error();
		}

		$templates = $this->get_templates($builder_type);

		$template_id = (string)FW_Request::POST('template_id');

		if (!isset($templates[$template_id])) {
			wp_send_json_error();
		}

		unset($templates[$template_id]);

		$this->set_templates($builder_type, $templates);

		wp_send_json_success();
	}
}
