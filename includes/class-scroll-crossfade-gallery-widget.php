<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class SCG_Elem_Scroll_Crossfade_Gallery_Widget extends Widget_Base {

    public function get_name() {
        return 'scroll_crossfade_gallery';
    }

    public function get_title() {
        return __( 'Scroll Crossfade Gallery', 'scroll-crossfade-gallery-elementor' );
    }

    public function get_icon() {
        return 'eicon-gallery-grid';
    }

    public function get_categories() {
        return [ 'basic' ];
    }

    public function get_script_depends() {
        return [ 'scg-scroll-crossfade' ];
    }

    public function get_style_depends() {
        return [ 'scg-scroll-crossfade' ];
    }

    protected function register_controls() {

        $this->start_controls_section(
            'section_content',
            [ 'label' => __( 'Images', 'scroll-crossfade-gallery-elementor' ) ]
        );

        $this->add_control(
            'images',
            [
                'label' => __( 'Gallery Images', 'scroll-crossfade-gallery-elementor' ),
                'type'  => Controls_Manager::GALLERY,
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_scroll',
            [ 'label' => __( 'Scroll Behavior', 'scroll-crossfade-gallery-elementor' ) ]
        );

        $this->add_control(
            'start_mode',
            [
                'label'       => __( 'Fade Start Trigger', 'scroll-crossfade-gallery-elementor' ),
                'type'        => Controls_Manager::SELECT,
                'default'     => 'fully_visible_bottom',
                'options'     => [
                    'fully_visible_bottom' => __( 'When image is fully visible and bottom touches viewport bottom', 'scroll-crossfade-gallery-elementor' ),
                    'top_hits_bottom'      => __( 'When image top reaches viewport bottom', 'scroll-crossfade-gallery-elementor' ),
                ],
            ]
        );

        $this->add_control(
            'end_point',
            [
                'label'       => __( 'End Point (Viewport %)', 'scroll-crossfade-gallery-elementor' ),
                'type'        => Controls_Manager::SLIDER,
                'size_units'  => [ '%' ],
                'range'       => [
                    '%' => [
                        'min'  => 0,
                        'max'  => 100,
                        'step' => 1,
                    ],
                ],
                'default'     => [
                    'unit' => '%',
                    'size' => 30,
                ],
                'description' => __(
                    'When the top of the image reaches this percentage of the viewport height, the last frame becomes fully visible.',
                    'scroll-crossfade-gallery-elementor'
                ),
            ]
        );

        // Ensure-all-frames animated catch-up
        $this->add_control(
            'ensure_all_frames',
            [
                'label'        => __( 'Ensure Every Frame Shows (Animated)', 'scroll-crossfade-gallery-elementor' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'scroll-crossfade-gallery-elementor' ),
                'label_off'    => __( 'No', 'scroll-crossfade-gallery-elementor' ),
                'return_value' => 'yes',
                'default'      => '',
                'description'  => __( 'If enabled, scroll sets a target and the gallery animates through every frame at a fixed speed until it catches up.', 'scroll-crossfade-gallery-elementor' ),
            ]
        );

        $this->add_control(
            'animation_speed',
            [
                'label'       => __( 'Animation Speed (seconds for full gallery)', 'scroll-crossfade-gallery-elementor' ),
                'type'        => Controls_Manager::SLIDER,
                'size_units'  => [ 's' ],
                'range'       => [
                    's' => [
                        'min'  => 0.1,
                        'max'  => 10,
                        'step' => 0.1,
                    ],
                ],
                'default'     => [
                    'unit' => 's',
                    'size' => 1.5,
                ],
                'condition'   => [
                    'ensure_all_frames' => 'yes',
                ],
                'description' => __( 'How long it takes (in seconds) for the gallery to run from the first frame to the last when scroll jumps from start to end.', 'scroll-crossfade-gallery-elementor' ),
            ]
        );

        // Scroll smoothing (when ensure_all_frames is off)
        $this->add_control(
            'scroll_smoothing',
            [
                'label'       => __( 'Scroll Smoothing', 'scroll-crossfade-gallery-elementor' ),
                'type'        => Controls_Manager::SELECT,
                'default'     => 'none',
                'options'     => [
                    'none'   => __( 'None (direct follow)', 'scroll-crossfade-gallery-elementor' ),
                    'light'  => __( 'Light smoothing', 'scroll-crossfade-gallery-elementor' ),
                    'strong' => __( 'Strong smoothing', 'scroll-crossfade-gallery-elementor' ),
                ],
                'description' => __( 'Applies easing between scroll position and animation when Ensure Every Frame is disabled.', 'scroll-crossfade-gallery-elementor' ),
            ]
        );

        // Preloader
        $this->add_control(
            'show_preloader',
            [
                'label'        => __( 'Show Preloader', 'scroll-crossfade-gallery-elementor' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'scroll-crossfade-gallery-elementor' ),
                'label_off'    => __( 'No', 'scroll-crossfade-gallery-elementor' ),
                'return_value' => 'yes',
                'default'      => '',
                'description'  => __( 'Show a simple overlay spinner while gallery images are loading.', 'scroll-crossfade-gallery-elementor' ),
            ]
        );

        // Mobile performance mode
        $this->add_control(
            'mobile_performance',
            [
                'label'        => __( 'Mobile Performance Mode', 'scroll-crossfade-gallery-elementor' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'On', 'scroll-crossfade-gallery-elementor' ),
                'label_off'    => __( 'Off', 'scroll-crossfade-gallery-elementor' ),
                'return_value' => 'yes',
                'default'      => '',
                'description'  => __( 'On smaller screens, reduces animation overhead (disables animated catch-up and uses simpler transitions).', 'scroll-crossfade-gallery-elementor' ),
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $images   = $settings['images'] ?? [];
        $start_mode = $settings['start_mode'] ?? 'fully_visible_bottom';
        $end_point_percent = floatval( $settings['end_point']['size'] ?? 30 );
        $ensure_all_frames = ! empty( $settings['ensure_all_frames'] ) && $settings['ensure_all_frames'] === 'yes';
        $animation_speed   = isset( $settings['animation_speed']['size'] ) ? floatval( $settings['animation_speed']['size'] ) : 1.5;
        $scroll_smoothing  = $settings['scroll_smoothing'] ?? 'none';
        $show_preloader    = ! empty( $settings['show_preloader'] ) && $settings['show_preloader'] === 'yes';
        $mobile_perf       = ! empty( $settings['mobile_performance'] ) && $settings['mobile_performance'] === 'yes';

        if ( empty( $images ) ) {
            return;
        }

        $end_ratio = max( 0, min( 100, $end_point_percent ) ) / 100;
        $animation_speed = $animation_speed > 0 ? $animation_speed : 1.5;
        $scroll_smoothing = in_array( $scroll_smoothing, [ 'none', 'light', 'strong' ], true ) ? $scroll_smoothing : 'none';
        ?>

        <div class="scg-scroll-crossfade-gallery-wrapper<?php echo $show_preloader ? ' scg-has-preloader' : ''; ?>">
            <?php if ( $show_preloader ) : ?>
                <div class="scg-preloader">
                    <div class="scg-preloader-spinner"></div>
                </div>
            <?php endif; ?>

            <div class="scg-scroll-crossfade-gallery"
                 data-start-mode="<?php echo esc_attr( $start_mode ); ?>"
                 data-end-point="<?php echo esc_attr( $end_ratio ); ?>"
                 data-ensure-all-frames="<?php echo esc_attr( $ensure_all_frames ? 'yes' : 'no' ); ?>"
                 data-animation-speed="<?php echo esc_attr( $animation_speed ); ?>"
                 data-scroll-smoothing="<?php echo esc_attr( $scroll_smoothing ); ?>"
                 data-mobile-performance="<?php echo esc_attr( $mobile_perf ? 'yes' : 'no' ); ?>">

                <?php
                foreach ( $images as $i => $img ) :

                    $url = $img['url'] ?? '';
                    if ( ! $url ) {
                        continue;
                    }

                    $id  = $img['id'] ?? 0;
                    $alt = $id ? get_post_meta( $id, '_wp_attachment_image_alt', true ) : '';

                    $frame_classes = 'scg-frame';
                    if ( 0 === $i ) {
                        $frame_classes .= ' scg-base-frame';
                    }
                ?>
                    <div class="<?php echo esc_attr( $frame_classes ); ?>"
                         data-frame-index="<?php echo esc_attr( $i ); ?>">
                        <img src="<?php echo esc_url( $url ); ?>"
                             alt="<?php echo esc_attr( $alt ); ?>" />
                    </div>
                <?php endforeach; ?>

            </div>
        </div>

        <?php
    }
}
