<?php
/**
 * Plugin Name: OmniaEight Control
 * Description: Visual controls for layout, motion, floating elements, scroll, cursor, menu, and layers.
 * Version: 1.0.0
 * Author: OmniaEight
 * Text Domain: omniaeight-control
 */

if (!defined('ABSPATH')) {
    exit;
}

final class OmniaEight_Control
{
    private const OPTION = 'omniaeight_control_options';
    private const VERSION = '1.1.0';
    private const CACHE_VERSION_OPTION = 'omniaeight_control_cache_version';
    private $floating_rendered = false;

    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_admin_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('init', [$this, 'maybe_purge_cache']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('wp_body_open', [$this, 'render_floating_elements']);
        add_action('wp_footer', [$this, 'render_floating_elements']);
        add_filter('body_class', [$this, 'add_body_classes']);
        add_filter('the_content', [$this, 'replace_home_content'], 20);
    }

    public static function defaults(): array
    {
        return [
            'accent_color' => '#00e5ff',
            'background_tint' => '#101828',
            'motion_enabled' => '1',
            'motion_speed' => '900',
            'float_enabled' => '1',
            'float_size' => '220',
            'float_opacity' => '32',
            'float_x' => '82',
            'float_y' => '18',
            'scroll_reveal' => '1',
            'smooth_scroll' => '1',
            'cursor_enabled' => '1',
            'sticky_menu' => '1',
            'layer_depth' => '35',
        ];
    }

    public static function get_options(): array
    {
        $saved = get_option(self::OPTION, []);
        return wp_parse_args(is_array($saved) ? $saved : [], self::defaults());
    }

    public function replace_home_content(string $content): string
    {
        if (is_admin() || !is_front_page() || !in_the_loop() || !is_main_query()) {
            return $content;
        }

        return '
            <main class="oe-home" aria-label="OmniaEight home">
                <section class="oe-hero alignfull">
                    <div class="oe-hero__copy">
                        <p class="oe-kicker">OmniaEight control layer</p>
                        <h1>Diseno vivo para una web que se mueve contigo.</h1>
                        <p class="oe-lead">Control visual sobre movimiento, scroll, cursor, capas, menu y elementos flotantes sin tocar el nucleo de WordPress.</p>
                        <div class="oe-actions">
                            <a class="oe-button oe-button--primary" href="#oe-control">Ver controles</a>
                            <a class="oe-button oe-button--ghost" href="#oe-flow">Como funciona</a>
                        </div>
                    </div>
                    <div class="oe-hero__stage" aria-hidden="true">
                        <span class="oe-stage-card oe-stage-card--one">cursor</span>
                        <span class="oe-stage-card oe-stage-card--two">scroll</span>
                        <span class="oe-stage-card oe-stage-card--three">layers</span>
                        <span class="oe-stage-ring"></span>
                    </div>
                </section>

                <section id="oe-control" class="oe-section">
                    <p class="oe-kicker">Panel principal</p>
                    <h2>Controla la experiencia sin pelear con codigo.</h2>
                    <div class="oe-grid">
                        <article class="oe-feature">
                            <span>01</span>
                            <h3>Movimiento</h3>
                            <p>Animaciones suaves, entrada por scroll y respuesta visual cuando el usuario toca botones o enlaces.</p>
                        </article>
                        <article class="oe-feature">
                            <span>02</span>
                            <h3>Capas flotantes</h3>
                            <p>Orbes, profundidad y brillo para dar energia al sitio sin romper la lectura.</p>
                        </article>
                        <article class="oe-feature">
                            <span>03</span>
                            <h3>Cursor</h3>
                            <p>Cursor personalizado para escritorio, con estado hover en elementos interactivos.</p>
                        </article>
                        <article class="oe-feature">
                            <span>04</span>
                            <h3>Menu sticky</h3>
                            <p>Cabecera fija con blur, sombra y prioridad visual para mantener navegacion cerca.</p>
                        </article>
                    </div>
                </section>

                <section id="oe-flow" class="oe-section oe-flow">
                    <div>
                        <p class="oe-kicker">Sistema limpio</p>
                        <h2>WordPress queda intacto. Git controla capa visual.</h2>
                    </div>
                    <ol class="oe-steps">
                        <li><strong>GitHub</strong><span>Repo guarda plugin OmniaEight.</span></li>
                        <li><strong>Hostinger</strong><span>Auto-deploy copia cambios a mu-plugins.</span></li>
                        <li><strong>WordPress</strong><span>MU plugin carga solo, sin activar manualmente.</span></li>
                        <li><strong>Web publica</strong><span>Usuarios ven diseno, movimiento y capas nuevas.</span></li>
                    </ol>
                </section>

                <section class="oe-section oe-cta">
                    <p class="oe-kicker">Siguiente nivel</p>
                    <h2>Esta base ya puede crecer hacia editor visual propio.</h2>
                    <p>Desde aqui se agregan presets, sliders, toggles, biblioteca de capas y control por secciones.</p>
                    <a class="oe-button oe-button--primary" href="/wp-admin/admin.php?page=omniaeight-control">Abrir OmniaEight</a>
                </section>
            </main>
        ';
    }

    public function maybe_purge_cache(): void
    {
        if (get_option(self::CACHE_VERSION_OPTION) === self::VERSION) {
            return;
        }

        if (has_action('litespeed_purge_all')) {
            do_action('litespeed_purge_all');
        }

        update_option(self::CACHE_VERSION_OPTION, self::VERSION, false);
    }

    public function add_admin_page(): void
    {
        add_menu_page(
            'OmniaEight Control',
            'OmniaEight',
            'manage_options',
            'omniaeight-control',
            [$this, 'render_admin_page'],
            'dashicons-art',
            58
        );
    }

    public function register_settings(): void
    {
        register_setting(
            'omniaeight_control_group',
            self::OPTION,
            ['sanitize_callback' => [$this, 'sanitize_options']]
        );
    }

    public function sanitize_options($input): array
    {
        $input = is_array($input) ? $input : [];
        $defaults = self::defaults();

        return [
            'accent_color' => sanitize_hex_color($input['accent_color'] ?? $defaults['accent_color']) ?: $defaults['accent_color'],
            'background_tint' => sanitize_hex_color($input['background_tint'] ?? $defaults['background_tint']) ?: $defaults['background_tint'],
            'motion_enabled' => empty($input['motion_enabled']) ? '0' : '1',
            'motion_speed' => (string) min(3000, max(100, absint($input['motion_speed'] ?? $defaults['motion_speed']))),
            'float_enabled' => empty($input['float_enabled']) ? '0' : '1',
            'float_size' => (string) min(500, max(24, absint($input['float_size'] ?? $defaults['float_size']))),
            'float_opacity' => (string) min(100, max(0, absint($input['float_opacity'] ?? $defaults['float_opacity']))),
            'float_x' => (string) min(100, max(0, absint($input['float_x'] ?? $defaults['float_x']))),
            'float_y' => (string) min(100, max(0, absint($input['float_y'] ?? $defaults['float_y']))),
            'scroll_reveal' => empty($input['scroll_reveal']) ? '0' : '1',
            'smooth_scroll' => empty($input['smooth_scroll']) ? '0' : '1',
            'cursor_enabled' => empty($input['cursor_enabled']) ? '0' : '1',
            'sticky_menu' => empty($input['sticky_menu']) ? '0' : '1',
            'layer_depth' => (string) min(100, max(0, absint($input['layer_depth'] ?? $defaults['layer_depth']))),
        ];
    }

    public function enqueue_admin_assets(string $hook): void
    {
        if ($hook !== 'toplevel_page_omniaeight-control') {
            return;
        }

        wp_enqueue_style(
            'omniaeight-admin',
            plugin_dir_url(__FILE__) . 'assets/css/admin.css',
            [],
            self::VERSION
        );
    }

    public function enqueue_frontend_assets(): void
    {
        $options = self::get_options();

        wp_enqueue_style(
            'omniaeight-frontend',
            plugin_dir_url(__FILE__) . 'assets/css/frontend.css',
            [],
            self::VERSION
        );

        wp_add_inline_style('omniaeight-frontend', $this->build_css_variables($options));

        wp_enqueue_script(
            'omniaeight-frontend',
            plugin_dir_url(__FILE__) . 'assets/js/frontend.js',
            [],
            self::VERSION,
            true
        );

        wp_localize_script('omniaeight-frontend', 'OmniaEightControl', [
            'motionEnabled' => $options['motion_enabled'] === '1',
            'scrollReveal' => $options['scroll_reveal'] === '1',
            'cursorEnabled' => $options['cursor_enabled'] === '1',
        ]);
    }

    private function build_css_variables(array $options): string
    {
        $opacity = ((int) $options['float_opacity']) / 100;

        return sprintf(
            ':root{--oe-accent:%1$s;--oe-bg-tint:%2$s;--oe-motion-speed:%3$sms;--oe-float-size:%4$spx;--oe-float-opacity:%5$s;--oe-float-x:%6$s%%;--oe-float-y:%7$s%%;--oe-layer-depth:%8$s;}',
            esc_html($options['accent_color']),
            esc_html($options['background_tint']),
            esc_html($options['motion_speed']),
            esc_html($options['float_size']),
            esc_html((string) $opacity),
            esc_html($options['float_x']),
            esc_html($options['float_y']),
            esc_html($options['layer_depth'])
        );
    }

    public function render_floating_elements(): void
    {
        $options = self::get_options();

        if ($this->floating_rendered || $options['float_enabled'] !== '1') {
            return;
        }

        $this->floating_rendered = true;

        echo '<div class="oe-floating-layer" aria-hidden="true">';
        echo '<span class="oe-floating-orb oe-floating-orb-primary"></span>';
        echo '<span class="oe-floating-orb oe-floating-orb-secondary"></span>';
        echo '</div>';
    }

    public function add_body_classes(array $classes): array
    {
        $options = self::get_options();

        if ($options['motion_enabled'] === '1') {
            $classes[] = 'oe-motion-enabled';
        }

        if ($options['smooth_scroll'] === '1') {
            $classes[] = 'oe-smooth-scroll';
        }

        if ($options['sticky_menu'] === '1') {
            $classes[] = 'oe-sticky-menu';
        }

        if ($options['cursor_enabled'] === '1') {
            $classes[] = 'oe-custom-cursor';
        }

        return $classes;
    }

    public function render_admin_page(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $options = self::get_options();
        ?>
        <div class="wrap oe-admin">
            <h1>OmniaEight Control</h1>
            <p>Control visual para diseno, movimiento, scroll, cursor, menu y capas sin editar archivos de WordPress.</p>

            <form method="post" action="options.php">
                <?php settings_fields('omniaeight_control_group'); ?>

                <div class="oe-admin-grid">
                    <?php $this->render_design_card($options); ?>
                    <?php $this->render_motion_card($options); ?>
                    <?php $this->render_float_card($options); ?>
                    <?php $this->render_behavior_card($options); ?>
                </div>

                <?php submit_button('Guardar cambios'); ?>
            </form>
        </div>
        <?php
    }

    private function field_name(string $key): string
    {
        return self::OPTION . '[' . $key . ']';
    }

    private function render_design_card(array $options): void
    {
        ?>
        <section class="oe-card">
            <h2>Diseno</h2>
            <label>
                Color acento
                <input type="color" name="<?php echo esc_attr($this->field_name('accent_color')); ?>" value="<?php echo esc_attr($options['accent_color']); ?>">
            </label>
            <label>
                Tinte fondo
                <input type="color" name="<?php echo esc_attr($this->field_name('background_tint')); ?>" value="<?php echo esc_attr($options['background_tint']); ?>">
            </label>
            <label>
                Profundidad capas
                <input type="range" min="0" max="100" name="<?php echo esc_attr($this->field_name('layer_depth')); ?>" value="<?php echo esc_attr($options['layer_depth']); ?>">
            </label>
        </section>
        <?php
    }

    private function render_motion_card(array $options): void
    {
        ?>
        <section class="oe-card">
            <h2>Movimiento</h2>
            <label class="oe-toggle">
                <input type="checkbox" name="<?php echo esc_attr($this->field_name('motion_enabled')); ?>" value="1" <?php checked($options['motion_enabled'], '1'); ?>>
                Activar movimiento
            </label>
            <label>
                Velocidad animacion (ms)
                <input type="number" min="100" max="3000" step="50" name="<?php echo esc_attr($this->field_name('motion_speed')); ?>" value="<?php echo esc_attr($options['motion_speed']); ?>">
            </label>
            <label class="oe-toggle">
                <input type="checkbox" name="<?php echo esc_attr($this->field_name('scroll_reveal')); ?>" value="1" <?php checked($options['scroll_reveal'], '1'); ?>>
                Reveal on scroll
            </label>
        </section>
        <?php
    }

    private function render_float_card(array $options): void
    {
        ?>
        <section class="oe-card">
            <h2>Elementos flotantes</h2>
            <label class="oe-toggle">
                <input type="checkbox" name="<?php echo esc_attr($this->field_name('float_enabled')); ?>" value="1" <?php checked($options['float_enabled'], '1'); ?>>
                Mostrar elementos flotantes
            </label>
            <label>
                Tamano
                <input type="range" min="24" max="500" name="<?php echo esc_attr($this->field_name('float_size')); ?>" value="<?php echo esc_attr($options['float_size']); ?>">
            </label>
            <label>
                Opacidad
                <input type="range" min="0" max="100" name="<?php echo esc_attr($this->field_name('float_opacity')); ?>" value="<?php echo esc_attr($options['float_opacity']); ?>">
            </label>
            <label>
                Posicion X
                <input type="range" min="0" max="100" name="<?php echo esc_attr($this->field_name('float_x')); ?>" value="<?php echo esc_attr($options['float_x']); ?>">
            </label>
            <label>
                Posicion Y
                <input type="range" min="0" max="100" name="<?php echo esc_attr($this->field_name('float_y')); ?>" value="<?php echo esc_attr($options['float_y']); ?>">
            </label>
        </section>
        <?php
    }

    private function render_behavior_card(array $options): void
    {
        ?>
        <section class="oe-card">
            <h2>Comportamiento</h2>
            <label class="oe-toggle">
                <input type="checkbox" name="<?php echo esc_attr($this->field_name('smooth_scroll')); ?>" value="1" <?php checked($options['smooth_scroll'], '1'); ?>>
                Smooth scroll
            </label>
            <label class="oe-toggle">
                <input type="checkbox" name="<?php echo esc_attr($this->field_name('cursor_enabled')); ?>" value="1" <?php checked($options['cursor_enabled'], '1'); ?>>
                Cursor personalizado
            </label>
            <label class="oe-toggle">
                <input type="checkbox" name="<?php echo esc_attr($this->field_name('sticky_menu')); ?>" value="1" <?php checked($options['sticky_menu'], '1'); ?>>
                Menu sticky
            </label>
        </section>
        <?php
    }
}

new OmniaEight_Control();
