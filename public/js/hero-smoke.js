/**
 * hero-smoke.js — Fondo animado de humo (WebGL2) para el hero del home.
 * Portado a JS vanilla desde un componente React/shadcn (React era solo el
 * envoltorio del ciclo de vida; el efecto es WebGL puro). Tinte rojo QuadCore.
 *
 * Guardas: si no hay WebGL2 o el usuario pidió menos movimiento, no se ejecuta
 * (queda el gradiente estático). Se pausa cuando el hero no está en pantalla.
 */
(function () {
    const canvas = document.getElementById('hero-smoke');
    if (!canvas || !window.WebGL2RenderingContext) return;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    const gl = canvas.getContext('webgl2');
    if (!gl) return;

    const fragmentSrc = `#version 300 es
precision highp float;
out vec4 O;
uniform float time;
uniform vec2 resolution;
uniform vec3 u_color;
#define FC gl_FragCoord.xy
#define R resolution
#define T (time+660.)
float rnd(vec2 p){p=fract(p*vec2(12.9898,78.233));p+=dot(p,p+34.56);return fract(p.x*p.y);}
float noise(vec2 p){vec2 i=floor(p),f=fract(p),u=f*f*(3.-2.*f);return mix(mix(rnd(i),rnd(i+vec2(1,0)),u.x),mix(rnd(i+vec2(0,1)),rnd(i+1.),u.x),u.y);}
float fbm(vec2 p){float t=.0,a=1.;for(int i=0;i<5;i++){t+=a*noise(p);p*=mat2(1,-1.2,.2,1.2)*2.;a*=.5;}return t;}
void main(){
  vec2 uv=(FC-.5*R)/R.y;
  vec3 col=vec3(1);
  uv.x+=.25; uv*=vec2(2,1);
  float n=fbm(uv*.28-vec2(T*.01,0));
  n=noise(uv*3.+n*2.);
  col.r-=fbm(uv+vec2(0,T*.015)+n);
  col.g-=fbm(uv*1.003+vec2(0,T*.015)+n+.003);
  col.b-=fbm(uv*1.006+vec2(0,T*.015)+n+.006);
  col=mix(col, u_color, dot(col,vec3(.21,.71,.07)));
  col=mix(vec3(.08),col,min(time*.1,1.));
  col=clamp(col,.08,1.);
  O=vec4(col,1);
}`;
    const vertexSrc = `#version 300 es
precision highp float;
in vec4 position;
void main(){gl_Position=position;}`;

    function compile(type, src) {
        const s = gl.createShader(type);
        gl.shaderSource(s, src);
        gl.compileShader(s);
        if (!gl.getShaderParameter(s, gl.COMPILE_STATUS)) console.error(gl.getShaderInfoLog(s));
        return s;
    }
    const program = gl.createProgram();
    gl.attachShader(program, compile(gl.VERTEX_SHADER, vertexSrc));
    gl.attachShader(program, compile(gl.FRAGMENT_SHADER, fragmentSrc));
    gl.linkProgram(program);
    if (!gl.getProgramParameter(program, gl.LINK_STATUS)) { console.error(gl.getProgramInfoLog(program)); return; }

    const buf = gl.createBuffer();
    gl.bindBuffer(gl.ARRAY_BUFFER, buf);
    gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1, 1, -1, -1, 1, 1, 1, -1]), gl.STATIC_DRAW);
    const pos = gl.getAttribLocation(program, 'position');
    gl.enableVertexAttribArray(pos);
    gl.vertexAttribPointer(pos, 2, gl.FLOAT, false, 0, 0);

    const uRes = gl.getUniformLocation(program, 'resolution');
    const uTime = gl.getUniformLocation(program, 'time');
    const uColor = gl.getUniformLocation(program, 'u_color');
    const COLOR = [247 / 255, 79 / 255, 60 / 255]; // rojo QuadCore #F74F3C

    function resize() {
        const dpr = Math.min(2, window.devicePixelRatio || 1); // cap dpr por performance
        const w = canvas.clientWidth, h = canvas.clientHeight;
        if (!w || !h) return;
        canvas.width = w * dpr;
        canvas.height = h * dpr;
        gl.viewport(0, 0, canvas.width, canvas.height);
    }
    window.addEventListener('resize', resize);

    let raf = null;
    function loop(now) {
        gl.useProgram(program);
        gl.uniform2f(uRes, canvas.width, canvas.height);
        gl.uniform1f(uTime, now * 1e-3);
        gl.uniform3fv(uColor, COLOR);
        gl.drawArrays(gl.TRIANGLE_STRIP, 0, 4);
        raf = requestAnimationFrame(loop);
    }
    const start = () => { if (raf == null) { resize(); raf = requestAnimationFrame(loop); } };
    const stop = () => { if (raf != null) { cancelAnimationFrame(raf); raf = null; } };

    // ¿El hero está realmente visible? (display:none → tamaño 0; o scrolleado fuera)
    function visible() {
        const r = canvas.getBoundingClientRect();
        return r.width > 1 && r.height > 1 && r.bottom > 0 && r.top < window.innerHeight;
    }
    const update = () => (visible() ? start() : stop());

    // Solo anima cuando el hero está a la vista → no gasta GPU de fondo.
    window.addEventListener('scroll', update, { passive: true });
    window.addEventListener('resize', update);
    window.addEventListener('hashchange', () => setTimeout(update, 60)); // tras el cambio de vista SPA
    // Primer arranque DESPUÉS de que el router activó la vista (si no, el canvas aún está oculto)
    requestAnimationFrame(() => requestAnimationFrame(update));
})();
