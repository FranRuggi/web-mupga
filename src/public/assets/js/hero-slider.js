(function () {
  var slides = Array.from(document.querySelectorAll('.hero-slide'));
  if (slides.length < 2) {
    if (slides.length === 1) slides[0].classList.add('active');
    return;
  }

  var current = 0;
  var transitioning = false;
  var pendingAdvance = false;
  var FADE = 1000;
  var IMG_DELAY = 5000;

  function getVid(slide) { return slide.querySelector('video'); }

  function schedule(slide) {
    var v = getVid(slide);
    if (v) {
      v.addEventListener('ended', advance, { once: true });
    } else {
      setTimeout(advance, IMG_DELAY);
    }
  }

  function advance() {
    if (transitioning) { pendingAdvance = true; return; }
    pendingAdvance = false;
    transitioning = true;

    var from = slides[current];
    current = (current + 1) % slides.length;
    var to = slides[current];

    to.classList.add('active');
    var toVid = getVid(to);
    if (toVid) {
      toVid.currentTime = 0;
      toVid.play().catch(function () {});
    }

    setTimeout(function () {
      from.classList.remove('active');
      var fromVid = getVid(from);
      if (fromVid) { fromVid.pause(); fromVid.currentTime = 0; }
      transitioning = false;
      if (pendingAdvance) {
        advance();
      } else {
        schedule(to);
      }
    }, FADE);
  }

  slides[0].classList.add('active');
  var firstVid = getVid(slides[0]);
  if (firstVid) firstVid.play().catch(function () {});
  schedule(slides[0]);
})();
