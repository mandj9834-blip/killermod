/* KillerMod Manager — Admin JS v5.3 */
(function ($) {
  'use strict';
  console.log('%cKillerMod Manager admin.js loaded — v5.3 (App & Ads section)', 'color:#a855f7;font-weight:bold;');

  /* ---- Toast ---- */
  function toast(msg, type) {
    var $t = $('#km-toast');
    $t.removeClass('success error show').addClass(type || 'success').text(msg);
    $t[0].offsetHeight;
    $t.addClass('show');
    clearTimeout($t.data('tmr'));
    $t.data('tmr', setTimeout(function(){ $t.removeClass('show'); }, 2800));
  }

  /* ---- Toggle expand row ---- */
  $(document).on('click', '.km-expand-btn', function () {
    var pid = $(this).data('pid');
    var $detail = $('#km-detail-' + pid);
    var $btn = $(this);
    if ($detail.is(':visible')) {
      $detail.hide();
      $btn.removeClass('open').text('✏️ Edit');
    } else {
      $detail.show();
      $btn.addClass('open').text('✕ Close');
      $detail[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
  });

  /* ---- Collapse button inside detail ---- */
  $(document).on('click', '.km-collapse-btn', function () {
    var pid = $(this).data('pid');
    $('#km-detail-' + pid).hide();
    $('[data-pid="' + pid + '"].km-expand-btn').removeClass('open').text('✏️ Edit');
  });

  /* ---- Click thumbnail in summary row → open edit row ---- */
  $(document).on('click', '.km-thumb-wrap', function () {
    var pid = $(this).data('pid');
    var $btn = $('.km-expand-btn[data-pid="' + pid + '"]');
    if (!$('#km-detail-' + pid).is(':visible')) $btn.trigger('click');
  });

  /* ---- WP Media Library picker — THUMBNAIL ---- */
  var mediaFrame = null;
  $(document).on('click', '.km-btn-pick-media', function () {
    var pid = $(this).data('pid');
    if (mediaFrame) { mediaFrame.off('select'); }
    mediaFrame = wp.media({
      title: 'Select Thumbnail',
      button: { text: 'Use as Thumbnail' },
      multiple: false,
      library: { type: 'image' }
    });
    mediaFrame.on('select', function () {
      var att = mediaFrame.state().get('selection').first().toJSON();
      $('#km-thumb-id-' + pid).val(att.id);
      var src = att.sizes && att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url;
      var $prev = $('#km-picker-preview-' + pid);
      if ($prev.is('img')) {
        $prev.attr('src', src);
      } else {
        $prev.replaceWith('<img src="'+src+'" class="km-picker-preview" id="km-picker-preview-'+pid+'">');
      }
    });
    mediaFrame.open();
  });

  /* ---- Remove thumbnail ---- */
  $(document).on('click', '.km-btn-remove-thumb', function () {
    var pid = $(this).data('pid');
    $('#km-thumb-id-' + pid).val('-1');
    var $prev = $('#km-picker-preview-' + pid);
    if ($prev.is('img')) {
      $prev.replaceWith('<div class="km-picker-empty" id="km-picker-preview-'+pid+'">No Image</div>');
    }
  });

  /* ---- WP Media Library picker — BANNER IMAGE ---- */
  var bannerFrame = null;
  $(document).on('click', '.km-btn-pick-banner', function () {
    var pid = $(this).data('pid');
    if (bannerFrame) { bannerFrame.off('select'); }
    bannerFrame = wp.media({
      title: 'Select Banner Image',
      button: { text: 'Use as Banner' },
      multiple: false,
      library: { type: 'image' }
    });
    bannerFrame.on('select', function () {
      var att = bannerFrame.state().get('selection').first().toJSON();
      $('#km-banner-id-' + pid).val(att.id);
      var src = att.sizes && att.sizes.medium ? att.sizes.medium.url : att.url;
      var $prev = $('#km-banner-preview-' + pid);
      if ($prev.is('img')) {
        $prev.attr('src', src);
      } else {
        $prev.replaceWith('<img src="'+src+'" class="km-banner-preview" id="km-banner-preview-'+pid+'">');
      }
    });
    bannerFrame.open();
  });

  /* ---- Remove banner ---- */
  $(document).on('click', '.km-btn-remove-banner', function () {
    var pid = $(this).data('pid');
    $('#km-banner-id-' + pid).val('-1');
    var $prev = $('#km-banner-preview-' + pid);
    if ($prev.is('img')) {
      $prev.replaceWith('<div class="km-banner-empty" id="km-banner-preview-'+pid+'">No Banner — click to pick one</div>');
    }
  });

  /* ---- Save All (per row) ---- */
  function saveAll(pid) {
    var $row     = $('#km-detail-' + pid);
    var $btn     = $row.find('.km-save-all-btn');
    var title    = $row.find('.km-post-title-input').val().trim();
    var link1    = $row.find('.km-link1').val().trim();
    var link2    = $row.find('.km-link2').val().trim();
    var yt       = $row.find('.km-yt').val().trim();
    var tg       = $row.find('.km-tg').val().trim();
    var feats    = $row.find('.km-features').val().trim();
    var thumbId  = $row.find('.km-thumb-id').val();
    var bannerId = $row.find('.km-banner-id').val();
    var catId    = $row.find('.km-cat-picker').val();
    var slug     = $row.find('.km-slug-input').val().trim();
    var showInApp= $row.find('.km-show-in-app').is(':checked');
    var appLink  = $row.find('.km-app-link').val().trim();
    var version  = $row.find('.km-version').val().trim();
    var size     = $row.find('.km-size').val().trim();
    var pkg      = $row.find('.km-package').val().trim();

    $btn.prop('disabled', true).text('💾 Saving…');

    $.post(kmData.ajaxUrl, {
      action:              'km_save_post',
      nonce:               kmData.nonce,
      post_id:             pid,
      post_title:          title,
      _km_download_link:   link1,
      _km_download_link2:  link2,
      _km_youtube_url:     yt,
      _km_telegram_url:    tg,
      _km_mod_features:    feats,
      thumb_id:            thumbId,
      banner_id:           bannerId,
      post_category_id:    catId,
      post_slug:           slug,
      show_in_app:         showInApp ? '1' : '',
      _km_app_link:        appLink,
      _km_version:         version,
      _km_size:            size,
      _km_package:         pkg
    }, function (res) {
      $btn.prop('disabled', false);
      if (res.success) {
        $btn.addClass('saved').text('✅ Saved!');
        setTimeout(function(){ $btn.removeClass('saved').text('💾 Save All Changes'); }, 2200);

        // Update summary row title
        var $sumRow = $('.km-summary-row[data-pid="' + pid + '"]');
        if (res.data.new_title) $sumRow.find('.km-title-text').text(res.data.new_title);

        // Update category badge in summary row
        if (res.data.new_cat) $sumRow.find('.km-cat-badge').text(res.data.new_cat);

        // Update slug input & permalink preview
        if (res.data.new_slug) {
          $row.find('.km-slug-input[data-pid="' + pid + '"]').val(res.data.new_slug);
        }
        if (res.data.new_permalink) {
          $row.find('#km-slug-url-' + pid).html('<a href="' + res.data.new_permalink + '" target="_blank">' + res.data.new_permalink + ' ↗</a>');
        }

        // Update thumbnail in summary row
        if (res.data.new_thumb) {
          var $tw = $sumRow.find('.km-thumb-wrap');
          var $img = $tw.find('.km-thumb-img');
          if ($img.length) { $img.attr('src', res.data.new_thumb); }
          else { $tw.find('.km-no-thumb').replaceWith('<img src="'+res.data.new_thumb+'" class="km-thumb-img" data-pid="'+pid+'">'); }
        }

        // Update status dots
        var dots = $sumRow.find('.km-link-dot');
        $(dots[0]).removeClass('grey green').addClass(link1    ? 'green'  : 'grey');
        $(dots[1]).removeClass('grey teal').addClass(link2     ? 'teal'   : 'grey');
        $(dots[2]).removeClass('grey red').addClass(yt         ? 'red'    : 'grey');
        $(dots[3]).removeClass('grey blue').addClass(tg        ? 'blue'   : 'grey');
        $(dots[4]).removeClass('grey orange').addClass(bannerId && bannerId !== '-1' ? 'orange' : 'grey');
        $(dots[5]).removeClass('grey purple').addClass(showInApp ? 'purple' : 'grey');

        // Fill in the auto-generated App Link placeholder if left blank
        if (res.data.new_app_link && !appLink) {
          $row.find('.km-app-link').attr('placeholder', res.data.new_app_link);
        }

        // Mark inputs as saved briefly
        $row.find('.km-input').addClass('saved');
        setTimeout(function(){ $row.find('.km-input').removeClass('saved'); }, 1800);

        toast('✅ Post #' + pid + ' saved!', 'success');
      } else {
        toast('❌ ' + (res.data || 'Save failed'), 'error');
        $btn.text('💾 Save All Changes');
      }
    }).fail(function () {
      $btn.prop('disabled', false).text('💾 Save All Changes');
      toast('❌ Request failed', 'error');
    });
  }

  /* ---- Apply to All Posts ---- */
  $(document).on('click', '.km-apply-all-btn', function () {
    var $btn  = $(this);
    var key   = $btn.data('key');
    var pid   = $btn.data('pid');
    var $row  = $('#km-detail-' + pid);

    // Get the current value from the matching input/textarea
    var value = '';
    if (key === '_km_youtube_url')   value = $row.find('.km-yt').val().trim();
    if (key === '_km_telegram_url')  value = $row.find('.km-tg').val().trim();
    if (key === '_km_mod_features')  value = $row.find('.km-features').val().trim();

    if (!value) { toast('⚠️ Field is empty — nothing to apply!', 'error'); return; }

    var label = key === '_km_youtube_url' ? 'YouTube URL' : key === '_km_telegram_url' ? 'Telegram URL' : 'Mod Features';
    if (!confirm('Apply this ' + label + ' to ALL posts?\n\nThis will overwrite the existing value on every post!')) return;

    $btn.prop('disabled', true).text('⏳ Applying…');

    $.post(kmData.ajaxUrl, {
      action:     'km_apply_to_all',
      nonce:      kmData.nonce,
      meta_key:   key,
      meta_value: value
    }, function (res) {
      $btn.prop('disabled', false).text('⚡ Apply to All Posts');
      if (res.success) {
        toast('✅ Applied to ' + res.data.updated + ' posts!', 'success');
      } else {
        toast('❌ ' + (res.data || 'Failed'), 'error');
      }
    }).fail(function () {
      $btn.prop('disabled', false).text('⚡ Apply to All Posts');
      toast('❌ Request failed', 'error');
    });
  });


  $(document).on('click', '.km-save-all-btn', function () {
    saveAll($(this).data('pid'));
  });

  /* Enter key in any input saves that row */
  $(document).on('keydown', '.km-detail-row .km-input', function (e) {
    if (e.key === 'Enter' && !$(this).is('textarea')) {
      e.preventDefault();
      var pid = $(this).closest('.km-detail-row').attr('id').replace('km-detail-', '');
      saveAll(pid);
    }
  });

  /* Auto-submit on category change */
  $(document).on('change', '.km-cat-select', function () {
    $(this).closest('form').submit();
  });

  /* Live slug preview as user types */
  $(document).on('input', '.km-slug-input', function () {
    var pid  = $(this).data('pid');
    var base = $(this).data('base');
    var val  = $(this).val().trim();
    var preview = base + (val || '…') + '/';
    $('#km-slug-url-' + pid).html('<a href="' + preview + '" target="_blank">' + preview + ' ↗</a>');
  });

  /* Theme Toggle Click */
  $(document).on('click', '#kmThemeToggle', function () {
    var $wrap = $('.km-admin-wrap');
    if ($wrap.hasClass('dark')) {
      $wrap.removeClass('dark');
      localStorage.setItem('km-theme', 'light');
    } else {
      $wrap.addClass('dark');
      localStorage.setItem('km-theme', 'dark');
    }
  });

  /* Tab Switching */
  $(document).on('click', '.km-tab-btn', function () {
    var tabId = $(this).data('tab');
    $('.km-tab-btn').removeClass('active');
    $(this).addClass('active');
    $('.km-tab-content').hide().removeClass('active');
    $('#' + tabId).show().addClass('active');
  });

  // Activate tab from URL query parameter
  var urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('tab') === 'homepage') {
    $('.km-tab-btn[data-tab="km-homepage-tab"]').trigger('click');
  }


  /* Save Global Settings */
  $(document).on('submit', '#km-global-settings-form', function (e) {
    e.preventDefault();
    var $form = $(this);
    var $btn = $('#km-save-global-settings');
    $btn.prop('disabled', true).text('⏳ Saving Settings…');

    var formData = $form.serializeArray();
    formData.push({ name: 'action', value: 'km_save_settings' });
    formData.push({ name: 'nonce', value: kmData.nonce });

    $.post(kmData.ajaxUrl, formData, function (res) {
      $btn.prop('disabled', false).text('💾 Save Homepage Settings');
      if (res.success) {
        toast('✅ ' + (res.data || 'Settings saved successfully!'), 'success');
      } else {
        toast('❌ ' + (res.data || 'Failed to save settings'), 'error');
      }
    }).fail(function () {
      $btn.prop('disabled', false).text('💾 Save Homepage Settings');
      toast('❌ Request failed', 'error');
    });
  });

  /* Add New Post/Card */
  $(document).on('click', '#km-add-new-post', function () {
    var $btn = $(this);
    $btn.prop('disabled', true).text('⏳ Creating Card…');

    $.post(kmData.ajaxUrl, {
      action: 'km_create_post',
      nonce:  kmData.nonce
    }, function (res) {
      $btn.prop('disabled', false).text('➕ Add New Card');
      if (res.success) {
        toast('✅ New mod card created!', 'success');
        var $tbody = $('.km-table tbody');
        if ($tbody.length) {
          $tbody.prepend(res.data.html);
          var newPid = res.data.post_id;
          var $newExpandBtn = $('.km-expand-btn[data-pid="' + newPid + '"]');
          $newExpandBtn.trigger('click');
        } else {
          window.location.reload();
        }
      } else {
        toast('❌ ' + (res.data || 'Failed to create card'), 'error');
      }
    }).fail(function () {
      $btn.prop('disabled', false).text('➕ Add New Card');
      toast('❌ Request failed', 'error');
    });
  });

})(jQuery);
