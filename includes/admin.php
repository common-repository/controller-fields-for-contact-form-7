<?php

namespace AuRise\Plugin\ControllerFields;

defined('ABSPATH') || exit; // Exit if accessed directly

class Controller
{
    private array $tags;

    function __construct()
    {
        $this->tags = array(
            'select_controller' => array(
                'label' => __('select controller', 'controller-fields-for-contact-form-7'),
                'callback' => false,
                'options' => array()
            ),
            'checkbox_controller' => array(
                'label' => __('checkbox controllers', 'controller-fields-for-contact-form-7'),
                'callback' => false,
                'options' => array()
            ),
            'radio_controller' => array(
                'label' => __('radio button controllers', 'controller-fields-for-contact-form-7'),
                'callback' => false,
                'options' => array()
            ),
            'number_controller' => array(
                'label' => __('number spinbox controller', 'controller-fields-for-contact-form-7'),
                'callback' => false,
                'options' => array()
            ),
            'range_controller' => array(
                'label' => __('range slider controller', 'controller-fields-for-contact-form-7'),
                'callback' => false,
                'options' => array()
            ),
            'controlled_wrapper' => array(
                'label' => __('controlled form tag wrapper', 'controller-fields-for-contact-form-7'),
                'callback' =>  array($this, 'controlled'),
                'options' => array()
            ),
        );
        add_action('admin_enqueue_scripts', array($this, 'load_assets'));
        add_action('wpcf7_admin_init', array($this, 'add_tag_generators'), 100);
    }

    /**
     * Admin Scripts and Styles
     *
     * Enqueue scripts and styles to be used on the admin pages
     *
     * @since 3.1.0
     *
     * @param string $hook Hook suffix for the current admin page
     */
    public function load_assets($hook)
    {
        //Only load on CF7 Form pages
        if ($hook == 'toplevel_page_wpcf7') {
            $prefix = 'au-cf7-controllable-fields';
            $url = plugin_dir_url(CF7_CONTROLLERFIELDS_FILE);
            $path = plugin_dir_path(CF7_CONTROLLERFIELDS_FILE);
            $min = au_cf7_cf_get_minified();

            wp_enqueue_style(
                $prefix . 'admin', //Handle
                $url . "assets/styles/tag-generator{$min}.css", //Source
                array('contact-form-7-admin'), //Dependencies
                $min ? CF7_CONTROLLERFIELDS_VERSION : @filemtime($path . "assets/styles/tag-generator{$min}.css") //Version
            );

            //Plugin Scripts
            wp_enqueue_script(
                $prefix . 'taggenerator', //Handle
                $url . "assets/scripts/tag-generator{$min}.js", //Source
                array('jquery', 'wpcf7-admin-taggenerator'), //Dependencies
                $min ? CF7_CONTROLLERFIELDS_VERSION : @filemtime($path . "assets/scripts/tag-generator{$min}.js"), //Version
                array('in_footer' => true, 'strategy' => 'defer')
            );
        }
    }


    /**
     * Create Tag Generators
     *
     * @return void
     */
    public function add_tag_generators()
    {
        if (!class_exists('WPCF7_TagGenerator')) {
            return;
        }
        /** @var \WPCF7_TagGenerator $tag_generator */
        $tag_generator = \WPCF7_TagGenerator::get_instance();
        foreach ($this->tags as $id => $tag) {
            $tag_generator->add(
                $id, // id
                $tag['label'], // title
                $tag['callback'] === false ? array($this, 'controller') : $tag['callback'], //callback
                $tag['callback'] === false && !count($tag['options']) ? array(
                    'name',
                    'default',
                    'defaultvalue',
                    'placeholder',
                    'readonly',
                    'disabled',
                    'required',
                    'label_first',
                    'use_label_element',
                    'min',
                    'max',
                    'step'
                ) : $tag['options'] // options
            );
        }
    }


    /**
     * Echo HTML for Controller Tag Generator
     *
     * @param \WPCF7_ContactForm $contact_form
     * @param array $options
     *
     * @return void
     */
    public function controller($contact_form, $options = '')
    {
        $options = wp_parse_args($options);
        $type = str_replace('_controller', '', $options['id']);
        $utm_source = urlencode(home_url());

        $html = $this->open(
            __('Generate a "controller" form tag that can hide/show other elements in the form.', 'controller-fields-for-contact-form-7')
        );

        //Input field - Required checkbox
        $html .= $this->input(
            $options['content'], // prefix
            'required', // field name
            __('Field type', 'controller-fields-for-contact-form-7'), // label
            array(), // attributes
            'checkbox', // type
            __('Required field', 'controller-fields-for-contact-form-7') // description
        );

        //Input field - Field Name
        $html .= $this->input(
            $options['content'], // prefix
            'name', // field name
            __('Name', 'controller-fields-for-contact-form-7'), // label
            array('class' => 'oneline'), // attributes, additional classes
            'text', // field type
            __("This is the controller's name attribute, use this value when creating fields controlled by checkboxes and radio buttons.")
        );

        //Input field - ID attribute
        $html .= $this->input(
            $options['content'], // prefix
            'id', // field name
            __('Id attribute', 'controller-fields-for-contact-form-7'), // label
            array('class' => 'idvalue oneline option'), // attributes, additional classes
            'text', // field type
            __("This is the controller's id attribute, use this value when creating fields controlled by drop-down menus and numbers.", 'controller-fields-for-contact-form-7'), // description
        );

        //Input field - Options / Dynamic value
        $html .= $this->input(
            $options['content'], // prefix
            'values', // field name
            __('Options', 'controller-fields-for-contact-form-7'), // label
            // attributes
            array(
                'class' => 'values oneline minheight', // additional classes
                'placeholder' => "Option 1&#10;option_2 | Option 2" // placeholder attribute
            ),
            'textarea', // field type
            __('Can be static text or a shortcode. If static text, put one option per line. If using a shortcode, it should output the option or option group HTML.', 'controller-fields-for-contact-form-7'), // description
            false, // select options
            // Link args
            array(
                'url' => 'https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/', // link to documentation
                'label' =>  __('View DTX shortcode syntax documentation', 'controller-fields-for-contact-form-7'), // link label
                'utm_source' => $utm_source // UTM source
            )
        );

        //Input field - Default
        $html .= $this->input(
            $options['content'], // prefix
            'defaultvalue', // field name
            __('Default value', 'controller-fields-for-contact-form-7'), // label
            // attributes
            array(
                'class' => 'oneline au-cf-option', // additional classes
                'placeholder' => "CF7_get_post_var key='post_title'" // placeholder attribute
            ),
            'text', // field type
            __('Can be static text or a shortcode.', 'controller-fields-for-contact-form-7'), // description
            false, // select options
            // Link args
            array(
                'url' => 'https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/dtx-attribute-placeholder/', // link to documentation
                'label' =>  __('View DTX placeholder documentation', 'controller-fields-for-contact-form-7'), // link label
                'utm_source' => $utm_source // UTM source
            )
        );

        if ($type == 'select') {
            //Input field - Dynamic placeholder
            $html .= $this->input(
                $options['content'], // prefix
                'placeholder', // field name
                __('Placeholder', 'controller-fields-for-contact-form-7'), // label
                // attributes
                array(
                    'class' => 'oneline au-cf-option', // additional classes
                    'placeholder' => "CF7_get_post_var key='post_title'" // placeholder attribute
                ),
                'text', // field type
                __('Can be static text or a shortcode.', 'controller-fields-for-contact-form-7'), // description
                false, // select options
                // Link args
                array(
                    'url' => 'https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/shortcodes/dtx-attribute-placeholder/', // link to documentation
                    'label' =>  __('View DTX placeholder documentation', 'controller-fields-for-contact-form-7'), // link label
                    'utm_source' => $utm_source // UTM source
                )
            );
        } elseif ($type == 'checkbox' || $type == 'radio') {
            //Input field - Label First
            $html .= $this->input(
                $options['content'], // prefix
                'label_first', // field name
                __('Text first', 'controller-fields-for-contact-form-7'), // label
                array('class' => 'option'), // attributes, additional classes
                'checkbox', // field type
                __('Display the label text first followed by the checkbox', 'controller-fields-for-contact-form-7') // description
            );

            //Input field - Label First
            $html .= $this->input(
                $options['content'], // prefix
                'use_label_element', // field name
                __('Label UI', 'controller-fields-for-contact-form-7'), // label
                // attributes
                array(
                    'class' => 'option', // additional classes
                    'value' => 'on' // Default this box to be checked
                ),
                'checkbox', // field type
                __('Wrap each item with label element to make clicking easier', 'controller-fields-for-contact-form-7') // description
            );

            if ($type == 'checkbox') {
                //Input field - exclusive
                $html .= $this->input(
                    $options['content'], // prefix
                    'exclusive', // field name
                    __('Exclusive', 'controller-fields-for-contact-form-7'), // label
                    array('class' => 'option'), // attributes, additional classes
                    'checkbox', // field type
                    __('Mimic radio button functionality by clearing other checkboxes when one is selected.', 'controller-fields-for-contact-form-7') // description
                );
            }
        } elseif ($type == 'number' || $type == 'range' || $type == 'date') {
            $default_input_type = 'number';
            $default_placeholder = 'Foo';
            $step_option = '';
            if ($type == 'date') {
                $default_input_type = 'date';
                $default_description =  __('Optionally define the minimum and/or maximum date values.', 'controller-fields-for-contact-form-7') . ' ';
                $default_placeholder = 'hello-world_Foo';
            } else {
                $step_option = sprintf(
                    '&nbsp;<span class="wpcf7dtx-mini-att"><label for="%s">%s</label> <input %s /><input %s /></span>',
                    esc_attr($options['content'] . '-step'),
                    esc_html__('Step', 'controller-fields-for-contact-form-7'),
                    wpcf7_format_atts(array(
                        'type' => 'hidden',
                        'name' => 'step',
                        'class' => 'option'
                    )),
                    wpcf7_format_atts(array(
                        'type' => 'text',
                        'name' => 'au-cf-step',
                        'id' => $options['content'] . '-step',
                        'class' => 'au-cf-option',
                        'size' => 7
                    ))
                );
                $default_description =  __('Optionally define the minimum, maximum, and/or step values.', 'controller-fields-for-contact-form-7') . ' ';
            }
            $default_description .= __('Each can be static text or a shortcode.', 'controller-fields-for-contact-form-7');
            $html .= sprintf(
                '<tr><th scope="row"><label>%s</label></th><td><span class="wpcf7dtx-mini-att"><label for="%s">%s</label> <input %s /><input %s /></span> - <span class="wpcf7dtx-mini-att"><label for="%s">%s</label> <input %s /><input %s /></span>%s<br /><small>%s <a href="https://aurisecreative.com/docs/contact-form-7-dynamic-text-extension/dynamic-attributes/?utm_source=%s&utm_medium=link&utm_campaign=contact-form-7-dynamic-text-extension&utm_content=form-tag-generator-%s" target="_blank" rel="noopener">%s</a></small></td></tr>',
                esc_html__('Range', 'controller-fields-for-contact-form-7'), // field label
                esc_attr($options['content'] . '-min'),
                esc_html__('Min', 'controller-fields-for-contact-form-7'),
                wpcf7_format_atts(array(
                    'type' => 'hidden',
                    'name' => 'min',
                    'class' => 'option'
                )),
                wpcf7_format_atts(array(
                    'type' => 'text',
                    'name' => 'au-cf-min',
                    'id' => $options['content'] . '-min',
                    'class' => 'au-cf-option',
                    'size' => 7
                )),
                esc_attr($options['content'] . '-max'),
                esc_html__('Max', 'controller-fields-for-contact-form-7'),
                wpcf7_format_atts(array(
                    'type' => 'hidden',
                    'name' => 'max',
                    'class' => 'option'
                )),
                wpcf7_format_atts(array(
                    'type' => 'text',
                    'name' => 'au-cf-max',
                    'id' => $options['content'] . '-max',
                    'class' => 'au-cf-option',
                    'size' => 7
                )),
                $step_option, // Optional "step" option for numbers and ranges
                esc_html($default_description), // Small note below input
                esc_attr($utm_source), //UTM source
                esc_attr($type), //UTM content
                esc_html__('View DTX attributes documentation', 'controller-fields-for-contact-form-7') //Link label
            );
        }

        //Input field - Readonly attribute
        $html .= $this->input(
            $options['content'], // prefix
            'readonly', // field name
            __('Read only attribute', 'controller-fields-for-contact-form-7'), // row label
            array('class' => 'option'), // attributes, additional classes
            'checkbox', // field type
            __('Do not let users edit this field', 'controller-fields-for-contact-form-7') // checkbox label / field description
        );

        //Input field - Readonly attribute
        $html .= $this->input(
            $options['content'], // prefix
            'disabled', // field name
            __('Disabled attribute', 'controller-fields-for-contact-form-7'), // row label
            array('class' => 'option'), // attributes, additional classes
            'checkbox', // field type
            __('Do not submit this field in the form', 'controller-fields-for-contact-form-7') // checkbox label / field description
        );

        //Input field - Class attribute
        $html .= $this->input(
            $options['content'], // prefix
            'class', // field name
            __('Class attribute', 'controller-fields-for-contact-form-7'), // row label
            array('class' => 'classvalue oneline option') // attributes, additional classes

        );

        $html .= $this->close($options['id']);

        echo wp_kses($html, $this->allowed_html());
    }

    /**
     * Echo HTML for Controller Tag Generator
     *
     * @param WPCF7_ContactForm $contact_form
     * @param array $options
     *
     * @return void
     */
    public function controlled($contact_form, $options = '')
    {
        $options = wp_parse_args($options);
        $type = $options['id'];
        $utm_source = urlencode(home_url());

        $html = $this->open(__('Generate a "controlled" element wrapper that will be displayed or hidden based on a controller field. Insert your form tags inside the wrapper.', 'controller-fields-for-contact-form-7'));

        //Input field - Field Name
        $html .= $this->input(
            $options['content'], // prefix
            'name', // field name
            __('Controller', 'controller-fields-for-contact-form-7'), // label
            // attributes
            array(
                'class' => 'oneline', // additional classes
                'placeholder' => 'subject'
            ),
            'text',
            __('The id or name attribute of the controller field that controls this element. If the controller is a select or number, use the id attribute. If the controller are checkboxes or radio buttons, use the name attribute.', 'controller-fields-for-contact-form-7'), // description
        );

        //Input field - Dynamic value
        $html .= $this->input(
            $options['content'], // prefix
            'values', // field name
            __('Values', 'controller-fields-for-contact-form-7'), // label
            // attributes
            array(
                'class' => 'oneline minheight', // additional classes
                'placeholder' => "cars,18,bar" // placeholder attribute
            ),
            'textarea', // field type
            __('If the controller is a radio or select field, put the list of values (one per line) that should cause this content to display', 'controller-fields-for-contact-form-7'), // description
            false, // select options
            // Link args
            array(
                'url' => 'https://aurisecreative.com/docs/controller-fields-for-contact-form-7/setting-options/', // link to documentation
                'label' =>  __('View controlled field documentation', 'controller-fields-for-contact-form-7'), // link label
                'utm_source' => $utm_source // UTM source
            )
        );

        $html .= $this->close($options['id']);

        echo wp_kses($html, $this->allowed_html());
    }

    private function allowed_html()
    {
        $allowed = array_merge(array(
            'div' => array('class' => true),
            'fieldset' => true,
            'legend' => true,
            'table' => array('class' => array('form-table')),
            'tbody' => true,
            'tr' => array('id' => true),
            'th' => array('scope' => true),
            'td' => true,
            'strong' => true,
            'em' => true,
            'a' => array('href' => true, 'target' => array('_blank' => true), 'rel' => array('noopener' => true, 'nofollow' => true), 'title' => true),
            'label' => array('for' => true),
            // 'input' => array(
            //     'type' => true,
            //     'id' => true,
            //     'name' => true,
            //     'value' => true,
            //     'class' => true,
            //     'placeholder' => true,
            //     'checked' => array('checked' => true),
            //     'required' => array('required' => true),
            //     'readonly' => array('readonly' => true),
            //     'disabled' => array('disabled' => true),
            //     //'onfocus' => array('this.select()' => true),
            // ),
            'input' => au_cf7_get_allowed_input_properties(),
            'textarea' => array(
                'id' => true,
                'name' => true,
                'class' => true,
                'readonly' => array('readonly' => true),
                'disabled' => array('disabled' => true),
                'onfocus' => array('this.select()' => true)
            ),
            'select' => array(),
            'br' => array('class' => true),
        ), au_cf7_get_allowed_option_properties());
        //var_dump($allowed);
        return $allowed;
    }

    /**
     * Open Form-Tag Generator
     *
     * Opens the div, fieldset, and table body.
     *
     * @since 1.0.0

     * @param string $description Optional.
     *
     * @return void
     */
    private function open($description = '')
    {
        //Open Form-Tag Generator
        return sprintf(
            '<div class="control-box"><fieldset>%s<table class="form-table"><tbody>',
            $description ? sprintf('<legend>%s</legend>', wp_kses($description, array(
                'strong' => true,
                'em' => true,
                'a' => array('href' => true, 'target' => array('_blank' => true), 'rel' => array('noopener' => true, 'nofollow' => true), 'title' => true)
            ))) : ''
        );
    }

    /**
     * Close Form-Tag Generator
     *
     * Closes the table body, adds the preview and insert button, then closes the fieldset and div.
     *
     * @since 1.0.0
     *
     * @param string $field_type The form tag's field type
     *
     * @return void
     */
    private function close($field_type)
    {
        $html = '';
        if ($field_type == 'controlled_wrapper') {
            $html .= sprintf(
                '</tbody></table></fieldset></div><div class="insert-box"><textarea name="%s" class="tag code" readonly="readonly" onfocus="this.select()"></textarea><div class="submitbox"><input type="button" class="button button-primary insert-controller-wrapper" value="%s" /></div><br class="clear"></div>',
                esc_attr($field_type),
                esc_html__('Insert Wrapper', 'controller-fields-for-contact-form-7')
            );
        } else {
            $html .= sprintf(
                '</tbody></table></fieldset></div><div class="insert-box"><input type="text" name="%s" class="tag code" readonly="readonly" onfocus="this.select()" /><div class="submitbox"><input type="button" class="button button-primary insert-tag" value="%s" /></div><br class="clear"></div>',
                esc_attr($field_type),
                esc_html__('Insert Tag', 'controller-fields-for-contact-form-7')
            );
        }
        return $html;
    }

    /**
     * Generator Input Field
     *
     * @since 1.0.0
     *
     * @param string $prefix form tag prefix
     * @param string $name form tag name
     * @param string $label form tag label
     * @param array $atts form field attributes
     * @param string $type Optional. Form type. Can be `text`, `checkbox`, `select` or empty string.
     * @param string $description Optional. Description to display under the form field.
     * @param string $link_url Optional. URL to documentation.
     * @param string $link_label Optional. Link label for documentation link.
     * @param string $utm_source Optional. UTM source attribute for documentation link.
     *
     * @return void
     */
    private function input($prefix, $name, $label, $atts = array(), $type = 'text', $description = '', $select_options = array(), $link_args = array())
    {
        $html = '';
        if (!empty($prefix) && !empty($name) && !empty($label)) {
            $input_html = '';
            // Default field attributes
            $atts = array_merge(array(
                'type' => $type ? $type : 'text', // Default field type
                'id' => $prefix . '-' . $name, // field id
                'name' => $name, // Set name, if not already
                'placeholder' => '',
                'value' => '',
                'required' => '',
                'class' => ''
            ), array_change_key_case((array)$atts, CASE_LOWER));
            $description = is_string($description) && !empty($description) ? $description : '';
            switch ($type) {
                case 'checkbox':
                    $input_html .= '<label>';
                    $input_html .= au_cf7_cf_input_html($atts, false);
                    if ($description) {
                        $input_html .= esc_html($description);
                    }
                    $input_html .= '</label>';
                    $description = '';
                    break;
                case 'select':
                    $input_html .= au_cf7_cf_select_html($atts, $select_options, false);
                    break;
                case 'textarea':
                    $input_html .= au_cf7_cf_textarea_html($atts, false);
                    break;
                default: // text
                    $type = 'text';
                    if (strpos($atts['class'], 'au-cf-option') !== false) {
                        $input_html .= sprintf('%s%s',  au_cf7_cf_input_html(array_merge($atts, array(
                            'type' => 'hidden', // Override to be hidden
                            'name' => $name, // Override to have the real name
                            'id' => $atts['name'], // Override to have a different ID so UI label doesn't match it
                            'class' => str_replace('au-cf-option', 'option', $atts['class']) // Set this as the real "option" class
                        )), false), au_cf7_cf_input_html(array_merge($atts, array(
                            'name' => 'au-cf-' . $name // Override to have a false name
                        )), false));
                    } else {
                        $input_html .= au_cf7_cf_input_html($atts, false);
                    }
                    break;
            }
            if ($input_html) {
                if (is_array($link_args) && count($link_args) && !empty($url = sanitize_url(au_cf7_array_has_key('url', $link_args)))) {
                    $description .= sprintf(
                        '%s<a href="%s?utm_source=%s&utm_medium=link&utm_campaign=controller-fields-for-contact-form-7&utm_content=form-tag-generator-%s" target="_blank" rel="noopener">%s</a>.',
                        $description ? '&nbsp;' : '',
                        esc_url($url),
                        au_cf7_array_has_key('utm_source', $link_args) ? esc_attr($link_args['utm_source']) : '',
                        esc_attr($atts['type']),
                        au_cf7_array_has_key('label', $link_args) ? esc_html($link_args['label']) : esc_html__('View documentation', 'controller-fields-for-contact-form-7'),
                    );
                }
                $html .= sprintf(
                    '<tr id="%s"><th scope="row"><label for="%s">%s</label></th><td>',
                    esc_attr($prefix . '-row-' . $name),
                    esc_attr($atts['id']),
                    esc_html($label)
                );
                //$allowed_properties = au_cf7_get_allowed_input_properties();
                $html .= $input_html;
                // $html .= (wp_kses($input_html, array_merge(array(
                //     'label' => array('for' => array()),
                //     'input' => $allowed_properties,
                //     'select' => $allowed_properties,
                //     'textarea' => $allowed_properties
                // ), au_cf7_get_allowed_option_properties())));
                if ($description) {
                    // $html .= sprintf('<br><small>%s</small>', wp_kses($description, array(
                    //     'strong' => array(),
                    //     'em' => array(),
                    //     'a' => array('href' => array(), 'target' => array(), 'rel' => array(), 'title' => array())
                    // )));
                    $html .= sprintf('<br><small>%s</small>', $description);
                }
                $html .=  ('</td></tr>');
            }
        }
        return $html;
    }
}

new Controller();
