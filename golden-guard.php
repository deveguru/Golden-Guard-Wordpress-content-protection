<?php
/*
 * Plugin Name: Golden Guard
 * Plugin URI: https://github.com/deveguru
 * Description: Complete website protection against content copying, right-click, keyboard shortcuts, and developer tools access with role-based control
 * Version: 1.0.0
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * Author: Alireza Fatemi
 * Author URI: https://alirezafatemi.ir
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: golden-guard
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
 exit;
}

class GoldenGuard {
 
 private $option_name = 'golden_guard_settings';
 
 public function __construct() {
 add_action('init', array($this, 'init'));
 add_action('admin_menu', array($this, 'add_admin_menu'));
 add_action('admin_init', array($this, 'settings_init'));
 add_action('wp_enqueue_scripts', array($this, 'enqueue_protection_scripts'));
 add_action('wp_head', array($this, 'add_protection_styles'));
 add_action('wp_footer', array($this, 'add_protection_scripts'));
 add_action('template_redirect', array($this, 'check_user_role_protection'));
 add_filter('wp_headers', array($this, 'add_security_headers'));
 }

 public function init() {
 // Initialize default settings
 if (get_option($this->option_name) === false) {
 $default_settings = array(
 'enabled_roles' => array('subscriber'),
 'enable_protection' => 1,
 'disable_right_click' => 1,
 'disable_text_selection' => 1,
 'disable_keyboard_shortcuts' => 1,
 'disable_developer_tools' => 1,
 'disable_view_source' => 1,
 'disable_copy_paste' => 1
 );
 add_option($this->option_name, $default_settings);
 }
 
 // Server-side protections
 add_filter('xmlrpc_enabled', '__return_false');
 remove_action('wp_head', 'wp_generator');
 
 if (!defined('DISALLOW_FILE_EDIT')) {
 define('DISALLOW_FILE_EDIT', true);
 }
 
 add_filter('login_errors', function() {
 return 'خطا در اطلاعات ورود';
 });
 }

 public function add_admin_menu() {
 add_options_page(
 'تنظیمات Golden Guard',
 'Golden Guard',
 'manage_options',
 'golden-guard',
 array($this, 'options_page')
 );
 }

 public function settings_init() {
 register_setting('golden_guard', $this->option_name);
 
 add_settings_section(
 'golden_guard_section',
 'تنظیمات حفاظت از محتوا',
 array($this, 'settings_section_callback'),
 'golden_guard'
 );
 
 add_settings_field(
 'enable_protection',
 'فعال‌سازی حفاظت',
 array($this, 'enable_protection_render'),
 'golden_guard',
 'golden_guard_section'
 );
 
 add_settings_field(
 'enabled_roles',
 'نقش‌های کاربری تحت حفاظت',
 array($this, 'enabled_roles_render'),
 'golden_guard',
 'golden_guard_section'
 );
 
 add_settings_field(
 'disable_right_click',
 'غیرفعال‌سازی کلیک راست',
 array($this, 'disable_right_click_render'),
 'golden_guard',
 'golden_guard_section'
 );
 
 add_settings_field(
 'disable_text_selection',
 'غیرفعال‌سازی انتخاب متن',
 array($this, 'disable_text_selection_render'),
 'golden_guard',
 'golden_guard_section'
 );
 
 add_settings_field(
 'disable_keyboard_shortcuts',
 'غیرفعال‌سازی میانبرهای صفحه‌کلید',
 array($this, 'disable_keyboard_shortcuts_render'),
 'golden_guard',
 'golden_guard_section'
 );
 
 add_settings_field(
 'disable_developer_tools',
 'غیرفعال‌سازی ابزارهای توسعه‌دهنده',
 array($this, 'disable_developer_tools_render'),
 'golden_guard',
 'golden_guard_section'
 );
 
 add_settings_field(
 'disable_view_source',
 'غیرفعال‌سازی مشاهده کد منبع',
 array($this, 'disable_view_source_render'),
 'golden_guard',
 'golden_guard_section'
 );
 
 add_settings_field(
 'disable_copy_paste',
 'غیرفعال‌سازی کپی و پیست',
 array($this, 'disable_copy_paste_render'),
 'golden_guard',
 'golden_guard_section'
 );
 }

 public function settings_section_callback() {
 echo '<p>تنظیمات مربوط به حفاظت از محتوای سایت را در این بخش پیکربندی کنید.</p>';
 }

 public function enable_protection_render() {
 $options = get_option($this->option_name);
 ?>
 <input type='checkbox' name='<?php echo $this->option_name; ?>[enable_protection]' <?php checked($options['enable_protection'], 1); ?> value='1'>
 <label>فعال‌سازی سیستم حفاظت Golden Guard</label>
 <?php
 }

 public function enabled_roles_render() {
 $options = get_option($this->option_name);
 $roles = wp_roles()->get_names();
 $enabled_roles = isset($options['enabled_roles']) ? $options['enabled_roles'] : array();
 
 echo '<div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #f9f9f9;">';
 foreach ($roles as $role_key => $role_name) {
 $checked = in_array($role_key, $enabled_roles) ? 'checked' : '';
 echo '<label style="display: block; margin-bottom: 5px;">';
 echo '<input type="checkbox" name="' . $this->option_name . '[enabled_roles][]" value="' . esc_attr($role_key) . '" ' . $checked . '> ';
 echo esc_html($role_name) . ' (' . esc_html($role_key) . ')';
 echo '</label>';
 }
 echo '</div>';
 echo '<p class="description">نقش‌های کاربری که باید تحت حفاظت قرار گیرند را انتخاب کنید. کاربران مدیر همیشه از حفاظت معاف هستند.</p>';
 }

 public function disable_right_click_render() {
 $options = get_option($this->option_name);
 ?>
 <input type='checkbox' name='<?php echo $this->option_name; ?>[disable_right_click]' <?php checked($options['disable_right_click'], 1); ?> value='1'>
 <label>غیرفعال‌سازی منوی کلیک راست ماوس</label>
 <?php
 }

 public function disable_text_selection_render() {
 $options = get_option($this->option_name);
 ?>
 <input type='checkbox' name='<?php echo $this->option_name; ?>[disable_text_selection]' <?php checked($options['disable_text_selection'], 1); ?> value='1'>
 <label>غیرفعال‌سازی انتخاب و هایلایت کردن متن</label>
 <?php
 }

 public function disable_keyboard_shortcuts_render() {
 $options = get_option($this->option_name);
 ?>
 <input type='checkbox' name='<?php echo $this->option_name; ?>[disable_keyboard_shortcuts]' <?php checked($options['disable_keyboard_shortcuts'], 1); ?> value='1'>
 <label>غیرفعال‌سازی میانبرهای صفحه‌کلید (F12, Ctrl+U, Ctrl+Shift+I و...)</label>
 <?php
 }

 public function disable_developer_tools_render() {
 $options = get_option($this->option_name);
 ?>
 <input type='checkbox' name='<?php echo $this->option_name; ?>[disable_developer_tools]' <?php checked($options['disable_developer_tools'], 1); ?> value='1'>
 <label>تشخیص و مسدودسازی ابزارهای توسعه‌دهنده</label>
 <?php
 }

 public function disable_view_source_render() {
 $options = get_option($this->option_name);
 ?>
 <input type='checkbox' name='<?php echo $this->option_name; ?>[disable_view_source]' <?php checked($options['disable_view_source'], 1); ?> value='1'>
 <label>غیرفعال‌سازی مشاهده کد منبع صفحه</label>
 <?php
 }

 public function disable_copy_paste_render() {
 $options = get_option($this->option_name);
 ?>
 <input type='checkbox' name='<?php echo $this->option_name; ?>[disable_copy_paste]' <?php checked($options['disable_copy_paste'], 1); ?> value='1'>
 <label>غیرفعال‌سازی کپی، پیست و برش متن</label>
 <?php
 }

 public function options_page() {
 ?>
 <div class="wrap">
 <h1>تنظیمات Golden Guard</h1>
 <div style="background: #fff; padding: 20px; margin: 20px 0; border-left: 4px solid #ffb900; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
 <h2 style="margin-top: 0; color: #ffb900;">🛡️ سیستم حفاظت پیشرفته Golden Guard</h2>
 <p><strong>نویسنده:</strong> Alireza Fatemi | <strong>مخزن:</strong> <a href="https://github.com/ftepic" target="_blank">github.com/ftepic</a></p>
 <p>این افزونه محتوای سایت شما را در برابر کپی، کلیک راست، میانبرهای صفحه‌کلید و ابزارهای توسعه‌دهنده محافظت می‌کند.</p>
 </div>
 
 <form action='options.php' method='post'>
 <?php
 settings_fields('golden_guard');
 do_settings_sections('golden_guard');
 submit_button('ذخیره تنظیمات');
 ?>
 </form>
 
 <div style="background: #f0f8ff; padding: 15px; margin: 20px 0; border: 1px solid #0073aa; border-radius: 4px;">
 <h3 style="margin-top: 0; color: #0073aa;">📋 راهنمای استفاده</h3>
 <ul>
 <li><strong>نقش‌های کاربری:</strong> انتخاب کنید کدام نقش‌های کاربری تحت حفاظت قرار گیرند</li>
 <li><strong>کاربران مدیر:</strong> همیشه از حفاظت معاف هستند</li>
 <li><strong>تنظیمات جزئی:</strong> هر بخش حفاظت را جداگانه فعال یا غیرفعال کنید</li>
 <li><strong>سازگاری:</strong> کاملاً با المنتور و تمام قالب‌ها سازگار است</li>
 </ul>
 </div>
 </div>
 <?php
 }

 public function check_user_role_protection() {
 $options = get_option($this->option_name);
 
 if (!isset($options['enable_protection']) || !$options['enable_protection']) {
 return false;
 }
 
 if (current_user_can('manage_options')) {
 return false;
 }
 
 if (!is_user_logged_in()) {
 return true;
 }
 
 $user = wp_get_current_user();
 $enabled_roles = isset($options['enabled_roles']) ? $options['enabled_roles'] : array();
 
 foreach ($user->roles as $role) {
 if (in_array($role, $enabled_roles)) {
 return true;
 }
 }
 
 return false;
 }

 public function enqueue_protection_scripts() {
 if (!$this->check_user_role_protection()) {
 return;
 }
 
 wp_enqueue_script('jquery');
 }

 public function add_protection_styles() {
 if (!$this->check_user_role_protection()) {
 return;
 }
 
 $options = get_option($this->option_name);
 
 echo '<style type="text/css">';
 
 if (isset($options['disable_text_selection']) && $options['disable_text_selection']) {
 echo '
 * {
 -webkit-user-select: none !important;
 -moz-user-select: none !important;
 -ms-user-select: none !important;
 user-select: none !important;
 -webkit-touch-callout: none !important;
 -webkit-tap-highlight-color: transparent !important;
 }
 
 body {
 -webkit-user-select: none !important;
 -moz-user-select: none !important;
 -ms-user-select: none !important;
 user-select: none !important;
 -webkit-touch-callout: none !important;
 }
 
 input, textarea {
 -webkit-user-select: text !important;
 -moz-user-select: text !important;
 -ms-user-select: text !important;
 user-select: text !important;
 }
 
 ::selection {
 background: transparent !important;
 }
 
 ::-moz-selection {
 background: transparent !important;
 }
 ';
 }
 
 echo '
 img {
 -webkit-user-drag: none !important;
 -khtml-user-drag: none !important;
 -moz-user-drag: none !important;
 -o-user-drag: none !important;
 user-drag: none !important;
 pointer-events: none !important;
 }
 
 .elementor-widget-image img,
 .elementor-image img,
 .wp-block-image img {
 -webkit-user-drag: none !important;
 -khtml-user-drag: none !important;
 -moz-user-drag: none !important;
 -o-user-drag: none !important;
 user-drag: none !important;
 pointer-events: none !important;
 }
 
 .elementor-element,
 .elementor-widget,
 .elementor-container,
 .elementor-section {
 -webkit-user-select: none !important;
 -moz-user-select: none !important;
 -ms-user-select: none !important;
 user-select: none !important;
 }
 ';
 
 echo '</style>';
 }

 public function add_protection_scripts() {
 if (!$this->check_user_role_protection()) {
 return;
 }
 
 $options = get_option($this->option_name);
 ?>
 <script type="text/javascript">
 (function() {
 'use strict';
 
 <?php if (isset($options['disable_right_click']) && $options['disable_right_click']): ?>
 // Disable right-click context menu
 document.addEventListener('contextmenu', function(e) {
 e.preventDefault();
 e.stopPropagation();
 return false;
 }, true);
 <?php endif; ?>
 
 <?php if (isset($options['disable_text_selection']) && $options['disable_text_selection']): ?>
 // Disable text selection
 document.addEventListener('selectstart', function(e) {
 e.preventDefault();
 return false;
 }, true);
 <?php endif; ?>
 
 // Disable drag and drop
 document.addEventListener('dragstart', function(e) {
 e.preventDefault();
 return false;
 }, true);
 
 <?php if (isset($options['disable_keyboard_shortcuts']) && $options['disable_keyboard_shortcuts']): ?>
 // Disable keyboard shortcuts
 document.addEventListener('keydown', function(e) {
 // Disable F12 (Developer Tools)
 if (e.keyCode === 123) {
 e.preventDefault();
 e.stopPropagation();
 return false;
 }
 
 // Disable Ctrl+Shift+I (Developer Tools)
 if (e.ctrlKey && e.shiftKey && e.keyCode === 73) {
 e.preventDefault();
 e.stopPropagation();
 return false;
 }
 
 // Disable Ctrl+Shift+J (Console)
 if (e.ctrlKey && e.shiftKey && e.keyCode === 74) {
 e.preventDefault();
 e.stopPropagation();
 return false;
 }
 
 <?php if (isset($options['disable_view_source']) && $options['disable_view_source']): ?>
 // Disable Ctrl+U (View Source)
 if (e.ctrlKey && e.keyCode === 85) {
 e.preventDefault();
 e.stopPropagation();
 return false;
 }
 <?php endif; ?>
 
 <?php if (isset($options['disable_copy_paste']) && $options['disable_copy_paste']): ?>
 // Disable Ctrl+C (Copy)
 if (e.ctrlKey && e.keyCode === 67) {
 e.preventDefault();
 e.stopPropagation();
 return false;
 }
 
 // Disable Ctrl+V (Paste)
 if (e.ctrlKey && e.keyCode === 86) {
 e.preventDefault();
 e.stopPropagation();
 return false;
 }
 
 // Disable Ctrl+X (Cut)
 if (e.ctrlKey && e.keyCode === 88) {
 e.preventDefault();
 e.stopPropagation();
 return false;
 }
 <?php endif; ?>
 
 // Disable Ctrl+A (Select All)
 if (e.ctrlKey && e.keyCode === 65) {
 e.preventDefault();
 e.stopPropagation();
 return false;
 }
 
 // Disable Ctrl+S (Save)
 if (e.ctrlKey && e.keyCode === 83) {
 e.preventDefault();
 e.stopPropagation();
 return false;
 }
 
 // Disable Ctrl+P (Print)
 if (e.ctrlKey && e.keyCode === 80) {
 e.preventDefault();
 e.stopPropagation();
 return false;
 }
 
 // Disable F1-F12 keys
 if (e.keyCode >= 112 && e.keyCode <= 123) {
 e.preventDefault();
 e.stopPropagation();
 return false;
 }
 
 // Disable Ctrl+Shift+C (Inspect Element)
 if (e.ctrlKey && e.shiftKey && e.keyCode === 67) {
 e.preventDefault();
 e.stopPropagation();
 return false;
 }
 
 // Disable Ctrl+Shift+K (Web Console Firefox)
 if (e.ctrlKey && e.shiftKey && e.keyCode === 75) {
 e.preventDefault();
 e.stopPropagation();
 return false;
 }
 }, true);
 <?php endif; ?>
 
 <?php if (isset($options['disable_developer_tools']) && $options['disable_developer_tools']): ?>
 // Detect developer tools
 var devtools = {
 open: false,
 orientation: null
 };
 
 setInterval(function() {
 if (window.outerHeight - window.innerHeight > 200 || window.outerWidth - window.innerWidth > 200) {
 if (!devtools.open) {
 devtools.open = true;
 window.location.href = 'about:blank';
 }
 } else {
 devtools.open = false;
 }
 }, 500);
 
 // Disable console
 if (typeof console !== 'undefined') {
 console.log = function() {};
 console.warn = function() {};
 console.error = function() {};
 console.info = function() {};
 console.debug = function() {};
 console.clear = function() {};
 console.dir = function() {};
 console.dirxml = function() {};
 console.trace = function() {};
 console.profile = function() {};
 console.profileEnd = function() {};
 console.table = function() {};
 console.exception = function() {};
 console.assert = function() {};
 console.mark = function() {};
 console.markTimeline = function() {};
 console.timeline = function() {};
 console.timelineEnd = function() {};
 console.time = function() {};
 console.timeEnd = function() {};
 console.timeStamp = function() {};
 console.group = function() {};
 console.groupCollapsed = function() {};
 console.groupEnd = function() {};
 }
 <?php endif; ?>
 
 // Disable mouse events
 document.addEventListener('mousedown', function(e) {
 if (e.button === 2) { // Right click
 e.preventDefault();
 e.stopPropagation();
 return false;
 }
 }, true);
 
 // Additional protection for images
 var images = document.getElementsByTagName('img');
 for (var i = 0; i < images.length; i++) {
 images[i].addEventListener('dragstart', function(e) {
 e.preventDefault();
 return false;
 });
 images[i].addEventListener('contextmenu', function(e) {
 e.preventDefault();
 return false;
 });
 }
 
 <?php if (isset($options['disable_copy_paste']) && $options['disable_copy_paste']): ?>
 // Clear clipboard
 setInterval(function() {
 if (navigator.clipboard && navigator.clipboard.writeText) {
 navigator.clipboard.writeText('').catch(function() {});
 }
 }, 1000);
 <?php endif; ?>
 
 // Disable zoom
 document.addEventListener('wheel', function(e) {
 if (e.ctrlKey) {
 e.preventDefault();
 return false;
 }
 }, { passive: false });
 
 // Disable touch zoom on mobile
 document.addEventListener('touchstart', function(e) {
 if (e.touches.length > 1) {
 e.preventDefault();
 }
 }, { passive: false });
 
 document.addEventListener('touchmove', function(e) {
 if (e.touches.length > 1) {
 e.preventDefault();
 }
 }, { passive: false });
 
 // Elementor specific protection
 jQuery(document).ready(function($) {
 $('.elementor-element').on('contextmenu', function(e) {
 e.preventDefault();
 return false;
 });
 
 $('.elementor-widget').on('selectstart', function(e) {
 e.preventDefault();
 return false;
 });
 
 $('.elementor-container').on('dragstart', function(e) {
 e.preventDefault();
 return false;
 });
 
 $('body').on('copy', function(e) {
 e.preventDefault();
 return false;
 });
 
 $('body').on('cut', function(e) {
 e.preventDefault();
 return false;
 });
 
 $('body').on('paste', function(e) {
 e.preventDefault();
 return false;
 });
 });
 
 })();
 </script>
 <?php
 }

 public function add_security_headers($headers) {
 $headers['X-Frame-Options'] = 'SAMEORIGIN';
 $headers['X-Content-Type-Options'] = 'nosniff';
 $headers['X-XSS-Protection'] = '1; mode=block';
 $headers['Referrer-Policy'] = 'strict-origin-when-cross-origin';
 return $headers;
 }
}

new GoldenGuard();

// Additional security measures
add_filter('rest_authentication_errors', function($result) {
 if (!empty($result)) {
 return $result;
 }
 if (!is_user_logged_in()) {
 return new WP_Error('rest_not_logged_in', 'دسترسی مجاز نیست.', array('status' => 401));
 }
 return $result;
});

// Remove version from scripts and styles
function golden_guard_remove_version($src) {
 if (strpos($src, 'ver=')) {
 $src = remove_query_arg('ver', $src);
 }
 return $src;
}
add_filter('style_loader_src', 'golden_guard_remove_version', 9999);
add_filter('script_loader_src', 'golden_guard_remove_version', 9999);

// Block suspicious user agents
add_action('init', function() {
 $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
 $blocked_agents = array('wget', 'curl', 'libwww', 'python', 'nikto', 'scan');
 
 foreach ($blocked_agents as $agent) {
 if (stripos($user_agent, $agent) !== false) {
 wp_die('دسترسی مجاز نیست', 'خطای امنیتی', array('response' => 403));
 }
 }
});
?>
