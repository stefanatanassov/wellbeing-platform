(function () {
  var dismissKey = 'sbStudentInstallDismissedAt';
  var installCard = document.querySelector('[data-install-card]');
  var installAction = document.querySelector('[data-install-action]');
  var installDismiss = document.querySelector('[data-install-dismiss]');
  var iosSheet = document.querySelector('[data-ios-sheet]');
  var iosClose = document.querySelector('[data-ios-close]');
  var nativeInstall = document.querySelector('[data-native-install]');
  var deferredPrompt = null;
  var search = new URLSearchParams(window.location.search);
  var forceInstall = search.get('install') === '1';
  var isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
  var isIos = /iphone|ipad|ipod/i.test(window.navigator.userAgent || '');
  var isSafari = /^((?!chrome|android).)*safari/i.test(window.navigator.userAgent || '');

  function shouldSuppressPrompt() {
    var dismissedAt = Number(window.localStorage.getItem(dismissKey) || '0');
    return dismissedAt > 0 && (Date.now() - dismissedAt) < (7 * 24 * 60 * 60 * 1000);
  }

  function hideInstallUx() {
    if (installCard) installCard.hidden = true;
    if (iosSheet) iosSheet.hidden = true;
  }

  function showInstallCard() {
    if (!installCard || isStandalone || (!forceInstall && shouldSuppressPrompt())) {
      return;
    }
    installCard.hidden = false;
    if (forceInstall) {
      installCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }

  function dismissInstallUx() {
    window.localStorage.setItem(dismissKey, String(Date.now()));
    hideInstallUx();
  }

  function openIosSheet() {
    if (!iosSheet || isStandalone || (!forceInstall && shouldSuppressPrompt())) {
      return;
    }
    iosSheet.hidden = false;
    showInstallCard();
  }

  function closeIosSheet() {
    if (iosSheet) {
      iosSheet.hidden = true;
    }
  }

  if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
      navigator.serviceWorker.register('./service-worker.js').catch(function () {});
    });
  }

  window.addEventListener('beforeinstallprompt', function (event) {
    event.preventDefault();
    deferredPrompt = event;
    if (nativeInstall) {
      nativeInstall.classList.add('is-visible');
    }
    showInstallCard();
  });

  window.addEventListener('appinstalled', function () {
    deferredPrompt = null;
    dismissInstallUx();
  });

  if (installAction) {
    installAction.addEventListener('click', function () {
      openIosSheet();
    });
  }

  if (installDismiss) {
    installDismiss.addEventListener('click', dismissInstallUx);
  }

  if (iosClose) {
    iosClose.addEventListener('click', function () {
      closeIosSheet();
      dismissInstallUx();
    });
  }

  if (nativeInstall) {
    nativeInstall.addEventListener('click', async function () {
      if (!deferredPrompt) {
        return;
      }
      deferredPrompt.prompt();
      try {
        await deferredPrompt.userChoice;
      } catch (error) {
      }
      deferredPrompt = null;
      nativeInstall.classList.remove('is-visible');
      dismissInstallUx();
    });
  }

  if (isIos && isSafari && !isStandalone && (forceInstall || !shouldSuppressPrompt())) {
    showInstallCard();
    window.setTimeout(openIosSheet, 600);
  } else if (forceInstall) {
    showInstallCard();
  }
}());
