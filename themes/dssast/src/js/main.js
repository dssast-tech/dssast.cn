import Alpine from 'alpinejs';

Alpine.data('heroOrbs', ({ count = 12, w = 800, h = 180 } = {}) => ({
  init() {
    const ns = 'http://www.w3.org/2000/svg';
    const fills = ['url(#orb-a)', 'url(#orb-b)', 'url(#orb-c)'];
    const reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;
    const rand = (min, max) => min + Math.random() * (max - min);

    for (let i = 0; i < count; i++) {
      const c = document.createElementNS(ns, 'circle');
      c.setAttribute('cx', rand(0, w).toFixed(1));
      c.setAttribute('cy', rand(0, h).toFixed(1));
      c.setAttribute('r', rand(40, 80).toFixed(1));
      c.setAttribute('fill', fills[i % fills.length]);
      this.$el.appendChild(c);

      if (reduced) continue;

      const wander = () => {
        const dx = rand(-110, 110).toFixed(1);
        const dy = rand(-70, 70).toFixed(1);
        c.animate(
          { transform: `translate(${dx}px, ${dy}px)` },
          { duration: rand(2400, 5600), easing: 'ease-in-out', fill: 'forwards' }
        ).onfinish = wander;
      };
      wander();
    }
  },
}));

Alpine.start();
