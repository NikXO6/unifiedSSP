<?php
class Unified_SSO {
    const OPTION_KEY = 'unified_sso_options';

    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'register_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
        add_action( 'login_form', array( __CLASS__, 'display_login_buttons' ) );
        add_action( 'init', array( __CLASS__, 'handle_oauth_callback' ) );
    }

    public static function get_options() {
        $defaults = array(
            'applications' => array(),
            'field_mapping' => array(
                'username' => '',
                'email' => '',
                'first_name' => '',
                'last_name' => '',
                'display_name' => '',
                'default_role' => '',
                'auto_create' => false,
                'keep_existing' => true,
            ),
            'login_settings' => array(
                'google_enabled' => false,
                'microsoft_enabled' => false,
                'redirect_login' => '',
                'redirect_logout' => '',
                'enable_reports' => false,
            ),
            'button' => array(
                'style' => 'round',
                'bg_color' => '#ffffff',
                'text_color' => '#000000',
                'display_name' => '',
                'icon_size' => 24,
                'icon_spacing' => 5,
            ),
            'delete_data' => false,
        );
        return wp_parse_args( get_option( self::OPTION_KEY, array() ), $defaults );
    }

    public static function save_options( $options ) {
        update_option( self::OPTION_KEY, $options );
    }

    public static function register_admin_menu() {
        add_menu_page( 'Unified SSO', 'Unified SSO', 'manage_options', 'unified-sso', array( __CLASS__, 'render_oauth_page' ), 'dashicons-universal-access' );
        add_submenu_page( 'unified-sso', 'Configure OAuth', 'Configure OAuth', 'manage_options', 'unified-sso', array( __CLASS__, 'render_oauth_page' ) );
        add_submenu_page( 'unified-sso', 'Field Mapping', 'Field Mapping', 'manage_options', 'unified-sso-mapping', array( __CLASS__, 'render_mapping_page' ) );
        add_submenu_page( 'unified-sso', 'Login Settings', 'Login Settings', 'manage_options', 'unified-sso-login', array( __CLASS__, 'render_login_page' ) );
        add_submenu_page( 'unified-sso', 'Login Button', 'Login Button', 'manage_options', 'unified-sso-button', array( __CLASS__, 'render_button_page' ) );
        add_submenu_page( 'unified-sso', 'Reports', 'Reports', 'manage_options', 'unified-sso-reports', array( __CLASS__, 'render_reports_page' ) );
        add_submenu_page( 'unified-sso', 'Data', 'Data', 'manage_options', 'unified-sso-data', array( __CLASS__, 'render_data_page' ) );
    }

    public static function enqueue_admin_assets( $hook ) {
        if ( strpos( $hook, 'unified-sso' ) === false ) {
            return;
        }
        wp_enqueue_style( 'unified-sso-admin', UNIFIED_SSO_URL . 'assets/css/admin.css', array(), UNIFIED_SSO_VERSION );
        wp_enqueue_script( 'unified-sso-admin', UNIFIED_SSO_URL . 'assets/js/admin.js', array( 'jquery' ), UNIFIED_SSO_VERSION, true );
    }

    public static function render_oauth_page() {
        $options = self::get_options();
        if ( isset( $_POST['unified_sso_nonce'] ) && check_admin_referer( 'unified_sso_save', 'unified_sso_nonce' ) ) {
            if ( isset( $_POST['new_app'] ) ) {
                $app = array(
                    'name'          => sanitize_text_field( $_POST['new_app']['name'] ),
                    'type'          => sanitize_text_field( $_POST['new_app']['type'] ),
                    'redirect'      => esc_url_raw( $_POST['new_app']['redirect'] ),
                    'client_id'     => sanitize_text_field( $_POST['new_app']['client_id'] ),
                    'client_secret' => sanitize_text_field( $_POST['new_app']['client_secret'] ),
                    'show_login'    => ! empty( $_POST['new_app']['show_login'] ),
                );
                $options['applications'][] = $app;
                self::save_options( $options );
            }
        }

        ?>
        <div class="wrap unified-sso-wrap">
            <h1>Configure OAuth</h1>
            <table>
                <thead><tr><th>Name</th><th>Type</th><th>Login Button</th></tr></thead>
                <tbody>
                    <?php foreach ( $options['applications'] as $app ) : ?>
                        <tr>
                            <td><?php echo esc_html( $app['name'] ); ?></td>
                            <td><?php echo esc_html( ucfirst( $app['type'] ) ); ?></td>
                            <td><?php echo $app['show_login'] ? 'Yes' : 'No'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p><a href="#" class="button add-app-btn">Add Application</a></p>
            <form method="post" class="modal">
                <div class="modal-content">
                    <h2>Add Application</h2>
                    <?php wp_nonce_field( 'unified_sso_save', 'unified_sso_nonce' ); ?>
                    <p><input type="text" name="new_app[name]" placeholder="Application Name" required></p>
                    <p>
                        <select name="new_app[type]">
                            <option value="google">Google</option>
                            <option value="microsoft">Microsoft</option>
                        </select>
                    </p>
                    <p><input type="text" name="new_app[redirect]" placeholder="Redirect URL"></p>
                    <p><input type="text" name="new_app[client_id]" placeholder="Client ID"></p>
                    <p><input type="text" name="new_app[client_secret]" placeholder="Client Secret"></p>
                    <p><label><input type="checkbox" name="new_app[show_login]" value="1"> Show login button on login page</label></p>
                    <p>
                        <button type="submit" class="button button-primary">Save Application</button>
                        <a href="#" class="button close-modal">Cancel</a>
                    </p>
                </div>
            </form>
        </div>
        <?php
    }

    public static function render_mapping_page() {
        $options = self::get_options();
        if ( isset( $_POST['unified_sso_nonce'] ) && check_admin_referer( 'unified_sso_save', 'unified_sso_nonce' ) ) {
            if ( isset( $_POST['options']['field_mapping'] ) ) {
                $options['field_mapping'] = array_replace( $options['field_mapping'], $_POST['options']['field_mapping'] );
                self::save_options( $options );
            }
        }
        ?>
        <div class="wrap unified-sso-wrap">
            <h1>Field Mapping</h1>
            <form method="post">
                <?php wp_nonce_field( 'unified_sso_save', 'unified_sso_nonce' ); ?>
                <p><label><input type="checkbox" name="options[field_mapping][auto_create]" value="1" <?php checked( $options['field_mapping']['auto_create'], true ); ?>> Auto-create users if they do not exist</label></p>
                <p><label><input type="checkbox" name="options[field_mapping][keep_existing]" value="1" <?php checked( $options['field_mapping']['keep_existing'], true ); ?>> Keep Existing Users</label></p>
                <table class="form-table">
                    <tr><th>Username</th><td><input type="text" name="options[field_mapping][username]" value="<?php echo esc_attr( $options['field_mapping']['username'] ); ?>"></td></tr>
                    <tr><th>Email</th><td><input type="text" name="options[field_mapping][email]" value="<?php echo esc_attr( $options['field_mapping']['email'] ); ?>"></td></tr>
                    <tr><th>First Name</th><td><input type="text" name="options[field_mapping][first_name]" value="<?php echo esc_attr( $options['field_mapping']['first_name'] ); ?>"></td></tr>
                    <tr><th>Last Name</th><td><input type="text" name="options[field_mapping][last_name]" value="<?php echo esc_attr( $options['field_mapping']['last_name'] ); ?>"></td></tr>
                    <tr><th>Display Name</th><td><input type="text" name="options[field_mapping][display_name]" value="<?php echo esc_attr( $options['field_mapping']['display_name'] ); ?>"></td></tr>
                    <tr><th>Default Role</th><td><input type="text" name="options[field_mapping][default_role]" value="<?php echo esc_attr( $options['field_mapping']['default_role'] ); ?>"></td></tr>
                </table>
                <p><button class="button button-primary" type="submit">Save Settings</button></p>
            </form>
        </div>
        <?php
    }

    public static function render_login_page() {
        $options = self::get_options();
        if ( isset( $_POST['unified_sso_nonce'] ) && check_admin_referer( 'unified_sso_save', 'unified_sso_nonce' ) ) {
            if ( isset( $_POST['options']['login_settings'] ) ) {
                $options['login_settings'] = array_replace( $options['login_settings'], $_POST['options']['login_settings'] );
                self::save_options( $options );
            }
        }
        ?>
        <div class="wrap unified-sso-wrap">
            <h1>Login Settings</h1>
            <form method="post">
                <?php wp_nonce_field( 'unified_sso_save', 'unified_sso_nonce' ); ?>
                <p><label><input type="checkbox" name="options[login_settings][google_enabled]" value="1" <?php checked( $options['login_settings']['google_enabled'], true ); ?>> Enable Google Login</label></p>
                <p><label><input type="checkbox" name="options[login_settings][microsoft_enabled]" value="1" <?php checked( $options['login_settings']['microsoft_enabled'], true ); ?>> Enable Microsoft Login</label></p>
                <p><label>Redirect after login <input type="text" name="options[login_settings][redirect_login]" value="<?php echo esc_attr( $options['login_settings']['redirect_login'] ); ?>"></label></p>
                <p><label>Redirect after logout <input type="text" name="options[login_settings][redirect_logout]" value="<?php echo esc_attr( $options['login_settings']['redirect_logout'] ); ?>"></label></p>
                <p><label><input type="checkbox" name="options[login_settings][enable_reports]" value="1" <?php checked( $options['login_settings']['enable_reports'], true ); ?>> Enable User login Activity reports</label></p>
                <p>Shortcode for Google: [unified_sso_google] &nbsp; Microsoft: [unified_sso_microsoft]</p>
                <p><button class="button button-primary" type="submit">Save Settings</button></p>
            </form>
        </div>
        <?php
    }

    public static function render_button_page() {
        $options = self::get_options();
        if ( isset( $_POST['unified_sso_nonce'] ) && check_admin_referer( 'unified_sso_save', 'unified_sso_nonce' ) ) {
            if ( isset( $_POST['options']['button'] ) ) {
                $options['button'] = array_replace( $options['button'], $_POST['options']['button'] );
                self::save_options( $options );
            }
        }
        ?>
        <div class="wrap unified-sso-wrap">
            <h1>Login Button Customization</h1>
            <form method="post">
                <?php wp_nonce_field( 'unified_sso_save', 'unified_sso_nonce' ); ?>
                <p>
                    <label><input type="radio" name="options[button][style]" value="round" <?php checked( $options['button']['style'], 'round' ); ?>> Round</label>
                    <label><input type="radio" name="options[button][style]" value="round-edge" <?php checked( $options['button']['style'], 'round-edge' ); ?>> Round Edge</label>
                    <label><input type="radio" name="options[button][style]" value="square" <?php checked( $options['button']['style'], 'square' ); ?>> Square</label>
                    <label><input type="radio" name="options[button][style]" value="long" <?php checked( $options['button']['style'], 'long' ); ?>> Long Button</label>
                </p>
                <p><label>Background Colour <input type="text" name="options[button][bg_color]" value="<?php echo esc_attr( $options['button']['bg_color'] ); ?>" class="regular-text"></label></p>
                <p><label>Text Colour <input type="text" name="options[button][text_color]" value="<?php echo esc_attr( $options['button']['text_color'] ); ?>" class="regular-text"></label></p>
                <p><label>Custom Display Name <input type="text" name="options[button][display_name]" value="<?php echo esc_attr( $options['button']['display_name'] ); ?>"></label></p>
                <p><label>Icon Size <input type="number" name="options[button][icon_size]" value="<?php echo esc_attr( $options['button']['icon_size'] ); ?>"></label></p>
                <p><label>Icon Spacing <input type="number" name="options[button][icon_spacing]" value="<?php echo esc_attr( $options['button']['icon_spacing'] ); ?>"></label></p>
                <p><button class="button button-primary" type="submit">Save Settings</button></p>
            </form>
        </div>
        <?php
    }

    public static function render_reports_page() {
        $options = self::get_options();
        ?>
        <div class="wrap unified-sso-wrap">
            <h1>Reports</h1>
            <?php if ( $options['login_settings']['enable_reports'] ) : ?>
                <p><em>Report generation not implemented.</em></p>
            <?php else : ?>
                <p>Enable reports in Login Settings.</p>
            <?php endif; ?>
        </div>
        <?php
    }

    public static function render_data_page() {
        $options = self::get_options();
        if ( isset( $_POST['unified_sso_nonce'] ) && check_admin_referer( 'unified_sso_save', 'unified_sso_nonce' ) ) {
            $options['delete_data'] = ! empty( $_POST['options']['delete_data'] );
            self::save_options( $options );
        }
        ?>
        <div class="wrap unified-sso-wrap">
            <h1>Data Settings</h1>
            <form method="post">
                <?php wp_nonce_field( 'unified_sso_save', 'unified_sso_nonce' ); ?>
                <p><label><input type="checkbox" name="options[delete_data]" value="1" <?php checked( $options['delete_data'], true ); ?>> Delete all plugin data on uninstall</label></p>
                <p><button class="button button-primary" type="submit">Save Settings</button></p>
            </form>
        </div>
        <?php
    }

    public static function handle_oauth_callback() {
        if ( isset( $_GET['unified-sso'] ) && isset( $_GET['code'] ) ) {
            // OAuth callback handling will go here.
        }
    }

    public static function display_login_buttons() {
        $options = self::get_options();
        echo '<div class="unified-sso-buttons" style="margin-bottom:10px;">';
        if ( $options['login_settings']['google_enabled'] ) {
            $url = add_query_arg( array( 'unified-sso' => 'google' ), wp_login_url() );
            echo '<a class="button" href="' . esc_url( $url ) . '">Login with Google</a> ';
        }
        if ( $options['login_settings']['microsoft_enabled'] ) {
            $url = add_query_arg( array( 'unified-sso' => 'microsoft' ), wp_login_url() );
            echo '<a class="button" href="' . esc_url( $url ) . '">Login with Microsoft</a>';
        }
        echo '</div>';
    }
}
