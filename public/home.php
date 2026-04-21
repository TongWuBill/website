<?php
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/media.php';

// Load home text content (gitignored, production-only)
$home_content_path = get_home_media_path() . '/content.json';
$home_text = file_exists($home_content_path) ? (json_decode(file_get_contents($home_content_path), true) ?: []) : [];
$lang = get_lang();
$home_name    = $lang === 'cn' && !empty($home_text['name_cn'])    ? $home_text['name_cn']    : ($home_text['name_en']    ?? 'Tong Wu');
$home_tagline = $lang === 'cn' && !empty($home_text['tagline_cn']) ? $home_text['tagline_cn'] : ($home_text['tagline_en'] ?? "Interactive Artist\nCreative Technologist");

render_header('Tong Wu');
?>

<!-- ── Hero ── -->
<?php
$hero_mode = $home_text['hero_mode'] ?? 'webgl';
$hero_images = $hero_mode === 'slideshow' ? list_home_media() : [];
?>
<section class="home-hero">
  <?php if ($hero_mode === 'slideshow'): ?>
  <div class="home-hero-image">
    <?php foreach ($hero_images as $i => $img): ?>
    <img src="<?= htmlspecialchars($img['url']) ?>"
         alt=""
         class="hero-slide<?= $i === 0 ? ' hero-slide--active' : '' ?>">
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <canvas id="liquid-canvas"></canvas>
  <?php endif; ?>
  <div class="home-hero-text">
    <h1 class="home-name"><?= htmlspecialchars($home_name) ?></h1>
    <p class="home-tagline"><?= nl2br(htmlspecialchars($home_tagline)) ?></p>
  </div>
</section>

<?php if ($hero_mode === 'slideshow' && count($hero_images) >= 2): ?>
<script>
(function () {
  var slides = document.querySelectorAll('.hero-slide');
  var current = 0;
  setInterval(function () {
    slides[current].classList.remove('hero-slide--active');
    current = (current + 1) % slides.length;
    slides[current].classList.add('hero-slide--active');
  }, 5000);
}());
</script>
<?php elseif ($hero_mode === 'webgl'): ?>
<script>
(function(){
  const STREAMS = 9;
  const WIDTH   = 0.21;
  const GRAIN   = 0;
  const TOUCH   = 0.5;
  const SPEED   = 0.8;

  const canvas = document.getElementById('liquid-canvas');
  const gl = canvas.getContext('webgl');
  if (!gl) return;
  gl.getExtension('OES_standard_derivatives');

  const vert = `attribute vec2 p;void main(){gl_Position=vec4(p,0,1);}`;

  const frag = `
#extension GL_OES_standard_derivatives : enable
precision highp float;
uniform vec2 r;
uniform float t;
uniform vec3 uBg1;
uniform vec3 uBg2;
uniform vec3 uCorePal[6];
uniform int uStreams;
uniform float uWidth;
uniform float uGrain;
uniform vec2 uFinger;
uniform float uPresence;
uniform float uTouch;
uniform float uStirAngle;
uniform float uStirScale;

#define MN min(r.x,r.y)
#define MAX_STREAMS 100

float rnd(vec2 p){p=fract(p*vec2(12.9898,78.233));p+=dot(p,p+34.56);return fract(p.x*p.y);}
float noise(vec2 p){
  vec2 i=floor(p),f=fract(p),u=f*f*(3.-2.*f);
  float a=rnd(i),b=rnd(i+vec2(1,0)),c=rnd(i+vec2(0,1)),d=rnd(i+1.);
  return mix(mix(a,b,u.x),mix(c,d,u.x),u.y);
}
float fbm(vec2 p){
  float v=0.,a=1.;
  mat2 m=mat2(1.2,-.8,.8,1.2);
  for(int i=0;i<4;i++){v+=a*noise(p);p=2.*p*m;a*=.5;}
  return v;
}
float streamHash(float i,float seed){return fract(sin(i*12.9898+seed*78.233)*43758.5453);}
vec3 colorFromIdx(float idx){
  idx=fract(idx)*6.0;
  float i0=floor(idx);float f=idx-i0;
  int i0i=int(mod(i0,6.0));int i1i=int(mod(i0+1.0,6.0));
  vec3 c0,c1;
  if(i0i==0)c0=uCorePal[0];else if(i0i==1)c0=uCorePal[1];
  else if(i0i==2)c0=uCorePal[2];else if(i0i==3)c0=uCorePal[3];
  else if(i0i==4)c0=uCorePal[4];else c0=uCorePal[5];
  if(i1i==0)c1=uCorePal[0];else if(i1i==1)c1=uCorePal[1];
  else if(i1i==2)c1=uCorePal[2];else if(i1i==3)c1=uCorePal[3];
  else if(i1i==4)c1=uCorePal[4];else c1=uCorePal[5];
  return mix(c0,c1,f);
}
vec3 pigmentMix(vec3 a,vec3 b,float t2){return sqrt(mix(a*a,b*b,t2));}

void main(){
  vec2 fc=gl_FragCoord.xy;
  vec2 uv=(fc-0.5*r)/MN;
  vec2 d=uv-uFinger;
  float dist=length(d);
  float falloff=exp(-dist*dist*3.5)*uPresence;
  float angle=uStirAngle*falloff*uTouch;
  float ca=cos(angle),sa=sin(angle);
  float scale=1.0+uStirScale*falloff*uTouch;
  vec2 uv_stirred=uFinger+mat2(ca,-sa,sa,ca)*d*scale;
  float bgMix=fbm(uv*0.4+vec2(t*0.03,t*0.02));
  vec3 bgColor=mix(uBg1,uBg2,bgMix);
  bgColor*=0.85+0.25*fbm(uv*0.8+vec2(-t*0.02,t*0.04));
  vec3 col=bgColor;
  for(int k=0;k<MAX_STREAMS;k++){
    if(k>=uStreams)break;
    float fi=float(k);
    float angleK=(streamHash(fi,1.0)-0.5)*3.14159;
    float freqK=1.5+streamHash(fi,2.0)*4.5;
    float ampK=0.12+streamHash(fi,3.0)*0.25;
    float speedK=(streamHash(fi,4.0)-0.5)*2.5;
    float phaseK=streamHash(fi,5.0)*6.28;
    float colIdxA=streamHash(fi,6.0);
    float colIdxB=streamHash(fi,7.0);
    float opacity=0.9/(1.0+float(uStreams)*0.015);
    float caK=cos(angleK),saK=sin(angleK);
    vec2 pp=vec2(caK*uv_stirred.x+saK*uv_stirred.y,-saK*uv_stirred.x+caK*uv_stirred.y);
    float centerline=ampK*sin(freqK*pp.x+t*speedK+phaseK)
                   +ampK*0.4*sin(freqK*2.3*pp.x+t*speedK*0.7-phaseK*1.3)
                   +ampK*0.2*sin(freqK*4.1*pp.x+t*speedK*1.2);
    float dy=pp.y-centerline;
    float absDy=abs(dy);
    float softCore=smoothstep(uWidth,0.0,absDy);
    float softBleed=smoothstep(uWidth*2.5,uWidth*0.5,absDy)*0.35;
    float thickness=softCore+softBleed;
    float flow=pp.x*2.0-t*speedK*1.5;
    float highlight=0.7+0.3*sin(flow+sin(pp.x*2.0+t*0.3));
    float energy=thickness*highlight;
    float m=0.5+0.5*sin(flow*0.7+sin(flow*0.3)*2.0);
    vec3 colA=colorFromIdx(colIdxA);
    vec3 colB=colorFromIdx(colIdxB);
    vec3 streamCol=pigmentMix(colA,colB,m);
    col=pigmentMix(col,streamCol,clamp(energy*opacity,0.0,0.9));
  }
  vec2 grainUV=fc/MN;
  float grain1=noise(grainUV*100.0)-0.5;
  float grain2=noise(grainUV*25.0+t*0.05)-0.5;
  col*=(1.0+(grain1*0.4+grain2*0.8)*uGrain*0.25);
  float inkSpots=smoothstep(0.75,0.95,noise(grainUV*50.0));
  col*=1.0-inkSpots*uGrain*0.15;
  float paper=noise(grainUV*300.0)*0.04*uGrain;
  col+=vec3(paper);
  float dyeAmt=exp(-dist*dist*3.5)*uPresence*0.25;
  col=pigmentMix(col,uCorePal[0]*0.6+uCorePal[1]*0.4,dyeAmt);
  col=pow(clamp(col,0.0,1.0),vec3(0.85));
  gl_FragColor=vec4(col,1.0);
}`;

  function mk(type, src) {
    const s = gl.createShader(type);
    gl.shaderSource(s, src);
    gl.compileShader(s);
    if (!gl.getShaderParameter(s, gl.COMPILE_STATUS)) {
      console.error(gl.getShaderInfoLog(s));
      return null;
    }
    return s;
  }

  const prog = gl.createProgram();
  gl.attachShader(prog, mk(gl.VERTEX_SHADER, vert));
  gl.attachShader(prog, mk(gl.FRAGMENT_SHADER, frag));
  gl.linkProgram(prog);
  gl.useProgram(prog);

  const buf = gl.createBuffer();
  gl.bindBuffer(gl.ARRAY_BUFFER, buf);
  gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1,-1,1,-1,-1,1,1,1]), gl.STATIC_DRAW);
  const aPos = gl.getAttribLocation(prog, 'p');
  gl.enableVertexAttribArray(aPos);
  gl.vertexAttribPointer(aPos, 2, gl.FLOAT, false, 0, 0);

  const u = {};
  ['r','t','uBg1','uBg2','uStreams','uWidth','uGrain','uFinger','uPresence','uTouch','uStirAngle','uStirScale'].forEach(n => {
    u[n] = gl.getUniformLocation(prog, n);
  });
  const uCorePalLoc = [];
  for (let i = 0; i < 6; i++) uCorePalLoc.push(gl.getUniformLocation(prog, 'uCorePal[' + i + ']'));

  let W, H;
  function resize() {
    const rect = canvas.getBoundingClientRect();
    const dpr = Math.min(window.devicePixelRatio || 1, 2);
    W = Math.floor(rect.width * dpr);
    H = Math.floor(rect.height * dpr);
    canvas.width = W;
    canvas.height = H;
    gl.viewport(0, 0, W, H);
  }
  resize();

  let rawX = 0, rawY = 0, fingerX = 0, fingerY = 0, prevFx = 0, prevFy = 0;
  let presence = 0, targetPresence = 0;
  let stirAngle = 0, stirOmega = 0, stirScale = 0, stirScaleOmega = 0;

  function uvFromEvent(ex, ey) {
    const rect = canvas.getBoundingClientRect();
    const fx = ex - rect.left;
    const fy = rect.height - (ey - rect.top);
    const minR = Math.min(rect.width, rect.height);
    return [(fx - rect.width / 2) / minR, (fy - rect.height / 2) / minR];
  }

  canvas.addEventListener('mousemove', e => {
    const [x, y] = uvFromEvent(e.clientX, e.clientY);
    rawX = x; rawY = y; targetPresence = 1;
  });
  canvas.addEventListener('mouseleave', () => { targetPresence = 0; });
  canvas.addEventListener('mouseenter', () => { targetPresence = 1; });
  canvas.addEventListener('touchmove', e => {
    e.preventDefault();
    const [x, y] = uvFromEvent(e.touches[0].clientX, e.touches[0].clientY);
    rawX = x; rawY = y; targetPresence = 1;
  }, { passive: false });
  canvas.addEventListener('touchstart', e => {
    const [x, y] = uvFromEvent(e.touches[0].clientX, e.touches[0].clientY);
    rawX = x; rawY = y; fingerX = x; fingerY = y; targetPresence = 1;
  }, { passive: true });
  canvas.addEventListener('touchend', () => { targetPresence = 0; });

  const palettes = [
    { bg: [[0.15,0.1,0.2],[0.22,0.12,0.25]], cols: [[0.6,0.3,0.8],[1.0,0.55,0.35],[0.3,0.6,1.0],[1.0,0.5,0.7],[0.9,0.85,0.7],[1.0,0.8,0.5]] },
    { bg: [[0.12,0.18,0.2],[0.14,0.2,0.22]], cols: [[0.2,0.6,0.7],[0.5,0.95,0.9],[0.85,0.95,1.0],[0.1,0.4,0.7],[0.4,0.85,0.7],[0.7,0.95,1.0]] },
    { bg: [[0.18,0.14,0.1],[0.2,0.16,0.11]], cols: [[0.85,0.45,0.2],[0.6,0.3,0.1],[0.3,0.5,0.3],[0.95,0.8,0.5],[0.5,0.2,0.2],[0.4,0.45,0.55]] },
    { bg: [[0.08,0.08,0.18],[0.1,0.1,0.22]], cols: [[0.2,1.0,0.7],[0.4,0.95,0.5],[0.9,0.4,0.9],[0.3,0.6,1.0],[0.75,0.5,1.0],[0.9,0.95,1.0]] },
    { bg: [[0.22,0.16,0.2],[0.25,0.18,0.22]], cols: [[0.95,0.5,0.7],[1.0,0.65,0.5],[1.0,0.9,0.6],[0.9,0.85,0.95],[0.85,0.5,0.75],[0.7,0.8,1.0]] },
    { bg: [[0.08,0.14,0.1],[0.1,0.16,0.12]], cols: [[1.0,0.45,0.2],[1.0,0.85,0.3],[0.3,0.8,0.4],[0.2,0.7,0.85],[1.0,0.5,0.7],[0.7,0.35,0.95]] },
    { bg: [[0.03,0.06,0.15],[0.05,0.08,0.18]], cols: [[0.1,0.4,0.9],[0.2,0.75,1.0],[0.4,0.9,0.85],[0.85,0.95,1.0],[0.3,0.5,0.95],[0.65,0.9,1.0]] },
  ];

  let palIdx = 0;
  let curBg1 = palettes[0].bg[0].slice(), curBg2 = palettes[0].bg[1].slice();
  let tgtBg1 = palettes[0].bg[0].slice(), tgtBg2 = palettes[0].bg[1].slice();
  let curPal = palettes[0].cols.map(c => c.slice());
  let tgtPal = palettes[0].cols.map(c => c.slice());

  function shuffle() {
    palIdx = (palIdx + 1 + Math.floor(Math.random() * (palettes.length - 1))) % palettes.length;
    tgtBg1 = palettes[palIdx].bg[0].slice();
    tgtBg2 = palettes[palIdx].bg[1].slice();
    tgtPal = palettes[palIdx].cols.map(c => c.slice());
  }
  canvas.addEventListener('click', shuffle);

  const t0 = performance.now();
  let accT = 0, lastNow = t0;
  function lerp(a, b, k) { return a + (b - a) * k; }

  function frame() {
    requestAnimationFrame(frame);
    const now = performance.now();
    const dt = Math.min((now - lastNow) / 1000, 0.05);
    lastNow = now;
    accT += dt * SPEED;

    const ease = 1 - Math.pow(0.05, dt);
    prevFx = fingerX; prevFy = fingerY;
    fingerX = lerp(fingerX, rawX, ease);
    fingerY = lerp(fingerY, rawY, ease);
    presence = lerp(presence, targetPresence, 1 - Math.pow(0.01, dt));

    const vx = (fingerX - prevFx) / Math.max(dt, 0.001);
    const vy = (fingerY - prevFy) / Math.max(dt, 0.001);
    const torque = (vx - vy) * 8.0;
    stirOmega += (torque - 3.0 * stirAngle - 2.5 * stirOmega) * dt;
    stirAngle += stirOmega * dt;
    const scaleForce = Math.sqrt(vx*vx + vy*vy) * 4.0;
    stirScaleOmega += (scaleForce - 5.0 * stirScale - 3.0 * stirScaleOmega) * dt;
    stirScale += stirScaleOmega * dt;
    stirAngle = Math.max(-3, Math.min(3, stirAngle));
    stirScale = Math.max(-0.8, Math.min(0.8, stirScale));

    const cEase = 1 - Math.pow(0.08, dt);
    for (let i = 0; i < 3; i++) {
      curBg1[i] = lerp(curBg1[i], tgtBg1[i], cEase);
      curBg2[i] = lerp(curBg2[i], tgtBg2[i], cEase);
    }
    for (let j = 0; j < 6; j++) for (let i = 0; i < 3; i++) curPal[j][i] = lerp(curPal[j][i], tgtPal[j][i], cEase);

    gl.uniform2f(u.r, W, H);
    gl.uniform1f(u.t, accT);
    gl.uniform3fv(u.uBg1, curBg1);
    gl.uniform3fv(u.uBg2, curBg2);
    for (let i = 0; i < 6; i++) gl.uniform3fv(uCorePalLoc[i], curPal[i]);
    gl.uniform1i(u.uStreams, STREAMS);
    gl.uniform1f(u.uWidth, WIDTH);
    gl.uniform1f(u.uGrain, GRAIN);
    gl.uniform2f(u.uFinger, fingerX, fingerY);
    gl.uniform1f(u.uPresence, presence);
    gl.uniform1f(u.uTouch, TOUCH);
    gl.uniform1f(u.uStirAngle, stirAngle);
    gl.uniform1f(u.uStirScale, stirScale);
    gl.drawArrays(gl.TRIANGLE_STRIP, 0, 4);
  }
  frame();

  window.addEventListener('resize', resize);
})();
</script>
<?php endif; ?>

<?php render_footer(); ?>
