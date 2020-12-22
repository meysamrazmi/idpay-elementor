<?php
/**
 * Elementor IDPay Widget.
 *
 * Elementor widget that inserts an embbedable content into the page, from any given URL.
 *
 * @since 1.0.0
 */
class Elementor_IDPay_Widget extends \Elementor\Widget_Base {

    /**
     * Retrieve IDPay widget name.
     * @return string Widget name.
     */
    public function get_name() {
        return 'idpay';
    }

    /**
     * Retrieve IDPay widget title.
     * @return string Widget title.
     */
    public function get_title() {
        return __( 'IDPay', 'plugin-name' );
    }

    /**
     * Get widget icon.
     *
     * @return string Widget icon.
     */
    public function get_icon() {
        return 'fa fa-code';
    }

    /**
     * Retrieve the list of categories the IDPay widget belongs to.
     *
     * @return array Widget categories.
     */
    public function get_categories() {
        return [ 'general' ];
    }

    /**
     * Register IDPay widget controls.
     *
     * Adds different input fields to allow the user to change and customize the widget settings.
     *
     * @since 1.0.0
     * @access protected
     */
    protected function _register_controls() {

        $this->start_controls_section(
            'content_section',
            [
                'label' => __( 'Content', 'plugin-name' ),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'url',
            [
                'label' => __( 'URL to embed', 'plugin-name' ),
                'type' => \Elementor\Controls_Manager::TEXT,
                'input_type' => 'url',
                'placeholder' => __( 'https://your-link.com', 'plugin-name' ),
            ]
        );

        $this->end_controls_section();

    }

    /**
     * Render IDPay widget output on the frontend.
     *
     * Written in PHP and used to generate the final HTML.
     *
     * @since 1.0.0
     * @access protected
     */
    protected function render() {

        $settings = $this->get_settings_for_display();

        $html = wp_idpay_get( $settings['url'] );

        echo '<div class="idpay-elementor-widget">';

        echo ( $html ) ? $html : $settings['url'];

        echo '</div>';

    }

}
