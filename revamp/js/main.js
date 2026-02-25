document.addEventListener('DOMContentLoaded', () => {
  // 1. Initial Setup
  gsap.registerPlugin(ScrollTrigger);

  // 2. Navbar Scroll Effect
  const navbar = document.querySelector('.navbar');
  if (navbar) {
    window.addEventListener('scroll', () => {
      if (window.scrollY > 50) {
        navbar.classList.add('scrolled');
      } else {
        navbar.classList.remove('scrolled');
      }
    });
  }

  // 3. Magnetic Buttons
  const buttons = document.querySelectorAll('.btn-primary, .btn-outline');
  buttons.forEach(btn => {
    btn.addEventListener('mousemove', (e) => {
      const rect = btn.getBoundingClientRect();
      const x = e.clientX - rect.left - rect.width / 2;
      const y = e.clientY - rect.top - rect.height / 2;
      gsap.to(btn, {
        x: x * 0.15,
        y: y * 0.15,
        duration: 0.3,
        ease: 'power3.out'
      });
    });
    btn.addEventListener('mouseleave', () => {
      gsap.to(btn, {
        x: 0,
        y: 0,
        duration: 0.6,
        ease: 'elastic.out(1, 0.3)'
      });
    });
  });

  // 4. GSAP Hero Sequence
  const heroTl = gsap.timeline();
  heroTl.fromTo('.hero-title',
    { y: 50, opacity: 0 },
    { y: 0, opacity: 1, duration: 1, ease: 'power3.out', delay: 0.2 }
  )
    .fromTo('.hero-desc',
      { y: 30, opacity: 0 },
      { y: 0, opacity: 1, duration: 0.8, ease: 'power3.out' },
      "-=0.6"
    )
    .fromTo('.hero .btn',
      { y: 20, opacity: 0 },
      { y: 0, opacity: 1, duration: 0.6, stagger: 0.1, ease: 'power3.out' },
      "-=0.4"
    );

  // 5. Scroll Reveals
  const revealEls = document.querySelectorAll('.gs-reveal-up');
  revealEls.forEach(el => {
    gsap.fromTo(el,
      { y: 60, opacity: 0 },
      {
        y: 0,
        opacity: 1,
        duration: 1,
        ease: 'power3.out',
        scrollTrigger: {
          trigger: el,
          start: 'top 85%',
          toggleActions: 'play none none reverse'
        }
      }
    );
  });

  // 6. Staggered Sections
  const staggerSections = document.querySelectorAll('.gs-stagger');
  staggerSections.forEach(section => {
    const children = section.children;
    gsap.fromTo(children,
      { y: 40, opacity: 0 },
      {
        y: 0,
        opacity: 1,
        duration: 0.8,
        stagger: 0.15,
        ease: 'power3.out',
        scrollTrigger: {
          trigger: section,
          start: 'top 80%',
          toggleActions: 'play none none reverse'
        }
      }
    );
  });

  // 7. Parallax Image
  const parallaxImgs = document.querySelectorAll('.gs-parallax');
  parallaxImgs.forEach(img => {
    gsap.to(img, {
      yPercent: 15,
      ease: 'none',
      scrollTrigger: {
        trigger: img.parentElement,
        start: 'top bottom',
        end: 'bottom top',
        scrub: true
      }
    });
  });

  // 8. Stat Counters
  const counters = document.querySelectorAll('.counter');
  counters.forEach(counter => {
    const target = parseInt(counter.getAttribute('data-target'));
    gsap.to({ val: 0 }, {
      val: target,
      duration: 2,
      ease: 'power2.out',
      scrollTrigger: {
        trigger: counter,
        start: 'top 90%',
      },
      onUpdate: function () {
        counter.innerHTML = Math.floor(this.targets()[0].val).toLocaleString() + "+";
      }
    });
  });

  // 9. Input Focus Effects
  const inputs = document.querySelectorAll('.cinematic-input');
  inputs.forEach(input => {
    input.addEventListener('focus', () => {
      input.parentElement.style.transform = "scale(1.02)";
      input.parentElement.style.transition = "transform 0.3s ease";
    });
    input.addEventListener('blur', () => {
      input.parentElement.style.transform = "scale(1)";
    });
  });
});
