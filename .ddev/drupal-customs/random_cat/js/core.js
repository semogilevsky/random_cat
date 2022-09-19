(function ($, Drupal) {
  Drupal.behaviors.randomCatCore = {
    attach: function attach(context) {
      $("img.rc-img").each(function (index, data) {
        if (!$(data).hasClass('rc-processed')) {
          $(data).addClass('rc-processed');

          $.get("/random_cat/get", function (response, status) {
            if (response.url) {
              $("img.rc-img").attr('src', response.url);
            }

            if (response.id) {
              $("a.rc-vote-up").attr('href', $("a.rc-vote-up").attr('href').replace('{cat_id}', response.id));
              $("a.rc-vote-up").addClass('use-ajax');

              $("a.rc-vote-down").attr('href', $("a.rc-vote-down").attr('href').replace('{cat_id}', response.id));
              $("a.rc-vote-down").addClass('use-ajax');

              Drupal.ajax.bindAjaxLinks();
            }
          });
        }
      });

      $("img.rc-search-result").each(function (index, data) {
        var imageId = $(data).attr('data-image-id');

        if (imageId && !$(data).hasClass('rc-search-processed')) {
          $(data).addClass('rc-search-processed');

          $.get("/random_cat/get/" + imageId, function (response, status) {
            $(data).attr('src', response.url);
          });
        }
      });
    }
  };
})(jQuery, Drupal);
