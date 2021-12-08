window.playerEvent = (action, name = '', value = '') => {
  window._paq && window._paq.push(['trackEvent', '播放器', action, name, value]);
};
