(function ($) {

  Drupal.zero.Settings = {

    fallback(value, fallback) {
      if (fallback === undefined) fallback = null;
      return value === undefined ? fallback : value;
    },

    uuid(element) {
      if (typeof element === 'string') {
        return element;
      } else {
        return element.data('zero-uuid') || null;
      }
    },

    element(uuid) {
      if (typeof uuid === 'string') {
        return $('[data-zero-uuid="' + uuid + '"]');
      } else {
        return uuid;
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

  };

})(jQuery);

(function ($) {

  var item = $('.item');
  var slider = Drupal.zero.Settings.get(item, 'slider');
  slider.page = 5;
  for (var uuid of Drupal.zero.Settings.list()) {
    console.log(Drupal.zero.Settings.element(uuid));
  }

})(jQuery);