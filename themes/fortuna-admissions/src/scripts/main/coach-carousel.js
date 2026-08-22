import { gsap } from 'gsap';

const CARD_MARGIN = 24;

const CoachCarousel = {
  init() {
    // Set up top and bottom sliders
    this.setupLoop({
      containerSelector: '.coach-slider--top',
      cardSelector: '.coach-card',
      direction: 'right'
    });

    this.setupLoop({
      containerSelector: '.coach-slider--bottom',
      cardSelector: '.coach-card',
      direction: 'left'
    });
  },
  setupLoop({ containerSelector, cardSelector, direction = 'right', durationFactor = 5 }) {
    const container = document.querySelector(containerSelector);

    if (!container) return;

    let cards = Array.from(container.querySelectorAll(cardSelector));
  
    // Clone cards until total width is at least 2x container width
    function ensureLoopFill() {
      const containerWidth = container.offsetWidth;
      let totalWidth = cards.reduce((acc, card) => acc + card.offsetWidth + CARD_MARGIN, 0);
  
      while (totalWidth < containerWidth * 2) {
        cards.forEach(card => {
          const clone = card.cloneNode(true);
          container.appendChild(clone);
        });
        cards = Array.from(container.querySelectorAll(cardSelector));
        totalWidth = cards.reduce((acc, card) => acc + card.offsetWidth + CARD_MARGIN, 0);
      }
  
      return totalWidth;
    }
  
    const totalWidth = ensureLoopFill();
  
    // Set initial position of each card
    gsap.set(cards, {
      x: (i) => i * (cards[i].offsetWidth + CARD_MARGIN)
    });
  
    // Set movement direction
    const movement = direction === 'left' ? `-=${totalWidth}` : `+=${totalWidth}`;
  
    // Animate with seamless loop
    gsap.to(cards, {
      x: movement,
      duration: cards.length * durationFactor,
      ease: 'linear',
      repeat: -1,
      modifiers: {
        x: gsap.utils.unitize(x =>
          (parseFloat(x) % totalWidth + totalWidth) % totalWidth
        )
      }
    });
  }
};

export default CoachCarousel;
