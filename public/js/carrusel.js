// Carrusel hero de categorías (home). Autoavance 5s, flechas, dots con progreso, pausa al hover.
(function(){
  var root   = document.getElementById('qcCarousel');
  if(!root) return;
  var slides = Array.prototype.slice.call(root.querySelectorAll('.qc-slide'));
  var dotsEl = document.getElementById('qcDots');
  var DURATION = 5000;          // ms por slide
  var i = 0, timer = null;

  // Construir indicadores
  slides.forEach(function(_, idx){
    var b = document.createElement('button');
    b.className = 'qc-dot' + (idx===0 ? ' is-active' : '');
    b.setAttribute('aria-label', 'Ir al slide ' + (idx+1));
    b.style.setProperty('--dur', (DURATION/1000)+'s');
    b.innerHTML = '<span class="fill"></span>';
    b.addEventListener('click', function(){ go(idx); });
    dotsEl.appendChild(b);
  });
  var dots = Array.prototype.slice.call(dotsEl.children);

  function restartFill(idx){
    var fill = dots[idx].querySelector('.fill');
    fill.style.transition = 'none'; fill.style.width = '0';
    void fill.offsetWidth;                       // reflow para reiniciar
    fill.style.transition = 'width ' + (DURATION/1000) + 's linear';
    fill.style.width = '100%';
  }

  function go(n){
    slides[i].classList.remove('is-active');
    dots[i].classList.remove('is-active');
    dots[i].querySelector('.fill').style.width = '0';
    i = (n + slides.length) % slides.length;
    slides[i].classList.add('is-active');
    dots[i].classList.add('is-active');
    restartFill(i);
    schedule();
  }
  function next(){ go(i+1); }
  function prev(){ go(i-1); }

  function schedule(){ clearTimeout(timer); timer = setTimeout(next, DURATION); }

  document.getElementById('qcNext').addEventListener('click', next);
  document.getElementById('qcPrev').addEventListener('click', prev);

  // Pausa al pasar el mouse
  root.addEventListener('mouseenter', function(){
    clearTimeout(timer);
    var f = dots[i].querySelector('.fill');
    f.style.transition = 'none';
    f.style.width = getComputedStyle(f).width;   // congela el progreso
  });
  root.addEventListener('mouseleave', function(){ restartFill(i); schedule(); });

  restartFill(0); schedule();
})();
