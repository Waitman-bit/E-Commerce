 const canvas = document.getElementById('particle-canvas');
  const ctx = canvas.getContext('2d');

  function resize() {
    canvas.width = canvas.offsetWidth;
    canvas.height = canvas.offsetHeight;
  }
  resize();
  window.addEventListener('resize', resize);

  const particles = Array.from({ length: 60 }, () => ({
    x: Math.random() * window.innerWidth,
    y: Math.random() * 455,
    r: Math.random() * 1.8 + 0.5,
    vx: (Math.random() - 0.5) * 0.4,
    vy: -(Math.random() * 0.4 + 0.1),
    alpha: Math.random() * 0.6 + 0.1,
    yellow: Math.random() > 0.5,
  }));

  const lines = Array.from({ length: 20 }, () => ({
    x1: Math.random() * window.innerWidth, y1: Math.random() * 455,
    x2: Math.random() * window.innerWidth, y2: Math.random() * 455,
    alpha: Math.random() * 0.08 + 0.02,
    t: Math.random() * Math.PI * 2,
    speed: Math.random() * 0.004 + 0.002,
  }));

  function draw() {
    const w = canvas.width, h = canvas.height;
    ctx.clearRect(0, 0, w, h);

    lines.forEach(l => {
      l.t += l.speed;
      const pulse = 0.5 + 0.5 * Math.sin(l.t);
      ctx.beginPath();
      ctx.moveTo(l.x1, l.y1);
      ctx.lineTo(l.x2, l.y2);
      ctx.strokeStyle = `rgba(255,230,0,${l.alpha * pulse})`;
      ctx.lineWidth = 0.5;
      ctx.stroke();
    });

    particles.forEach(p => {
      p.x += p.vx;
      p.y += p.vy;
      if (p.y < -4) { p.y = h + 4; p.x = Math.random() * w; }
      if (p.x < -4) p.x = w + 4;
      if (p.x > w + 4) p.x = -4;

      ctx.beginPath();
      ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
      ctx.fillStyle = p.yellow
        ? `rgba(255,230,0,${p.alpha})`
        : `rgba(255,255,255,${p.alpha * 0.4})`;
      ctx.fill();
    });

    requestAnimationFrame(draw);
  }
  draw();
