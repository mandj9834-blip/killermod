<?php
/**
 * Plugin Name: KillerMod Manager
 * Description: Full post manager — edit title, thumbnail, banner image, download links, mod features, YouTube & Telegram from one place.
 * Version:     5.2.0
 * Author:      KillerMod / Jashan Mods
 * Text Domain: killermod-manager
 */

defined('ABSPATH') || exit;

define('KM_VERSION', '5.3.0');
define('KM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('KM_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Cache-busting version for an admin asset = its last-modified time.
 * Guarantees the browser fetches the new admin.js/admin.css the moment
 * either file changes, instead of relying on a hand-bumped version string.
 */
function km_asset_ver($relpath) {
    $file = KM_PLUGIN_DIR . ltrim($relpath, '/');
    return file_exists($file) ? (string) filemtime($file) : KM_VERSION;
}

/* ------------------------------------------------------------------
   Register post meta
------------------------------------------------------------------ */
add_action('init', function () {
    foreach (['_km_download_link','_km_download_link2','_km_youtube_url','_km_telegram_url','_km_mod_features','_km_banner_id',
              '_km_show_in_app','_km_app_link','_km_version','_km_size','_km_package'] as $key) {
        register_post_meta('post', $key, [
            'show_in_rest'  => false, 'single' => true, 'type' => 'string',
            'auth_callback' => fn() => current_user_can('edit_posts'),
        ]);
    }
});

/* ------------------------------------------------------------------
   Helper — the default Fast Download / app-funnel URL for a post.
   Used whenever the admin hasn't typed a custom App Link override.
------------------------------------------------------------------ */
function km_default_app_link($pid) {
    $slug = get_post_field('post_name', $pid);
    return trailingslashit(home_url()) . 'fast-download/?mod=' . $slug;
}

/* ------------------------------------------------------------------
   ADS — suppress ALL ads (Ad Slot for KillerMod + Ad Inserter) on
   any single post where "Show in App" is turned on. Nothing in the
   other two plugins is touched; both expose hooks for exactly this.
------------------------------------------------------------------ */
// Ad Slot for KillerMod stores its config in the 'askm_settings' option.
// Returning [] for that option (only for this one request) makes the
// plugin's own "if (empty($settings)) return;" bail out — no ads injected.
add_filter('option_askm_settings', function ($settings) {
    if (is_single()) {
        $pid = get_the_ID();
        if ($pid && get_post_meta($pid, '_km_show_in_app', true) === '1') {
            return [];
        }
    }
    return $settings;
});

// Ad Inserter exposes this filter specifically for custom skip logic —
// returning false here stops that block from inserting on this post.
add_filter('ai_block_insertion_check', function ($check, $block_number, $output) {
    if (is_single()) {
        $pid = get_the_ID();
        if ($pid && get_post_meta($pid, '_km_show_in_app', true) === '1') {
            return false;
        }
    }
    return $check;
}, 10, 3);

/* ------------------------------------------------------------------
   REST — public read-only feed of every mod marked "Show in App".
   This is what the Fast Download page (and later the Android app)
   should call instead of hardcoding mod data anywhere else.
   GET /wp-json/killermod/v1/app-mods
   GET /wp-json/killermod/v1/app-mods?mod=otr-2-mod-apk
------------------------------------------------------------------ */
add_action('rest_api_init', function () {
    register_rest_route('killermod/v1', '/app-mods', [
        'methods'             => 'GET',
        'permission_callback' => '__return_true',
        'callback'            => function (WP_REST_Request $req) {
            $slug = sanitize_title($req->get_param('mod') ?? '');
            $args = ['post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => -1,
                     'meta_key' => '_km_show_in_app', 'meta_value' => '1'];
            if ($slug) $args['name'] = $slug;
            $posts = get_posts($args);

            $out = [];
            foreach ($posts as $p) {
                $pid        = $p->ID;
                $banner_id  = get_post_meta($pid, '_km_banner_id', true);
                $features   = get_post_meta($pid, '_km_mod_features', true);
                $cats       = get_the_category($pid);
                $app_link   = get_post_meta($pid, '_km_app_link', true);

                $out[] = [
                    'id'            => $pid,
                    'slug'          => $p->post_name,
                    'name'          => get_the_title($pid),
                    'thumbnail'     => get_the_post_thumbnail_url($pid, 'thumbnail') ?: '',
                    'banner'        => $banner_id ? wp_get_attachment_image_url($banner_id, 'full') : '',
                    'category'      => !empty($cats) ? $cats[0]->name : '',
                    'version'       => get_post_meta($pid, '_km_version', true),
                    'size'          => get_post_meta($pid, '_km_size', true),
                    'package'       => get_post_meta($pid, '_km_package', true),
                    'features'      => array_values(array_filter(array_map('trim', explode("\n", $features)))),
                    'download_link' => get_post_meta($pid, '_km_download_link', true),
                    'mirror_link'   => get_post_meta($pid, '_km_download_link2', true),
                    'app_link'      => $app_link ?: km_default_app_link($pid),
                    'page_url'      => get_permalink($pid),
                ];
            }
            return rest_ensure_response($out);
        },
    ]);
});

/* ------------------------------------------------------------------
   Meta box on post edit screen
------------------------------------------------------------------ */
add_action('add_meta_boxes', function () {
    add_meta_box('km_post_meta_box','🎮 KillerMod Post Settings','km_render_post_meta_box','post','normal','high');
});
function km_render_post_meta_box($post) {
    wp_nonce_field('km_save_meta','km_meta_nonce');
    $f = [
        '_km_download_link'  => ['🟢 Download Link (Green Button)', 'url'],
        '_km_download_link2' => ['🔵 Mirror Link (Teal Button)',     'url'],
        '_km_youtube_url'    => ['▶ YouTube Channel URL',           'url'],
        '_km_telegram_url'   => ['✈ Telegram Channel URL',          'url'],
        '_km_mod_features'   => ['✅ Mod Features (one per line)',   'textarea'],
    ];
    echo '<style>.kmb{margin-bottom:12px}.kmb label{display:block;font-weight:600;margin-bottom:3px;font-size:13px}.kmb input,.kmb textarea{width:100%;border:1px solid #ddd;border-radius:4px;padding:7px 10px;font-size:13px}.kmb textarea{height:90px;resize:vertical}</style>';
    foreach ($f as $key => [$label, $type]) {
        $val = get_post_meta($post->ID, $key, true);
        echo '<div class="kmb"><label>'.esc_html($label).'</label>';
        if ($type === 'textarea') echo '<textarea name="'.esc_attr($key).'">'.esc_textarea($val).'</textarea>';
        else echo '<input type="url" name="'.esc_attr($key).'" value="'.esc_attr($val).'" placeholder="https://…">';
        echo '</div>';
    }
}
add_action('save_post', function ($pid) {
    if (!isset($_POST['km_meta_nonce']) || !wp_verify_nonce($_POST['km_meta_nonce'],'km_save_meta')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post',$pid)) return;
    foreach (['_km_download_link','_km_download_link2','_km_youtube_url','_km_telegram_url','_km_mod_features'] as $key)
        if (isset($_POST[$key])) update_post_meta($pid, $key, sanitize_textarea_field($_POST[$key]));
});

/* ------------------------------------------------------------------
   Admin menu
------------------------------------------------------------------ */
add_action('admin_menu', function () {
    add_menu_page('KillerMod Manager','Mod Manager','edit_posts','killermod-manager','km_render_admin_page','dashicons-games',25);
    add_submenu_page('killermod-manager','Bulk Apply','⚡ Bulk Apply','edit_posts','killermod-bulk','km_render_bulk_page');
});

/* Add to Admin Bar for easy access from front-end */
add_action('admin_bar_menu', function ($wp_admin_bar) {
    if (!current_user_can('edit_posts')) return;
    $wp_admin_bar->add_node([
        'id'    => 'killermod-manager',
        'title' => '🎮 KillerMod Manager',
        'href'  => admin_url('admin.php?page=killermod-manager'),
    ]);
    $wp_admin_bar->add_node([
        'id'     => 'killermod-homepage',
        'parent' => 'killermod-manager',
        'title'  => '🏠 Homepage Settings',
        'href'   => admin_url('admin.php?page=killermod-manager&tab=homepage'),
    ]);
}, 99);


/* ------------------------------------------------------------------
   Bulk Apply page
------------------------------------------------------------------ */
function km_render_bulk_page() {
    $total = wp_count_posts('post')->publish;
    ?>
    <div class="wrap km-admin-wrap" id="kmAdminWrap">
      <script>
        if (localStorage.getItem('km-theme') === 'dark') {
          document.getElementById('kmAdminWrap').classList.add('dark');
        }
      </script>
      <div class="km-admin-header" style="display: flex; justify-content: space-between; align-items: center;">
        <div>
          <div class="km-admin-logo">⚡ <span>Bulk</span> Apply</div>
          <div class="km-admin-sub">Paste once — applies to all <?php echo number_format($total); ?> published posts instantly</div>
        </div>
        <button type="button" class="km-theme-toggle-btn" id="kmThemeToggle" title="Toggle Light/Dark Mode">
          <span class="km-toggle-track">
            <span class="km-toggle-thumb"></span>
          </span>
        </button>
      </div>

      <div id="km-toast" class="km-toast" aria-live="polite"></div>

      <div class="km-bulk-wrap">

        <!-- YouTube -->
        <div class="km-bulk-card">
          <div class="km-bulk-card-header">
            <span class="km-bulk-icon">▶</span>
            <div>
              <div class="km-bulk-title">YouTube Channel URL</div>
              <div class="km-bulk-sub">Paste your YouTube channel link — will be applied to all posts</div>
            </div>
          </div>
          <div class="km-bulk-field">
            <input type="url" id="km-bulk-yt" class="km-bulk-input" placeholder="https://youtube.com/@YourChannel">
          </div>
          <button class="km-bulk-apply-btn" id="km-apply-yt" data-key="_km_youtube_url" data-input="km-bulk-yt">
            ⚡ Apply YouTube URL to All <?php echo number_format($total); ?> Posts
          </button>
        </div>

        <!-- Telegram -->
        <div class="km-bulk-card">
          <div class="km-bulk-card-header">
            <span class="km-bulk-icon">✈</span>
            <div>
              <div class="km-bulk-title">Telegram Channel URL</div>
              <div class="km-bulk-sub">Paste your Telegram channel link — will be applied to all posts</div>
            </div>
          </div>
          <div class="km-bulk-field">
            <input type="url" id="km-bulk-tg" class="km-bulk-input" placeholder="https://t.me/YourChannel">
          </div>
          <button class="km-bulk-apply-btn" id="km-apply-tg" data-key="_km_telegram_url" data-input="km-bulk-tg">
            ⚡ Apply Telegram URL to All <?php echo number_format($total); ?> Posts
          </button>
        </div>

        <!-- Mod Features -->
        <div class="km-bulk-card">
          <div class="km-bulk-card-header">
            <span class="km-bulk-icon">✅</span>
            <div>
              <div class="km-bulk-title">Mod Features List</div>
              <div class="km-bulk-sub">One feature per line — will be applied to all posts</div>
            </div>
          </div>
          <div class="km-bulk-field">
            <textarea id="km-bulk-features" class="km-bulk-input km-bulk-textarea" rows="6"
              placeholder="Unlimited Money&#10;Unlocked Everything&#10;God Mode&#10;Free Shopping&#10;No Password Required"></textarea>
          </div>
          <button class="km-bulk-apply-btn" id="km-apply-features" data-key="_km_mod_features" data-input="km-bulk-features">
            ⚡ Apply Mod Features to All <?php echo number_format($total); ?> Posts
          </button>
        </div>

      </div>
    </div>

    <script>
    (function($){
      function toast(msg, type) {
        var $t = $('#km-toast');
        $t.removeClass('success error show').addClass(type||'success').text(msg);
        $t[0].offsetHeight;
        $t.addClass('show');
        clearTimeout($t.data('tmr'));
        $t.data('tmr', setTimeout(function(){ $t.removeClass('show'); }, 3500));
      }

      $(document).on('click', '.km-bulk-apply-btn', function(){
        var $btn   = $(this);
        var key    = $btn.data('key');
        var inputId = $btn.data('input');
        var value  = $('#' + inputId).val().trim();
        var label  = $btn.text().replace('⚡ ','').split(' to All')[0];

        if (!value) { toast('⚠️ Field is empty!', 'error'); return; }
        if (!confirm('Apply "' + label + '" to ALL posts?\n\nThis will overwrite existing values on every post!')) return;

        $btn.prop('disabled', true).text('⏳ Applying to all posts…');

        $.post(kmData.ajaxUrl, {
          action:     'km_apply_to_all',
          nonce:      kmData.nonce,
          meta_key:   key,
          meta_value: value
        }, function(res){
          $btn.prop('disabled', false);
          if (res.success) {
            $btn.text('✅ Applied to ' + res.data.updated + ' posts!');
            toast('✅ Done! Applied to ' + res.data.updated + ' posts!', 'success');
            setTimeout(function(){
              $btn.text('⚡ ' + label + ' to All Posts');
            }, 3000);
          } else {
            $btn.text('⚡ ' + label + ' to All Posts');
            toast('❌ ' + (res.data || 'Failed'), 'error');
          }
        }).fail(function(){
          $btn.prop('disabled', false).text('⚡ ' + label + ' to All Posts');
          toast('❌ Request failed', 'error');
        });
      });
    })(jQuery);
    </script>
    <?php
}


/* ------------------------------------------------------------------
   AJAX — save full post data
------------------------------------------------------------------ */
add_action('wp_ajax_km_save_post', function () {
    check_ajax_referer('km_ajax_nonce','nonce');
    if (!current_user_can('edit_posts')) wp_send_json_error('Permission denied');
    $pid = intval($_POST['post_id'] ?? 0);
    if (!$pid || !get_post($pid)) wp_send_json_error('Invalid post ID');

    // Title
    if (isset($_POST['post_title']) && $_POST['post_title'] !== '') {
        wp_update_post(['ID'=>$pid,'post_title'=>sanitize_text_field($_POST['post_title'])]);
    }

    // Slug / Permalink
    if (isset($_POST['post_slug']) && $_POST['post_slug'] !== '') {
        $new_slug = sanitize_title(sanitize_text_field($_POST['post_slug']));
        if ($new_slug) {
            wp_update_post(['ID' => $pid, 'post_name' => $new_slug]);
        }
    }

    // Meta fields
    $meta = ['_km_download_link','_km_download_link2','_km_youtube_url','_km_telegram_url','_km_mod_features',
             '_km_app_link','_km_version','_km_size','_km_package'];
    foreach ($meta as $key)
        if (isset($_POST[$key])) update_post_meta($pid, $key, sanitize_textarea_field($_POST[$key]));

    // Show in App (checkbox — only present in POST when checked)
    update_post_meta($pid, '_km_show_in_app', !empty($_POST['show_in_app']) ? '1' : '0');

    // Thumbnail (attachment ID)
    if (!empty($_POST['thumb_id'])) {
        $tid = intval($_POST['thumb_id']);
        if ($tid > 0) set_post_thumbnail($pid, $tid);
        elseif ($tid === -1) delete_post_thumbnail($pid);
    }

    // Banner image (attachment ID)
    if (isset($_POST['banner_id'])) {
        $bid = intval($_POST['banner_id']);
        if ($bid > 0) update_post_meta($pid, '_km_banner_id', $bid);
        elseif ($bid === -1) delete_post_meta($pid, '_km_banner_id');
    }

    // Category
    if (isset($_POST['post_category_id'])) {
        $cat_id = intval($_POST['post_category_id']);
        if ($cat_id > 0) {
            wp_set_post_categories($pid, [$cat_id]);
        } else {
            wp_set_post_categories($pid, []);
        }
    }

    $new_title    = get_the_title($pid);
    $new_thumb    = get_the_post_thumbnail_url($pid,'thumbnail');
    $banner_id    = get_post_meta($pid,'_km_banner_id',true);
    $new_banner   = $banner_id ? wp_get_attachment_image_url($banner_id,'full') : '';
    $new_cats     = get_the_category($pid);
    $new_cat_name = !empty($new_cats) ? $new_cats[0]->name : '—';
    $new_slug     = get_post_field('post_name', $pid);
    $new_permalink = get_permalink($pid);
    $new_app_link  = get_post_meta($pid, '_km_app_link', true) ?: km_default_app_link($pid);
    wp_send_json_success(['post_id'=>$pid,'new_title'=>$new_title,'new_thumb'=>$new_thumb ?: '','new_banner'=>$new_banner,'new_cat'=>$new_cat_name,'new_slug'=>$new_slug,'new_permalink'=>$new_permalink,'new_show_in_app'=>get_post_meta($pid,'_km_show_in_app',true),'new_app_link'=>$new_app_link]);
});

/* ------------------------------------------------------------------
   AJAX — search media library for thumbnail/banner picker
------------------------------------------------------------------ */
add_action('wp_ajax_km_search_media', function () {
    check_ajax_referer('km_ajax_nonce','nonce');
    $q = sanitize_text_field($_POST['q'] ?? '');
    $media = get_posts(['post_type'=>'attachment','post_status'=>'inherit','posts_per_page'=>12,
        'post_mime_type'=>'image','s'=>$q,'orderby'=>'date','order'=>'DESC']);
    $out = [];
    foreach ($media as $m) {
        $src = wp_get_attachment_image_src($m->ID,'thumbnail');
        if ($src) $out[] = ['id'=>$m->ID,'url'=>$src[0],'title'=>$m->post_title];
    }
    wp_send_json_success($out);
});

/* ------------------------------------------------------------------
   AJAX — apply one meta value to ALL published posts
------------------------------------------------------------------ */
add_action('wp_ajax_km_apply_to_all', function () {
    check_ajax_referer('km_ajax_nonce','nonce');
    if (!current_user_can('edit_posts')) wp_send_json_error('Permission denied');

    $key   = sanitize_text_field($_POST['meta_key'] ?? '');
    $value = sanitize_textarea_field($_POST['meta_value'] ?? '');
    $allowed = ['_km_youtube_url','_km_telegram_url','_km_download_link','_km_download_link2'];
    if (!in_array($key, $allowed, true)) wp_send_json_error('Invalid key');

    $posts = get_posts(['post_type'=>'post','post_status'=>'publish','posts_per_page'=>-1,'fields'=>'ids']);
    foreach ($posts as $pid) {
        update_post_meta($pid, $key, $value);
    }
    wp_send_json_success(['updated' => count($posts)]);
});

/* ------------------------------------------------------------------
   AJAX — save global homepage settings
------------------------------------------------------------------ */
add_action('wp_ajax_km_save_settings', function () {
    check_ajax_referer('km_ajax_nonce','nonce');
    if (!current_user_can('edit_posts')) wp_send_json_error('Permission denied');

    $fields = [
        'killermod_hero_badge',
        'killermod_hero_title',
        'killermod_hero_desc',
        'killermod_hero_btn1_text',
        'killermod_hero_btn1_url',
        'killermod_hero_btn2_text',
        'killermod_hero_btn2_url',
        'killermod_banner_title',
        'killermod_banner_tagline',
        'killermod_global_youtube',
        'killermod_global_telegram',
        'killermod_app_apk_url',
    ];

    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            if ($field === 'killermod_hero_title') {
                update_option($field, wp_kses($_POST[$field], [
                    'span' => ['class' => []],
                    'br'   => [],
                    'strong' => [],
                    'em'   => [],
                ]));
            } else {
                update_option($field, sanitize_text_field($_POST[$field]));
            }
        }
    }

    $sections = $_POST['sections'] ?? [];
    $active_sections = [
        'trending' => isset($sections['trending']) ? '1' : '0',
        'latest'   => isset($sections['latest'])   ? '1' : '0',
        'updated'  => isset($sections['updated'])  ? '1' : '0',
    ];
    update_option('killermod_active_sections', $active_sections);

    if (isset($_POST['killermod_latest_columns'])) {
        update_option('killermod_latest_columns', sanitize_text_field($_POST['killermod_latest_columns']));
    }
    if (isset($_POST['killermod_updated_columns'])) {
        update_option('killermod_updated_columns', sanitize_text_field($_POST['killermod_updated_columns']));
    }

    wp_send_json_success('Settings saved successfully!');
});

add_action('admin_enqueue_scripts', function ($hook) {
    if (strpos($hook,'killermod') === false) return;
    wp_enqueue_media();
    wp_enqueue_style('km-admin-fonts', 'https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Syne:wght@600;700;800&family=DM+Sans:wght@300;400;500;700&display=swap', [], null);
    wp_enqueue_style('km-admin-style',  KM_PLUGIN_URL.'admin/admin.css', ['km-admin-fonts'], km_asset_ver('admin/admin.css'));
    wp_enqueue_script('km-admin-script', KM_PLUGIN_URL.'admin/admin.js', ['jquery'], km_asset_ver('admin/admin.js'), true);
    wp_localize_script('km-admin-script','kmData',[
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('km_ajax_nonce'),
    ]);
});

/* ------------------------------------------------------------------
   Render single post row helper
------------------------------------------------------------------ */
function km_render_post_row_html($pid) {
    $link1     = get_post_meta($pid,'_km_download_link',true);
    $link2     = get_post_meta($pid,'_km_download_link2',true);
    $yt        = get_post_meta($pid,'_km_youtube_url',true);
    $tg        = get_post_meta($pid,'_km_telegram_url',true);
    $features  = get_post_meta($pid,'_km_mod_features',true);
    $thumb     = get_the_post_thumbnail_url($pid,'thumbnail');
    $banner_id = get_post_meta($pid,'_km_banner_id',true);
    $banner_url= $banner_id ? wp_get_attachment_image_url($banner_id,'medium') : '';
    $cats      = get_the_category($pid);
    $cat_name  = !empty($cats) ? $cats[0]->name : '—';
    $show_in_app = get_post_meta($pid,'_km_show_in_app',true) === '1';
    $app_link    = get_post_meta($pid,'_km_app_link',true);
    $version     = get_post_meta($pid,'_km_version',true);
    $size        = get_post_meta($pid,'_km_size',true);
    $package     = get_post_meta($pid,'_km_package',true);
    ob_start();
    ?>
    <!-- SUMMARY ROW -->
    <tr class="km-row km-summary-row" data-pid="<?php echo $pid; ?>">
      <td class="col-thumb">
        <div class="km-thumb-wrap" data-pid="<?php echo $pid; ?>">
          <?php if ($thumb): ?>
            <img src="<?php echo esc_url($thumb); ?>" class="km-thumb-img km-thumb-preview" data-pid="<?php echo $pid; ?>">
          <?php else: ?>
            <div class="km-no-thumb km-thumb-preview" data-pid="<?php echo $pid; ?>">🎮</div>
          <?php endif; ?>
          <div class="km-thumb-overlay">📷</div>
        </div>
      </td>
      <td class="col-title">
        <div class="km-title-text" data-pid="<?php echo $pid; ?>"><?php echo esc_html(get_the_title($pid)); ?></div>
        <div class="km-post-id">
          ID: <?php echo $pid; ?> &nbsp;·&nbsp;
          <a href="<?php echo esc_url(get_permalink($pid)); ?>" target="_blank">View ↗</a>
        </div>
      </td>
      <td class="col-cat"><span class="km-cat-badge"><?php echo esc_html($cat_name); ?></span></td>
      <td class="col-status">
        <span class="km-link-dot <?php echo $link1    ? 'green'  : 'grey'; ?>" title="Download Link"></span>
        <span class="km-link-dot <?php echo $link2    ? 'teal'   : 'grey'; ?>" title="Mirror Link"></span>
        <span class="km-link-dot <?php echo $yt       ? 'red'    : 'grey'; ?>" title="YouTube"></span>
        <span class="km-link-dot <?php echo $tg       ? 'blue'   : 'grey'; ?>" title="Telegram"></span>
        <span class="km-link-dot <?php echo $banner_id? 'orange' : 'grey'; ?>" title="Banner Image"></span>
        <span class="km-link-dot <?php echo $show_in_app ? 'purple' : 'grey'; ?>" title="Show in App"></span>
      </td>
      <td class="col-expand">
        <button class="km-expand-btn" data-pid="<?php echo $pid; ?>">✏️ Edit</button>
      </td>
    </tr>

    <!-- EXPANDED EDIT ROW (hidden by default) -->
    <tr class="km-detail-row" id="km-detail-<?php echo $pid; ?>" style="display:none">
      <td colspan="5">
        <div class="km-detail-wrap">

          <!-- SECTION 1: TITLE + THUMBNAIL -->
          <div class="km-section">
            <div class="km-section-label">📝 Section 1 — Post Title &amp; Thumbnail</div>
            <div class="km-section-body km-grid-2">
              <div class="km-field-group">
                <label>Post Title</label>
                <input type="text" class="km-input km-post-title-input"
                       data-pid="<?php echo $pid; ?>"
                       value="<?php echo esc_attr(get_the_title($pid)); ?>"
                       placeholder="Enter post title…">
              </div>
              <div class="km-field-group">
                <label>Thumbnail <small>(small icon shown in lists)</small></label>
                <div class="km-thumb-picker" data-pid="<?php echo $pid; ?>">
                  <div class="km-current-thumb">
                    <?php if ($thumb): ?>
                      <img src="<?php echo esc_url($thumb); ?>" class="km-picker-preview" id="km-picker-preview-<?php echo $pid; ?>">
                    <?php else: ?>
                      <div class="km-picker-empty" id="km-picker-preview-<?php echo $pid; ?>">No Image</div>
                    <?php endif; ?>
                  </div>
                  <div class="km-thumb-actions">
                    <button type="button" class="km-btn-pick-media" data-pid="<?php echo $pid; ?>">📁 Media Library</button>
                    <button type="button" class="km-btn-remove-thumb" data-pid="<?php echo $pid; ?>">✕ Remove</button>
                  </div>
                  <input type="hidden" class="km-thumb-id" id="km-thumb-id-<?php echo $pid; ?>" data-pid="<?php echo $pid; ?>" value="">
                </div>
              </div>
            </div>
          </div>

          <!-- SECTION 1A: CATEGORY -->
          <div class="km-section">
            <div class="km-section-label">🏷️ Section 1A — Post Category</div>
            <div class="km-section-body">
              <div class="km-field-group">
                <label>Category <small>(choose which category this post belongs to)</small></label>
                <?php
                  $all_cats       = get_categories(['hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC']);
                  $current_cat_ids = wp_get_post_categories($pid, ['fields' => 'ids']);
                  $current_cat_id  = !empty($current_cat_ids) ? intval($current_cat_ids[0]) : 0;
                ?>
                <select class="km-input km-cat-picker" data-pid="<?php echo $pid; ?>">
                  <option value="0">— No Category —</option>
                  <?php foreach ($all_cats as $cat): ?>
                    <option value="<?php echo esc_attr($cat->term_id); ?>"
                            <?php selected($current_cat_id, $cat->term_id); ?>>
                      <?php echo esc_html($cat->name); ?> (<?php echo intval($cat->count); ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>

          <!-- SECTION 1B: PERMALINK / SLUG -->
          <div class="km-section">
            <div class="km-section-label">🔗 Section 1B — Permalink / Slug</div>
            <div class="km-section-body">
              <?php
                $post_slug  = get_post_field('post_name', $pid);
                $permalink  = get_permalink($pid);
                $base_url   = trailingslashit(get_option('siteurl'));
              ?>
              <div class="km-field-group">
                <label>Post Slug <small>(the URL-friendly name of this post)</small></label>
                <div class="km-slug-wrap" data-pid="<?php echo $pid; ?>">
                  <div class="km-slug-preview-row">
                    <span class="km-slug-base"><?php echo esc_html($base_url); ?></span>
                    <input type="text"
                           class="km-input km-slug-input"
                           id="km-slug-<?php echo $pid; ?>"
                           data-pid="<?php echo $pid; ?>"
                           data-base="<?php echo esc_attr($base_url); ?>"
                           value="<?php echo esc_attr($post_slug); ?>"
                           placeholder="my-post-slug">
                    <span class="km-slug-slash">/</span>
                  </div>
                  <div class="km-slug-full-url" id="km-slug-url-<?php echo $pid; ?>">
                    <a href="<?php echo esc_url($permalink); ?>" target="_blank"><?php echo esc_html($permalink); ?> ↗</a>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- SECTION 1C: BANNER IMAGE -->
          <div class="km-section km-section-banner">
            <div class="km-section-label">🖼️ Section 1C — Banner Image <small style="font-weight:400;color:#aaa">(big image shown inside post)</small></div>
            <div class="km-section-body">
              <div class="km-field-group">
                <label>Banner Image <small>(replaces the image you used to put in the post editor)</small></label>
                <div class="km-banner-picker" data-pid="<?php echo $pid; ?>">
                  <div class="km-banner-preview-wrap">
                    <?php if ($banner_url): ?>
                      <img src="<?php echo esc_url($banner_url); ?>" class="km-banner-preview" id="km-banner-preview-<?php echo $pid; ?>">
                    <?php else: ?>
                      <div class="km-banner-empty" id="km-banner-preview-<?php echo $pid; ?>">No Banner — click to pick one</div>
                    <?php endif; ?>
                  </div>
                  <div class="km-thumb-actions">
                    <button type="button" class="km-btn-pick-banner" data-pid="<?php echo $pid; ?>">📁 Pick Banner Image</button>
                    <button type="button" class="km-btn-remove-banner" data-pid="<?php echo $pid; ?>">✕ Remove Banner</button>
                  </div>
                  <input type="hidden" class="km-banner-id" id="km-banner-id-<?php echo $pid; ?>" data-pid="<?php echo $pid; ?>" value="">
                </div>
              </div>
            </div>
          </div>

          <!-- SECTION 1D: APP METADATA -->
          <div class="km-section">
            <div class="km-section-label">🧩 Section 1D — App Metadata <small style="font-weight:400;color:#aaa">(shown on the Fast Download / app page)</small></div>
            <div class="km-section-body km-grid-3">
              <div class="km-field-group">
                <label>Version</label>
                <input type="text" class="km-input km-version" data-pid="<?php echo $pid; ?>"
                       value="<?php echo esc_attr($version); ?>" placeholder="v1.0.2">
              </div>
              <div class="km-field-group">
                <label>Size</label>
                <input type="text" class="km-input km-size" data-pid="<?php echo $pid; ?>"
                       value="<?php echo esc_attr($size); ?>" placeholder="846 MB">
              </div>
              <div class="km-field-group">
                <label>Package Name</label>
                <input type="text" class="km-input km-package" data-pid="<?php echo $pid; ?>"
                       value="<?php echo esc_attr($package); ?>" placeholder="com.example.app">
              </div>
            </div>
          </div>

          <!-- SECTION 2: DOWNLOAD LINKS -->
          <div class="km-section">
            <div class="km-section-label">🔗 Section 2 — Download Links</div>
            <div class="km-section-body km-grid-2">
              <div class="km-field-group">
                <label><span class="dot green"></span> Download Link (Green Button)</label>
                <input type="url" class="km-input km-link1"
                       data-pid="<?php echo $pid; ?>"
                       value="<?php echo esc_attr($link1); ?>"
                       placeholder="https://mediafire.com/…">
              </div>
              <div class="km-field-group">
                <label><span class="dot teal"></span> Mirror Link (Teal Button)</label>
                <input type="url" class="km-input km-link2"
                       data-pid="<?php echo $pid; ?>"
                       value="<?php echo esc_attr($link2); ?>"
                       placeholder="https://mega.nz/…">
              </div>
            </div>
          </div>

          <!-- SECTION 3: MOD FEATURES -->
          <div class="km-section">
            <div class="km-section-label">✅ Section 3 — Mod Features</div>
            <div class="km-section-body">
              <div class="km-field-group">
                <label>Features List <small>(one per line — shows as bullet list on post)</small></label>
                <textarea class="km-input km-features" data-pid="<?php echo $pid; ?>"
                          rows="5" placeholder="Unlimited Money&#10;Unlocked Everything&#10;God Mode&#10;Free Shopping"><?php echo esc_textarea($features); ?></textarea>
              </div>
            </div>
          </div>

          <!-- SECTION 4: YOUTUBE + TELEGRAM -->
          <div class="km-section">
            <div class="km-section-label">📡 Section 4 — YouTube &amp; Telegram Channel Links</div>
            <div class="km-section-body km-grid-2">
              <div class="km-field-group">
                <label>▶ YouTube Channel URL</label>
                <input type="url" class="km-input km-yt"
                       data-pid="<?php echo $pid; ?>"
                       value="<?php echo esc_attr($yt); ?>"
                       placeholder="https://youtube.com/@YourChannel">
              </div>
              <div class="km-field-group">
                <label>✈ Telegram Channel URL</label>
                <input type="url" class="km-input km-tg"
                       data-pid="<?php echo $pid; ?>"
                       value="<?php echo esc_attr($tg); ?>"
                       placeholder="https://t.me/YourChannel">
              </div>
            </div>
          </div>

          <!-- SECTION 5: KILLERMOD APP & ADS -->
          <div class="km-section km-section-app">
            <div class="km-section-label">🚀 Section 5 — KillerMod App &amp; Ads</div>
            <div class="km-section-body">
              <label class="km-app-toggle-row" data-pid="<?php echo $pid; ?>">
                <span class="km-switch">
                  <input type="checkbox" class="km-show-in-app" data-pid="<?php echo $pid; ?>" <?php checked($show_in_app); ?>>
                  <span class="km-switch-track"><span class="km-switch-thumb"></span></span>
                </span>
                <span>
                  <strong>Show in App</strong>
                  <small>When ON: this mod appears in the app's Fast Download flow, and ads are automatically hidden on this post's page.</small>
                </span>
              </label>

              <div class="km-field-group" style="margin-top:14px;">
                <label>App Link <small>(leave blank to auto-generate from the slug — only fill this in if you want to override it)</small></label>
                <input type="url" class="km-input km-app-link" data-pid="<?php echo $pid; ?>"
                       value="<?php echo esc_attr($app_link); ?>"
                       placeholder="<?php echo esc_attr(km_default_app_link($pid)); ?>">
              </div>
            </div>
          </div>

          <!-- ACTION BAR -->
          <div class="km-action-bar">
            <button class="km-save-all-btn" data-pid="<?php echo $pid; ?>">💾 Save All Changes</button>
            <button class="km-collapse-btn" data-pid="<?php echo $pid; ?>">✕ Close</button>
            <a href="<?php echo get_edit_post_link($pid); ?>" target="_blank" class="km-edit-link">Open Full Editor ↗</a>
          </div>

        </div>
      </td>
    </tr>
    <?php
    return ob_get_clean();
}

/* ------------------------------------------------------------------
   AJAX — create a new mod game post
------------------------------------------------------------------ */
add_action('wp_ajax_km_create_post', function () {
    check_ajax_referer('km_ajax_nonce','nonce');
    if (!current_user_can('edit_posts')) wp_send_json_error('Permission denied');

    $cat_id = get_cat_ID('Mod Games');
    $post_data = [
        'post_title'    => 'New Mod Game',
        'post_status'   => 'publish',
        'post_type'     => 'post',
        'post_category' => $cat_id ? [$cat_id] : [],
    ];
    $pid = wp_insert_post($post_data);
    if (is_wp_error($pid) || !$pid) {
        wp_send_json_error('Failed to create post');
    }

    $html = km_render_post_row_html($pid);
    wp_send_json_success([
        'post_id' => $pid,
        'html'    => $html,
    ]);
});

/* ------------------------------------------------------------------
   Admin page HTML
------------------------------------------------------------------ */
function km_render_admin_page() {
    $per_page   = 20;
    $paged      = max(1, intval($_GET['paged'] ?? 1));
    $search     = sanitize_text_field($_GET['km_search'] ?? '');
    $cat_filter = intval($_GET['km_cat'] ?? 0);

    $args = ['post_type'=>'post','post_status'=>'publish','posts_per_page'=>$per_page,
             'paged'=>$paged,'orderby'=>'modified','order'=>'DESC'];
    if ($search)     $args['s']   = $search;
    if ($cat_filter) $args['cat'] = $cat_filter;

    $query       = new WP_Query($args);
    $total       = $query->found_posts;
    $total_pages = ceil($total / $per_page);
    $categories  = get_categories(['hide_empty'=>true]);
    ?>
    <div class="wrap km-admin-wrap" id="kmAdminWrap">
      <script>
        if (localStorage.getItem('km-theme') === 'dark') {
          document.getElementById('kmAdminWrap').classList.add('dark');
        }
      </script>

      <div class="km-admin-header" style="display: flex; justify-content: space-between; align-items: center;">
        <div>
          <div class="km-admin-logo">🎮 KILLER<span>MOD</span> Manager</div>
          <div class="km-admin-sub">Edit title · thumbnail · banner · links · features · social — all in one place</div>
        </div>
        <button type="button" class="km-theme-toggle-btn" id="kmThemeToggle" title="Toggle Light/Dark Mode">
          <span class="km-toggle-track">
            <span class="km-toggle-thumb"></span>
          </span>
        </button>
      </div>

      <!-- Navigation Tabs -->
      <div class="km-tabs-nav">
        <button type="button" class="km-tab-btn active" data-tab="km-posts-tab">📝 Post Manager</button>
        <button type="button" class="km-tab-btn" data-tab="km-homepage-tab">🏠 Homepage Settings</button>
      </div>

      <!-- Tab 1: Post Manager -->
      <div id="km-posts-tab" class="km-tab-content active">

      <form method="get" class="km-filters">
        <input type="hidden" name="page" value="killermod-manager">
        <input type="text" name="km_search" class="km-search-input"
               value="<?php echo esc_attr($search); ?>" placeholder="🔍 Search posts…">
        <select name="km_cat" class="km-cat-select">
          <option value="">All Categories</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?php echo $cat->term_id; ?>" <?php selected($cat_filter,$cat->term_id); ?>>
              <?php echo esc_html($cat->name); ?> (<?php echo $cat->count; ?>)
            </option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="km-filter-btn">Filter</button>
        <button type="button" class="km-add-new-btn" id="km-add-new-post">➕ Add New Card</button>
        <?php if ($search || $cat_filter): ?>
          <a href="?page=killermod-manager" class="km-clear-btn">✕ Clear</a>
        <?php endif; ?>
        <span class="km-count-label"><?php echo number_format($total); ?> posts</span>
      </form>

      <div id="km-toast" class="km-toast" aria-live="polite"></div>

      <?php if ($query->have_posts()): ?>
        <table class="km-table">
          <thead>
            <tr>
              <th class="col-thumb">Thumb</th>
              <th class="col-title">Post Title</th>
              <th class="col-cat">Category</th>
              <th class="col-status">Links</th>
              <th class="col-expand"></th>
            </tr>
          </thead>
          <tbody>
          <?php while ($query->have_posts()): $query->the_post();
            echo km_render_post_row_html(get_the_ID());
          endwhile; wp_reset_postdata(); ?>
          </tbody>
        </table>

        <?php if ($total_pages > 1):
          $base = '?page=killermod-manager'.($search?'&km_search='.urlencode($search):'').($cat_filter?'&km_cat='.$cat_filter:'');
        ?>
          <div class="km-pagination">
            <?php for ($p=1;$p<=$total_pages;$p++): ?>
              <a href="<?php echo esc_url($base.'&paged='.$p); ?>"
                 class="km-page-btn<?php echo $p===$paged?' active':''; ?>"><?php echo $p; ?></a>
            <?php endfor; ?>
          </div>
        <?php endif; ?>

      <?php else: ?>
        <div class="km-no-posts">No posts found.</div>
      <?php endif; ?>

      </div> <!-- End Tab 1: Post Manager -->

      <!-- Tab 2: Homepage Settings -->
      <div id="km-homepage-tab" class="km-tab-content" style="display:none;">
        <form id="km-global-settings-form" style="margin-top:20px;">
          
          <!-- Card 1: Hero Banner Config -->
          <div class="km-section">
            <div class="km-section-label">🚀 Homepage Hero Banner</div>
            <div class="km-section-body" style="display:flex; flex-direction:column; gap:16px;">
              <div class="km-field-group">
                <label>Hero Badge <small>(small label above the big title)</small></label>
                <input type="text" name="killermod_hero_badge" class="km-input" value="<?php echo esc_attr(get_option('killermod_hero_badge', 'Mod Platform — 2026')); ?>" placeholder="Mod Platform — 2026">
              </div>

              <div class="km-field-group">
                <label>Hero Title <small>(big headline — use the tags below for styling)</small></label>
                <input type="text" name="killermod_hero_title" class="km-input" value="<?php echo esc_attr(get_option('killermod_hero_title', 'PREMIUM<br><span class="glow-text">MOD</span> <span class="outline">GAMES</span><br>CURATED')); ?>">
                <small style="color:var(--muted); font-size:11px; line-height:1.6;">
                  <code style="background:var(--surface3);padding:1px 5px;border-radius:4px;">&lt;br&gt;</code> = new line ·
                  <code style="background:var(--surface3);padding:1px 5px;border-radius:4px;">&lt;span class="glow-text"&gt;…&lt;/span&gt;</code> = neon accent word ·
                  <code style="background:var(--surface3);padding:1px 5px;border-radius:4px;">&lt;span class="outline"&gt;…&lt;/span&gt;</code> = hollow outline word
                </small>
              </div>

              <div class="km-field-group">
                <label>Hero Description <small>(sub-text under the title)</small></label>
                <textarea name="killermod_hero_desc" class="km-input" rows="2"><?php echo esc_textarea(get_option('killermod_hero_desc', '40–60 perfectly crafted mods. Same-day updates. No broken downloads. Ever. The only platform that actually works.')); ?></textarea>
              </div>

              <div class="km-grid-2">
                <div class="km-field-group">
                  <label>CTA Button Text</label>
                  <input type="text" name="killermod_hero_btn1_text" class="km-input" value="<?php echo esc_attr(get_option('killermod_hero_btn1_text', 'Browse Mods')); ?>" placeholder="Browse Mods">
                </div>
                <div class="km-field-group">
                  <label>CTA Button Link</label>
                  <input type="text" name="killermod_hero_btn1_url" class="km-input" value="<?php echo esc_attr(get_option('killermod_hero_btn1_url', '/category/mod-games/')); ?>" placeholder="/category/mod-games/">
                </div>
              </div>
            </div>
          </div>

          <!-- Card 2: Homepage Sections -->
          <div class="km-section">
            <div class="km-section-label">🧱 Homepage Sections</div>
            <div class="km-section-body" style="display:flex; flex-direction:column; gap:14px;">
              <?php
                $active_sections = get_option('killermod_active_sections', ['trending' => '1', 'latest' => '1', 'updated' => '1']);
                // New & Popular shows when EITHER latest or updated is on.
                $new_popular_on = (($active_sections['latest'] ?? '0') === '1') || (($active_sections['updated'] ?? '0') === '1');
              ?>
              <p style="font-size:12px;color:var(--muted);margin:0;">Choose which sections appear on the homepage. Both pull from the <strong>Mod Games</strong> category automatically.</p>

              <label style="display:flex; align-items:center; gap:10px; font-weight:700; font-size:13px; cursor:pointer; padding:10px 12px; background:var(--surface2); border:1px solid var(--border); border-radius:10px;">
                <input type="checkbox" name="sections[trending]" value="1" <?php checked($active_sections['trending'] ?? '0', '1'); ?>>
                Show <span style="color:var(--primary);">“Trending Mods”</span> grid <small style="font-weight:400;color:var(--muted);">— newest 10 mods</small>
              </label>

              <label style="display:flex; align-items:center; gap:10px; font-weight:700; font-size:13px; cursor:pointer; padding:10px 12px; background:var(--surface2); border:1px solid var(--border); border-radius:10px;">
                <input type="checkbox" name="sections[latest]" value="1" <?php checked($new_popular_on, true); ?>>
                Show <span style="color:var(--accent2);">“New &amp; Popular”</span> grid <small style="font-weight:400;color:var(--muted);">— recently updated mods</small>
              </label>
            </div>
          </div>

          <!-- Card 3: Global Brand socials (Jashan Banner) -->
          <div class="km-section">
            <div class="km-section-label">📢 Jashan Banner &amp; Channel Links</div>
            <div class="km-section-body km-grid-2">
              <div class="km-field-group">
                <label>Banner Title</label>
                <input type="text" name="killermod_banner_title" class="km-input" value="<?php echo esc_attr(get_option('killermod_banner_title', 'Jashan Mods')); ?>">
              </div>
              <div class="km-field-group">
                <label>Banner Tagline</label>
                <input type="text" name="killermod_banner_tagline" class="km-input" value="<?php echo esc_attr(get_option('killermod_banner_tagline', 'Real &amp; Premium Mods for the community')); ?>">
              </div>
              <div class="km-field-group">
                <label>Global YouTube Channel URL</label>
                <input type="url" name="killermod_global_youtube" class="km-input" value="<?php echo esc_url(get_option('killermod_global_youtube', 'https://youtube.com/@JashanMods')); ?>">
              </div>
              <div class="km-field-group">
                <label>Global Telegram Channel URL</label>
                <input type="url" name="killermod_global_telegram" class="km-input" value="<?php echo esc_url(get_option('killermod_global_telegram', 'https://t.me/JashanMods')); ?>">
              </div>
            </div>
          </div>

          <!-- Card 4: KillerMod App download link (global — same APK for every mod) -->
          <div class="km-section km-section-app">
            <div class="km-section-label">📱 KillerMod App</div>
            <div class="km-section-body">
              <div class="km-field-group">
                <label>App APK Download Link <small>(the actual KillerMod App .apk file — used as the fallback on every Fast Download page when someone doesn't have the app installed yet)</small></label>
                <input type="url" name="killermod_app_apk_url" class="km-input" value="<?php echo esc_url(get_option('killermod_app_apk_url', '')); ?>" placeholder="https://killermod.com/downloads/killermod-app.apk">
              </div>
            </div>
          </div>

          <div style="margin-top:20px; display:flex; justify-content:flex-end;">
            <button type="submit" class="km-save-all-btn" id="km-save-global-settings">💾 Save Homepage Settings</button>
          </div>

        </form>
      </div> <!-- End Tab 2: Homepage Settings -->

    </div>
    <?php
}
