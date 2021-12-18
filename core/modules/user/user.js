/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/

(($, Drupal) => {
  Drupal.user = {
    password: {
      css: {
        passwordParent: 'password-parent',
        passwordsMatch: 'ok',
        passwordsNotMatch: 'error',
        passwordWeak: 'is-weak',
        passwordFair: 'is-fair',
        passwordGood: 'is-good',
        passwordStrong: 'is-strong',
        widgetInitial: '',
        passwordEmpty: '',
        passwordFilled: '',
        confirmEmpty: '',
        confirmFilled: ''
      }
    }
  };
  Drupal.behaviors.password = {
    attach(context, settings) {
      const cssClasses = Drupal.user.password.css;
      once('password', 'input.js-password-field', context).forEach(value => {
        const $mainInput = $(value);
        const $mainInputParent = $mainInput.parent().addClass(cssClasses.passwordParent);
        const $passwordWidget = $mainInput.closest('.js-form-type-password-confirm');
        const $confirmInput = $passwordWidget.find('input.js-password-confirm');
        const $passwordConfirmMessage = $(Drupal.theme('passwordConfirmMessage', settings.password));
        let $passwordMatchStatus = $passwordConfirmMessage.find('[data-drupal-selector="password-match-status-text"]').first();

        if ($passwordMatchStatus.length === 0) {
          $passwordMatchStatus = $passwordConfirmMessage.find('span').first();
          Drupal.deprecationError({
            message: 'Returning <span> without data-drupal-selector="password-match-status-text" attribute is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. See https://www.drupal.org/node/3152101'
          });
        }

        const $confirmInputParent = $confirmInput.parent().addClass('confirm-parent').append($passwordConfirmMessage);
        const passwordStrengthBarClassesToRemove = [cssClasses.passwordWeak || '', cssClasses.passwordFair || '', cssClasses.passwordGood || '', cssClasses.passwordStrong || ''].join(' ').trim();
        const confirmTextWrapperClassesToRemove = [cssClasses.passwordsMatch || '', cssClasses.passwordsNotMatch || ''].join(' ').trim();
        const widgetClassesToRemove = [cssClasses.widgetInitial || '', cssClasses.passwordEmpty || '', cssClasses.passwordFilled || '', cssClasses.confirmEmpty || '', cssClasses.confirmFilled || ''].join(' ').trim();
        const password = {};

        if (settings.password.showStrengthIndicator) {
          const $passwordStrength = $(Drupal.theme('passwordStrength', settings.password));
          password.$strengthBar = $passwordStrength.find('[data-drupal-selector="password-strength-indicator"]').first();

          if (password.$strengthBar.length === 0) {
            password.$strengthBar = $passwordStrength.find('.js-password-strength__indicator').first();
            Drupal.deprecationError({
              message: 'The js-password-strength__indicator class is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Replace js-password-strength__indicator with a data-drupal-selector="password-strength-indicator" attribute. See https://www.drupal.org/node/3152101'
            });
          }

          password.$strengthTextWrapper = $passwordStrength.find('[data-drupal-selector="password-strength-text"]').first();

          if (password.$strengthTextWrapper.length === 0) {
            password.$strengthTextWrapper = $passwordStrength.find('.js-password-strength__text').first();
            Drupal.deprecationError({
              message: 'The js-password-strength__text class is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Replace js-password-strength__text with a data-drupal-selector="password-strength-text" attribute. See https://www.drupal.org/node/3152101'
            });
          }

          password.$suggestions = $(Drupal.theme('passwordSuggestions', settings.password, []));
          password.$suggestions.hide();
          $mainInputParent.append($passwordStrength);
          $confirmInputParent.after(password.$suggestions);
        }

        const addWidgetClasses = () => {
          $passwordWidget.addClass($mainInput.val() ? cssClasses.passwordFilled : cssClasses.passwordEmpty).addClass($confirmInput.val() ? cssClasses.confirmFilled : cssClasses.confirmEmpty);
        };

        const passwordCheckMatch = confirmInputVal => {
          const passwordsAreMatching = $mainInput.val() === confirmInputVal;
          const confirmClass = passwordsAreMatching ? cssClasses.passwordsMatch : cssClasses.passwordsNotMatch;
          const confirmMessage = passwordsAreMatching ? settings.password.confirmSuccess : settings.password.confirmFailure;

          if (!$passwordMatchStatus.hasClass(confirmClass) || !$passwordMatchStatus.html() === confirmMessage) {
            if (confirmTextWrapperClassesToRemove) {
              $passwordMatchStatus.removeClass(confirmTextWrapperClassesToRemove);
            }

            $passwordMatchStatus.html(confirmMessage).addClass(confirmClass);
          }
        };

        const passwordCheck = () => {
          if (settings.password.showStrengthIndicator) {
            const result = Drupal.evaluatePasswordStrength($mainInput.val(), settings.password);
            const $currentPasswordSuggestions = $(Drupal.theme('passwordSuggestions', settings.password, result.messageTips));

            if (password.$suggestions.html() !== $currentPasswordSuggestions.html()) {
              password.$suggestions.replaceWith($currentPasswordSuggestions);
              password.$suggestions = $currentPasswordSuggestions.toggle(result.strength !== 100);
            }

            if (passwordStrengthBarClassesToRemove) {
              password.$strengthBar.removeClass(passwordStrengthBarClassesToRemove);
            }

            password.$strengthBar.css('width', `${result.strength}%`).addClass(result.indicatorClass);
            password.$strengthTextWrapper.html(result.indicatorText);
          }

          if ($confirmInput.val()) {
            passwordCheckMatch($confirmInput.val());
            $passwordConfirmMessage.css({
              visibility: 'visible'
            });
          } else {
            $passwordConfirmMessage.css({
              visibility: 'hidden'
            });
          }

          if (widgetClassesToRemove) {
            $passwordWidget.removeClass(widgetClassesToRemove);
            addWidgetClasses();
          }
        };

        if (widgetClassesToRemove) {
          addWidgetClasses();
        }

        $mainInput.on('input', passwordCheck);
        $confirmInput.on('input', passwordCheck);
      });
    }

  };

  Drupal.evaluatePasswordStrength = (password, passwordSettings) => {
    password = password.trim();
    let indicatorText;
    let indicatorClass;
    let weaknesses = 0;
    let strength = 100;
    let msg = [];
    const hasLowercase = /[a-z]/.test(password);
    const hasUppercase = /[A-Z]/.test(password);
    const hasNumbers = /[0-9]/.test(password);
    const hasPunctuation = /[^a-zA-Z0-9]/.test(password);
    const $usernameBox = $('input.username');
    const username = $usernameBox.length > 0 ? $usernameBox.val() : passwordSettings.username;

    if (password.length < 12) {
      msg.push(passwordSettings.tooShort);
      strength -= (12 - password.length) * 5 + 30;
    }

    if (!hasLowercase) {
      msg.push(passwordSettings.addLowerCase);
      weaknesses += 1;
    }

    if (!hasUppercase) {
      msg.push(passwordSettings.addUpperCase);
      weaknesses += 1;
    }

    if (!hasNumbers) {
      msg.push(passwordSettings.addNumbers);
      weaknesses += 1;
    }

    if (!hasPunctuation) {
      msg.push(passwordSettings.addPunctuation);
      weaknesses += 1;
    }

    switch (weaknesses) {
      case 1:
        strength -= 12.5;
        break;

      case 2:
        strength -= 25;
        break;

      case 3:
      case 4:
        strength -= 40;
        break;
    }

    if (password !== '' && password.toLowerCase() === username.toLowerCase()) {
      msg.push(passwordSettings.sameAsUsername);
      strength = 5;
    }

    const cssClasses = Drupal.user.password.css;

    if (strength < 60) {
      indicatorText = passwordSettings.weak;
      indicatorClass = cssClasses.passwordWeak;
    } else if (strength < 70) {
      indicatorText = passwordSettings.fair;
      indicatorClass = cssClasses.passwordFair;
    } else if (strength < 80) {
      indicatorText = passwordSettings.good;
      indicatorClass = cssClasses.passwordGood;
    } else if (strength <= 100) {
      indicatorText = passwordSettings.strong;
      indicatorClass = cssClasses.passwordStrong;
    }

    const messageTips = msg;
    msg = `${passwordSettings.hasWeaknesses}<ul><li>${msg.join('</li><li>')}</li></ul>`;
    return Drupal.deprecatedProperty({
      target: {
        strength,
        message: msg,
        indicatorText,
        indicatorClass,
        messageTips
      },
      deprecatedProperty: 'message',
      message: 'The message property is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. The markup should be constructed using messageTips property and Drupal.theme.passwordSuggestions. See https://www.drupal.org/node/3130352'
    });
  };
})(jQuery, Drupal);