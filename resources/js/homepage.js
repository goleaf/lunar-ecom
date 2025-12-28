/**
 * Homepage functionality
 */

(function() {
    'use strict';

    // Hero Slider
    class HeroSlider {
        constructor(container) {
            this.container = container;
            this.slides = container.querySelectorAll('.hero-slide');
            this.dots = container.querySelectorAll('.hero-dot');
            this.prevBtn = container.querySelector('.hero-prev');
            this.nextBtn = container.querySelector('.hero-next');
            this.currentSlide = 0;
            this.autoplayInterval = null;
            this.autoplayDelay = 5000; // 5 seconds
            this.prefersReducedMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)')?.matches ?? false;

            this.init();
        }

        init() {
            if (this.container.dataset.heroSliderInitialized === 'true') return;
            this.container.dataset.heroSliderInitialized = 'true';

            if (this.slides.length === 0) return;

            // Set up event listeners
            if (this.prevBtn) {
                this.prevBtn.addEventListener('click', () => this.prevSlide());
            }

            if (this.nextBtn) {
                this.nextBtn.addEventListener('click', () => this.nextSlide());
            }

            // Set up dot navigation
            this.dots.forEach((dot, index) => {
                dot.addEventListener('click', () => this.goToSlide(index));
            });

            // Ensure initial state is consistent (classes + aria)
            this.slides.forEach((slide, index) => {
                this.setSlideActive(slide, index === this.currentSlide);
            });
            this.dots.forEach((dot, index) => {
                this.setDotActive(dot, index === this.currentSlide);
            });

            // Start autoplay unless reduced motion is requested
            if (!this.prefersReducedMotion && this.slides.length > 1) {
                this.startAutoplay();
            }

            // Pause autoplay on hover
            this.container.addEventListener('mouseenter', () => this.stopAutoplay());
            this.container.addEventListener('mouseleave', () => this.startAutoplay());

            // Keyboard navigation
            this.onKeyDown = (e) => {
                if (!this.container.isConnected) {
                    this.destroy();
                    return;
                }

                const activeEl = document.activeElement;
                const isTyping = activeEl && (
                    activeEl.tagName === 'INPUT' ||
                    activeEl.tagName === 'TEXTAREA' ||
                    activeEl.tagName === 'SELECT' ||
                    activeEl.isContentEditable
                );

                if (isTyping) return;
                if (e.key === 'ArrowLeft') this.prevSlide();
                if (e.key === 'ArrowRight') this.nextSlide();
            };
            document.addEventListener('keydown', this.onKeyDown);

            // Pause autoplay when tab is hidden
            this.onVisibilityChange = () => {
                if (!this.container.isConnected) {
                    this.destroy();
                    return;
                }

                if (document.hidden) {
                    this.stopAutoplay();
                } else if (!this.prefersReducedMotion) {
                    this.startAutoplay();
                }
            };
            document.addEventListener('visibilitychange', this.onVisibilityChange);
        }

        goToSlide(index) {
            if (this.slides.length === 0) return;

            // Remove active styling from current slide and dot
            this.setSlideActive(this.slides[this.currentSlide], false);
            if (this.dots[this.currentSlide]) {
                this.setDotActive(this.dots[this.currentSlide], false);
            }

            // Set new current slide
            this.currentSlide = index % this.slides.length;
            if (this.currentSlide < 0) {
                this.currentSlide = this.slides.length - 1;
            }

            // Add active styling to new slide and dot
            this.setSlideActive(this.slides[this.currentSlide], true);
            if (this.dots[this.currentSlide]) {
                this.setDotActive(this.dots[this.currentSlide], true);
            }

            // Reset autoplay
            this.resetAutoplay();
        }

        setSlideActive(slide, isActive) {
            if (!slide) return;
            slide.setAttribute('aria-hidden', isActive ? 'false' : 'true');

            // Tailwind-driven state
            slide.classList.toggle('opacity-100', isActive);
            slide.classList.toggle('z-10', isActive);
            slide.classList.toggle('opacity-0', !isActive);
            slide.classList.toggle('pointer-events-none', !isActive);
        }

        setDotActive(dot, isActive) {
            if (!dot) return;
            dot.setAttribute('aria-current', isActive ? 'true' : 'false');

            dot.classList.toggle('bg-white', isActive);
            dot.classList.toggle('opacity-100', isActive);
            dot.classList.toggle('opacity-60', !isActive);
        }

        nextSlide() {
            this.goToSlide(this.currentSlide + 1);
        }

        prevSlide() {
            this.goToSlide(this.currentSlide - 1);
        }

        startAutoplay() {
            if (this.prefersReducedMotion) return;
            if (this.slides.length <= 1) return;
            if (this.autoplayInterval) return;

            this.autoplayInterval = setInterval(() => {
                this.nextSlide();
            }, this.autoplayDelay);
        }

        stopAutoplay() {
            if (this.autoplayInterval) {
                clearInterval(this.autoplayInterval);
                this.autoplayInterval = null;
            }
        }

        resetAutoplay() {
            this.stopAutoplay();
            this.startAutoplay();
        }

        destroy() {
            this.stopAutoplay();

            if (this.onKeyDown) {
                document.removeEventListener('keydown', this.onKeyDown);
            }

            if (this.onVisibilityChange) {
                document.removeEventListener('visibilitychange', this.onVisibilityChange);
            }
        }
    }

    // Smooth Scroll
    function initSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            if (anchor.dataset.smoothScrollBound === 'true') return;
            anchor.dataset.smoothScrollBound = 'true';

            anchor.addEventListener('click', function (e) {
                const href = this.getAttribute('href');
                if (href === '#') return;

                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    }

    function initCarousels() {
        document.querySelectorAll('[data-carousel]').forEach((carousel) => {
            if (carousel.dataset.carouselInitialized === 'true') return;
            carousel.dataset.carouselInitialized = 'true';

            const track = carousel.querySelector('[data-carousel-track]');
            const prev = carousel.querySelector('[data-carousel-prev]');
            const next = carousel.querySelector('[data-carousel-next]');

            if (!track || !prev || !next) return;

            const scrollAmount = () => {
                const item = track.querySelector('[data-carousel-item]');
                if (item) {
                    const itemWidth = item.getBoundingClientRect().width;
                    const gap = parseFloat(getComputedStyle(track.firstElementChild || track).gap || '0') || 0;
                    return (itemWidth + gap) * 2;
                }
                return track.clientWidth * 0.9;
            };

            const update = () => {
                const max = Math.max(track.scrollWidth - track.clientWidth, 0);
                const atStart = track.scrollLeft <= 1;
                const atEnd = track.scrollLeft >= max - 1;

                prev.disabled = atStart;
                next.disabled = atEnd;

                prev.classList.toggle('opacity-40', atStart);
                prev.classList.toggle('pointer-events-none', atStart);
                next.classList.toggle('opacity-40', atEnd);
                next.classList.toggle('pointer-events-none', atEnd);
            };

            prev.addEventListener('click', () => {
                track.scrollBy({ left: -scrollAmount(), behavior: 'smooth' });
            });
            next.addEventListener('click', () => {
                track.scrollBy({ left: scrollAmount(), behavior: 'smooth' });
            });

            let ticking = false;
            track.addEventListener(
                'scroll',
                () => {
                    if (ticking) return;
                    ticking = true;
                    requestAnimationFrame(() => {
                        update();
                        ticking = false;
                    });
                },
                { passive: true }
            );

            window.addEventListener('resize', update);
            update();
        });
    }

    function initHomepage() {
        const heroSection = document.querySelector('.hero-section');
        if (heroSection && heroSection.dataset.heroSliderInitialized !== 'true') {
            new HeroSlider(heroSection);
        }

        initSmoothScroll();
        initCarousels();
    }

    // Initialize on first load (or immediately if loaded after DOMContentLoaded)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initHomepage);
    } else {
        initHomepage();
    }

    // Re-initialize after Livewire navigation (if enabled)
    document.addEventListener('livewire:navigated', initHomepage);
})();

