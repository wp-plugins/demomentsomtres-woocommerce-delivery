<?php
/**
 * Plugin Name: DeMomentSomTres Woocommerce Delivery Customization
 * Plugin URI:  http://demomentsomtres.com/english/wordpress-plugins/woocommerce-delivery-customization/
 * Version: 1.3.1
 * Author URI: demomentsomtres.com
 * Author: Marc Queralt
 * Description: Extend Woocommerce plugin to add delivery date on other aspects to checkout
 * Requires at least: 3.9
 * Tested up to: 3.9.1
 * License: GPLv3 or later
 * License URI: http://www.opensource.org/licenses/gpl-license.php
 */
define('DMS3_WCDD_TEXT_DOMAIN', 'DeMomentSomTres-WC-deliveryCustomization');

if (!in_array('demomentsomtres-tools/demomentsomtres-tools.php', apply_filters('active_plugins', get_option('active_plugins')))):
    add_action('admin_notices', 'DMS3_WCDD_messageNoTools');
else:
    if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))):
        add_action('admin_notices', 'DMS3_WCDD_messageNoWC');
    else:
        $dms3_wcdc = new DeMomentSomTresWCdeliveryCustomization();
    endif;
endif;

function DMS3_WCDD_messageNoTools() {
    ?>
    <div class="error">
        <p><?php _e('The DeMomentSomTres WooCommerce Delivery Customization plugin requires the free DeMomentSomTres Tools plugin.', DMS3_WCDD_TEXT_DOMAIN); ?>
            <br/>
            <a href="http://demomentsomtres.com/english/wordpress-plugins/demomentsomtres-tools/?utm_source=web&utm_medium=wordpress&utm_campaign=adminnotice&utm_term=dms3WCdeliveryDate" target="_blank"><?php _e('Download it here', DMS3_WCDD_TEXT_DOMAIN); ?></a>
        </p>
    </div>
    <?php
}

function DMS3_WCDD_messageNoWC() {
    ?>
    <div class="error">
        <p>
            <?php _e('The DeMomentSomTres WooCommerce Delivery Customization plugin requires WooCommerce.', DMS3_WCDD_TEXT_DOMAIN); ?>
        </p>
    </div>
    <?php
}

class DeMomentSomTresWCdeliveryCustomization {

    const TEXT_DOMAIN = DMS3_WCDD_TEXT_DOMAIN;
    const MENU_SLUG = 'dmst_wc_deliveryCustom';
    const OPTIONS = 'dmst_wc_delivery_customization_options';
    const PAGE = 'dmst_wc_deliveryCustom';
    const SECTION_WC_DAYS = 'dmst_wcdd_days';
    const SECTION_WC_MESSAGES = 'dmst_wcdd_messages';
    const OPTION_AFTER_DAYS = 'countDays';
    const OPTION_LIMIT_HOUR = 'limitHour';
    const OPTION_LIMIT_MINUTES = 'limitMinute';
    const OPTION_PLANNING_SCOPE = 'monthsPlanned';
    const OPTION_DELIVERY_DAYS = 'deliveryDates';
    const OPTION_DELIVERY_HOLIDAYS = 'deliveryHolidays';
    const OPTION_DELIVERY_RANGES = 'deliveryRanges';
    const OPTION_DATE_FORMAT = 'dateFormat';
    const OPTION_DATE_FORMAT_PHP = 'dateFormatPhp';
    const OPTION_FIRST_DAY_OF_WEEK = 'firstDayOfWeek';
    const OPTION_MESSAGE_TOP_OF_DELIVERY = 'messageTopDelivery';
    const OPTION_MESSAGE_YOU_NEED_HELP = 'messageYouNeedHelp';
    const OPTION_MESSAGE_CHECKOUT_MESSAGE = 'messageCheckout';
    const CHECKOUT_FIELD_DELIVERY_DATE = 'dms3_wcdd_deliveryDate';
    const CHECKOUT_FIELD_DELIVERY_RANGES = 'dms3_wcdd_deliveryRanges';
    const CHECKOUT_AUX_DELIVERY_RANGES = 'dms3_wcdd_deliveryRangesKeys';
    const CHECKOUT_TXT_DELIVERY_RANGES = 'dms3_wcdd_deliveryRangesText';
    const CHECKOUT_FIELD_CONTACT1 = 'dms3_wcdd_deliveryContact1';
    const CHECKOUT_FIELD_PHONE1 = 'dms3_wcdd_deliveryPhone1';
    const CHECKOUT_FIELD_CONTACT2 = 'dms3_wcdd_deliveryContact2';
    const CHECKOUT_FIELD_PHONE2 = 'dms3_wcdd_deliveryPhone2';

//    const OPTION_FILTER_CATS = OPTION_FILTER_CATS;

    private $pluginURL;
    private $pluginPath;
    private $langDir;

    /**
     * @since 1.0
     */
    function __construct() {
        $this->pluginURL = plugin_dir_url(__FILE__);
        $this->pluginPath = plugin_dir_path(__FILE__);
        $this->langDir = dirname(plugin_basename(__FILE__)) . '/languages';

        add_action('plugins_loaded', array(&$this, 'plugin_init'));
        add_action('admin_menu', array(&$this, 'admin_menu'));
        add_action('admin_init', array(&$this, 'admin_init'));

        add_action('woocommerce_before_order_notes', array(&$this, 'checkout_customization'));
        add_action('woocommerce_checkout_update_order_meta', array(&$this, 'checkout_update_meta'));
        add_action('woocommerce_checkout_process', array(&$this, 'checkout_process'));
        add_action('woocommerce_thankyou', array(&$this, 'show_delivery_instructions'));
        add_action('woocommerce_email_before_order_table', array(&$this, 'email_delivery_instructions'));

        add_action('woocommerce_before_cart_contents', array(&$this, 'message'));
        add_action('woocommerce_before_checkout_form', array(&$this, 'message'));
    }

    /**
     * @since 1.0
     */
    function plugin_init() {
        load_plugin_textdomain(DMS3_WCDD_TEXT_DOMAIN, false, $this->langDir);
    }

    /**
     * @since 1.0
     */
    function admin_menu() {
        add_submenu_page('woocommerce', __('DeMomentSomTres Delivery Customization', self::TEXT_DOMAIN), __('DeMomentSomTres Delivery Customization', self::TEXT_DOMAIN), 'manage_options', self::MENU_SLUG, array(&$this, 'admin_page'));
    }

    /**
     * @since 1.0
     */
    function admin_page() {
        ?>
        <div class="wrap">
            <h2><?php _e('DeMomentSomTres WooCommerce Delivery Customization', self::TEXT_DOMAIN); ?></h2>
            <form action="options.php" method="post">
                <?php settings_fields(self::OPTIONS); ?>
                <?php do_settings_sections(self::PAGE); ?>
                <br/>
                <input name="Submit" class="button button-primary" type="submit" value="<?php _e('Save Changes', self::TEXT_DOMAIN); ?>"/>
            </form>
        </div>
        <div style="background-color:#eee;/*display:none;*/">
            <h2><?php _e('Options', self::TEXT_DOMAIN); ?></h2>
            <pre style="font-size:0.8em;"><?php print_r(get_option(self::OPTIONS)); ?></pre>
        </div>
        <?php
    }

    /**
     * @since 1.0
     */
    function admin_init() {
        register_setting(self::OPTIONS, self::OPTIONS, array(&$this, 'admin_validate_options'));

        add_settings_section(self::SECTION_WC_DAYS, __('Main parameters', self::TEXT_DOMAIN), array(&$this, 'admin_section_days'), self::PAGE);

        add_settings_field(self::OPTION_AFTER_DAYS, __('Days after', self::TEXT_DOMAIN), array(&$this, 'admin_field_days_after'), self::PAGE, self::SECTION_WC_DAYS);
        add_settings_field(self::OPTION_LIMIT_HOUR, __('Latest order hour', self::TEXT_DOMAIN), array(&$this, 'admin_field_limit_time'), self::PAGE, self::SECTION_WC_DAYS);
        add_settings_field(self::OPTION_PLANNING_SCOPE, __('Months to show in delivery calendar', self::TEXT_DOMAIN), array(&$this, 'admin_field_planning_scope'), self::PAGE, self::SECTION_WC_DAYS);
        add_settings_field(self::OPTION_DELIVERY_DAYS, __('Delivery days', self::TEXT_DOMAIN), array(&$this, 'admin_field_delivery_days'), self::PAGE, self::SECTION_WC_DAYS);
        add_settings_field(self::OPTION_DELIVERY_RANGES, __('Delivery ranges', self::TEXT_DOMAIN), array(&$this, 'admin_field_deliveryRanges'), self::PAGE, self::SECTION_WC_DAYS);
        add_settings_field(self::OPTION_DELIVERY_HOLIDAYS, __('Delivery holidays', self::TEXT_DOMAIN), array(&$this, 'admin_field_delivery_holidays'), self::PAGE, self::SECTION_WC_DAYS);
        add_settings_field(self::OPTION_DATE_FORMAT, __('Javascript Date Format', self::TEXT_DOMAIN), array(&$this, 'admin_field_date_format'), self::PAGE, self::SECTION_WC_DAYS);
        add_settings_field(self::OPTION_DATE_FORMAT_PHP, __('php Date Format', self::TEXT_DOMAIN), array(&$this, 'admin_field_date_format_php'), self::PAGE, self::SECTION_WC_DAYS);

        add_settings_section(self::SECTION_WC_MESSAGES, __('Customizable Messages', self::TEXT_DOMAIN), array(&$this, 'admin_section_messages'), self::PAGE);

        add_settings_field(self::OPTION_MESSAGE_CHECKOUT_MESSAGE, __('Message at the top of checkout page', self::TEXT_DOMAIN), array(&$this, 'admin_field_MessageCheckout'), self::PAGE, self::SECTION_WC_MESSAGES);
        add_settings_field(self::OPTION_MESSAGE_TOP_OF_DELIVERY, __('Top of delivery customization', self::TEXT_DOMAIN), array(&$this, 'admin_field_MessagetopOfDelivery'), self::PAGE, self::SECTION_WC_MESSAGES);
        add_settings_field(self::OPTION_MESSAGE_YOU_NEED_HELP, __('Do You Need Help', self::TEXT_DOMAIN), array(&$this, 'admin_field_MessageYouNeedHelp'), self::PAGE, self::SECTION_WC_MESSAGES);
    }

    /**
     * @since 1.0
     */
    function admin_validate_options($input = array()) {
        $deliveryDays = array();
        $name = 'deliveryDates-sunday';
        if (isset($input[$name])):
            $deliveryDays[] = 0;
            unset($input[$name]);
        endif;
        $name = 'deliveryDates-monday';
        if (isset($input[$name])):
            $deliveryDays[] = 1;
            unset($input[$name]);
        endif;
        $name = 'deliveryDates-tuesday';
        if (isset($input[$name])):
            $deliveryDays[] = 2;
            unset($input[$name]);
        endif;
        $name = 'deliveryDates-wednesday';
        if (isset($input[$name])):
            $deliveryDays[] = 3;
            unset($input[$name]);
        endif;
        $name = 'deliveryDates-thursday';
        if (isset($input[$name])):
            $deliveryDays[] = 4;
            unset($input[$name]);
        endif;
        $name = 'deliveryDates-friday';
        if (isset($input[$name])):
            $deliveryDays[] = 5;
            unset($input[$name]);
        endif;
        $name = 'deliveryDates-saturday';
        if (isset($input[$name])):
            $deliveryDays[] = 6;
            unset($input[$name]);
        endif;
        $input[self::OPTION_DELIVERY_DAYS] = $deliveryDays;
        $html = array();
        $html[self::OPTION_MESSAGE_TOP_OF_DELIVERY] = $input[self::OPTION_MESSAGE_TOP_OF_DELIVERY];
        $html[self::OPTION_MESSAGE_YOU_NEED_HELP] = $input[self::OPTION_MESSAGE_YOU_NEED_HELP];
        $html[self::OPTION_MESSAGE_CHECKOUT_MESSAGE] = $input[self::OPTION_MESSAGE_CHECKOUT_MESSAGE];
        $input = DeMomentSomTresTools::adminHelper_esc_attr($input);
        $input[self::OPTION_MESSAGE_TOP_OF_DELIVERY] = $html[self::OPTION_MESSAGE_TOP_OF_DELIVERY];
        $input[self::OPTION_MESSAGE_YOU_NEED_HELP] = $html[self::OPTION_MESSAGE_YOU_NEED_HELP];
        $input[self::OPTION_MESSAGE_CHECKOUT_MESSAGE] = $html[self::OPTION_MESSAGE_CHECKOUT_MESSAGE];
        return $input;
    }

    /**
     * @since 1.0
     */
    function admin_section_days() {
        echo '<p>' . __('Main parameters to control delivery date', self::TEXT_DOMAIN) . '</p>';
    }

    /**
     * @since 1.0
     */
    function admin_section_messages() {
        echo '<p>' . __('Messages to show in the delivery area', self::TEXT_DOMAIN) . '</p>';
    }

    /**
     * @since 1.0
     */
    function admin_field_days_after() {
        $name = self::OPTION_AFTER_DAYS;
        $value = DeMomentSomTresTools::get_option(self::OPTIONS, $name, 0);
        DeMomentSomTresTools::adminHelper_inputArray(self::OPTIONS, $name, $value, array(
//            'class' => 'regular-text'
        ));
        echo "<p style='font-size:0.8em;'>"
        . __('Delivery date must be after, at least this number of days.', self::TEXT_DOMAIN);
    }

    /**
     * @since 1.0
     */
    function admin_field_limit_time() {
        $name = self::OPTION_LIMIT_HOUR;
        $value = DeMomentSomTresTools::get_option(self::OPTIONS, $name, 23);
        DeMomentSomTresTools::adminHelper_inputArray(self::OPTIONS, $name, $value, array(
//            'class' => 'regular-text'
        ));
        echo ":";
        $name = self::OPTION_LIMIT_MINUTES;
        $value = DeMomentSomTresTools::get_option(self::OPTIONS, $name, 59);
        DeMomentSomTresTools::adminHelper_inputArray(self::OPTIONS, $name, $value, array(
//            'class' => 'regular-text'
        ));
        echo "<p style='font-size:0.8em;'>"
        . __('If order happens after this time, an additional day will be added to delivery date.', self::TEXT_DOMAIN);
    }

    /**
     * @since 1.0
     */
    function admin_field_deliveryRanges() {
        $name = self::OPTION_DELIVERY_RANGES;
        $value = DeMomentSomTresTools::get_option(self::OPTIONS, $name, '');
        DeMomentSomTresTools::adminHelper_inputArray(self::OPTIONS, $name, $value, array(
            'type' => 'textarea',
            'rows' => 5,
            'class' => 'regular-text'
        ));
        echo "<p style='font-size:0.8em;'>"
        . __('Put all the delivery ranges in a semicolumn (;) separated list', self::TEXT_DOMAIN);
    }

    /**
     * @since 1.0
     */
    function admin_field_date_format() {
        $name = self::OPTION_DATE_FORMAT;
        $value = DeMomentSomTresTools::get_option(self::OPTIONS, $name, 'd/mm/yy');
        DeMomentSomTresTools::adminHelper_inputArray(self::OPTIONS, $name, $value, array(
            'class' => 'regular-text'
        ));
        echo "<p style='font-size:0.8em;'>"
        . __('Date format to use in the datepicker.', self::TEXT_DOMAIN);
    }

    /**
     * @since 1.0
     */
    function admin_field_date_format_php() {
        $name = self::OPTION_DATE_FORMAT_PHP;
        $value = DeMomentSomTresTools::get_option(self::OPTIONS, $name, 'j/m/y');
        DeMomentSomTresTools::adminHelper_inputArray(self::OPTIONS, $name, $value, array(
            'class' => 'regular-text'
        ));
        echo "<p style='font-size:0.8em;'>"
        . __('Date format for php equivalent to the javascript one.', self::TEXT_DOMAIN);
    }

    /**
     * @since 1.0
     */
    function admin_field_planning_scope() {
        $name = self::OPTION_PLANNING_SCOPE;
        $value = DeMomentSomTresTools::get_option(self::OPTIONS, $name, '2');
        DeMomentSomTresTools::adminHelper_inputArray(self::OPTIONS, $name, $value, array(
        ));
    }

    /**
     * @since 1.0
     */
    function admin_field_delivery_days() {
        $name = self::OPTION_DELIVERY_DAYS;
        $value = DeMomentSomTresTools::get_option(self::OPTIONS, $name, array(1, 2, 3, 4, 5));
        $sunday = (in_array(0, $value)) ? 'on' : '';
        $monday = (in_array(1, $value)) ? 'on' : '';
        $tuesday = (in_array(2, $value)) ? 'on' : '';
        $wednesday = (in_array(3, $value)) ? 'on' : '';
        $thursday = (in_array(4, $value)) ? 'on' : '';
        $friday = (in_array(5, $value)) ? 'on' : '';
        $saturday = (in_array(6, $value)) ? 'on' : '';
        DeMomentSomTresTools::adminHelper_inputArray(self::OPTIONS, $name . '-sunday', $sunday, array(
            'type' => 'checkbox',
        ));
        echo __('Sunday', self::TEXT_DOMAIN) . '<br/>';
        DeMomentSomTresTools::adminHelper_inputArray(self::OPTIONS, $name . '-monday', $monday, array(
            'type' => 'checkbox',
        ));
        echo __('Monday', self::TEXT_DOMAIN) . '<br/>';
        DeMomentSomTresTools::adminHelper_inputArray(self::OPTIONS, $name . '-tuesday', $tuesday, array(
            'type' => 'checkbox',
        ));
        echo __('Tuesday', self::TEXT_DOMAIN) . '<br/>';
        DeMomentSomTresTools::adminHelper_inputArray(self::OPTIONS, $name . '-wednesday', $wednesday, array(
            'type' => 'checkbox',
        ));
        echo __('Wednesday', self::TEXT_DOMAIN) . '<br/>';
        DeMomentSomTresTools::adminHelper_inputArray(self::OPTIONS, $name . '-thursday', $thursday, array(
            'type' => 'checkbox',
        ));
        echo __('Thursday', self::TEXT_DOMAIN) . '<br/>';
        DeMomentSomTresTools::adminHelper_inputArray(self::OPTIONS, $name . '-friday', $friday, array(
            'type' => 'checkbox',
        ));
        echo __('Friday', self::TEXT_DOMAIN) . '<br/>';
        DeMomentSomTresTools::adminHelper_inputArray(self::OPTIONS, $name . '-saturday', $saturday, array(
            'type' => 'checkbox',
        ));
        echo __('Saturday', self::TEXT_DOMAIN);
    }

    /**
     * @since 1.0
     */
    function admin_field_delivery_holidays() {
        $name = self::OPTION_DELIVERY_HOLIDAYS;
        $value = DeMomentSomTresTools::get_option(self::OPTIONS, $name, '');
        DeMomentSomTresTools::adminHelper_inputArray(self::OPTIONS, $name, $value, array(
            'type' => 'textarea',
            'rows' => 5,
            'class' => 'regular-text'
        ));
        echo "<p style='font-size:0.8em;'>"
        . __('Column (;) separated list of delivery holidays dates in format YYYY-M-D.', self::TEXT_DOMAIN)
        . '<br/>'
        . __('If month or day is lower than 10, please use ONLY ONE DIGIT', self::TEXT_DOMAIN)
        . '</p>';
    }

    /**
     * @since 1.0
     */
    function admin_field_messageTopOfDelivery() {
        $name = self::OPTION_MESSAGE_TOP_OF_DELIVERY;
        $value = DeMomentSomTresTools::get_option(self::OPTIONS, $name, __('Please give as much information as possible in order to inform our delivery boy how and when to deliver your purchase', self::TEXT_DOMAIN));
        DeMomentSomTresTools::adminHelper_inputArray(self::OPTIONS, $name, $value, array(
            'type' => 'textarea',
            'rows' => 5,
            'class' => 'regular-text'
        ));
        echo "<p style='font-size:0.8em;'>"
        . __('Message to be shown just after the Delivery Instructions title', self::TEXT_DOMAIN)
        . '<br/>'
        . __('HTML allowed', self::TEXT_DOMAIN)
        . '</p>';
    }

    /**
     * @since 1.0
     */
    function admin_field_messageYouNeedHelp() {
        $name = self::OPTION_MESSAGE_YOU_NEED_HELP;
        $value = DeMomentSomTresTools::get_option(self::OPTIONS, $name, __("If you need help, don&apos;t hesitate to contact us.", self::TEXT_DOMAIN));
        DeMomentSomTresTools::adminHelper_inputArray(self::OPTIONS, $name, $value, array(
            'type' => 'textarea',
            'rows' => 5,
            'class' => 'regular-text'
        ));
        echo "<p style='font-size:0.8em;'>"
        . __('Message to be show just at the bottom of Delivery Instructions focused on Help the user.', self::TEXT_DOMAIN)
        . '<br/>'
        . __('HTML allowed', self::TEXT_DOMAIN)
        . '<br/>'
        . '<strong>' . __('If you set an url, please put it as an ABSOLUTE address', self::TEXT_DOMAIN) . '</strong>'
        . '</p>';
    }

    /**
     * @since 1.1
     */
    function admin_field_messageCheckout() {
        $name = self::OPTION_MESSAGE_CHECKOUT_MESSAGE;
        $value = DeMomentSomTresTools::get_option(self::OPTIONS, $name);
        DeMomentSomTresTools::adminHelper_inputArray(self::OPTIONS, $name, $value, array(
            'type' => 'textarea',
            'rows' => 5,
            'class' => 'regular-text'
        ));
        echo "<p style='font-size:0.8em;'>"
        . __('Message to be shown every time that someone enters checkout', self::TEXT_DOMAIN)
        . '<br/>'
        . __('HTML allowed', self::TEXT_DOMAIN)
        . '</p>';
    }

    /**
     * @since 1.0
     */
    function checkout_customization($checkout) {
        wp_enqueue_script('jquery-ui-datepicker');
//        wp_enqueue_style('jquery-ui', "http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/smoothness/jquery-ui.css", '', '', false);
//        wp_enqueue_style('datepicker', plugins_url('/css/datepicker.css', __FILE__), '', '', false);
        ?>
        <script language="javascript">jQuery(document).ready(function() {
                jQuery("#<?php echo self::CHECKOUT_FIELD_DELIVERY_DATE; ?>").width("150px");
                jQuery("#<?php echo self::CHECKOUT_FIELD_DELIVERY_DATE; ?>").val("<?php echo $this->get_firstDay(); ?>").datepicker(
                        {
                            maxDate: "<?php echo DeMomentSomTresTools::get_option(self::OPTIONS, self::OPTION_PLANNING_SCOPE, 3); ?>M",
                            dateFormat: "<?php echo DeMomentSomTresTools::get_option(self::OPTIONS, self::OPTION_DATE_FORMAT, 'd/mm/yy'); ?>",
                            minDate: <?php echo $this->get_MinimumDelay(); ?>,
                            firstDay: <?php echo DeMomentSomTresTools::get_option(self::OPTIONS, self::OPTION_FIRST_DAY_OF_WEEK, 1); ?>,
                            beforeShowDay: dms3WCDDDisableDays,
                        }
                );
            });
            function dms3WCDDDisableDays(date) {
                var deliveryDays = [<?php echo $this->get_deliveryDaysToDisable(); ?>];
                var holidays = [<?php echo $this->get_DeliveryHolidays(); ?>];
                var dw = date.getDay();
                var year = date.getYear() + 1900;
                var month = date.getMonth() + 1;
                var day = date.getDate();
                var sd = year + '-' + month + '-' + day;
                var isH = (-1 < holidays.indexOf(sd));
                var isDD = (-1 < deliveryDays.indexOf(dw));
                return [isDD && !isH];
            }
        </script>
        <div id="dms3_delivery_customization" style="width:100%;clear:both;">
            <h3>
                <?php _e('Delivery instructions', self::TEXT_DOMAIN); ?>
            </h3>
            <?php if ('' != DeMomentSomTresTools::get_option(self::OPTIONS, self::OPTION_MESSAGE_TOP_OF_DELIVERY)): ?>
                <div class="message-intro" style='clear:both;'>
                    <?php echo DeMomentSomTresTools::get_option(self::OPTIONS, self::OPTION_MESSAGE_TOP_OF_DELIVERY); ?>
                </div>
            <?php endif; ?>
            <?php
            woocommerce_form_field(self::CHECKOUT_FIELD_DELIVERY_DATE, array(
                'type' => 'text',
                'class' => array('form-row-first'),
                'label' => __('Choose a delivery Date', self::TEXT_DOMAIN),
                'required' => false,
                'placeholder' => __('Delivery Date', self::TEXT_DOMAIN),
                    ), $checkout->get_value(self::CHECKOUT_FIELD_DELIVERY_DATE)
            );
            ?>
            <?php
            $deliveryRanges = DeMomentSomTresTools::get_option(self::OPTIONS, self::OPTION_DELIVERY_RANGES, '');
            if ('' != $deliveryRanges):
                echo '<div class="form-row form-row-last">';
                echo '<p>' . __('Tell us which time is the best to get the delivery', self::TEXT_DOMAIN) . '</p>';
                $deliveryRangesArray = explode(';', $deliveryRanges);
                $values = $checkout->get_value(self::CHECKOUT_FIELD_DELIVERY_RANGES);
                foreach ($deliveryRangesArray as $k => $range):
                    woocommerce_form_field(self::CHECKOUT_AUX_DELIVERY_RANGES . "[$k]", array(
                        'type' => 'checkbox',
                        'label' => $range,
                            ), 1
                    );
                    woocommerce_form_field(self::CHECKOUT_TXT_DELIVERY_RANGES . "[$k]", array(
                        'type' => 'text',
                        'class' => array('hidden')
                            ), $range
                    );
                endforeach;
                echo '</div>';
            endif;
            ?>
            <div class="form-row" style="clear:both;">
                <h4>
                    <?php _e('Deliver to...', self::TEXT_DOMAIN); ?>
                </h4>
            </div>
            <div class="form-row form-row-first">
                <?php
                woocommerce_form_field(self::CHECKOUT_FIELD_CONTACT1, array(
                    'type' => 'text',
//                'class' => array('form-row-first'),
                    'label' => __('Main Contact', self::TEXT_DOMAIN),
                    'required' => false,
                    'placeholder' => __('Name', self::TEXT_DOMAIN),
                        ), $checkout->get_value(self::CHECKOUT_FIELD_CONTACT1));
                ?>
                <?php
                woocommerce_form_field(self::CHECKOUT_FIELD_PHONE1, array(
                    'type' => 'text',
//                'class' => array('form-row-last'),
                    'label' => __('Main phone', self::TEXT_DOMAIN),
                    'required' => false,
                    'placeholder' => __('Contact phone', self::TEXT_DOMAIN),
                        ), $checkout->get_value(self::CHECKOUT_FIELD_PHONE1));
                ?>
            </div>
            <div class="form-row form-row-last">
                <?php
                woocommerce_form_field(self::CHECKOUT_FIELD_CONTACT2, array(
                    'type' => 'text',
//                'class' => array('form-row-first'),
                    'label' => __('Alternative Contact', self::TEXT_DOMAIN),
                    'required' => false,
                    'placeholder' => __('Name', self::TEXT_DOMAIN),
                        ), $checkout->get_value(self::CHECKOUT_FIELD_CONTACT2));
                ?>
                <?php
                woocommerce_form_field(self::CHECKOUT_FIELD_PHONE2, array(
                    'type' => 'text',
//                'class' => array('form-row-last'),
                    'label' => __('Alterantive Phone', self::TEXT_DOMAIN),
                    'required' => false,
                    'placeholder' => __('Contact phone', self::TEXT_DOMAIN),
                        ), $checkout->get_value(self::CHECKOUT_FIELD_PHONE2));
                ?>
            </div>
            <?php if ('' != DeMomentSomTresTools::get_option(self::OPTIONS, self::OPTION_MESSAGE_YOU_NEED_HELP)): ?>
                <div class="message-help" style='clear:both;'>
                    <?php echo DeMomentSomTresTools::get_option(self::OPTIONS, self::OPTION_MESSAGE_YOU_NEED_HELP); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * @since 1.0
     */
    function checkout_update_meta($order_id) {
        $name = self::CHECKOUT_FIELD_DELIVERY_DATE;
        if ($_POST[$name]) {
            update_post_meta($order_id, $name, esc_attr($_POST[$name]));
        }
        $name = self::CHECKOUT_FIELD_CONTACT1;
        if ($_POST[$name]) {
            update_post_meta($order_id, $name, esc_attr($_POST[$name]));
        }
        $name = self::CHECKOUT_FIELD_PHONE1;
        if ($_POST[$name]) {
            update_post_meta($order_id, $name, esc_attr($_POST[$name]));
        }
        $name = self::CHECKOUT_FIELD_CONTACT2;
        if ($_POST[$name]) {
            update_post_meta($order_id, $name, esc_attr($_POST[$name]));
        }
        $name = self::CHECKOUT_FIELD_PHONE2;
        if ($_POST[$name]) {
            update_post_meta($order_id, $name, esc_attr($_POST[$name]));
        }
        if ($_POST[self::CHECKOUT_AUX_DELIVERY_RANGES] && $_POST[self::CHECKOUT_TXT_DELIVERY_RANGES]):
            $source = $_POST[self::CHECKOUT_AUX_DELIVERY_RANGES];
            $texts = $_POST[self::CHECKOUT_TXT_DELIVERY_RANGES];
            $ranges = array();
            foreach ($source as $k => $v):
                $ranges[] = esc_attr($texts[$k]);
            endforeach;
            update_post_meta($order_id, self::CHECKOUT_FIELD_DELIVERY_RANGES, implode(';', $ranges));
        endif;
    }

    /**
     * @since 1.0
     */
    function checkout_process() {
        global $woocommerce;
        if (!$_POST[self::CHECKOUT_FIELD_DELIVERY_DATE])
            $woocommerce->add_error(__('Please enter a Delivery Date', self::TEXT_DOMAIN));
        if (!$_POST[self::CHECKOUT_FIELD_CONTACT1] || !$_POST[self::CHECKOUT_FIELD_PHONE1])
            $woocommerce->add_error(__('Please enter a Main Contact and a Phone Number', self::TEXT_DOMAIN));
        if (!$_POST[self::CHECKOUT_AUX_DELIVERY_RANGES])
            $woocommerce->add_error(__('Select at least one Delivery Range', self::TEXT_DOMAIN));
    }

    /**
     * Minimum delay from purchase date and delivery date
     * @since 1.0
     * @return integer days
     */
    function get_MinimumDelay() {
        $delay = DeMomentSomTresTools::get_option(self::OPTIONS, self::OPTION_AFTER_DAYS, 0);
        $limitHour = DeMomentSomTresTools::get_option(self::OPTIONS, self::OPTION_LIMIT_HOUR, 23);
        $limitMinutes = DeMomentSomTresTools::get_option(self::OPTIONS, self::OPTION_LIMIT_MINUTES, 59);
        $time = current_time('timestamp', false);
        $today = getdate($time);
        $limitTime = mktime($limitHour, $limitMinutes, 0, $today['mon'], $today['mday'], $today['year']);
        if ($time > $limitTime):
            $delay = $delay + 1;
        endif;
        return $delay;
    }

    /**
     * First available delivery day
     * @since 1.1
     * @return integer days
     */
    function get_firstDay() {
        $delay = self::get_MinimumDelay();
        $timeToServe = current_time('timestamp', false) + $delay * 3600 * 24;
        $theDate = getdate($timeToServe);
        $dayToServe = mktime(0, 0, 0, $theDate['mon'], $theDate['mday'], $theDate['year']);
        while (!$this->isValidServiceDay($dayToServe)):
            $dayToServe = $dayToServe + 24 * 3600;
        endwhile;
        $result = date(DeMomentSomTresTools::get_option(self::OPTIONS, self::OPTION_DATE_FORMAT_PHP), $dayToServe);
        return $result;
    }

    /**
     * Checks if the provided day is a valid delivery date
     * @since 1.3
     */
    function isValidServiceDay($timestamp) {
        $date = getdate($timestamp);
        $weekdaysToServeString = $this->get_deliveryDaysToDisable();
        $weekdaysToServe = explode(',', $weekdaysToServeString);
        if (!in_array($date['wday'], $weekdaysToServe)):
            return false;
        else:
            $holidays = $this->get_deliveryHolidays();
            $aHolidays = explode(',', $holidays);
            foreach ($aHolidays as $stringday):
                $adate = DateTime::createFromFormat('"Y-n-j"', $stringday);
                $stdate = getdate(date_timestamp_get($adate));
                if (($date['year'] == $stdate['year']) && ($date['yday'] == $stdate['yday'])):
                    return false;
                endif;
            endforeach;
        endif;
        return true;
    }

    /**
     * get the days that have to be disabled
     * @since 1.0
     * @return string
     */
    function get_deliveryDaysToDisable() {
        $days = DeMomentSomTresTools::get_option(self::OPTIONS, self::OPTION_DELIVERY_DAYS, array());
        return implode(',', $days);
    }

    /**
     * @since 1.0
     * @return string
     */
    function get_deliveryHolidays() {
        $days = DeMomentSomTresTools::get_option(self::OPTIONS, self::OPTION_DELIVERY_HOLIDAYS, '');
        $array = explode(';', $days);
        foreach ($array as $k => $v):
            $array[$k] = '"' . $v . '"';
        endforeach;
        return implode(',', $array);
    }

    /**
     * @since 1.0
     */
    function email_delivery_instructions($order, $is_admin_email) {
        $this->show_delivery_instructions($order->id, true);
    }

    /**
     * @since 1.0
     */
    function show_delivery_instructions($orderid, $isMail = false) {
        $payment_method = get_post_meta($orderid, '_payment_method_title');
        $delivery_date = get_post_meta($orderid, self::CHECKOUT_FIELD_DELIVERY_DATE, true);
        $delivery_ranges = get_post_meta($orderid, self::CHECKOUT_FIELD_DELIVERY_RANGES, true);
        $delivery_contact_1 = get_post_meta($orderid, self::CHECKOUT_FIELD_CONTACT1, true);
        $delivery_phone_1 = get_post_meta($orderid, self::CHECKOUT_FIELD_PHONE1, true);
        $delivery_contact_2 = get_post_meta($orderid, self::CHECKOUT_FIELD_CONTACT2, true);
        $delivery_phone_2 = get_post_meta($orderid, self::CHECKOUT_FIELD_PHONE2, true);
        $moreHelp = DeMomentSomTresTools::get_option(self::OPTIONS, self::OPTION_MESSAGE_YOU_NEED_HELP, '');

        if (empty($delivery_date)):
            $delivery_date_text = __('As soon as possible', self::TEXT_DOMAIN);
        else:
            $delivery_date_text = $delivery_date;
        endif;
        $ranges = explode(';', $delivery_ranges);

        echo '<h2>' . __('Delivery information', self::TEXT_DOMAIN) . '</h2>';
        echo '<p>' . __('Date', self::TEXT_DOMAIN) . ': ' . $delivery_date_text;
        if (count($ranges) != 0):
            $ranges_text = implode(' o ', $ranges);
            echo ' (' . $ranges_text . ')';
        endif;
        echo '</p>';
        if (!empty($delivery_contact_1)):
            echo '<p>' . __('We will try to deliver this order to', self::TEXT_DOMAIN);
            echo ' ' . $delivery_contact_1 . ' (' . $delivery_phone_1 . ')';
            if (!empty($delivery_contact_2)):
                echo ' ' . __('if we cannot reach the first contact we will try to deliver the order to', self::TEXT_DOMAIN);
                echo ' ' . $delivery_contact_2 . ' (' . $delivery_phone_2 . ')';
            endif;
            echo '</p>';
        endif;
        if ($isMail):
            echo '<h2>' . __('Payment', self::TEXT_DOMAIN) . '</h2>';
            echo '<p>' . __('Payment Method', self::TEXT_DOMAIN) . ': <strong>' . $payment_method[0] . '</strong></p>';
        endif;
        if (!empty($moreHelp)):
            echo $moreHelp;
        endif;
    }

    /**
     * Prints the message
     * @since 1.1
     * @global type $woocommerce
     */
    function message() {
        global $woocommerce;
        $message = DeMomentSomTresTools::get_option(self::OPTIONS, self::OPTION_MESSAGE_CHECKOUT_MESSAGE);
        if ($message):
            ?>
            <div class="woocommerce-info">
                <?php echo $message; ?>
            </div>
            <?php
        endif;
    }

}
