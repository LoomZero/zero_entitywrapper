(() => {

  const script = {

    attach(context) {
      document.querySelectorAll('#zero-wrapper-generator__codes template.zero-generator__template:not(.zero-generator__template--ready)').forEach(script.init);
    },

    init(template) {
      template.classList.add('zero-generator__template--ready');
      const string = template.content.textContent.trim();
      const container = document.createElement('div');
      container.classList.add('zero-generator__item');
      if (template.dataset.fileType === 'preprocess') {
        html = Prism.highlight(string, Prism.languages.php, 'php');
        container.innerHTML = '<pre class="language-php"><code>' + html + '</code></pre>';
      } else if (template.dataset.fileType === 'template') {
        html = Prism.highlight(string, Prism.languages.twig, 'twig');
        container.innerHTML = '<pre class="language-twig"><code>' + html + '</code></pre>';
      } else if (template.dataset.fileType === 'info') {
        html = Prism.highlight(string, Prism.languages.yaml, 'yaml');
        container.innerHTML = '<pre class="language-yaml"><code>' + html + '</code></pre>';
      }
      template.after(container);

      container.insertAdjacentHTML('afterbegin', '<div class="zero-generator__path">' + template.dataset.filePath + '</div>');
    },

  };

  Drupal.behaviors.zero_wrapper_generator__zero_generator = script;

})();
