(function () {
  'use strict';

  document.querySelectorAll('[data-fdshop-products-module].is-carousel').forEach(function (module) {
    var viewport = module.querySelector('[data-products-viewport]');
    var previous = module.querySelector('[data-products-prev]');
    var next = module.querySelector('[data-products-next]');
    if (!viewport || !previous || !next) return;

    var move = function (direction) {
      viewport.scrollBy({ left: direction * viewport.clientWidth, behavior: 'smooth' });
    };
    previous.addEventListener('click', function () { move(-1); });
    next.addEventListener('click', function () { move(1); });
  });
}());
