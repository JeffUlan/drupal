/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/

((Drupal, once) => {
  const closeMessage = message => {
    const messageContainer = message.querySelector('[data-drupal-selector="messages-container"]');
    const closeBtnWrapper = document.createElement('div');
    closeBtnWrapper.setAttribute('class', 'messages__button');
    const closeBtn = document.createElement('button');
    closeBtn.setAttribute('type', 'button');
    closeBtn.setAttribute('class', 'messages__close');
    const closeBtnText = document.createElement('span');
    closeBtnText.setAttribute('class', 'visually-hidden');
    closeBtnText.innerText = Drupal.t('Close message');
    messageContainer.appendChild(closeBtnWrapper);
    closeBtnWrapper.appendChild(closeBtn);
    closeBtn.appendChild(closeBtnText);
    closeBtn.addEventListener('click', () => {
      message.classList.add('hidden');
    });
  };

  Drupal.behaviors.messages = {
    attach(context) {
      once('messages', '[data-drupal-selector="messages"]', context).forEach(closeMessage);
    }

  };
  Drupal.olivero.closeMessage = closeMessage;
})(Drupal, once);