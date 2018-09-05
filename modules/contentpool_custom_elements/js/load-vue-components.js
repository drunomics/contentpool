Vue.component('pg-text', window['vue-lupus-paragraph-text'].PgText);
Vue.component("pg-image", window['vue-lupus-paragraph-image'].PgImage);
Vue.component("pg-instagram", window['vue-lupus-paragraph-instagram'].PgInstagram);
Vue.component("pg-gallery", window['vue-lupus-paragraph-gallery'].PgGallery);
Vue.component("pg-pinterest", window['vue-lupus-paragraph-pinterest'].PgPinterest);
Vue.component("pg-quote", window['vue-lupus-paragraph-quote'].PgQuote);
Vue.component("pg-twitter", window['vue-lupus-paragraph-twitter'].PgTwitter);
Vue.component("pg-link", window['vue-lupus-paragraph-link'].PgLink);
Vue.component("pg-video", window['vue-lupus-paragraph-video'].PgVideo);

var elements = document.getElementsByClassName('custom-elements');

Array.prototype.forEach.call(elements, function(el, i) {
  el.id = 'custom-element-' + i;
  var app = new Vue({ el: '#custom-element-' + i});
  i++;
});
