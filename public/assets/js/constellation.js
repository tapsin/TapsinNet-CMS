/* ============================================================================
 *  TapsinNet — Dala Constellation Particle Field
 *  Thousands of tiny outlined triangles forming an organic brain shape
 *  on pure black canvas. Ambient drift + mouse repulsion.
 *  ========================================================================= */
(function () {
  'use strict';

  var canvas = document.getElementById('constellation');
  if (!canvas) return;

  var ctx = canvas.getContext('2d');
  if (!ctx) return;

  var W = 0, H = 0, dpr = 1;
  var mouseX = -9999, mouseY = -9999;
  var PARTICLES = 2800;
  var BOUNDARY = 0.72;   /* brain shape fill ratio */
  var DRIFT_SPEED = 0.3;
  var MOUSE_RADIUS = 140;
  var MOUSE_FORCE = 0.6;

  /* --- color palette (Dala: violet · amber · teal · magenta · blue) --- */
  var COLORS = ['#ffedd7', '#dc5000', '#6c5f51', '#382416', '#c28b62'];

  function randomColor() {
    return COLORS[(Math.random() * COLORS.length) | 0];
  }

  /* --- brain shape: two organic lobes, narrow bridge --- */
  function brainShape(nx, ny) {
    /* nx, ny in -1..1  →  returns fill weight 0..1 */
    var x = nx, y = ny;
    /* two lobes centered at (±0.18, -0.08) */
    var left  = Math.exp(-Math.pow((x + 0.18) * 1.6, 2) - Math.pow(y * 1.35, 2));
    var right = Math.exp(-Math.pow((x - 0.18) * 1.6, 2) - Math.pow(y * 1.35, 2));
    /* cerebellum bulge below */
    var cereb = Math.exp(-Math.pow(x * 0.9, 2) - Math.pow((y + 0.55) * 1.1, 2));
    /* brain stem */
    var stem  = Math.exp(-Math.pow(x * 0.25, 2) - Math.pow((y + 0.82) * 2.5, 2));
    return Math.max(0, Math.min(1, (left * 0.45 + right * 0.45 + cereb * 0.12 + stem * 0.06)));
  }

  /* --- particle data --- */
  var pts = [];

  function resize() {
    dpr = Math.min(window.devicePixelRatio || 1, 2);
    W = canvas.clientWidth;
    H = canvas.clientHeight;
    canvas.width = W * dpr;
    canvas.height = H * dpr;
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
  }

  function initParticles() {
    pts.length = 0;
    var cx = W / 2, cy = H / 2;
    var scale = Math.min(W, H) * 0.42;

    for (var i = 0; i < PARTICLES; i++) {
      var nx = Math.random() * 2 - 1;
      var ny = Math.random() * 2 - 1;
      var weight = brainShape(nx, ny);
      var inside = Math.random() < weight * BOUNDARY;

      var px, py;
      if (inside) {
        /* inside brain shape */
        px = cx + nx * scale;
        py = cy + ny * scale * 0.85;
      } else {
        /* ambient scatter around brain */
        var angle = Math.random() * Math.PI * 2;
        var dist = scale * (1.1 + Math.random() * 0.7);
        px = cx + Math.cos(angle) * dist * (0.6 + Math.random() * 0.4);
        py = cy + Math.sin(angle) * dist * 0.7 * (0.6 + Math.random() * 0.4);
      }

      var size = inside ? (1.2 + Math.random() * 1.6) : (0.7 + Math.random() * 1.0);
      var rot = Math.random() * Math.PI;
      var rotSpeed = (Math.random() - 0.5) * 0.004;
      var driftX = (Math.random() - 0.5) * DRIFT_SPEED;
      var driftY = (Math.random() - 0.5) * DRIFT_SPEED * 0.6;

      pts.push({
        x: px, y: py,
        baseX: px, baseY: py,
        size: size,
        rot: rot,
        rotSpeed: rotSpeed,
        driftX: driftX,
        driftY: driftY,
        color: randomColor(),
        inside: inside,
        opacity: inside ? (0.7 + Math.random() * 0.3) : (0.25 + Math.random() * 0.35)
      });
    }
  }

  function update() {
    var now = performance.now() * 0.001;
    for (var i = 0; i < pts.length; i++) {
      var p = pts[i];
      p.x += p.driftX + Math.sin(now * 0.3 + i * 0.007) * 0.08;
      p.y += p.driftY + Math.cos(now * 0.2 + i * 0.005) * 0.05;
      p.rot += p.rotSpeed;

      /* mouse repulsion */
      var dx = p.x - mouseX;
      var dy = p.y - mouseY;
      var dist = Math.sqrt(dx * dx + dy * dy);
      if (dist < MOUSE_RADIUS && dist > 0) {
        var force = (MOUSE_RADIUS - dist) / MOUSE_RADIUS * MOUSE_FORCE;
        p.x += (dx / dist) * force;
        p.y += (dy / dist) * force;
      }
    }
  }

  function drawTriangle(cx, cy, size, rot, color, opacity) {
    ctx.save();
    ctx.globalAlpha = opacity;
    ctx.strokeStyle = color;
    ctx.lineWidth = Math.max(0.6, size * 0.25);
    ctx.beginPath();
    var r = Math.max(0.5, size);
    for (var j = 0; j < 3; j++) {
      var a = rot + (j * Math.PI * 2) / 3 - Math.PI / 2;
      var vx = cx + Math.cos(a) * r;
      var vy = cy + Math.sin(a) * r;
      if (j === 0) ctx.moveTo(vx, vy); else ctx.lineTo(vx, vy);
    }
    ctx.closePath();
    ctx.stroke();
    ctx.restore();
  }

  function draw() {
    ctx.clearRect(0, 0, W, H);
    for (var i = 0; i < pts.length; i++) {
      var p = pts[i];
      /* skip if way offscreen */
      if (p.x < -20 || p.x > W + 20 || p.y < -20 || p.y > H + 20) continue;
      drawTriangle(p.x, p.y, p.size, p.rot, p.color, p.opacity);
    }
  }

  function loop() {
    update();
    draw();
    requestAnimationFrame(loop);
  }

  /* --- init --- */
  resize();
  initParticles();
  loop();

  window.addEventListener('resize', function () {
    resize();
    initParticles();
  });

  canvas.addEventListener('mousemove', function (e) {
    var rect = canvas.getBoundingClientRect();
    mouseX = e.clientX - rect.left;
    mouseY = e.clientY - rect.top;
  });
  canvas.addEventListener('mouseleave', function () {
    mouseX = -9999;
    mouseY = -9999;
  });

  /* pause when hidden */
  document.addEventListener('visibilitychange', function () {
    if (document.hidden) return;
  });
})();
