(function () {

  if (Drupal.zero === undefined) Drupal.zero = {};

  Drupal.zero.Settings = {

    fallback(value, fallback) {
      if (fallback === undefined) fallback = null;
      return value === undefined ? fallback : value;
    },

    uuid(element) {
      if (typeof element === 'string') {
        return element;
      } else {
        return element.dataset.zeroUuid || null;
      }
    },

    get(element, namespace) {
      var uuid = this.uuid(element);

      if (namespace) {
        return this.fallback(drupalSettings['zero_entitywrapper__' + uuid][namespace]);
      } else {
        return this.fallback(drupalSettings['zero_entitywrapper__' + uuid]);
      }
    },

    set(element, namespace, value) {
      var uuid = this.uuid(element);
      drupalSettings['zero_entitywrapper__' + uuid][namespace] = value;
    },

    list() {
      var list = [];
      for (var index in drupalSettings) {
        if (index.startsWith('zero_entitywrapper__')) {
          list.push(index.substring(20));
        }
      }
      return list;
    },

    getConfig(uuid, namespace, defaults = {}) {
      return {...defaults, ...(this.fallback(drupalSettings && drupalSettings['zero_entitywrapper__' + uuid] && drupalSettings['zero_entitywrapper__' + uuid][namespace] || null) || {})};
    },

  };

})();
