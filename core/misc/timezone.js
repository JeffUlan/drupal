/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/

(function ($, Drupal) {
  Drupal.behaviors.setTimezone = {
    attach(context, settings) {
      const timezone = once('timezone', '.timezone-detect', context);

      if (timezone.length) {
        const tz = new Intl.DateTimeFormat().resolvedOptions().timeZone;

        if (tz && $(timezone).find(`option[value="${tz}"]`).length) {
          timezone.forEach(item => {
            item.value = tz;
          });
          return;
        }

        const dateString = Date();
        const matches = dateString.match(/\(([A-Z]{3,5})\)/);
        const abbreviation = matches ? matches[1] : 0;
        const dateNow = new Date();
        const offsetNow = dateNow.getTimezoneOffset() * -60;
        const dateJan = new Date(dateNow.getFullYear(), 0, 1, 12, 0, 0, 0);
        const dateJul = new Date(dateNow.getFullYear(), 6, 1, 12, 0, 0, 0);
        const offsetJan = dateJan.getTimezoneOffset() * -60;
        const offsetJul = dateJul.getTimezoneOffset() * -60;
        let isDaylightSavingTime;

        if (offsetJan === offsetJul) {
          isDaylightSavingTime = '';
        } else if (Math.max(offsetJan, offsetJul) === offsetNow) {
          isDaylightSavingTime = 1;
        } else {
          isDaylightSavingTime = 0;
        }

        const path = `system/timezone/${abbreviation}/${offsetNow}/${isDaylightSavingTime}`;
        $.ajax({
          async: false,
          url: Drupal.url(path),
          data: {
            date: dateString
          },
          dataType: 'json',

          success(data) {
            if (data) {
              document.querySelectorAll('.timezone-detect').forEach(item => {
                item.value = data;
              });
            }
          }

        });
      }
    }

  };
})(jQuery, Drupal);