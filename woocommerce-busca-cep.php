<?php

/**
 * Plugin Name: WooCommerce Busca CEP por Endereço por Feharo Tech
 * Plugin URI: https://feharo.com.br/woocommerce-busca-cep
 * Description: Permite aos clientes buscar o CEP preenchendo o endereço no checkout do WooCommerce.
 * Version: 1.1.1
 * Author: Feharo Tech
 * Author URI: https://feharo.com.br
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: wc-busca-cep
 * Domain Path: /languages
 * WC requires at least: 3.0
 * WC tested up to: 8.0
 * Requires Plugins: WooCommerce
 */

defined('ABSPATH') || exit;

// Verifica se WooCommerce está ativo
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', 'wc_busca_cep_woocommerce_missing_notice');
    return;
}

function wc_busca_cep_woocommerce_missing_notice()
{
    echo '<div class="error"><p>' . sprintf(
        esc_html__('WooCommerce Busca CEP requer que o WooCommerce esteja instalado e ativo. Você pode baixar o %s aqui.', 'wc-busca-cep'),
        '<a href="https://wordpress.org/plugins/woocommerce/" target="_blank">WooCommerce</a>'
    ) . '</p></div>';
}

class WC_Busca_CEP
{
    /**
     * Inicializa o plugin
     */
    public function __construct()
    {
        // Carrega textos para internacionalização
        add_action('init', array($this, 'load_textdomain'));

        // Adiciona menu de configurações
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));

        // Adiciona o link e modal no checkout
        add_action('woocommerce_after_checkout_billing_form', array($this, 'add_cep_search_link'));

        // Carrega scripts e estilos
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));

        add_shortcode('busca_cep', array($this, 'render_shortcode_busca_cep'));
    }

    /**
     * Carrega arquivos de tradução
     */
    public function load_textdomain()
    {
        load_plugin_textdomain('wc-busca-cep', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    /**
     * Adiciona menu de administração
     */
    public function add_admin_menu()
    {
        add_submenu_page(
            'woocommerce',
            __('Configurações Busca CEP', 'wc-busca-cep'),
            __('Busca CEP', 'wc-busca-cep'),
            'manage_options',
            'wc-busca-cep',
            array($this, 'options_page')
        );
    }


    /**
     * Inicializa as configurações
     */
    public function settings_init()
    {
        register_setting('wc_busca_cep', 'wc_busca_cep_settings');

        // Seção principal
        add_settings_section(
            'wc_busca_cep_section',
            __('Configurações do Plugin Busca CEP', 'wc-busca-cep'),
            array($this, 'settings_section_callback'),
            'wc_busca_cep'
        );

        // Campos de configuração
        add_settings_field(
            'link_text',
            __('Texto do Link', 'wc-busca-cep'),
            array($this, 'link_text_render'),
            'wc_busca_cep',
            'wc_busca_cep_section'
        );

        add_settings_field(
            'link_position',
            __('Posição do Link', 'wc-busca-cep'),
            array($this, 'link_position_render'),
            'wc_busca_cep',
            'wc_busca_cep_section'
        );

        add_settings_field(
            'link_color',
            __('Cor do Link', 'wc-busca-cep'),
            array($this, 'link_color_render'),
            'wc_busca_cep',
            'wc_busca_cep_section'
        );

        add_settings_field(
            'link_hover_color',
            __('Cor do Link (hover)', 'wc-busca-cep'),
            array($this, 'link_hover_color_render'),
            'wc_busca_cep',
            'wc_busca_cep_section'
        );
    }

    /**
     * Callback da seção de configurações
     */
    public function settings_section_callback()
    {
        echo __('Personalize a aparência e comportamento do link de busca de CEP no checkout.', 'wc-busca-cep');
    }


    /**
     * Renderiza campo de texto do link
     */
    public function link_text_render()
    {
        $options = get_option('wc_busca_cep_settings');
?>
        <input type="text" name="wc_busca_cep_settings[link_text]" value="<?php echo esc_attr($options['link_text'] ?? __('Não sei meu CEP', 'wc-busca-cep')); ?>">
        <p class="description"><?php _e('Texto que será exibido no link que abre o modal de busca.', 'wc-busca-cep'); ?></p>
    <?php
    }

    /**
     * Renderiza campo de posição do link
     */
    public function link_position_render()
    {
        $options = get_option('wc_busca_cep_settings');
        $position = $options['link_position'] ?? 'after';
    ?>
        <select name="wc_busca_cep_settings[link_position]">
            <option value="after" <?php selected($position, 'after'); ?>><?php _e('Após o campo de CEP', 'wc-busca-cep'); ?></option>
            <option value="before" <?php selected($position, 'before'); ?>><?php _e('Antes do campo de CEP', 'wc-busca-cep'); ?></option>
        </select>
        <p class="description"><?php _e('Onde o link de busca de CEP será exibido no formulário de checkout.', 'wc-busca-cep'); ?></p>
    <?php
    }

    /**
     * Renderiza campo de cor do link
     */
    public function link_color_render()
    {
        $options = get_option('wc_busca_cep_settings');
    ?>
        <input type="text" name="wc_busca_cep_settings[link_color]" value="<?php echo esc_attr($options['link_color'] ?? '#0071a1'); ?>" class="color-field">
    <?php
    }

    /**
     * Renderiza campo de cor do link (hover)
     */
    public function link_hover_color_render()
    {
        $options = get_option('wc_busca_cep_settings');
    ?>
        <input type="text" name="wc_busca_cep_settings[link_hover_color]" value="<?php echo esc_attr($options['link_hover_color'] ?? '#005177'); ?>" class="color-field">
    <?php
    }

    /**
     * Página de opções
     */
    public function options_page()
    {
    ?>
        <div class="wrap">
            <h1>🚀 Sobre o Plugin Busca CEP para WooCommerce</h1>

            <h2>🌟 Apoie o Plugin</h2>
            <p>Esse plugin é gratuito e mantido pela <strong>Feharo Tech</strong> com carinho para a comunidade WooCommerce no Brasil.</p>
            <p>Você pode ajudar de várias formas:</p>
            <ul>
                <li><a href="https://br.wordpress.org/plugins/" target="_blank">⭐ Avalie o plugin no WordPress.org</a></li>
            </ul>

            <h2>👋 Sobre a Feharo Tech</h2>
            <p>A Feharo Tech é especializada em soluções WordPress sob medida. Criamos ferramentas leves, úteis e voltadas para negócios reais.</p>
            <a class="button button-primary-outline" href="https://feharo.com.br" target="_blank">Conheça a Feharo Tech</a>
        </div>
        <?php

        ?>
        <div class="wrap">
            <form action="options.php" method="post">
                <?php
                settings_fields('wc_busca_cep');
                do_settings_sections('wc_busca_cep');
                submit_button();
                ?>
            </form>
        </div>
    <?php
    }

    /**
     * Carrega scripts e estilos na administração
     */
    public function admin_enqueue_scripts($hook)
    {
        if ('woocommerce_page_wc-busca-cep' !== $hook) {
            return;
        }

        // Color picker
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');

        // Scripts personalizados
        wp_enqueue_script(
            'wc-busca-cep-admin',
            plugins_url('assets/js/admin.js', __FILE__),
            array('jquery', 'wp-color-picker'),
            filemtime(plugin_dir_path(__FILE__) . 'assets/js/admin.js'),
            true
        );
    }

    /**
     * Carrega scripts e estilos necessários
     */

    public function enqueue_scripts()
    {
        global $post;
        $post_id = is_a($post, 'WP_Post') ? $post->ID : get_the_ID();
        $content = get_post_field('post_content', $post_id);
        $has_shortcode = has_shortcode($content, 'busca_cep');

        if ($has_shortcode || is_checkout()) {
            // CSS
            wp_enqueue_style(
                'wc-busca-cep-style',
                plugins_url('assets/css/style.css', __FILE__),
                array(),
                filemtime(plugin_dir_path(__FILE__) . 'assets/css/style.css')
            );

            // JS
            wp_enqueue_script(
                'wc-busca-cep-script',
                plugins_url('assets/js/script.js', __FILE__),
                array('jquery'),
                filemtime(plugin_dir_path(__FILE__) . 'assets/js/script.js'),
                true
            );

            // Localiza script com configurações
            $options = get_option('wc_busca_cep_settings') ?: array();
            $options = is_array($options) ? $options : array();

            wp_localize_script('wc-busca-cep-script', 'wc_busca_cep_params', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wc-busca-cep-nonce'),
                'link_position' => isset($options['link_position']) ? $options['link_position'] : 'after',
                'i18n' => array(
                    'dont_know_cep' => isset($options['link_text']) ? $options['link_text'] : __('Não sei meu CEP', 'wc-busca-cep'),
                    'fill_all_fields' => __('Preencha todos os campos corretamente.', 'wc-busca-cep'),
                    'cep_found' => __('CEP encontrado', 'wc-busca-cep'),
                    'cep_not_found' => __('CEP não encontrado para esse endereço.', 'wc-busca-cep'),
                    'cep_error' => __('Erro ao consultar CEP. Tente novamente.', 'wc-busca-cep'),
                    'select_cep' => __('Selecione seu CEP:', 'wc-busca-cep'),
                    'loading' => __('Buscando CEP...', 'wc-busca-cep')
                )
            ));

            // Adiciona CSS dinâmico
            $custom_css = "
                .wc-busca-cep-link {
                    color: " . (isset($options['link_color']) ? $options['link_color'] : '#0071a1') . " !important;
                }
                .wc-busca-cep-link:hover {
                    color: " . (isset($options['link_hover_color']) ? $options['link_hover_color'] : '#005177') . " !important;
                }
            ";
            wp_add_inline_style('wc-busca-cep-style', $custom_css);
        }
    }



    /**
     * Adiciona o link e modal de busca de CEP no checkout
     */
    public function add_cep_search_link()
    {
        $options = get_option('wc_busca_cep_settings');
        $position = $options['link_position'] ?? 'after';

    ?>
        <!-- Modal -->
        <div id="wc-modal-busca-cep" class="wc-busca-cep-modal" style="display:none;">
            <div class="wc-busca-cep-modal-content">
                <div class="wc-busca-cep-modal-header">
                    <h3><?php esc_html_e('Buscar CEP pelo Endereço', 'wc-busca-cep'); ?></h3>
                    <button type="button" id="wc-fechar-modal-cep" class="wc-busca-cep-close">&times;</button>
                </div>
                <input type="text" id="wc-busca-logradouro" class="wc-busca-cep-input" placeholder="<?php esc_attr_e('Rua (ex: Av. Paulista)', 'wc-busca-cep'); ?>" maxlength="80">
                <input type="text" id="wc-busca-cidade" class="wc-busca-cep-input" placeholder="<?php esc_attr_e('Cidade (ex: São Paulo)', 'wc-busca-cep'); ?>" maxlength="80">
                <input type="text" id="wc-busca-uf" class="wc-busca-cep-input" placeholder="<?php esc_attr_e('UF (ex: SP)', 'wc-busca-cep'); ?>" maxlength="2">
                <button type="button" id="wc-buscar-cep" class="wc-busca-cep-button"><?php esc_html_e('Buscar CEP', 'wc-busca-cep'); ?></button>
                <div id="wc-resultado-cep" class="wc-busca-cep-result"></div>
            </div>
        </div>

        <!-- Container para o link (posição será controlada pelo JS) -->
        <div id="wc-busca-cep-container" style="display:block;">
            <div class="wc-busca-cep-link-container">
                <a href="#" id="wc-abrir-busca-cep" class="wc-busca-cep-link">
                    <?php echo esc_html($options['link_text'] ?? __('Não sei meu CEP', 'wc-busca-cep')); ?>
                </a>
            </div>
        </div>
<?php
    }

    public function render_shortcode_busca_cep()
    {
        ob_start();
        echo '<div id="wc-busca-cep-shortcode">';
        $this->add_cep_search_link();
        echo '</div>';
        return ob_get_clean();
    }
}

// Inicializa o plugin
function wc_busca_cep_init_plugin()
{
    new WC_Busca_CEP();
}
add_action('plugins_loaded', 'wc_busca_cep_init_plugin');

/**
 * Adiciona link de configurações na listagem de plugins
 */
function wc_busca_cep_plugin_action_links($links)
{
    $settings_link = '<a href="' . admin_url('admin.php?page=wc-busca-cep') . '">' . __('Configurações', 'wc-busca-cep') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'wc_busca_cep_plugin_action_links');
